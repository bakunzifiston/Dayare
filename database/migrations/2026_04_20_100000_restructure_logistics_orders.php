<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_orders', function (Blueprint $table) {
            $table->string('order_number', 40)->nullable()->after('id');
            $table->string('service_type', 20)->nullable()->after('company_id');
            $table->string('transport_mode', 20)->nullable()->after('service_type');
            $table->decimal('total_weight', 14, 3)->nullable()->after('delivery_location');
            $table->decimal('total_volume', 14, 3)->nullable()->after('total_weight');
            $table->text('special_instructions')->nullable()->after('total_volume');
        });

        foreach (DB::table('logistics_orders')->orderBy('id')->get() as $row) {
            $weight = $row->weight !== null ? (float) $row->weight : (float) $row->quantity;
            $status = ($row->status === 'rejected') ? 'cancelled' : 'confirmed';

            DB::table('logistics_orders')->where('id', $row->id)->update([
                'order_number' => 'LP-'.str_pad((string) $row->id, 8, '0', STR_PAD_LEFT),
                'service_type' => 'local',
                'transport_mode' => 'road',
                'total_weight' => $weight,
                'total_volume' => 0,
                'status' => $status,
            ]);
        }

        Schema::table('logistics_orders', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        Schema::table('logistics_orders', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'requested_date']);
            $table->dropColumn([
                'client_id',
                'species',
                'quantity',
                'weight',
                'requested_date',
                'priority',
            ]);
        });

        Schema::table('logistics_orders', function (Blueprint $table) {
            $table->unique('order_number');
        });

        Schema::table('logistics_orders', function (Blueprint $table) {
            $table->string('order_number', 40)->nullable(false)->change();
            $table->string('service_type', 20)->nullable(false)->default('local')->change();
            $table->string('transport_mode', 20)->nullable(false)->default('road')->change();
            $table->decimal('total_weight', 14, 3)->nullable(false)->default(0)->change();
            $table->decimal('total_volume', 14, 3)->nullable(false)->default(0)->change();
            $table->string('status', 30)->nullable(false)->default('confirmed')->change();
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration cannot be reversed safely.');
    }
};
