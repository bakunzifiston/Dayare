<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_tracking_logs', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('trip_id')->constrained('locations')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable()->after('location_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->dateTime('event_time')->nullable()->after('longitude');
        });

        $now = now();

        $resolveLocationId = function (string $label) use ($now): ?int {
            $name = mb_substr(trim($label), 0, 255);
            if ($name === '') {
                return null;
            }
            $existing = DB::table('locations')->where('name', $name)->value('id');
            if ($existing !== null) {
                return (int) $existing;
            }

            return (int) DB::table('locations')->insertGetId([
                'name' => $name,
                'address' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $mapStatus = function (string $status): string {
            return match (mb_strtolower($status)) {
                'loading' => 'loaded',
                'delivered', 'failed' => 'completed',
                default => $status,
            };
        };

        foreach (DB::table('logistics_tracking_logs')->orderBy('id')->get() as $row) {
            $locId = $resolveLocationId((string) $row->location);
            $newStatus = $mapStatus((string) $row->status);
            if (! in_array($newStatus, [
                'scheduled', 'loaded', 'in_transit', 'at_checkpoint', 'delayed', 'arrived', 'completed',
            ], true)) {
                $newStatus = 'in_transit';
            }

            DB::table('logistics_tracking_logs')->where('id', $row->id)->update([
                'location_id' => $locId,
                'event_time' => $row->timestamp,
                'status' => $newStatus,
            ]);
        }

        Schema::disableForeignKeyConstraints();

        Schema::table('logistics_tracking_logs', function (Blueprint $table) {
            $table->dropIndex(['trip_id', 'timestamp']);
        });

        Schema::table('logistics_tracking_logs', function (Blueprint $table) {
            $table->dropColumn(['timestamp', 'location']);
        });

        Schema::enableForeignKeyConstraints();

        DB::table('logistics_tracking_logs')->whereNull('event_time')->update([
            'event_time' => DB::raw('created_at'),
        ]);

        Schema::table('logistics_tracking_logs', function (Blueprint $table) {
            $table->dateTime('event_time')->nullable(false)->change();
        });

        Schema::table('logistics_tracking_logs', function (Blueprint $table) {
            $table->index(['trip_id', 'event_time']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration cannot be reversed safely.');
    }
};
