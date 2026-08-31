<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceEbmRecord extends Model
{
    public const STATUS_ISSUED = 'issued';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_MISMATCH = 'mismatch';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ISSUED,
        self::STATUS_PENDING,
        self::STATUS_CANCELLED,
        self::STATUS_MISMATCH,
    ];

    public const RECON_MATCHED = 'matched';

    public const RECON_MISSING_EBM = 'missing_ebm';

    public const RECON_ORPHAN_EBM = 'orphan_ebm';

    public const RECON_AMOUNT_MISMATCH = 'amount_mismatch';

    public const RECON_REFERENCE_MISMATCH = 'reference_mismatch';

    protected $fillable = [
        'business_id',
        'finance_invoice_id',
        'facility_id',
        'ebm_invoice_number',
        'ebm_receipt_number',
        'issued_at',
        'amount',
        'status',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FinanceInvoice::class, 'finance_invoice_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reconciliationState(): string
    {
        if ($this->finance_invoice_id === null) {
            return self::RECON_ORPHAN_EBM;
        }

        $invoice = $this->invoice;
        if ($invoice === null) {
            return self::RECON_ORPHAN_EBM;
        }

        if ($this->amount !== null && abs((float) $this->amount - (float) $invoice->total_amount) >= 1) {
            return self::RECON_AMOUNT_MISMATCH;
        }

        // EBM tax numbers are independent of internal AR numbers. Flag a reference
        // mismatch only when this EBM number is actually another invoice's number.
        if ($this->ebm_invoice_number !== ''
            && $invoice->invoice_number !== $this->ebm_invoice_number
            && FinanceInvoice::query()
                ->where('business_id', $this->business_id)
                ->where('invoice_number', $this->ebm_invoice_number)
                ->where('id', '!=', $invoice->id)
                ->exists()) {
            return self::RECON_REFERENCE_MISMATCH;
        }

        return self::RECON_MATCHED;
    }
}
