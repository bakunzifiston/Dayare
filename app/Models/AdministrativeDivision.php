<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdministrativeDivision extends Model
{
    protected $fillable = ['parent_id', 'name', 'type', 'code'];

    public const TYPE_COUNTRY = 'country';
    public const TYPE_PROVINCE = 'province';
    public const TYPE_DISTRICT = 'district';
    public const TYPE_SECTOR = 'sector';
    public const TYPE_CELL = 'cell';
    public const TYPE_VILLAGE = 'village';

    public const TYPES = [
        self::TYPE_COUNTRY,
        self::TYPE_PROVINCE,
        self::TYPE_DISTRICT,
        self::TYPE_SECTOR,
        self::TYPE_CELL,
        self::TYPE_VILLAGE,
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AdministrativeDivision::class, 'parent_id')->orderBy('name');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByParent($query, ?int $parentId)
    {
        return $query->where('parent_id', $parentId);
    }
}
