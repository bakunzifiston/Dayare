<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class FinancePayable extends Model
{
    use HasFactory;

    public const BUCKET_SUPPLIER = 'supplier';

    public const BUCKET_CLIENT = 'client';

    public const BUCKET_EMPLOYEE = 'employee';

    public const BUCKET_CASUAL_WORKER = 'casual_worker';

    /** @var list<string> */
    public const AP_BUCKETS = [
        self::BUCKET_SUPPLIER,
        self::BUCKET_CLIENT,
        self::BUCKET_EMPLOYEE,
        self::BUCKET_CASUAL_WORKER,
    ];

    protected $fillable = [
        'business_id',
        'ap_bucket',
        'supplier_id',
        'client_id',
        'employee_id',
        'casual_worker_id',
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

    public static function usesApBucketColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn((new static)->getTable(), 'ap_bucket');
        }

        return $hasColumn;
    }

    public function scopeForPayablesTab(Builder $query, string $tab): Builder
    {
        if (! static::usesApBucketColumn()) {
            return match ($tab) {
                'employees' => Schema::hasColumn($this->getTable(), 'employee_id')
                    ? $query->whereNotNull('employee_id')
                    : $query->whereRaw('0 = 1'),
                'casual' => Schema::hasColumn($this->getTable(), 'casual_worker_id')
                    ? $query->whereNotNull('casual_worker_id')
                    : $query->whereRaw('0 = 1'),
                default => $query->where(function (Builder $inner): void {
                    $inner->whereNotNull('supplier_id');
                    if (Schema::hasColumn($this->getTable(), 'client_id')) {
                        $inner->orWhereNotNull('client_id');
                    }
                }),
            };
        }

        return match ($tab) {
            'employees' => $query->where('ap_bucket', self::BUCKET_EMPLOYEE),
            'casual' => $query->where('ap_bucket', self::BUCKET_CASUAL_WORKER),
            default => $query->whereIn('ap_bucket', [self::BUCKET_SUPPLIER, self::BUCKET_CLIENT]),
        };
    }

    protected function apBucket(): Attribute
    {
        return Attribute::get(function (?string $value): string {
            if ($value !== null && $value !== '') {
                return $value;
            }

            if (! empty($this->attributes['employee_id'])) {
                return self::BUCKET_EMPLOYEE;
            }

            if (! empty($this->attributes['casual_worker_id'])) {
                return self::BUCKET_CASUAL_WORKER;
            }

            if (! empty($this->attributes['client_id']) && empty($this->attributes['supplier_id'])) {
                return self::BUCKET_CLIENT;
            }

            return self::BUCKET_SUPPLIER;
        });
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function casualWorker(): BelongsTo
    {
        return $this->belongsTo(CasualWorker::class);
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

    /** Tab query key for AP index (`suppliers` | `employees` | `casual`). */
    public function payablesTabKey(): string
    {
        return match ($this->ap_bucket) {
            self::BUCKET_EMPLOYEE => 'employees',
            self::BUCKET_CASUAL_WORKER => 'casual',
            default => 'suppliers',
        };
    }

    public function counterpartyLabel(): string
    {
        return match ($this->ap_bucket) {
            self::BUCKET_EMPLOYEE => $this->employee !== null
                ? trim(($this->employee->first_name ?? '').' '.($this->employee->last_name ?? '')) ?: ('#'.$this->employee_id)
                : '—',
            self::BUCKET_CASUAL_WORKER => $this->casualWorker !== null
                ? $this->casualWorker->displayName()
                : '—',
            self::BUCKET_CLIENT => $this->client?->name ?? '—',
            default => trim((string) (optional($this->supplier)->first_name ?? '').' '.(string) (optional($this->supplier)->last_name ?? ''))
                ?: ($this->supplier_id ? '#'.$this->supplier_id : '—'),
        };
    }
}
