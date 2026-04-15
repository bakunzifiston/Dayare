<?php

namespace App\Services\Logistics\Concerns;

use Illuminate\Validation\ValidationException;

trait ThrowsRuleViolation
{
    private function ruleViolation(string $message, string $field = 'rule'): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}

