<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BusinessUser extends Pivot
{
    protected $table = 'business_user';

    public $incrementing = true;

    protected $fillable = ['business_id', 'user_id', 'role'];

    public const ROLE_MANAGER = 'manager';
    public const ROLE_STAFF = 'staff';

    public const ROLES = [self::ROLE_MANAGER, self::ROLE_STAFF];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
