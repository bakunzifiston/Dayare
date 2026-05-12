<?php

namespace App\Services\Farmer;

use App\Models\MovementLog;
use App\Models\MovementPermit;
use Illuminate\Http\Request;

class MovementHistoryService
{
    public function log(
        MovementPermit $permit,
        string $action,
        ?int $userId = null,
        ?string $ip = null,
        ?string $notes = null,
    ): void {
        $permit->logs()->create([
            'action_type' => $action,
            'action_by' => $userId,
            'action_date' => now(),
            'ip_address' => $ip,
            'notes' => $notes,
        ]);
    }

    public function requestIp(?Request $request = null): ?string
    {
        return $request?->ip();
    }
}
