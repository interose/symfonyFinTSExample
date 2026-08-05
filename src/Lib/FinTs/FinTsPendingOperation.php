<?php

namespace App\Lib\FinTs;

/**
 * Written once, the moment a TAN is first requested. Never rewritten while
 * polling — only the dialog state (FinTsPendingDialog) changes per poll.
 *
 * @see FinTsPendingDialog
 */
final class FinTsPendingOperation
{
    /**
     * @param array<string,scalar> $operationArgs must stay serialize-safe —
     *                                             no objects, no closures.
     */
    public function __construct(
        public readonly FinTsOperation $operation,
        public readonly array $operationArgs,
    ) {
    }
}
