<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientAnimalsException extends RuntimeException
{
    public function __construct(
        public readonly int $available,
        public readonly int $requested,
        public readonly string $species,
    ) {
        parent::__construct(
            __('Only :available :species animals are available — :requested requested.', [
                'available' => $available,
                'species' => $species,
                'requested' => $requested,
            ]),
        );
    }
}
