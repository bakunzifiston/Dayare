<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedSmallInteger('year_business_started')->nullable()->after('baseline_revenue');
            $table->string('access_road_condition', 20)->nullable()->after('year_business_started');
            $table->string('network_connectivity', 30)->nullable()->after('access_road_condition');
            $table->unsignedInteger('full_time_employees')->default(0)->after('network_connectivity');
            $table->unsignedInteger('workers_with_disabilities')->default(0)->after('full_time_employees');
            $table->unsignedInteger('refugee_workers')->default(0)->after('workers_with_disabilities');
            $table->unsignedInteger('seasonal_workers')->default(0)->after('refugee_workers');
            $table->string('bank_account_type', 30)->nullable()->after('seasonal_workers');
            $table->string('uses_mobile_money', 20)->nullable()->after('bank_account_type');
            $table->boolean('willing_to_receive_digital_payments')->default(false)->after('uses_mobile_money');
            $table->boolean('digital_record_keeping')->default(false)->after('willing_to_receive_digital_payments');
            $table->string('record_keeping_system', 255)->nullable()->after('digital_record_keeping');
            $table->json('available_devices')->nullable()->after('record_keeping_system');
            $table->boolean('willing_to_use_daily_digital_ledger')->default(false)->after('available_devices');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'year_business_started',
                'access_road_condition',
                'network_connectivity',
                'full_time_employees',
                'workers_with_disabilities',
                'refugee_workers',
                'seasonal_workers',
                'bank_account_type',
                'uses_mobile_money',
                'willing_to_receive_digital_payments',
                'digital_record_keeping',
                'record_keeping_system',
                'available_devices',
                'willing_to_use_daily_digital_ledger',
            ]);
        });
    }
};
