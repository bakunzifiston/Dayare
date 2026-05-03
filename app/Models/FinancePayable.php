<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancePayable extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'supplier_id',
        'client_id',
        'contract_id',
        'animal_intake_id',
        'payable_number',
        'status',
        'currency',
        'subtotal',
        'tax_amount',
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

    public function animalIntake(): BelongsTo
    {
        return $this->belongsTo(AnimalIntake::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinancePayableLine::class, 'payable_id');
    }
}
