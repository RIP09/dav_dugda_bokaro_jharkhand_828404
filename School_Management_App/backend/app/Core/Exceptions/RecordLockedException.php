<?php

namespace App\Core\Exceptions;

use RuntimeException;

class RecordLockedException extends RuntimeException
{
    public function __construct(public string $reason)
    {
        parent::__construct('This record is locked and cannot be changed. '.$reason);
    }

    public function render()
    {
        return response()->json(['message' => $this->getMessage(), 'lock_reason' => $this->reason], 423);
    }
}
