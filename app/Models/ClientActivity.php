<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'client_id',
        'activity_type',
        'subject',
        'notes',
        'occurred_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public const TYPE_CALL = 'call';
    public const TYPE_EMAIL = 'email';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_NOTE = 'note';
    public const TYPE_INSPECTION_NOTICE = 'inspection_notice';

    public const TYPES = [
        self::TYPE_CALL => 'Call',
        self::TYPE_EMAIL => 'Email',
        self::TYPE_MEETING => 'Meeting',
        self::TYPE_NOTE => 'Note',
        self::TYPE_INSPECTION_NOTICE => 'Inspection Notice',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
