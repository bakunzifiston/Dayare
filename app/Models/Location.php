<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'address',
    ];

    public function tripsOriginating(): HasMany
    {
        return $this->hasMany(LogisticsTrip::class, 'origin_location_id');
    }

    public function tripsDestined(): HasMany
    {
        return $this->hasMany(LogisticsTrip::class, 'destination_location_id');
    }

    public function trackingLogs(): HasMany
    {
        return $this->hasMany(LogisticsTrackingLog::class, 'location_id');
    }
}
