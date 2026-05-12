<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDocument extends Model
{
    public const TYPE_INVOICE = 'sales_invoice';

    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_AGREEMENT = 'animal_sale_agreement';

    public const TYPE_BUYER_CONFIRMATION = 'buyer_confirmation';

    public const TYPE_SUMMARY = 'sales_summary';

    public const TYPE_HEALTH_SUMMARY = 'health_summary_attachment';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_INVOICE,
        self::TYPE_RECEIPT,
        self::TYPE_AGREEMENT,
        self::TYPE_BUYER_CONFIRMATION,
        self::TYPE_SUMMARY,
        self::TYPE_HEALTH_SUMMARY,
    ];

    protected $fillable = [
        'sale_id',
        'document_type',
        'document_number',
        'generated_by',
        'document_path',
        'generated_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
