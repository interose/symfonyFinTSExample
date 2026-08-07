<?php

namespace App\Lib\FinTs;

/**
 * Identifies which top-level bank operation is in flight, so a resolved TAN
 * confirmation knows what to resume into and consumeResult() knows which
 * result to look for. Deliberately has no "Login" case — login is always an
 * invisible first step of one of these, never something a caller asks for
 * on its own.
 */
enum FinTsOperation: string
{
    case Subaccounts = 'subaccounts';
    case StatementsOfAccount = 'statementsofaccount';
    case StatementsOfHolding = 'statementsofholding';
}
