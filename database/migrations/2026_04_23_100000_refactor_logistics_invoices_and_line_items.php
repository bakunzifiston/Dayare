<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('logistics_invoices')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 14, 4)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();

            $table->index('invoice_id');
        });

        Schema::table('logistics_invoices', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('trip_id')->constrained('logistics_orders')->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->after('order_id')->constrained('clients')->nullOnDelete();
            $table->string('currency', 8)->default('RWF')->after('client_id');
            $table->string('invoice_number', 40)->nullable()->unique()->after('currency');
            $table->decimal('subtotal', 14, 2)->default(0)->after('invoice_number');
            $table->decimal('tax_amount', 14, 2)->default(0)->after('subtotal');
            $table->decimal('discount_amount', 14, 2)->default(0)->after('tax_amount');
            $table->dateTime('issued_at')->nullable()->after('discount_amount');
            $table->dateTime('due_date')->nullable()->after('issued_at');
        });

        foreach (DB::table('logistics_invoices')->orderBy('id')->get() as $inv) {
            $trip = DB::table('logistics_trips')->find($inv->trip_id);
            $orderId = $trip !== null ? $trip->order_id : null;

            if ($orderId === null) {
                DB::table('logistics_invoices')->where('id', $inv->id)->delete();

                continue;
            }

            DB::table('logistics_invoices')->where('id', $inv->id)->update([
                'order_id' => $orderId,
                'currency' => 'RWF',
                'invoice_number' => 'INV-'.str_pad((string) $inv->id, 8, '0', STR_PAD_LEFT),
                'subtotal' => (float) $inv->total_amount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'issued_at' => $inv->created_at ?? now(),
                'due_date' => null,
            ]);

            $base = (float) $inv->base_cost;
            $dist = (float) $inv->cost_per_km * (float) $inv->distance_km;
            $qty = 0.0;
            if ($trip !== null) {
                $qty = max((int) ($trip->delivered_weight_kg ?? 0), (int) ($trip->allocated_weight_kg ?? 0));
            }
            $var = (float) $inv->cost_per_unit * $qty;
            $extra = (float) $inv->extra_charges;

            if ($base > 0) {
                DB::table('logistics_invoice_items')->insert([
                    'invoice_id' => $inv->id,
                    'description' => __('Base fee (migrated)'),
                    'quantity' => 1,
                    'unit_price' => round($base, 2),
                    'total' => round($base, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if ($dist > 0) {
                DB::table('logistics_invoice_items')->insert([
                    'invoice_id' => $inv->id,
                    'description' => __('Distance charge (migrated)'),
                    'quantity' => round((float) $inv->distance_km, 4),
                    'unit_price' => round((float) $inv->cost_per_km, 2),
                    'total' => round($dist, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if ($var > 0 || (float) $inv->cost_per_unit > 0) {
                DB::table('logistics_invoice_items')->insert([
                    'invoice_id' => $inv->id,
                    'description' => __('Weight / variable (migrated)'),
                    'quantity' => max(1, $qty),
                    'unit_price' => round((float) $inv->cost_per_unit, 2),
                    'total' => round($var, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if ($extra > 0) {
                DB::table('logistics_invoice_items')->insert([
                    'invoice_id' => $inv->id,
                    'description' => __('Extra charges (migrated)'),
                    'quantity' => 1,
                    'unit_price' => round($extra, 2),
                    'total' => round($extra, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (DB::table('logistics_invoice_items')->where('invoice_id', $inv->id)->doesntExist()) {
                DB::table('logistics_invoice_items')->insert([
                    'invoice_id' => $inv->id,
                    'description' => __('Invoice total (migrated)'),
                    'quantity' => 1,
                    'unit_price' => round((float) $inv->total_amount, 2),
                    'total' => round((float) $inv->total_amount, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $sumItems = (float) DB::table('logistics_invoice_items')->where('invoice_id', $inv->id)->sum('total');
            DB::table('logistics_invoices')->where('id', $inv->id)->update([
                'subtotal' => round($sumItems, 2),
                'total_amount' => round($sumItems, 2),
            ]);
        }

        Schema::table('logistics_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'base_cost',
                'cost_per_km',
                'distance_km',
                'cost_per_unit',
                'extra_charges',
            ]);
        });

        DB::table('logistics_invoices')->where('payment_status', 'paid')->update(['payment_status' => 'paid']);
        DB::table('logistics_invoices')->whereNotIn('payment_status', [
            'pending', 'partially_paid', 'paid', 'overdue', 'cancelled',
        ])->update(['payment_status' => 'pending']);

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE logistics_invoices MODIFY order_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE logistics_invoices MODIFY invoice_number VARCHAR(40) NOT NULL');
            DB::statement('ALTER TABLE logistics_invoices MODIFY issued_at DATETIME NOT NULL');
            DB::statement("ALTER TABLE logistics_invoices MODIFY payment_status VARCHAR(32) NOT NULL DEFAULT 'pending'");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE logistics_invoices ALTER COLUMN order_id SET NOT NULL');
            DB::statement('ALTER TABLE logistics_invoices ALTER COLUMN invoice_number SET NOT NULL');
            DB::statement('ALTER TABLE logistics_invoices ALTER COLUMN issued_at SET NOT NULL');
        }

        Schema::table('logistics_invoices', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('client_id');
            $table->index('invoice_number');
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration cannot be reversed safely.');
    }
};
