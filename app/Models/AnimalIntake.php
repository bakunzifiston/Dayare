<?php

namespace App\Models;

use App\Support\AnimalIntakeMovementPermitStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Animal intake session header — source, facility, compliance documents.
 * Individual animals are stored in AnimalIntakeItem rows.
 */
class AnimalIntake extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (AnimalIntake $intake): void {
            if (empty($intake->reference)) {
                $intake->reference = static::generateReference();
            }
        });

        static::deleting(function (AnimalIntake $intake): void {
            AnimalIntakeMovementPermitStorage::delete($intake->movement_permit_document_path);
        });
    }

    protected $fillable = [
        'reference',
        'facility_id',
        'supply_request_id',
        'farm_id',
        'source_type',
        'supplier_id',
        'client_id',
        'contract_id',
        'intake_date',
        'supplier_firstname',
        'supplier_lastname',
        'supplier_contact',
        'farm_name',
        'farm_registration_number',
        'movement_permit_no',
        'movement_permit_document_path',
        'country_id',
        'province_id',
        'district_id',
        'sector_id',
        'cell_id',
        'village_id',
        'species',
        'species_ear_tag',
        'sex',
        'age',
        'number_of_animals',
        'unit_price',
        'total_price',
        'animal_identification_numbers',
        'observation',
        'meat_inspector_name',
        'transport_vehicle_plate',
        'driver_name',
        'animal_health_certificate_number',
        'health_certificate_issue_date',
        'health_certificate_expiry_date',
        'status',
        'is_draft',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'intake_date' => 'datetime',
            'health_certificate_issue_date' => 'date',
            'health_certificate_expiry_date' => 'date',
            'submitted_at' => 'datetime',
            'age' => 'integer',
            'is_draft' => 'boolean',
        ];
    }

    public const STATUS_RECEIVED = 'received';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_RECEIVED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const SPECIES_CATTLE = 'Cattle';

    public const SPECIES_GOAT = 'Goat';

    public const SPECIES_SHEEP = 'Sheep';

    public const SPECIES_PIG = 'Pig';

    public const SPECIES_OTHER = 'Other';

    public const SPECIES_OPTIONS = [
        self::SPECIES_CATTLE,
        self::SPECIES_GOAT,
        self::SPECIES_SHEEP,
        self::SPECIES_PIG,
        self::SPECIES_OTHER,
    ];

    public const SEX_MALE = 'male';

    public const SEX_FEMALE = 'female';

    public const SEX_UNKNOWN = 'unknown';

    public const SEX_OPTIONS = [
        self::SEX_MALE,
        self::SEX_FEMALE,
        self::SEX_UNKNOWN,
    ];

    public const SOURCE_TYPE_SUPPLIER = 'supplier';

    public const SOURCE_TYPE_CLIENT = 'client';

    /** @var list<string> Allowed values for new intake records. */
    public const SOURCE_TYPES = [
        self::SOURCE_TYPE_CLIENT,
    ];

    public function isSupplierSource(): bool
    {
        return $this->source_type === self::SOURCE_TYPE_SUPPLIER;
    }

    public static function generateReference(): string
    {
        $year = now()->year;
        $sequence = static::query()
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('INT-%d-%05d', $year, $sequence);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function supplyRequest(): BelongsTo
    {
        return $this->belongsTo(SupplyRequest::class);
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'country_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'province_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(AdministrativeDivision::class, 'district_id');
    }

    public function sector(): BelongsTo
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

    public function items(): HasMany
    {
        return $this->hasMany(AnimalIntakeItem::class)->orderBy('id');
    }

    public function slaughterPlans(): HasMany
    {
        return $this->hasMany(SlaughterPlan::class);
    }

    public function isDraft(): bool
    {
        return (bool) $this->is_draft;
    }

    public function isSubmitted(): bool
    {
        return ! $this->is_draft && $this->submitted_at !== null;
    }

    public function isPlannableForSlaughter(): bool
    {
        return ! $this->is_draft
            && in_array($this->status, [self::STATUS_RECEIVED, self::STATUS_APPROVED], true);
    }

    /**
     * Submitted intakes with animals still available for slaughter planning.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<AnimalIntake>  $query
     * @return \Illuminate\Database\Eloquent\Builder<AnimalIntake>
     */
    public function scopePlannableForSlaughter(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where('is_draft', false)
            ->whereIn('status', [self::STATUS_RECEIVED, self::STATUS_APPROVED]);
    }

    public function isHealthCertificateExpired(): bool
    {
        if (blank($this->health_certificate_expiry_date)) {
            return false;
        }

        return $this->health_certificate_expiry_date->isPast();
    }

    /** Human-readable intake timestamp; omits midnight for legacy date-only rows. */
    public function intakeDatetimeLabel(): string
    {
        if ($this->intake_date === null) {
            return '—';
        }

        if ($this->intake_date->format('H:i') === '00:00') {
            return $this->intake_date->format('d M Y');
        }

        return $this->intake_date->format('d M Y H:i');
    }

    /** Total number of animals already scheduled for slaughter from this intake */
    public function totalScheduledForSlaughter(): int
    {
        return (int) $this->slaughterPlans()->sum('number_of_animals_scheduled');
    }

    /** Animals not yet assigned to a slaughter plan (item-based, with legacy fallback). */
    public function remainingAnimalsAvailable(): int
    {
        if ($this->hasItemRows()) {
            if ($this->relationLoaded('items')) {
                return $this->items
                    ->filter(fn (AnimalIntakeItem $item) => $item->isAvailableForPlanning())
                    ->count();
            }

            return (int) $this->items()->available()->count();
        }

        return max(0, $this->legacyNumberOfAnimals() - $this->totalScheduledForSlaughter());
    }

    public function getNumberOfAnimalsAttribute($value): int
    {
        if ($this->relationLoaded('items')) {
            if ($this->items->isNotEmpty()) {
                return $this->items->count();
            }
        } elseif ($this->items()->exists()) {
            return (int) $this->items()->count();
        }

        return (int) ($value ?? 0);
    }

    public function getTotalPriceAttribute($value): float
    {
        if ($this->relationLoaded('items')) {
            if ($this->items->isNotEmpty()) {
                return (float) $this->items->sum('unit_price');
            }
        } elseif ($this->items()->exists()) {
            return (float) $this->items()->sum('unit_price');
        }

        return (float) ($value ?? 0);
    }

    /**
     * @return array<string, int>
     */
    public function getSpeciesMixAttribute(): array
    {
        if (! $this->hasItemRows()) {
            return [];
        }

        return $this->itemCollection()
            ->groupBy('species')
            ->map(fn (Collection $group) => $group->count())
            ->sortKeys()
            ->all();
    }

    public function getSpeciesMixLabelAttribute(): string
    {
        $mix = $this->species_mix;

        if ($mix !== []) {
            return collect($mix)
                ->map(fn (int $count, string $species) => $species.' ('.$count.')')
                ->implode(', ');
        }

        return (string) ($this->attributes['species'] ?? '');
    }

    public function getHasRejectedAnimalsAttribute(): bool
    {
        if (! $this->hasItemRows()) {
            return false;
        }

        if ($this->relationLoaded('items')) {
            return $this->items->contains(fn (AnimalIntakeItem $item) => $item->isRejected());
        }

        return $this->items()->rejected()->exists();
    }

    public function getHasObservationAnimalsAttribute(): bool
    {
        if (! $this->hasItemRows()) {
            return false;
        }

        if ($this->relationLoaded('items')) {
            return $this->items->contains(
                fn (AnimalIntakeItem $item) => $item->health_status === AnimalIntakeItem::HEALTH_OBSERVATION,
            );
        }

        return $this->items()
            ->where('health_status', AnimalIntakeItem::HEALTH_OBSERVATION)
            ->exists();
    }

    /**
     * @return array{healthy: int, under_observation: int, rejected: int}
     */
    public function getHealthSummaryAttribute(): array
    {
        $summary = [
            'healthy' => 0,
            'under_observation' => 0,
            'rejected' => 0,
        ];

        if (! $this->hasItemRows()) {
            $legacyCount = $this->legacyNumberOfAnimals();
            if ($legacyCount > 0) {
                $summary['healthy'] = $legacyCount;
            }

            return $summary;
        }

        $items = $this->itemCollection();

        $summary['healthy'] = $items->where('health_status', AnimalIntakeItem::HEALTH_HEALTHY)->count();
        $summary['under_observation'] = $items->where('health_status', AnimalIntakeItem::HEALTH_OBSERVATION)->count();
        $summary['rejected'] = $items->where('health_status', AnimalIntakeItem::HEALTH_REJECTED)->count();

        return $summary;
    }

    /** Linked CRM client name, or manual client names on client-source intake without `client_id`. */
    public function clientSourceDisplayName(): string
    {
        if ($this->client_id) {
            $this->loadMissing('client');
            if ($this->client) {
                return (string) $this->client->name;
            }
        }

        $name = trim((string) ($this->supplier_firstname ?? '').' '.(string) ($this->supplier_lastname ?? ''));

        return $name !== '' ? $name : (string) __('Intake #:id', ['id' => $this->id]);
    }

    /** One line for AR invoice selector: name · species · number of animals. */
    public function labelForFinanceInvoice(): string
    {
        $speciesLabel = $this->species_mix_label !== ''
            ? $this->species_mix_label
            : (string) ($this->attributes['species'] ?? '');

        return $this->clientSourceDisplayName().' · '.$speciesLabel.' · '.$this->number_of_animals.' '.__('animals');
    }

    public function movementPermitDocumentUrl(): ?string
    {
        if ($this->movement_permit_document_path === null || $this->movement_permit_document_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->movement_permit_document_path);
    }

    protected function hasItemRows(): bool
    {
        if ($this->relationLoaded('items')) {
            return $this->items->isNotEmpty();
        }

        return $this->items()->exists();
    }

    /**
     * @return Collection<int, AnimalIntakeItem>
     */
    protected function itemCollection(): Collection
    {
        if ($this->relationLoaded('items')) {
            return $this->items;
        }

        return $this->items()->get();
    }

    protected function legacyNumberOfAnimals(): int
    {
        return (int) ($this->attributes['number_of_animals'] ?? 0);
    }
}
