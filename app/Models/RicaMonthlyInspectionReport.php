<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RicaMonthlyInspectionReport extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
    ];

    protected $fillable = [
        'facility_id',
        'period_year',
        'period_month',
        'challenges',
        'recommendations',
        'inspector_signatures',
        'operator_name',
        'operator_signed_at',
        'stamp_acknowledged',
        'status',
        'submitted_at',
        'submitted_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'inspector_signatures' => 'array',
            'operator_signed_at' => 'datetime',
            'stamp_acknowledged' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public static function findForPeriod(int $facilityId, Carbon $periodStart): ?self
    {
        return self::query()
            ->where('facility_id', $facilityId)
            ->where('period_year', $periodStart->year)
            ->where('period_month', $periodStart->month)
            ->first();
    }

    /**
     * @return list<array{name: string|null, signed_at: string|null}>
     */
    public function normalizedInspectorSignatures(): array
    {
        $signatures = is_array($this->inspector_signatures) ? $this->inspector_signatures : [];

        return collect($signatures)
            ->map(function ($row) {
                $signedAt = $row['signed_at'] ?? null;
                if ($signedAt instanceof \Carbon\CarbonInterface) {
                    $signedAt = $signedAt->toIso8601String();
                }

                return [
                    'name' => isset($row['name']) ? trim((string) $row['name']) : null,
                    'signed_at' => $signedAt ? (string) $signedAt : null,
                ];
            })
            ->filter(fn (array $row) => ($row['name'] ?? '') !== '' || ($row['signed_at'] ?? '') !== '')
            ->values()
            ->all();
    }
}
