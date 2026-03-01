<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Ownership info
            $table->string('owner_name')->nullable()->after('status');
            $table->string('owner_phone', 50)->nullable()->after('owner_name');
            $table->string('owner_email')->nullable()->after('owner_phone');
            $table->string('ownership_type', 100)->nullable()->after('owner_email'); // e.g. sole_proprietor, partnership, company

            // Location info
            $table->string('address_line_1')->nullable()->after('ownership_type');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('state_region', 100)->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('state_region');
            $table->string('country', 100)->nullable()->after('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'owner_name', 'owner_phone', 'owner_email', 'ownership_type',
                'address_line_1', 'address_line_2', 'city', 'state_region', 'postal_code', 'country',
            ]);
        });
    }
};
