<?php

namespace App\Models;

use App\Support\SalesComplianceCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesComplianceEscalation extends Model
{
    protected $fillable = [
        'business_id',
        'site_id',
        'inspection_id',
        'status',
        'reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(SalesComplianceSite::class, 'site_id');
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(SalesComplianceInspection::class, 'inspection_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusLabel(): string
    {
        return SalesComplianceCatalog::escalationStatusLabels()[$this->status] ?? $this->status;
    }
}
