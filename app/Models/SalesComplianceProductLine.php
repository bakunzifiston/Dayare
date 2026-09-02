<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesComplianceProductLine extends Model
{
    protected $fillable = [
        'inspection_id',
        'product_name',
        'quantity_description',
        'certificate_status',
        'sort_order',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(SalesComplianceInspection::class, 'inspection_id');
    }
}
