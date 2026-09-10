<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_delivery_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('butcher_deliveries')->cascadeOnDelete();
            $table->string('meat_type', 32);
            $table->decimal('expected_weight_kg', 12, 3)->nullable();
            $table->decimal('received_weight_kg', 12, 3);
            $table->decimal('temperature_c', 5, 2)->nullable();
            $table->text('condition_notes')->nullable();
            $table->string('outcome', 32);
            $table->decimal('accepted_weight_kg', 12, 3)->default(0);
            $table->decimal('rejected_weight_kg', 12, 3)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['delivery_id', 'outcome']);
        });

        // Allow multiple batches / rejections per delivery (one per line outcome).
        $this->relaxDeliveryUnique('butcher_inventory_batches');
        $this->relaxDeliveryUnique('butcher_delivery_rejections');

        $this->backfillDeliveryLines();
    }

    public function down(): void
    {
        foreach (['butcher_inventory_batches', 'butcher_delivery_rejections'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'delivery_line_id')) {
                    $table->dropConstrainedForeignId('delivery_line_id');
                }
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->unique('delivery_id');
            });
        }

        Schema::dropIfExists('butcher_delivery_lines');
    }

    private function relaxDeliveryUnique(string $tableName): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: dropUnique via table rebuild; skip named FK dance.
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropUnique(['delivery_id']);
            });
        } else {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['delivery_id']);
            });
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropUnique(['delivery_id']);
            });
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('delivery_id')->references('id')->on('butcher_deliveries')->cascadeOnDelete();
            });
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreignId('delivery_line_id')
                ->nullable()
                ->constrained('butcher_delivery_lines')
                ->nullOnDelete();
        });
    }

    private function backfillDeliveryLines(): void
    {
        DB::table('butcher_deliveries')->orderBy('id')->chunkById(100, function ($deliveries): void {
            foreach ($deliveries as $delivery) {
                $condition = (string) $delivery->condition;
                $weight = (float) $delivery->received_weight_kg;

                if ($condition === 'rejected') {
                    $outcome = 'rejected';
                    $accepted = 0.0;
                    $rejected = $weight;
                } else {
                    $outcome = 'accepted';
                    $accepted = $weight;
                    $rejected = 0.0;
                }

                $lineId = DB::table('butcher_delivery_lines')->insertGetId([
                    'delivery_id' => $delivery->id,
                    'meat_type' => $delivery->meat_type,
                    'expected_weight_kg' => null,
                    'received_weight_kg' => $weight,
                    'temperature_c' => null,
                    'condition_notes' => null,
                    'outcome' => $outcome,
                    'accepted_weight_kg' => $accepted,
                    'rejected_weight_kg' => $rejected,
                    'unit_cost' => $delivery->unit_cost_per_kg,
                    'created_at' => $delivery->created_at,
                    'updated_at' => $delivery->updated_at,
                ]);

                DB::table('butcher_inventory_batches')
                    ->where('delivery_id', $delivery->id)
                    ->whereNull('delivery_line_id')
                    ->update(['delivery_line_id' => $lineId]);

                DB::table('butcher_delivery_rejections')
                    ->where('delivery_id', $delivery->id)
                    ->whereNull('delivery_line_id')
                    ->update(['delivery_line_id' => $lineId]);
            }
        });
    }
};
