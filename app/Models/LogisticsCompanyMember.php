<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsCompanyMember extends Model
{
    protected $table = 'logistics_company_members';

    protected $fillable = [
        'logistics_company_id',
        'first_name',
        'last_name',
        'phone',
        'email',
    ];

    public function logisticsCompany(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class, 'logistics_company_id');
    }
}
