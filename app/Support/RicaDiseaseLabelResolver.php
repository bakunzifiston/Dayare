<?php

namespace App\Support;

use Illuminate\Support\Str;

class RicaDiseaseLabelResolver
{
    public static function fromText(?string $text): string
    {
        $text = Str::lower(trim((string) $text));
        if ($text === '') {
            return __('Other');
        }

        foreach (config('rica_disease_intelligence.catalog', []) as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ($entry['keywords'] ?? [] as $keyword) {
                if ($keyword !== '' && str_contains($text, Str::lower((string) $keyword))) {
                    return (string) $entry['name'];
                }
            }
        }

        return Str::title(Str::limit($text, 60));
    }

    public static function fromChecklistItem(?string $item): string
    {
        $item = trim((string) $item);
        if ($item === '') {
            return __('Other');
        }

        $mapped = config('rica_disease_intelligence.checklist_item_labels.'.$item);
        if (is_string($mapped) && $mapped !== '') {
            return self::fromText($mapped);
        }

        return self::fromText(str_replace('_', ' ', $item));
    }

    /**
     * @return array{name: string, condition: string, condemnation_reason: string}
     */
    public static function catalogEntry(int $index): array
    {
        $catalog = config('rica_disease_intelligence.catalog', []);
        $entry = $catalog[$index % max(1, count($catalog))] ?? [];

        return [
            'name' => (string) ($entry['name'] ?? __('Other')),
            'condition' => (string) ($entry['condition'] ?? __('Unspecified clinical finding')),
            'condemnation_reason' => (string) ($entry['condemnation_reason'] ?? __('Unfit for human consumption')),
        ];
    }
}
