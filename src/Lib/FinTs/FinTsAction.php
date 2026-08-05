<?php

namespace App\Lib\FinTs;

use Fhp\BaseAction;

final class FinTsAction
{
    private function __construct()
    {
    }

    public static function restoreAction(string $persistedAction): BaseAction
    {
        return unserialize($persistedAction);
    }
}
