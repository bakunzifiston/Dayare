<?php

namespace App\Services\Farmer;

use App\Models\MovementPermit;
use Illuminate\Support\Str;

class MovementPermitCodeService
{
    public function generate(): string
    {
        do {
            $number = 'MP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (MovementPermit::withTrashed()->where('permit_number', $number)->exists());

        return $number;
    }

    public function generateVerificationToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (MovementPermit::withTrashed()->where('verification_token', $token)->exists());

        return $token;
    }
}
