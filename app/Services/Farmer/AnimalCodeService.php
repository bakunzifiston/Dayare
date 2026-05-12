<?php

namespace App\Services\Farmer;

use App\Models\Animal;
use App\Models\Livestock;
use Illuminate\Support\Str;

class AnimalCodeService
{
    public function generateForLivestock(Livestock $livestock): string
    {
        $prefix = 'ANM-'.str_pad((string) $livestock->id, 5, '0', STR_PAD_LEFT);

        do {
            $code = $prefix.'-'.Str::upper(Str::random(5));
        } while (
            Animal::withTrashed()
                ->where('livestock_id', $livestock->id)
                ->where('animal_code', $code)
                ->exists()
        );

        return $code;
    }

    public function generateQrPayload(string $animalCode): string
    {
        return 'DAYARE:ANIMAL:'.$animalCode;
    }
}
