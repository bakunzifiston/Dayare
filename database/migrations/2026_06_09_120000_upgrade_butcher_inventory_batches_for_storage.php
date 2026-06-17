<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'butcher_fresh_max_temp_c')) {
                $table->decimal('butcher_fresh_max_temp_c', 5, 2)->default(4)->after('gps_lng');
            }
            if (! Schema::hasColumn('businesses', 'butcher_frozen_max_temp_c')) {
                $table->decimal('butcher_frozen_max_temp_c', 5, 2)->default(-18)->after('butcher_fresh_max_temp_c');
            }
            if (! Schema::hasColumn('businesses', 'butcher_batch_shelf_life_days')) {
                $table->unsignedSmallInteger('butcher_batch_shelf_life_days')->default(3)->after('butcher_frozen_max_temp_c');
            }
        });

        if (Schema::hasColumn('butcher_inventory_batches', 'quantity_kg')) {
            Schema::table('butcher_inventory_batches', function (Blueprint $table) {
                $table->renameColumn('quantity_kg', 'initial_weight_kg');
            });
        }

        if (Schema::hasColumn('butcher_inventory_batches', 'remaining_quantity_kg')) {
            Schema::table('butcher_inventory_batches', function (Blueprint $table) {
                $table->renameColumn('remaining_quantity_kg', 'remaining_weight_kg');
            });
        }

        Schema::table('butcher_inventory_batches', function (Blueprint $table) {
            if (! Schema::hasColumn('butcher_inventory_batches', 'best_before_date')) {
                $table->date('best_before_date')->nullable()->after('received_at');
            }
            if (! Schema::hasColumn('butcher_inventory_batches', 'storage_location')) {
                $table->string('storage_location')->nullable()->after('status');
            }
        });

        DB::table('butcher_inventory_batches')
            ->where('status', 'available')
            ->update(['status' => 'in_storage']);

        DB::table('butcher_inventory_batches')
            ->where('status', 'depleted')
            ->update(['status' => 'fully_used']);

        Schema::table('butcher_inventory_batches', function (Blueprint $table) {
            foreach (['total_cost', 'certificate_ref', 'certificate_issuer', 'condition'] as $column) {
                if (Schema::hasColumn('butcher_inventory_batches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        foreach (DB::table('butcher_inventory_batches')->whereNull('best_before_date')->get(['id', 'received_at']) as $batch) {
            DB::table('butcher_inventory_batches')
                ->where('id', $batch->id)
                ->update([
                    'best_before_date' => \Carbon\Carbon::parse($batch->received_at)->addDays(3)->toDateString(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('butcher_inventory_batches', function (Blueprint $table) {
            if (Schema::hasColumn('butcher_inventory_batches', 'storage_location')) {
                $table->dropColumn('storage_location');
            }
            if (Schema::hasColumn('butcher_inventory_batches', 'best_before_date')) {
                $table->dropColumn('best_before_date');
            }
        });

        if (Schema::hasColumn('butcher_inventory_batches', 'initial_weight_kg')) {
            Schema::table('butcher_inventory_batches', function (Blueprint $table) {
                $table->renameColumn('initial_weight_kg', 'quantity_kg');
            });
        }

        if (Schema::hasColumn('butcher_inventory_batches', 'remaining_weight_kg')) {
            Schema::table('butcher_inventory_batches', function (Blueprint $table) {
                $table->renameColumn('remaining_weight_kg', 'remaining_quantity_kg');
            });
        }

        Schema::table('businesses', function (Blueprint $table) {
            foreach (['butcher_fresh_max_temp_c', 'butcher_frozen_max_temp_c', 'butcher_batch_shelf_life_days'] as $column) {
                if (Schema::hasColumn('businesses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
