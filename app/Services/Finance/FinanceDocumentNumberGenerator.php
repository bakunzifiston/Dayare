<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\DB;

class FinanceDocumentNumberGenerator
{
    public static function next(string $prefix, int $businessId, string $table, string $column): string
    {
        $date = now()->format('Ymd');
        $stem = sprintf('%s-%d-%s-', $prefix, $businessId, $date);

        return DB::transaction(function () use ($stem, $table, $column) {
            $latest = DB::table($table)
                ->where($column, 'like', $stem.'%')
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            $sequence = 1;
            if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches) === 1) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return $stem.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
