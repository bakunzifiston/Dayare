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

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE logistics_orders MODIFY order_number VARCHAR(40) NOT NULL');
            DB::statement("ALTER TABLE logistics_orders MODIFY service_type VARCHAR(20) NOT NULL DEFAULT 'local'");
            DB::statement("ALTER TABLE logistics_orders MODIFY transport_mode VARCHAR(20) NOT NULL DEFAULT 'road'");
            DB::statement('ALTER TABLE logistics_orders MODIFY total_weight DECIMAL(14,3) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE logistics_orders MODIFY total_volume DECIMAL(14,3) NOT NULL DEFAULT 0');
            DB::statement("ALTER TABLE logistics_orders MODIFY status VARCHAR(30) NOT NULL DEFAULT 'confirmed'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE logistics_orders ALTER COLUMN order_number SET NOT NULL');
            DB::statement("ALTER TABLE logistics_orders ALTER COLUMN service_type SET DEFAULT 'local'");
            DB::statement('ALTER TABLE logistics_orders ALTER COLUMN service_type SET NOT NULL');
            DB::statement("ALTER TABLE logistics_orders ALTER COLUMN transport_mode SET DEFAULT 'road'");
            DB::statement('ALTER TABLE logistics_orders ALTER COLUMN transport_mode SET NOT NULL');
            DB::statement('ALTER TABLE logistics_orders ALTER COLUMN total_weight SET DEFAULT 0');
            DB::statement('ALTER TABLE logistics_orders ALTER COLUMN total_weight SET NOT NULL');
            DB::statement('ALTER TABLE logistics_orders ALTER COLUMN total_volume SET DEFAULT 0');
            DB::statement('ALTER TABLE logistics_orders ALTER COLUMN total_volume SET NOT NULL');
            DB::statement("ALTER TABLE logistics_orders ALTER COLUMN status SET DEFAULT 'confirmed'");
            DB::statement('ALTER TABLE logistics_orders ALTER COLUMN status SET NOT NULL');
        }
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration cannot be reversed safely.');
    }
};
