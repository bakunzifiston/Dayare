<?php

namespace App\Services\Farmer;

use App\Models\Buyer;
use Illuminate\Support\Str;

class BuyerCodeService
{
    public function generate(int $businessId): string
    {
        do {
            $code = 'BYR-'.$businessId.'-'.Str::upper(Str::random(6));
        } while (Buyer::withTrashed()->where('business_id', $businessId)->where('buyer_code', $code)->exists());

        return $code;
    }
}
