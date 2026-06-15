<?php

namespace App\Support;

class PostMortemChecklist
{
    public static function all(): array
    {
        return config('post_mortem_checklist.checklists', []);
    }

    public static function speciesKey(?string $species): ?string
    {
        if (! is_string($species) || trim($species) === '') {
            return null;
        }

        $normalized = strtolower(trim($species));

        return config('post_mortem_checklist.species_aliases.'.$normalized, null);
    }

    public static function itemsForSpecies(?string $species): array
    {
        $key = self::speciesKey($species);
        if (! $key) {
            return config('post_mortem_checklist.checklists.all_species', []);
        }

        return config('post_mortem_checklist.checklists.'.$key, config('post_mortem_checklist.checklists.all_species', []));
    }

    /**
     * Checklist items for per-animal inspection (excludes batch-level decision).
     *
     * @return array<string, array{label: string, type: string, category?: string, critical?: bool}>
     */
    public static function itemsForInspection(?string $species, bool $hasInspectableAnimals): array
    {
        $items = self::itemsForSpecies($species);

        if ($hasInspectableAnimals) {
            unset($items['decision']);
        }

        return $items;
    }

    public static function allowedValuesForType(string $type): array
    {
        return config('post_mortem_checklist.value_options.'.$type, []);
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

    public static function isAbnormalValue(string $value): bool
    {
        return in_array($value, ['abnormal', 'yes'], true);
    }

    public static function isCriticalItem(?string $species, string $item): bool
    {
        $items = self::itemsForSpecies($species);

        return (bool) ($items[$item]['critical'] ?? false);
    }
}
