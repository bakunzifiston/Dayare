<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_companies', function (Blueprint $table) {
            $table->string('company_type', 40)->default('individual')->after('business_id');
            $table->foreignId('country_id')->nullable()->after('contact_person')->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->after('country_id')->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('province_id')->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->after('district_id')->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('cell_id')->nullable()->after('sector_id')->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->after('cell_id')->constrained('administrative_divisions')->nullOnDelete();
        });

        Schema::create('logistics_company_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_company_id')->constrained('logistics_companies')->cascadeOnDelete();
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->string('phone', 50);
            $table->string('email', 255);
            $table->timestamps();

            $table->unique(['logistics_company_id', 'phone']);
            $table->unique(['logistics_company_id', 'email']);
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_company_members');

        Schema::table('logistics_companies', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['province_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['sector_id']);
            $table->dropForeign(['cell_id']);
            $table->dropForeign(['village_id']);
            $table->dropColumn([
                'company_type',
                'country_id',
                'province_id',
                'district_id',
                'sector_id',
                'cell_id',
                'village_id',
            ]);
        });
    }
};
