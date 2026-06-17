<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherHygieneLog extends Model
{
    public const STATUS_PASS = 'pass';

    public const STATUS_FAIL = 'fail';

    public const STATUS_PARTIAL = 'partial';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_PASS,
        self::STATUS_FAIL,
        self::STATUS_PARTIAL,
    ];

    /** @var array<string, bool> */
    public const DEFAULT_CHECKLIST = [
        'floor_cleaned' => false,
        'knives_sanitized' => false,
        'cold_room_checked' => false,
        'waste_disposed' => false,
        'surfaces_wiped' => false,
        'staff_ppe_worn' => false,
    ];

    /** @var array<string, string> */
    public const CHECKLIST_LABELS = [
        'floor_cleaned' => 'Floor cleaned',
        'knives_sanitized' => 'Knives sanitized',
        'cold_room_checked' => 'Cold room checked',
        'waste_disposed' => 'Waste disposed',
        'surfaces_wiped' => 'Surfaces wiped',
        'staff_ppe_worn' => 'Staff PPE worn',
    ];

    protected $fillable = [
        'business_id',
        'outlet_id',
        'log_date',
        'checklist',
        'issues_found',
        'corrective_action',
        'signed_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'checklist' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(ButcherOutlet::class, 'outlet_id');
    }

    public function signedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public static function resolveStatus(array $checklist): string
    {
        $values = array_values($checklist);
        $passed = count(array_filter($values));
        $total = count($values);

        if ($passed === 0) {
            return self::STATUS_FAIL;
        }

        if ($passed === $total) {
            return self::STATUS_PASS;
        }

        return self::STATUS_PARTIAL;
    }
}
