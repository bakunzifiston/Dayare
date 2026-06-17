<?php

namespace App\Models;

use App\Models\Concerns\DefinesButcherMeatTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherDeliveryRejection extends Model
{
    use DefinesButcherMeatTypes;

    protected $fillable = [
        'business_id',
        'delivery_id',
        'supplier_id',
        'meat_type',
        'rejected_weight_kg',
        'certificate_ref',
        'certificate_issuer',
        'notes',
        'rejected_by',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'rejected_weight_kg' => 'decimal:3',
            'rejected_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(ButcherDelivery::class, 'delivery_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ButcherSupplier::class, 'supplier_id');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
