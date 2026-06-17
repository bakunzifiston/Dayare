<?php

namespace App\Models\Concerns;

trait DefinesButcherMeatTypes
{
    public const MEAT_BEEF = 'beef';

    public const MEAT_PORK = 'pork';

    public const MEAT_GOAT = 'goat';

    public const MEAT_LAMB = 'lamb';

    public const MEAT_POULTRY = 'poultry';

    public const MEAT_MIXED = 'mixed';

    /** @var list<string> */
    public const MEAT_TYPES = [
        self::MEAT_BEEF,
        self::MEAT_PORK,
        self::MEAT_GOAT,
        self::MEAT_LAMB,
        self::MEAT_POULTRY,
        self::MEAT_MIXED,
    ];
}
