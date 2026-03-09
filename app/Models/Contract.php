<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    public const CATEGORY_EMPLOYEE = 'employee';
    public const CATEGORY_SUPPLIER = 'supplier';
    public const CATEGORY_CUSTOMER = 'customer';
    public const CATEGORY_TRANSPORT = 'transport';

    public const CATEGORIES = [
        self::CATEGORY_EMPLOYEE => 'Employee contract',
        self::CATEGORY_SUPPLIER => 'Supplier contract',
        self::CATEGORY_CUSTOMER => 'Customer contract',
        self::CATEGORY_TRANSPORT => 'Transport contract',
    ];

    /** Contract type when category = employee */
    public const TYPE_EMPLOYMENT = 'employment';
    public const TYPE_TEMPORARY = 'temporary';
    public const TYPE_CONSULTANT = 'consultant';

    public const EMPLOYEE_TYPES = [
        self::TYPE_EMPLOYMENT => 'Employment',
        self::TYPE_TEMPORARY => 'Temporary',
        self::TYPE_CONSULTANT => 'Consultant',
    ];

    /** Contract type when category = supplier */
    public const TYPE_SUPPLY_AGREEMENT = 'supply_agreement';
    public const TYPE_LIVESTOCK_SUPPLY = 'livestock_supply';
    public const TYPE_SLAUGHTER_AGREEMENT = 'slaughter_agreement';
    public const TYPE_SALE_AGREEMENT = 'sale_agreement';
    public const TYPE_SERVICE_AGREEMENT = 'service_agreement';
    public const TYPE_OTHER = 'other';

    public const SUPPLIER_TYPES = [
        self::TYPE_SUPPLY_AGREEMENT => 'Supply agreement',
        self::TYPE_LIVESTOCK_SUPPLY => 'Livestock supply contract',
        self::TYPE_SLAUGHTER_AGREEMENT => 'Slaughter agreement',
        self::TYPE_SALE_AGREEMENT => 'Sale agreement',
        self::TYPE_SERVICE_AGREEMENT => 'Service agreement',
        self::TYPE_OTHER => 'Other',
    ];

    /** All types (for backward compatibility and validation) */
    public const TYPES = [
        self::TYPE_EMPLOYMENT => 'Employment',
        self::TYPE_TEMPORARY => 'Temporary',
        self::TYPE_CONSULTANT => 'Consultant',
        self::TYPE_SUPPLY_AGREEMENT => 'Supply agreement',
        self::TYPE_LIVESTOCK_SUPPLY => 'Livestock supply contract',
        self::TYPE_SLAUGHTER_AGREEMENT => 'Slaughter agreement',
        self::TYPE_SALE_AGREEMENT => 'Sale agreement',
        self::TYPE_SERVICE_AGREEMENT => 'Service agreement',
        self::TYPE_OTHER => 'Other',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TERMINATED = 'terminated';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_EXPIRED => 'Expired',
        self::STATUS_TERMINATED => 'Terminated',
    ];

    /** Employment type (full-time, part-time, contract) */
    public const EMPLOYMENT_FULL_TIME = 'full_time';
    public const EMPLOYMENT_PART_TIME = 'part_time';
    public const EMPLOYMENT_CONTRACT = 'contract';

    public const EMPLOYMENT_TYPES = [
        self::EMPLOYMENT_FULL_TIME => 'Full-time',
        self::EMPLOYMENT_PART_TIME => 'Part-time',
        self::EMPLOYMENT_CONTRACT => 'Contract',
    ];

    public const DELIVERY_FREQUENCY_DAILY = 'daily';
    public const DELIVERY_FREQUENCY_WEEKLY = 'weekly';
    public const DELIVERY_FREQUENCY_MONTHLY = 'monthly';

    public const DELIVERY_FREQUENCIES = [
        self::DELIVERY_FREQUENCY_DAILY => 'Daily',
        self::DELIVERY_FREQUENCY_WEEKLY => 'Weekly',
        self::DELIVERY_FREQUENCY_MONTHLY => 'Monthly',
    ];

    public const TRANSPORT_SUPPLIER = 'supplier';
    public const TRANSPORT_FACILITY = 'facility';

    public const TRANSPORT_RESPONSIBILITY = [
        self::TRANSPORT_SUPPLIER => 'Supplier',
        self::TRANSPORT_FACILITY => 'Facility',
    ];

    protected $fillable = [
        'business_id',
        'contract_category',
        'supplier_id',
        'employee_id',
        'facility_id',
        'client_id',
        'contract_number',
        'title',
        'type',
        'description',
        'start_date',
        'end_date',
        'status',
        'amount',
        'notes',
        'renewal_date',
        'termination_reason',
        'contract_owner_id',
        // Employee
        'job_position',
        'department',
        'supervisor_name',
        'employment_type',
        'work_schedule',
        'salary_payment_terms',
        'working_hours',
        'probation_period',
        'medical_certificate_number',
        'medical_certificate_expiry_date',
        'safety_training_date',
        'certification_requirements',
        'signed_contract_file',
        'supporting_documents',
        // Supplier
        'farm_name',
        'farm_registration_number',
        'supplier_contact_person',
        'supplier_phone',
        'supplier_email',
        'location_district',
        'location_sector',
        'species_covered',
        'estimated_quantity',
        'delivery_frequency',
        'animal_health_cert_requirement',
        'veterinary_inspection_requirement',
        'animal_welfare_compliance',
        'transport_responsibility',
        'vehicle_plate',
        'driver_name',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'renewal_date' => 'date',
            'medical_certificate_expiry_date' => 'date',
            'safety_training_date' => 'date',
            'supporting_documents' => 'array', // JSON array of file paths
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contractOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contract_owner_id');
    }

    public function animalIntakes(): HasMany
    {
        return $this->hasMany(AnimalIntake::class, 'contract_id');
    }

    public function isEmployeeContract(): bool
    {
        return $this->contract_category === self::CATEGORY_EMPLOYEE;
    }

    public function isSupplierContract(): bool
    {
        return $this->contract_category === self::CATEGORY_SUPPLIER;
    }

    /** Display name for the counterparty (employee, supplier, client, or facility). */
    public function getCounterpartyNameAttribute(): string
    {
        if ($this->contract_category === self::CATEGORY_EMPLOYEE && $this->employee_id && $this->relationLoaded('employee') && $this->employee) {
            return trim(($this->employee->first_name ?? '') . ' ' . ($this->employee->last_name ?? '')) ?: ('Employee #' . $this->employee_id);
        }
        if ($this->supplier_id && $this->relationLoaded('supplier') && $this->supplier) {
            return trim(($this->supplier->first_name ?? '') . ' ' . ($this->supplier->last_name ?? '')) ?: ('Supplier #' . $this->supplier_id);
        }
        if ($this->contract_category === self::CATEGORY_CUSTOMER && $this->client_id && $this->relationLoaded('client') && $this->client) {
            return $this->client->name ?? $this->client->display_name ?? ('Client #' . $this->client_id);
        }
        if ($this->facility_id && $this->relationLoaded('facility') && $this->facility) {
            return $this->facility->facility_name ?? 'Facility #' . $this->facility_id;
        }
        return '—';
    }

    /** Label for contract type (employee or supplier types). */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isExpired(): bool
    {
        if (! $this->end_date) {
            return false;
        }
        return $this->end_date->isPast();
    }

    /** Whether this supplier contract is active (for authorizing animal intake). */
    public function isActiveSupplierContract(): bool
    {
        return $this->contract_category === self::CATEGORY_SUPPLIER
            && $this->status === self::STATUS_ACTIVE
            && ! $this->isExpired();
    }
}
