<?php

namespace App\Lib\FinTs\Action;

use App\Lib\FinTs\AccountCapabilities;
use Fhp\Action\GetSEPAAccounts;
use Fhp\Model\SEPAAccount;
use Fhp\Protocol\BPD;
use Fhp\Protocol\UPD;
use Fhp\Segment\HIUPD\HIUPDv6;

/**
 * Same as {@see GetSEPAAccounts}, but additionally keeps the user parameter
 * data (UPD) the library hands to createRequest(). FinTs itself has no public
 * accessor for the UPD, and the UPD is where the bank lists, per account,
 * which business transactions ("erlaubte Geschäftsvorfälle") are allowed.
 */
class GetSEPAAccountsWithCapabilities extends GetSEPAAccounts
{
    /** Statements of account: MT940 (HKKAZ) or camt (HKCAZ). */
    private const STATEMENT_OF_ACCOUNT_REQUESTS = ['HKKAZ', 'HKCAZ'];

    /** Statement of holdings (Depotaufstellung). */
    private const STATEMENT_OF_HOLDING_REQUESTS = ['HKWPD'];

    private ?UPD $upd = null;

    public static function create(): static
    {
        return new static();
    }

    protected function createRequest(BPD $bpd, ?UPD $upd)
    {
        $this->upd = $upd;

        return parent::createRequest($bpd, $upd);
    }

    public function getCapabilities(SEPAAccount $account): AccountCapabilities
    {
        $hiupd = $this->upd?->findHiupd($account);

        return new AccountCapabilities(
            statementsOfAccount: $this->supportsAny($account, self::STATEMENT_OF_ACCOUNT_REQUESTS),
            statementsOfHolding: $this->supportsAny($account, self::STATEMENT_OF_HOLDING_REQUESTS),
            accountType: $hiupd instanceof HIUPDv6 ? $hiupd->kontoart : null,
        );
    }

    /**
     * @param string[] $requestNames
     */
    private function supportsAny(SEPAAccount $account, array $requestNames): bool
    {
        // If the bank sent no UPD, no entry for this account, or no list of
        // allowed transactions, we cannot tell, so don't block anything.
        $hiupd = $this->upd?->findHiupd($account);
        if (null === $hiupd || [] === $hiupd->getErlaubteGeschaeftsvorfaelle()) {
            return true;
        }

        foreach ($requestNames as $requestName) {
            if ($this->upd->isRequestSupportedForAccount($account, $requestName)) {
                return true;
            }
        }

        return false;
    }

    // The action is serialized into the session while waiting for a TAN, so
    // the captured UPD has to survive that round trip.

    public function __serialize(): array
    {
        return [parent::__serialize(), $this->upd];
    }

    public function __unserialize(array $serialized): void
    {
        [$parentSerialized, $this->upd] = $serialized;

        parent::__unserialize($parentSerialized);
    }
}
