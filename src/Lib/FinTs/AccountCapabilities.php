<?php

namespace App\Lib\FinTs;

/**
 * What the bank allows for a single (sub)account, as reported in the
 * user parameter data (UPD / HIUPD) during dialog initialization.
 */
final readonly class AccountCapabilities
{
    /**
     * @param int|null $accountType FinTS "Kontoart" (1-9 current account, 10-19 savings,
     *                              30-39 securities depot, 60-69 fund depot, ...); null if the bank doesn't report it
     */
    public function __construct(
        public bool $statementsOfAccount,
        public bool $statementsOfHolding,
        public ?int $accountType,
    ) {
    }

    public function getAccountTypeLabel(): ?string
    {
        return match (true) {
            null === $this->accountType => null,
            $this->accountType <= 9 => 'Current account',
            $this->accountType <= 19 => 'Savings account',
            $this->accountType <= 29 => 'Fixed deposit',
            $this->accountType <= 39 => 'Securities depot',
            $this->accountType <= 49 => 'Loan',
            $this->accountType <= 59 => 'Credit card',
            $this->accountType <= 69 => 'Fund depot',
            $this->accountType <= 79 => 'Building savings',
            $this->accountType <= 89 => 'Insurance',
            default => 'Other',
        };
    }
}
