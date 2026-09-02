<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesComplianceCertificateRule extends Model
{
    protected $fillable = [
        'business_id',
        'site_type',
        'meat_source',
        'certificate_required',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'certificate_required' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public static function isCertificateRequired(?int $businessId, string $siteType, ?string $meatSource): bool
    {
        if (! $meatSource) {
            return true;
        }

        $businessRule = self::query()
            ->where('business_id', $businessId)
            ->where('site_type', $siteType)
            ->where('meat_source', $meatSource)
            ->first();

        if ($businessRule) {
            return (bool) $businessRule->certificate_required;
        }

        $global = self::query()
            ->whereNull('business_id')
            ->where('site_type', $siteType)
            ->where('meat_source', $meatSource)
            ->first();

        return $global ? (bool) $global->certificate_required : true;
    }
}
