<?php

namespace App\Exceptions\Butcher;

use RuntimeException;

class InsufficientButcherStockException extends RuntimeException
{
    public static function forRequest(float $requestedKg, float $availableKg, string $meatType): self
    {
        return new self(sprintf(
            'Insufficient %s stock: requested %.3f kg, available %.3f kg.',
            $meatType,
            $requestedKg,
            $availableKg
        ));
    }
}
