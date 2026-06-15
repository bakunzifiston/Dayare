<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SuperAdminActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(User $actor, string $action, array $properties = []): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        $payload = [
            'description' => $action,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('activity_log', 'log_name')) {
            $payload['log_name'] = 'super_admin';
        }

        if (Schema::hasColumn('activity_log', 'event')) {
            $payload['event'] = $action;
        }

        if (Schema::hasColumn('activity_log', 'causer_id')) {
            $payload['causer_id'] = $actor->id;
        }

        if (Schema::hasColumn('activity_log', 'causer_type')) {
            $payload['causer_type'] = User::class;
        }

        if (Schema::hasColumn('activity_log', 'properties')) {
            $payload['properties'] = json_encode($properties);
        }

        DB::table('activity_log')->insert($payload);
    }
}
