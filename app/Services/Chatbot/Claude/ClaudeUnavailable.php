<?php

namespace App\Services\Chatbot\Claude;

use RuntimeException;
use Throwable;

/** The assistant cannot be reached: no API key, or the API call failed. */
class ClaudeUnavailable extends RuntimeException
{
    /** $reason: missing_key | auth | rate_limit | api_error | connection | error */
    public function __construct(public readonly string $reason, string $message = '', ?Throwable $previous = null)
    {
        parent::__construct($message !== '' ? $message : $reason, 0, $previous);
    }
}
