<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'name')) {
                $table->dropColumn('name');
            }
            $table->string('first_name')->nullable()->after('business_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('nationality', 100)->nullable()->after('date_of_birth');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'date_of_birth', 'nationality']);
            $table->string('name')->after('business_id');
        });
    }
};
