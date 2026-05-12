<?php

namespace App\Services\Farmer;

use App\Models\Sale;
use App\Models\SaleLog;
use Illuminate\Http\Request;

class SaleHistoryService
{
    public function log(
        Sale $sale,
        string $action,
        ?int $userId = null,
        ?string $ip = null,
        ?string $notes = null,
    ): void {
        $sale->logs()->create([
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
