<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SalesComplianceAttachment extends Model
{
    protected $fillable = [
        'inspection_id',
        'original_name',
        'path',
        'mime',
        'size',
        'uploaded_by',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(SalesComplianceInspection::class, 'inspection_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return is_string($this->mime) && str_starts_with($this->mime, 'image/');
    }

    public function existsOnDisk(): bool
    {
        return $this->path !== '' && Storage::disk('local')->exists($this->path);
    }
}
