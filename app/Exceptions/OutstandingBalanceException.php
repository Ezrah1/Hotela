<?php

namespace App\Exceptions;

use Exception;

class OutstandingBalanceException extends Exception
{
    protected int $reservationId;
    protected float $balance;

    public function __construct(int $reservationId, float $balance)
    {
        $this->reservationId = $reservationId;
        $this->balance = $balance;
        parent::__construct('Outstanding balance must be settled before check-out');
    }

    public function getReservationId(): int
    {
        return $this->reservationId;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }
}

