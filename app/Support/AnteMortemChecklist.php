<?php

namespace App\Support;

class AnteMortemChecklist
{
    public static function all(): array
    {
        return config('ante_mortem_checklist.checklists', []);
    }

    public static function speciesKey(?string $species): ?string
    {
        if (! is_string($species) || trim($species) === '') {
            return null;
        }

        $normalized = strtolower(trim($species));

        return config('ante_mortem_checklist.species_aliases.'.$normalized, null);
    }

    public static function itemsForSpecies(?string $species): array
    {
        $key = self::speciesKey($species);
        if (! $key) {
            return [];
        }

        return config('ante_mortem_checklist.checklists.'.$key, []);
    }

    public static function allowedValuesForType(string $type): array
    {
        return config('ante_mortem_checklist.value_options.'.$type, []);
    }

    public static function allowedValuesForItem(?string $species, string $item): array
    {
        $items = self::itemsForSpecies($species);
        $type = $items[$item]['type'] ?? null;

        if (! is_string($type)) {
            return [];
        }

        return self::allowedValuesForType($type);
    }
}
