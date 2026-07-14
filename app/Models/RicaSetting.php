<?php

namespace App\Models;

use App\Support\TenantEnvironmentScope;
use Illuminate\Database\Eloquent\Model;

class RicaSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'workspace_name' => 'RICA oversight',
            'default_tenant_environment' => TenantEnvironmentScope::FILTER_ALL,
            'default_dashboard_period' => 'all',
            'notification_email' => '',
            'monthly_report_deadline_day' => '5',
            'condemnation_loss_per_kg_rwf' => '3880',
        ];
    }

    public static function condemnationLossPerKgRwf(): float
    {
        $value = static::get('condemnation_loss_per_kg_rwf');

        return is_numeric($value) ? (float) $value : 3880.0;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where('key', $key)->value('value');

        if ($value !== null) {
            return $value;
        }

        return $default ?? (static::defaults()[$key] ?? null);
    }

    /**
     * @return array<string, string>
     */
    public static function allMerged(): array
    {
        $stored = static::query()->pluck('value', 'key')->all();

        return array_merge(static::defaults(), $stored);
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
