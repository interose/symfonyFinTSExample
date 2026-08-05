<?php

namespace App\Lib\FinTs;

class TanRequiredException extends \Exception
{
    public function __construct(private readonly FinTsTanChallenge $challenge)
    {
        parent::__construct($challenge->message);
    }

    public function getChallenge(): FinTsTanChallenge
    {
        return $this->challenge;
    }
}
