<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementVeterinaryApproval extends Model
{
    public const RESULT_PASSED = 'passed';

    public const RESULT_FAILED = 'failed';

    public const RESULT_OBSERVATION = 'further_observation_needed';

    public const RESULT_PENDING = 'pending';

    /** @var list<string> */
    public const RESULTS = [
        self::RESULT_PASSED,
        self::RESULT_FAILED,
        self::RESULT_OBSERVATION,
        self::RESULT_PENDING,
    ];

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    public const APPROVAL_PENDING = 'pending';

    /** @var list<string> */
    public const APPROVAL_STATUSES = [
        self::APPROVAL_APPROVED,
        self::APPROVAL_REJECTED,
        self::APPROVAL_PENDING,
    ];

    protected $fillable = [
        'movement_permit_id',
        'veterinarian_name',
        'inspection_date',
        'inspection_result',
        'health_clearance',
        'disease_check',
        'quarantine_check',
        'recommendations',
        'approval_status',
        'digital_signature',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'health_clearance' => 'boolean',
            'disease_check' => 'boolean',
            'quarantine_check' => 'boolean',
        ];
    }

    public function movementPermit(): BelongsTo
    {
        return $this->belongsTo(MovementPermit::class);
    }
}
