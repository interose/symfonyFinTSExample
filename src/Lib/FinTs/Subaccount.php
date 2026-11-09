<?php

namespace App\Lib\FinTs;

use Fhp\Model\SEPAAccount;

/**
 * A subaccount returned by the bank together with the operations the bank
 * allows on it.
 */
final readonly class Subaccount
{
    public function __construct(
        public SEPAAccount $account,
        public AccountCapabilities $capabilities,
    ) {
    }
}
