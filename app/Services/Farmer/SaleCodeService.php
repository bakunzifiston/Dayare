<?php

namespace App\Services\Farmer;

use App\Models\Sale;
use Illuminate\Support\Str;

class SaleCodeService
{
    public function generate(): string
    {
        do {
            $number = 'SL-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Sale::withTrashed()->where('sale_number', $number)->exists());

        return $number;
    }
}
