<?php

namespace App\Models;

use App\Support\SalesComplianceCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesComplianceInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'site_id',
        'inspector_id',
        'assigned_user_id',
        'scheduled_date',
        'scheduled_time',
        'status',
        'meat_source',
        'inspector_notes',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(SalesComplianceSite::class, 'site_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Inspector::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SalesComplianceChecklistResponse::class, 'inspection_id');
    }

    public function productLines(): HasMany
    {
        return $this->hasMany(SalesComplianceProductLine::class, 'inspection_id')->orderBy('sort_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SalesComplianceAttachment::class, 'inspection_id');
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(SalesComplianceEscalation::class, 'inspection_id');
    }

    public function assigneeName(): string
    {
        if ($this->inspector) {
            return $this->inspector->full_name;
        }

        return $this->assignedUser?->name ?? '—';
    }

    public function assigneeValue(): string
    {
        if ($this->inspector_id) {
            return 'inspector:'.$this->inspector_id;
        }
        if ($this->assigned_user_id) {
            return 'user:'.$this->assigned_user_id;
        }

        return '';
    }

    public function scheduledTimeDisplay(): string
    {
        $time = (string) $this->scheduled_time;

        return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
    }

    public function isPending(): bool
    {
        return $this->status === SalesComplianceCatalog::STATUS_PENDING;
    }

    public function isUpcoming(): bool
    {
        return $this->isPending() && $this->scheduled_date && $this->scheduled_date->gte(now()->startOfDay());
    }

    public function statusLabel(): string
    {
        return SalesComplianceCatalog::statusLabels()[$this->status] ?? $this->status;
    }
}
