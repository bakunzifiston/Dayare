<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\AdministrativeDivision;

class Employee extends Model
{
    use HasFactory;

    /** Job title options for dropdown (optional). */
    public const JOB_TITLES = [
        'general_manager' => 'General Manager',
        'facility_manager' => 'Facility Manager',
        'slaughter_operator' => 'Slaughter Operator',
        'butcher' => 'Butcher',
        'warehouse_manager' => 'Cold Room Manager',
        'transport_manager' => 'Transport Manager',
        'other' => 'Other',
    ];

    protected $fillable = [
        'business_id',
        'facility_id',
        'first_name',
        'last_name',
        'national_id',
        'date_of_birth',
        'nationality',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'work_email',
        'personal_email',
        'phone',
        'job_title',
        'employment_type',
        'hire_date',
        'termination_date',
        'status',
        'user_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'country_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'province_id');
    }

    public function districtDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'district_id');
    }

    public function sectorDivision(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'sector_id');
    }

    public function cell(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'cell_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'village_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}

