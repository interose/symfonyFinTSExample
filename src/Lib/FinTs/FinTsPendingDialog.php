<?php

namespace App\Lib\FinTs;

/**
 * The live FinTs dialog + action, re-persisted after every single poll
 * (resolved or not) per FinTs::checkDecoupledSubmission()'s contract.
 */
final class FinTsPendingDialog
{
    public function __construct(
        public readonly string $persistedInstance,
        public readonly string $persistedAction,
    ) {
    }
}
