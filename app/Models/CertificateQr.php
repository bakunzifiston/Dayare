<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * QR Traceability – one per certificate.
 * Certificate (1) → One QR. QR links to traceability page (facility, inspector, slaughter date, batch code, certificate number).
 */
class CertificateQr extends Model
{
    protected $table = 'certificate_qrs';

    protected $fillable = [
        'certificate_id',
        'slug',
    ];

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function getTraceUrlAttribute(): string
    {
        return url('/trace/' . $this->slug);
    }

    public static function generateSlug(): string
    {
        do {
            $slug = strtolower(Str::random(24));
        } while (self::where('slug', $slug)->exists());

        return $slug;
    }
}
