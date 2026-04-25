<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::table('logistics_trips', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('company_id')->constrained('logistics_orders')->cascadeOnDelete();
            $table->foreignId('origin_location_id')->nullable()->after('order_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->nullable()->after('origin_location_id')->constrained('locations')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->unsignedInteger('allocated_weight_kg')->nullable();
            $table->unsignedInteger('delivered_weight_kg')->default(0);
            $table->unsignedInteger('loss_weight_kg')->default(0);
        });

        $now = now();

        $resolveLocationId = function (string $label) use ($now): int {
            $name = mb_substr(trim($label), 0, 255);
            if ($name === '') {
                $name = 'Unknown';
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

        $tripIdsWithPivot = DB::table('logistics_trip_orders')->distinct()->pluck('trip_id');
        $allTripIds = DB::table('logistics_trips')->pluck('id');
        $orphanTripIds = $allTripIds->diff($tripIdsWithPivot);

        foreach ($orphanTripIds as $tripId) {
            DB::table('logistics_tracking_logs')->where('trip_id', $tripId)->delete();
            DB::table('logistics_compliance_documents')->where('trip_id', $tripId)->delete();
            DB::table('logistics_invoices')->where('trip_id', $tripId)->delete();
            DB::table('logistics_trips')->where('id', $tripId)->delete();
        }

        foreach (DB::table('logistics_trips')->orderBy('id')->get() as $trip) {
            $pivotRows = DB::table('logistics_trip_orders')->where('trip_id', $trip->id)->orderBy('id')->get();
            if ($pivotRows->isEmpty()) {
                continue;
            }

            $primaryPivot = $pivotRows->first();
            $orderId = (int) $primaryPivot->order_id;
            $allocatedSum = (int) $pivotRows->sum('allocated_quantity');

            $order = DB::table('logistics_orders')->find($orderId);
            if ($order === null) {
                continue;
            }

            $originId = $resolveLocationId((string) $order->pickup_location);
            $destId = $resolveLocationId((string) $order->delivery_location);

            DB::table('logistics_trips')->where('id', $trip->id)->update([
                'order_id' => $orderId,
                'origin_location_id' => $originId,
                'destination_location_id' => $destId,
                'allocated_weight_kg' => max(0, $allocatedSum),
                'delivered_weight_kg' => (int) $pivotRows->sum('delivered_quantity'),
                'loss_weight_kg' => (int) $pivotRows->sum('loss_quantity'),
            ]);
        }

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('logistics_trip_orders');
        Schema::enableForeignKeyConstraints();

        DB::table('logistics_trips')->where('status', 'loading')->update(['status' => 'loaded']);
        DB::table('logistics_trips')->where('status', 'delivered')->update(['status' => 'completed']);
        DB::table('logistics_trips')->where('status', 'failed')->update(['status' => 'cancelled']);

        foreach (DB::table('logistics_trips')->whereNull('order_id')->pluck('id') as $tripId) {
            DB::table('logistics_tracking_logs')->where('trip_id', $tripId)->delete();
            DB::table('logistics_compliance_documents')->where('trip_id', $tripId)->delete();
            DB::table('logistics_invoices')->where('trip_id', $tripId)->delete();
            DB::table('logistics_trips')->where('id', $tripId)->delete();
        }

        Schema::table('logistics_trips', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
            $table->unsignedBigInteger('origin_location_id')->nullable(false)->change();
            $table->unsignedBigInteger('destination_location_id')->nullable(false)->change();
            $table->unsignedInteger('allocated_weight_kg')->nullable(false)->change();
            $table->string('status', 32)->nullable(false)->default('scheduled')->change();
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration cannot be reversed safely.');
    }
};
