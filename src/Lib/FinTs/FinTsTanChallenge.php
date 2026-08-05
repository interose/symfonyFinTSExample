<?php

namespace App\Lib\FinTs;

/**
 * Everything the UI needs to display a pending TAN request and drive its
 * polling cadence. Pure data — no session, no bank-protocol behavior.
 */
final class FinTsTanChallenge
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $tanMediumName,
        public readonly bool $isDecoupled,
        public readonly bool $allowsAutomatedPolling,
        public readonly ?int $firstDecoupledCheckDelaySeconds,
        public readonly ?int $periodicDecoupledCheckDelaySeconds,
        public readonly ?int $maxDecoupledChecks,
    ) {
    }
}