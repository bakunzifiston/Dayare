<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ButcherSalePayment extends Model
{
    public const METHOD_CASH = 'cash';

    public const METHOD_MOMO = 'momo';

    public const METHOD_CARD = 'card';

    /** @var list<string> */
    public const SPLIT_METHODS = [
        self::METHOD_CASH,
        self::METHOD_MOMO,
        self::METHOD_CARD,
    ];

    protected $fillable = [
        'sale_id',
        'payment_method',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(ButcherSale::class, 'sale_id');
    }
}
