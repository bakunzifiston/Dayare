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
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('date_of_birth');
            $table->enum('pwd_status', ['none', 'physical', 'visual', 'hearing', 'cognitive', 'other'])->default('none')->after('gender');
            $table->boolean('is_refugee')->default(false)->after('pwd_status');
            $table->boolean('is_host_community')->default(false)->after('is_refugee');
            $table->boolean('consent_given')->default(false)->after('is_host_community');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'pwd_status',
                'is_refugee',
                'is_host_community',
                'consent_given',
            ]);
        });
    }
};
