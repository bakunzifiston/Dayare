<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $columns = [
                // Cooperative demographics (step 2)
                ['total_members', 'unsignedInteger', 'baseline_revenue'],
                ['female_members', 'unsignedInteger', 'total_members'],
                ['members_18_35', 'unsignedInteger', 'female_members'],
                ['young_women_members', 'unsignedInteger', 'members_18_35'],
                // Slaughterhouse operations (step 3)
                ['animals_processed', 'json', 'young_women_members'],
                ['animals_processed_other', 'string', 'animals_processed', 255],
                ['daily_processing', 'json', 'animals_processed_other'],
                ['products_sold', 'json', 'daily_processing'],
                ['products_sold_other', 'string', 'products_sold', 255],
                ['customer_segments', 'json', 'products_sold_other'],
                ['customer_segments_other', 'string', 'customer_segments', 255],
                ['daily_sales_kg', 'json', 'customer_segments_other'],
                ['buyer_count', 'unsignedInteger', 'daily_sales_kg'],
                ['contract_type', 'string', 'buyer_count', 64],
                ['contracted_buyers', 'text', 'contract_type'],
                ['digital_marketplace', 'boolean', 'contracted_buyers'],
                ['digital_marketplace_name', 'string', 'digital_marketplace', 255],
                ['baseline_revenue_rwf', 'decimal', 'digital_marketplace_name', '14,2'],
                // Infrastructure
                ['has_receiving_area', 'boolean', 'baseline_revenue_rwf'],
                ['road_condition', 'string', 'has_receiving_area', 20],
                ['has_potable_water', 'boolean', 'road_condition'],
                ['waste_system', 'string', 'has_potable_water', 32],
                ['has_cold_storage', 'boolean', 'waste_system'],
                ['cold_storage_capacity_kg', 'unsignedInteger', 'has_cold_storage'],
                // Compliance
                ['sanitary_certificate', 'boolean', 'cold_storage_capacity_kg'],
                ['sanitary_certificate_expiry', 'date', 'sanitary_certificate'],
                ['waste_disposal_plan', 'boolean', 'sanitary_certificate_expiry'],
                ['has_sops', 'boolean', 'waste_disposal_plan'],
                ['workers_trained', 'boolean', 'has_sops'],
                // Workforce (step 4)
                ['total_employees', 'unsignedInteger', 'workers_trained'],
                ['female_employees', 'unsignedInteger', 'total_employees'],
                ['employees_18_35', 'unsignedInteger', 'female_employees'],
                ['female_employees_18_35', 'unsignedInteger', 'employees_18_35'],
                ['pwd_employees', 'unsignedInteger', 'female_employees_18_35'],
                ['refugee_employees', 'unsignedInteger', 'pwd_employees'],
                ['seasonal_workers', 'unsignedInteger', 'refugee_employees'],
                ['has_dedicated_manager', 'string', 'seasonal_workers', 32],
                ['manager_first_name', 'string', 'has_dedicated_manager', 255],
                ['manager_gender', 'string', 'manager_first_name', 20],
                ['manager_age', 'unsignedSmallInteger', 'manager_gender'],
                // Digital readiness (step 5)
                ['bank_account', 'string', 'manager_age', 32],
                ['uses_mobile_money', 'string', 'bank_account', 20],
                ['digital_payment_willingness', 'string', 'uses_mobile_money', 32],
                ['uses_digital_records', 'boolean', 'digital_payment_willingness'],
                ['digital_system_name', 'string', 'uses_digital_records', 255],
                ['digital_devices', 'json', 'digital_system_name'],
                ['network_connectivity', 'string', 'digital_devices', 32],
                ['digital_ledger_willingness', 'string', 'network_connectivity', 32],
                // Documentation (step 8)
                ['supporting_documents', 'json', 'digital_ledger_willingness'],
                ['supporting_documents_other', 'string', 'supporting_documents', 255],
            ];

            $after = 'country';
            foreach ($columns as $def) {
                $name = $def[0];
                if (Schema::hasColumn('businesses', $name)) {
                    continue;
                }
                $type = $def[1];
                if ($type === 'json') {
                    $table->json($name)->nullable()->after($after);
                } elseif ($type === 'string') {
                    $table->string($name, $def[3] ?? 255)->nullable()->after($after);
                } elseif ($type === 'text') {
                    $table->text($name)->nullable()->after($after);
                } elseif ($type === 'boolean') {
                    $table->boolean($name)->nullable()->after($after);
                } elseif ($type === 'date') {
                    $table->date($name)->nullable()->after($after);
                } elseif ($type === 'unsignedInteger') {
                    $table->unsignedInteger($name)->nullable()->after($after);
                } elseif ($type === 'unsignedSmallInteger') {
                    $table->unsignedSmallInteger($name)->nullable()->after($after);
                } elseif ($type === 'decimal') {
                    $table->decimal($name, 14, 2)->nullable()->after($after);
                }
                $after = $name;
            }
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'total_members', 'female_members', 'members_18_35', 'young_women_members',
                'animals_processed', 'animals_processed_other', 'daily_processing',
                'products_sold', 'products_sold_other', 'customer_segments', 'customer_segments_other',
                'daily_sales_kg', 'buyer_count', 'contract_type', 'contracted_buyers',
                'digital_marketplace', 'digital_marketplace_name', 'baseline_revenue_rwf',
                'has_receiving_area', 'road_condition', 'has_potable_water', 'waste_system',
                'has_cold_storage', 'cold_storage_capacity_kg',
                'sanitary_certificate', 'sanitary_certificate_expiry', 'waste_disposal_plan', 'has_sops', 'workers_trained',
                'total_employees', 'female_employees', 'employees_18_35', 'female_employees_18_35',
                'pwd_employees', 'refugee_employees', 'seasonal_workers',
                'has_dedicated_manager', 'manager_first_name', 'manager_gender', 'manager_age',
                'bank_account', 'uses_mobile_money', 'digital_payment_willingness',
                'uses_digital_records', 'digital_system_name', 'digital_devices',
                'network_connectivity', 'digital_ledger_willingness',
                'supporting_documents', 'supporting_documents_other',
            ]);
        });
    }
};
