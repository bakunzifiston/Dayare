<?php

namespace Database\Seeders\Support;

use App\Support\AnteMortemChecklist;
use App\Support\PostMortemChecklist;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Rwanda-style demo data helpers: time distribution, checklist rows, name lists.
 */
class RwandaSeederHelper
{
    /** @var list<string> */
    public const FIRST_NAMES = [
        'Jean', 'Marie', 'Patrick', 'Chantal', 'Eric', 'Grace', 'Fabrice', 'Innocent',
        'Vestine', 'David', 'Claudine', 'André', 'Jean Pierre', 'Uwimana', 'Niyonzima',
        'Habimana', 'Mukamana', 'Nsengiyumva', 'Umutoni', 'Bizimana', 'Uwase', 'Nkusi',
        'Mukiza', 'Ndayisaba', 'Kamanzi', 'Ingabire', 'Hategekimana', 'Murekatete',
    ];

    /** @var list<string> */
    public const LAST_NAMES = [
        'Nkurunziza', 'Uwera', 'Mugisha', 'Mukandori', 'Nsengimana', 'Habyarimana',
        'Niyonsenga', 'Uwineza', 'Murekezi', 'Mukamana', 'Kabera', 'Nzabonimana',
        'Bayingana', 'Irakoze', 'Manirakiza', 'Ntambara', 'Rwamugema', 'Sebera',
    ];

    public static function fullName(int $seed): string
    {
        $f = self::FIRST_NAMES[$seed % count(self::FIRST_NAMES)];
        $l = self::LAST_NAMES[($seed * 7) % count(self::LAST_NAMES)];

        return $f.' '.$l;
    }

    public static function phone(int $seed): string
    {
        return '+25078'.str_pad((string) (100000 + ($seed % 899999)), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Spread timestamps across [start, end] with light randomness (not all at midnight).
     */
    public static function dateInRange(CarbonInterface $start, CarbonInterface $end, int $index, int $total): Carbon
    {
        $total = max(1, $total);
        $t0 = $start->timestamp;
        $t1 = $end->timestamp;
        $frac = $index / $total;
        $base = $t0 + (int) (($t1 - $t0) * $frac);
        $jitter = random_int(-3600, 14_400);

        return Carbon::createFromTimestamp($base + $jitter)->seconds(0);
    }

    /**
     * @return list<array{item: string, value: string, notes: ?string}>
     */
    public static function anteMortemObservationPayload(string $speciesLabel): array
    {
        $items = AnteMortemChecklist::itemsForSpecies($speciesLabel);
        $rows = [];
        foreach ($items as $itemKey => $meta) {
            $type = is_array($meta) ? ($meta['type'] ?? 'yes_no') : 'yes_no';
            $opts = AnteMortemChecklist::allowedValuesForType($type);
            if ($opts === []) {
                continue;
            }
            $rows[] = [
                'item' => (string) $itemKey,
                'value' => (string) $opts[array_rand($opts)],
                'notes' => null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{category: string, item: string, value: string, notes: ?string}>
     */
    public static function postMortemObservationPayload(string $speciesLabel): array
    {
        $items = PostMortemChecklist::itemsForSpecies($speciesLabel);
        $rows = [];
        foreach ($items as $itemKey => $meta) {
            if (! is_array($meta)) {
                continue;
            }
            $type = $meta['type'] ?? 'yes_no';
            $category = $meta['category'] ?? 'organ';
            $opts = PostMortemChecklist::allowedValuesForType($type);
            if ($opts === []) {
                continue;
            }
            $rows[] = [
                'category' => (string) $category,
                'item' => (string) $itemKey,
                'value' => (string) $opts[array_rand($opts)],
                'notes' => null,
            ];
        }

        return $rows;
    }
}
