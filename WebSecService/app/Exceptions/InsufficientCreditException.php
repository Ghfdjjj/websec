<?php

namespace App\Exceptions;

use Exception;

class InsufficientCreditException extends Exception
{
    protected $requiredAmount;
    protected $availableCredit;

    public function __construct($requiredAmount, $availableCredit, $message = "Insufficient credit for purchase")
    {
        parent::__construct($message);
        $this->requiredAmount = $requiredAmount;
        $this->availableCredit = $availableCredit;
    }

    public function getRequiredAmount()
    {
        return $this->requiredAmount;
    }

    public function getAvailableCredit()
    {
        return $this->availableCredit;
    }
} 