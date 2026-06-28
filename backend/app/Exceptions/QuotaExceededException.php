<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an action would exceed the organization's plan quota.
 * Rendered as HTTP 402 Payment Required with a machine-readable code so
 * the frontend can show the right upgrade prompt.
 */
class QuotaExceededException extends RuntimeException
{
    public function __construct(
        public readonly string $quota,
        string $message,
    ) {
        parent::__construct($message);
    }
}
