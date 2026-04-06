<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ColdRoomStandard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'min_temperature',
        'max_temperature',
        'tolerance_minutes',
    ];

    protected function casts(): array
    {
        return [
            'min_temperature' => 'decimal:2',
            'max_temperature' => 'decimal:2',
            'tolerance_minutes' => 'integer',
        ];
    }

    public const TYPE_CHILLER = 'chiller';

    public const TYPE_FREEZER = 'freezer';

    public function coldRooms(): HasMany
    {
        return $this->hasMany(ColdRoom::class, 'standard_id');
    }

    public function temperatureInRange(float $celsius): bool
    {
        return $celsius >= (float) $this->min_temperature
            && $celsius <= (float) $this->max_temperature;
    }
}
