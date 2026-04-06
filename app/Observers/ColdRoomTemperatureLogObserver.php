<?php

namespace App\Observers;

use App\Models\ColdRoomTemperatureLog;
use App\Services\ColdRoomMonitoringService;

class ColdRoomTemperatureLogObserver
{
    public function created(ColdRoomTemperatureLog $log): void
    {
        app(ColdRoomMonitoringService::class)->processLog($log);
    }
}
