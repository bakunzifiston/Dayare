<?php

namespace App\Services\Farmer;

use App\Models\Farm;
use App\Models\Livestock;
use Illuminate\Support\Str;

class LivestockCodeService
{
    public function generateForFarm(Farm $farm): string
    {
        $prefix = 'LSK-'.str_pad((string) $farm->id, 4, '0', STR_PAD_LEFT);

        do {
            $code = $prefix.'-'.Str::upper(Str::random(6));
        } while (
            Livestock::withTrashed()
                ->where('farm_id', $farm->id)
                ->where('livestock_code', $code)
                ->exists()
        );

        return $code;
    }
}
