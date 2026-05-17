<?php

namespace App\Services\Farmer;

use App\Models\PermitRequest;
use Illuminate\Support\Str;

class PermitRequestCodeService
{
    public function generate(): string
    {
        $year = now()->year;

        do {
            $sequence = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $number = "PR-{$year}-{$sequence}";
        } while (PermitRequest::withTrashed()->where('request_number', $number)->exists());

        return $number;
    }
}
