<?php

namespace App\Controller;

use App\Entity\Account;
use App\Form\Step1Type;
use App\Form\Step2Type;
use App\Form\Step3Type;
use App\Lib\AccountManager;
use App\Lib\FinTs\FinTsOperation;
use App\Lib\FinTs\FinTsSymfonyAdapter;
use App\Lib\FinTs\TanRequiredException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/account/step1', name: 'app_account_step1')]
    public function step1(Request $request, AccountManager $accountManager): Response
    {
        $form = $this->createForm(Step1Type::class, null, [
            'action' => $this->generateUrl('app_account_step1'),
        ]);

        // do not autofill username and password, it has to be reentered by
        // the user every time due to security issues.
        $form->get('username')->setData('');
        $form->get('password')->setData('');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $account = $accountManager->saveStep1($form->getData());

            return $this->redirectToRoute('app_account_step2', ['id' => $account->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('account/step1.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/account/{id}/step2', name: 'app_account_step2')]
    public function step2(Account $account, Request $request, FinTsSymfonyAdapter $finTsAdapter, AccountManager $accountManager): Response
    {
        try {
            $tanModeChoices = $finTsAdapter->getTanModes($account);
        } catch (\Exception $e) {
            $tanModeChoices = [];
            // @TODO: log error, inform user ...
        }

        $form = $this->createForm(Step2Type::class, null, [
            'action' => $this->generateUrl('app_account_step2', ['id' => $account->getId()]),
            'tanModeChoices' => $tanModeChoices,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $accountManager->saveStep2($form->getData(), $account);

            return $this->redirectToRoute('app_account_step3', ['id' => $account->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('account/step2.html.twig', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    #[Route('/account/{id}/step3', name: 'app_account_step3')]
    public function step3(Account $account, Request $request, FinTsSymfonyAdapter $finTsAdapter, AccountManager $accountManager): Response
    {
        try {
            $subAccounts = $this->loadSubaccountChoices($request, $account, $finTsAdapter);
        } catch (TanRequiredException $e) {
            return $this->render('account/step3.html.twig', [
                'tanRequired' => true,
                'account' => $account,
                'challenge' => $e->getChallenge(),
            ]);
        }

        $form = $this->createForm(Step3Type::class, null, [
            'action' => $this->generateUrl('app_account_step3', ['id' => $account->getId()]),
            'subaccounts' => $subAccounts,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $accountManager->finalize($form->get('subaccounts')->getData(), $account);

            $request->getSession()->remove('step3_subaccounts_'.$account->getId());

            $this->addFlash('success', 'Account added!');

            return $this->redirectToRoute('app_index');
        }

        return $this->render('account/step3.html.twig', [
            'tanRequired' => false,
            'form' => $form,
            'account' => $account,
        ]);
    }

    #[Route('/account/{id}/fetch-statements', name: 'app_account_fetch_statements')]
    public function fetchStatements(Request $request, Account $account, FinTsSymfonyAdapter $finTsAdapter): Response
    {
        $from = (new \DateTime())->modify('-5 days');
        $to = new \DateTime();

        try {
            $statements = $finTsAdapter->consumeResult($account, FinTsOperation::StatementsOfAccount)
                ?? $finTsAdapter->getStatementsOfAccount($account, $from, $to);
        } catch (TanRequiredException $e) {
            return $this->render('account/statements.html.twig', [
                'tanRequired' => true,
                'account' => $account,
                'challenge' => $e->getChallenge(),
                'statements' => [],
            ]);
        }

        return $this->render('account/statements.html.twig', [
            'tanRequired' => false,
            'account' => $account,
            'statements' => $statements,
        ]);
    }

    #[Route('/account/{id}/fetch-holdings', name: 'app_account_fetch_holdings')]
    public function fetchHoldings(Request $request, Account $account, FinTsSymfonyAdapter $finTsAdapter): Response
    {
        try {
            $holdings = $finTsAdapter->consumeResult($account, FinTsOperation::StatementsOfHolding)
                ?? $finTsAdapter->getStatementsOfHolding($account);
        } catch (TanRequiredException $e) {
            return $this->render('account/holdings.html.twig', [
                'tanRequired' => true,
                'account' => $account,
                'challenge' => $e->getChallenge(),
                'holdings' => [],
            ]);
        }

        return $this->render('account/holdings.html.twig', [
            'tanRequired' => false,
            'account' => $account,
            'holdings' => $holdings,
        ]);
    }

    #[Route('/account/{id}/fetch-tan-media', name: 'app_account_fetch_tanmedia', methods: ['GET', 'POST'])]
    public function fetchTanMedia(Request $request, Account $account, FinTsSymfonyAdapter $finTsAdapter): JsonResponse
    {
        if (!$request->query->has('tan-mode')) {
            return new JsonResponse('', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $tanMedia = $finTsAdapter->getTanMedia($account, $request->query->getInt('tan-mode'));
        } catch (\Exception $e) {
            return new JsonResponse('', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($tanMedia);
    }

    #[Route('/account/tan-poll/{id}', name: 'app_account_tan_poll', methods: ['POST', 'GET'])]
    public function tanPoll(Account $account, FinTsSymfonyAdapter $finTsAdapter): Response
    {
        try {
            $resolved = $finTsAdapter->checkPending($account);
        } catch (\LogicException) {
            return new Response(status: 200);
        }

        return new Response(status: $resolved ? 200 : 202);
    }

    /**
     * @throws TanRequiredException
     */
    private function loadSubaccountChoices(Request $request, Account $account, FinTsSymfonyAdapter $finTsAdapter): array
    {
        $session = $request->getSession();
        $key = 'step3_subaccounts_'.$account->getId();

        if ($session->has($key)) {
            return $session->get($key);
        }

        $subAccounts = $finTsAdapter->consumeResult($account, FinTsOperation::Subaccounts)
            ?? $finTsAdapter->getSubaccounts($account); // may still throw TanRequiredException on first-ever visit

        $session->set($key, $subAccounts);

        return $subAccounts;
    }
}
