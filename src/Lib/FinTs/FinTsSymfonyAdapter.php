<?php

namespace App\Lib\FinTs;

use App\Entity\Account;
use App\Service\EncryptionService;
use Fhp\Action\GetDepotAufstellung;
use Fhp\Action\GetSEPAAccounts;
use Fhp\Action\GetStatementOfAccount;
use Fhp\BaseAction;
use Fhp\FinTs;
use Fhp\Model\NoPsd2TanMode;
use Fhp\Model\SEPAAccount;
use Fhp\Model\StatementOfAccount\Transaction;
use Fhp\Model\StatementOfHoldings\Holding;
use Fhp\Model\TanMedium;
use Fhp\Model\TanMode;
use Fhp\Protocol\ServerException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class FinTsSymfonyAdapter
{
    private const SESSION_OP_PREFIX = 'fints_pending_op_';
    private const SESSION_DIALOG_PREFIX = 'fints_pending_dialog_';
    private const SESSION_RESULT_PREFIX = 'fints_result_';

    public function __construct(
        private readonly EncryptionService $encryptionService,
        private readonly ParameterBagInterface $parameterBag,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return SEPAAccount[]
     *
     * @throws TanRequiredException
     */
    public function getSubaccounts(Account $account): array
    {
        $this->clearPendingState($account); // a deliberate fresh start supersedes any leftovers

        $finTs = $this->buildFints($account);
        $this->login($finTs, $account, FinTsOperation::Subaccounts, []);

        return $this->fetchSubaccounts($finTs, $account);
    }

    /**
     * @return Transaction[]
     *
     * @throws TanRequiredException
     */
    public function getStatementsOfAccount(Account $account, \DateTime $from, \DateTime $to): array
    {
        $this->clearPendingState($account);

        $finTs = $this->buildFints($account);
        $this->login($finTs, $account, FinTsOperation::StatementsOfAccount, [
            'from' => $from->format('c'),
            'to' => $to->format('c'),
        ]);

        return $this->fetchStatementsOfAccount($finTs, $account, $from, $to);
    }

    /**
     * @return Holding[]
     *
     * @throws TanRequiredException
     */
    public function getStatementsOfHolding(Account $account): array
    {
        $this->clearPendingState($account);

        $finTs = $this->buildFints($account);
        $this->login($finTs, $account, FinTsOperation::StatementsOfHolding, []);

        return $this->fetchStatementsOfHolding($finTs, $account);
    }


    /**
     * @return TanMode[]
     *
     * @throws \Fhp\CurlException
     * @throws ServerException
     */
    public function getTanModes(Account $account): array
    {
        $this->clearPendingState($account);
        $finTs = $this->buildFints($account);

        if ('50010517' == trim($account->getBankCode())) {
            $tanModes = [
                new NoPsd2TanMode(),
            ];
        } else {
            $tanModes = $finTs->getTanModes();
        }

        return $tanModes;
    }

    /**
     * @return TanMedium[]
     *
     * @throws \Fhp\CurlException
     * @throws ServerException
     */
    public function getTanMedia(Account $account, int $tanMode): array
    {
        $this->clearPendingState($account);
        $finTs = $this->buildFints($account);

        return $finTs->getTanMedia($tanMode);
    }

    /**
     * The one generic endpoint every TAN confirmation screen polls.
     *
     * @throws \LogicException                    if there's no pending confirmation for this account
     * @throws ServerException|\Fhp\CurlException
     */
    public function checkPending(Account $account): bool
    {
        $opState = $this->loadPendingOperation($account);
        $dialogState = $this->loadPendingDialog($account);

        if (!$opState || !$dialogState) {
            throw new \LogicException('No pending FinTS confirmation for this account.');
        }

        $finTs = $this->buildFints($account, $dialogState->persistedInstance);
        $action = FinTsAction::restoreAction($dialogState->persistedAction);

        $resolved = $finTs->checkDecoupledSubmission($action);

        if (!$resolved) {
            // Still waiting — not done yet, so still serializable. Re-persist to
            // capture the dialog's advanced state for the next poll.
            $this->savePendingDialog($account, new FinTsPendingDialog($finTs->persist(), serialize($action)));

            return false;
        }

        try {
            $data = match ($opState->operation) {
                // If $action is already the specific business action, the TAN was
                // on it directly and its result is already resolved on this exact
                // object — no re-fetch needed. Otherwise the TAN was on login, and
                // the business action hasn't run yet at all.
                FinTsOperation::Subaccounts => $action instanceof GetSEPAAccounts
                    ? $action->getAccounts()
                    : $this->fetchSubaccounts($finTs, $account),

                FinTsOperation::StatementsOfAccount => $action instanceof GetStatementOfAccount
                    ? $this->flattenStatementOfAccount($action)
                    : $this->fetchStatementsOfAccount(
                        $finTs,
                        $account,
                        new \DateTime($opState->operationArgs['from']),
                        new \DateTime($opState->operationArgs['to']),
                    ),

                FinTsOperation::StatementsOfHolding => $action instanceof GetDepotAufstellung
                    ? $this->flattenStatementOfHolding($action)
                    : $this->fetchStatementsOfHolding($finTs, $account),
            };
        } catch (TanRequiredException) {
            return false;
        }

        $this->clearPendingState($account);
        $this->saveResult($account, $opState->operation, $data);

        return true;
    }

    /**
     * Reads back a resolved operation's result and clears it. Null means
     * nothing is waiting — either no TAN was ever needed, or it hasn't
     * resolved yet.
     */
    public function consumeResult(Account $account, FinTsOperation $operation): mixed
    {
        $session = $this->requestStack->getSession();
        $key = $this->resultKey($account, $operation);

        if (!$session->has($key)) {
            return null;
        }

        $result = $session->get($key);
        $session->remove($key);

        return $result;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------
    /**
     * @throws TanRequiredException
     */
    private function login(FinTs $finTs, Account $account, FinTsOperation $operation, array $args): void
    {
        $action = $finTs->login();

        if ($action->needsTan()) {
            $this->preserveState($account, $finTs, $action, $operation, $args);
            throw new TanRequiredException($this->buildChallenge($finTs, $action));
        }
    }

    /**
     * @return SEPAAccount[]
     *
     * @throws TanRequiredException
     */
    private function fetchSubaccounts(FinTs $finTs, Account $account): array
    {
        $action = GetSEPAAccounts::create();
        $finTs->execute($action);

        if ($action->needsTan()) {
            $this->preserveState($account, $finTs, $action, FinTsOperation::Subaccounts, []);
            throw new TanRequiredException($this->buildChallenge($finTs, $action));
        }

        return $action->getAccounts();
    }

    /**
     * @return Transaction[]
     *
     * @throws TanRequiredException
     */
    private function fetchStatementsOfAccount(FinTs $finTs, Account $account, \DateTime $from, \DateTime $to): array
    {
        $action = GetStatementOfAccount::create($account->getSEPAAcount($this->encryptionService), $from, $to);
        $finTs->execute($action);

        if ($action->needsTan()) {
            $this->preserveState($account, $finTs, $action, FinTsOperation::StatementsOfAccount, [
                'from' => $from->format('c'),
                'to' => $to->format('c'),
            ]);
            throw new TanRequiredException($this->buildChallenge($finTs, $action));
        }

        return $this->flattenStatementOfAccount($action);
    }

    /**
     * @return Holding[]
     *
     * @throws TanRequiredException
     */
    private function fetchStatementsOfHolding(FinTs $finTs, Account $account): array
    {
        $action = GetDepotAufstellung::create($account->getSEPAAcount($this->encryptionService));
        $finTs->execute($action);

        if ($action->needsTan()) {
            $this->preserveState($account, $finTs, $action, FinTsOperation::StatementsOfHolding, []);
            throw new TanRequiredException($this->buildChallenge($finTs, $action));
        }

        return $this->flattenStatementOfHolding($action);
    }

    /**
     * @return Transaction[]
     */
    private function flattenStatementOfAccount(GetStatementOfAccount $action): array
    {
        $transactions = [];

        foreach ($action->getStatement()->getStatements() as $statement) {
            $transactions = array_merge($transactions, $statement->getTransactions());
        }

        return $transactions;
    }

    /**
     * @return Holding[]
     */
    private function flattenStatementOfHolding(GetDepotAufstellung $action): array
    {
        return $action->getStatement()->getHoldings();
    }

    private function buildChallenge(FinTs $finTs, BaseAction $action): FinTsTanChallenge
    {
        $tanMode = $finTs->getSelectedTanMode();
        $tanRequest = $action->getTanRequest();

        $message = $tanMode->isDecoupled()
            ? 'The bank requested authentication on your other device.'
            : 'The bank requested a TAN.';

        if (null !== $tanRequest->getChallenge()) {
            $message .= "\n".$tanRequest->getChallenge();
        }

        return new FinTsTanChallenge(
            message: $message,
            tanMediumName: $tanRequest->getTanMediumName(),
            isDecoupled: $tanMode->isDecoupled(),
            allowsAutomatedPolling: $tanMode->allowsAutomatedPolling(),
            firstDecoupledCheckDelaySeconds: $tanMode->getFirstDecoupledCheckDelaySeconds(),
            periodicDecoupledCheckDelaySeconds: $tanMode->getPeriodicDecoupledCheckDelaySeconds(),
            maxDecoupledChecks: $tanMode->getMaxDecoupledChecks(),
        );
    }

    private function buildFints(Account $account, ?string $persistedInstance = null): FinTs
    {
        $finTs = FinTsFactory::create(
            server: $account->getUrl(),
            bankCode: $account->getBankCode(),
            username: $account->getUsername($this->encryptionService),
            pin: $account->getPassword($this->encryptionService),
            productName: $this->parameterBag->get('app.product_name'),
            productVersion: $this->parameterBag->get('app.product_version'),
            persistedInstance: $persistedInstance,
        );

        $tanMedium = $account->getTanMediaName($this->encryptionService);
        $finTs->selectTanMode(
            (int) $account->getTanMechanism($this->encryptionService),
            !empty(trim((string) $tanMedium)) ? $tanMedium : null,
        );

        return $finTs;
    }

    private function preserveState(Account $account, FinTs $finTs, BaseAction $action, FinTsOperation $operation, array $args): void
    {
        $this->savePendingOperation($account, new FinTsPendingOperation($operation, $args));
        $this->savePendingDialog($account, new FinTsPendingDialog($finTs->persist(), serialize($action)));
    }

    private function loadPendingOperation(Account $account): ?FinTsPendingOperation
    {
        $state = $this->requestStack->getSession()->get($this->opKey($account));

        return $state instanceof FinTsPendingOperation ? $state : null;
    }

    private function savePendingOperation(Account $account, FinTsPendingOperation $state): void
    {
        $this->requestStack->getSession()->set($this->opKey($account), $state);
    }

    private function loadPendingDialog(Account $account): ?FinTsPendingDialog
    {
        $state = $this->requestStack->getSession()->get($this->dialogKey($account));

        return $state instanceof FinTsPendingDialog ? $state : null;
    }

    private function savePendingDialog(Account $account, FinTsPendingDialog $state): void
    {
        $this->requestStack->getSession()->set($this->dialogKey($account), $state);
    }

    private function clearPendingState(Account $account): void
    {
        $session = $this->requestStack->getSession();
        $session->remove($this->opKey($account));
        $session->remove($this->dialogKey($account));
    }

    private function saveResult(Account $account, FinTsOperation $operation, mixed $data): void
    {
        $this->requestStack->getSession()->set($this->resultKey($account, $operation), $data);
    }

    private function opKey(Account $account): string
    {
        return self::SESSION_OP_PREFIX.$account->getId();
    }

    private function dialogKey(Account $account): string
    {
        return self::SESSION_DIALOG_PREFIX.$account->getId();
    }

    private function resultKey(Account $account, FinTsOperation $operation): string
    {
        return self::SESSION_RESULT_PREFIX.$operation->value.'_'.$account->getId();
    }
}
