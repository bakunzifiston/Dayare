<?php

namespace App\Models;

use App\Models\Concerns\HasFinancePayments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class FinanceExpense extends Model
{
    use HasFinancePayments;

    public const CATEGORY_SUPPLIES = 'supplies';

    public const CATEGORY_UTILITIES = 'utilities';

    public const CATEGORY_TRANSPORT = 'transport';

    public const CATEGORY_OPERATIONAL = 'operational';

    public const CATEGORY_OTHER = 'other';

    /** @var list<string> */
    public const CATEGORIES = [
        self::CATEGORY_SUPPLIES,
        self::CATEGORY_UTILITIES,
        self::CATEGORY_TRANSPORT,
        self::CATEGORY_OPERATIONAL,
        self::CATEGORY_OTHER,
    ];

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_UNPAID,
        self::STATUS_PENDING,
        self::STATUS_PAID,
    ];

    protected $fillable = [
        'business_id',
        'facility_id',
        'supplier_id',
        'payable_id',
        'expense_number',
        'category',
        'description',
        'amount',
        'currency',
        'expense_date',
        'reference_number',
        'attachment_path',
        'status',
        'amount_paid',
        'paid_at',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'expense_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            self::CATEGORY_SUPPLIES => __('Supplies'),
            self::CATEGORY_UTILITIES => __('Utilities'),
            self::CATEGORY_TRANSPORT => __('Transport'),
            self::CATEGORY_OPERATIONAL => __('Operational costs'),
            default => __('Other'),
        };
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(FinancePayable::class, 'payable_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function costAllocations(): HasMany
    {
        return $this->hasMany(FinanceCostAllocation::class, 'source_id')
            ->where('source_type', self::class);
    }

    public function hasAttachment(): bool
    {
        return is_string($this->attachment_path) && $this->attachment_path !== '';
    }

    public function attachmentDiskPath(): ?string
    {
        return $this->hasAttachment() ? $this->attachment_path : null;
    }

    public function attachmentExists(): bool
    {
        $path = $this->attachmentDiskPath();

        return $path !== null && Storage::disk('local')->exists($path);
    }
}
