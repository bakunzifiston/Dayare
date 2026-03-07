<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Demand extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'demand_number',
        'title',
        'destination_facility_id',
        'client_id',
        'contract_id',
        'client_name',
        'client_company',
        'client_country',
        'client_contact',
        'client_address',
        'species',
        'product_description',
        'quantity',
        'quantity_unit',
        'requested_delivery_date',
        'status',
        'notes',
        'fulfilled_by_delivery_id',
    ];

    protected function casts(): array
    {
        return [
            'requested_delivery_date' => 'date',
            'quantity' => 'decimal:2',
        ];
    }

    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_FULFILLED => 'Fulfilled',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public const UNIT_KG = 'kg';
    public const UNIT_HEADS = 'heads';
    public const UNIT_OTHER = 'other';

    public const QUANTITY_UNITS = [
        self::UNIT_KG => 'kg',
        self::UNIT_HEADS => 'Heads',
        self::UNIT_OTHER => 'Other',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function destinationFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'destination_facility_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /** Delivery that fulfilled this demand (when set, status should be fulfilled). */
    public function fulfilledByDelivery(): BelongsTo
    {
        return $this->belongsTo(DeliveryConfirmation::class, 'fulfilled_by_delivery_id');
    }

    /** Destination display: facility name, or client (name/country), or inline client fields. */
    public function getDestinationDisplayAttribute(): string
    {
        if ($this->destination_facility_id && $this->relationLoaded('destinationFacility') && $this->destinationFacility) {
            return $this->destinationFacility->facility_name;
        }
        if ($this->client_id && $this->relationLoaded('client') && $this->client) {
            return $this->client->display_name;
        }
        if ($this->client_name || $this->client_company) {
            $name = trim($this->client_company ?: $this->client_name);
            return $this->client_country ? "{$name} ({$this->client_country})" : $name;
        }
        return '—';
    }

    public function isExternalClient(): bool
    {
        return $this->destination_facility_id === null;
    }

    /**
     * Get available quantity (same species) from warehouse storage for this demand's business.
     * Only compliant stock counts (certificate active and not expired).
     * Returns array: available_quantity (compliant), total_warehouse_quantity, can_fulfill, short_by, compliance_ok, message, etc.
     */
    public function getFulfillmentInfo(): array
    {
        $required = (float) $this->quantity;
        $unit = $this->quantity_unit;

        $baseQuery = \App\Models\WarehouseStorage::query()
            ->where('status', \App\Models\WarehouseStorage::STATUS_IN_STORAGE)
            ->whereHas('batch', fn ($q) => $q->where('species', $this->species))
            ->whereHas('warehouseFacility', fn ($q) => $q->where('business_id', $this->business_id));

        $totalInWarehouse = (float) (clone $baseQuery)->sum('quantity_stored');

        $compliantAvailable = (float) (clone $baseQuery)
            ->whereHas('certificate', fn ($q) => $q->compliant())
            ->sum('quantity_stored');

        $available = $compliantAvailable;
        $canFulfill = $available >= $required;
        $shortBy = $canFulfill ? 0.0 : max(0, $required - $available);
        $complianceOk = $totalInWarehouse <= 0 || $compliantAvailable >= $required;

        $message = $compliantAvailable > 0
            ? __(':available :unit in warehouse (same species), compliant.', ['available' => number_format($compliantAvailable, 2), 'unit' => $unit])
            : __('No compliant stock in warehouse for this species yet.');

        if ($totalInWarehouse > 0 && $compliantAvailable < $totalInWarehouse) {
            $message .= ' ' . __(':non_compliant :unit in warehouse with expired/revoked certificate.', ['non_compliant' => number_format($totalInWarehouse - $compliantAvailable, 2), 'unit' => $unit]);
        } elseif ($totalInWarehouse > 0 && $compliantAvailable === 0) {
            $message = __('No compliant stock (certificates expired/revoked). :total :unit in warehouse.', ['total' => number_format($totalInWarehouse, 2), 'unit' => $unit]);
        } elseif ($totalInWarehouse === 0) {
            $message = __('No stock in warehouse for this species yet.');
        }

        if ($canFulfill && $required > 0) {
            $message = __('Can fulfill. ') . $message;
        } elseif ($required > 0) {
            $message = __('Short by :short :unit. ', ['short' => number_format($shortBy, 2), 'unit' => $unit]) . $message;
        }

        return [
            'available_quantity' => $available,
            'compliant_quantity' => $compliantAvailable,
            'total_warehouse_quantity' => $totalInWarehouse,
            'required_quantity' => $required,
            'unit' => $unit,
            'can_fulfill' => $canFulfill,
            'compliance_ok' => $complianceOk,
            'short_by' => $shortBy,
            'message' => $message,
        ];
    }

    /** Whether current warehouse stock (same species, same business) can cover this demand. */
    public function getCanFulfillFromWarehouseAttribute(): bool
    {
        return $this->getFulfillmentInfo()['can_fulfill'];
    }
}
