<?php

namespace App\Models;

use App\Models\Concerns\HasFinancePayments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FinanceInvoice extends Model
{
    use HasFactory;
    use HasFinancePayments;

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_DELIVERY = 'delivery';

    public const SOURCE_INTAKE = 'intake';

    protected $fillable = [
        'business_id',
        'client_id',
        'animal_intake_id',
        'contract_id',
        'delivery_confirmation_id',
        'facility_id',
        'invoice_number',
        'source_type',
        'status',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'issued_at',
        'due_date',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_date' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function animalIntake(): BelongsTo
    {
        return $this->belongsTo(AnimalIntake::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function deliveryConfirmation(): BelongsTo
    {
        return $this->belongsTo(DeliveryConfirmation::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinanceInvoiceLine::class, 'invoice_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function ebmRecord(): HasOne
    {
        return $this->hasOne(FinanceEbmRecord::class, 'finance_invoice_id');
    }

    public function resolvedFacilityId(): ?int
    {
        if ($this->facility_id) {
            return (int) $this->facility_id;
        }

        if ($this->relationLoaded('animalIntake') && $this->animalIntake?->facility_id) {
            return (int) $this->animalIntake->facility_id;
        }

        if ($this->relationLoaded('deliveryConfirmation')) {
            $originId = $this->deliveryConfirmation?->transportTrip?->origin_facility_id;
            if ($originId) {
                return (int) $originId;
            }
        }

        return $this->animalIntake?->facility_id ? (int) $this->animalIntake->facility_id : null;
    }

    public function needsEbmFollowUp(): bool
    {
        if (in_array($this->status, ['draft', 'cancelled'], true)) {
            return false;
        }

        if ($this->ebmRecord === null) {
            return true;
        }

        return in_array($this->ebmRecord->reconciliationState(), [
            FinanceEbmRecord::RECON_AMOUNT_MISMATCH,
            FinanceEbmRecord::RECON_REFERENCE_MISMATCH,
        ], true);
    }
}
