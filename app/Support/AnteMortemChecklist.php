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
            return config('ante_mortem_checklist.checklists.all_species', []);
        }

        return config('ante_mortem_checklist.checklists.'.$key, config('ante_mortem_checklist.checklists.all_species', []));
    }

    /**
     * Clinical checklist items for an inspection. Approve/reject belongs on individual
     * animals when the slaughter plan has assigned intake items.
     *
     * @return array<string, array{label: string, type: string}>
     */
    public static function itemsForInspection(?string $species, bool $hasAssignedAnimals): array
    {
        $items = self::itemsForSpecies($species);

        if ($hasAssignedAnimals) {
            unset($items['decision']);
        }

        return $items;
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
