<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('owner_first_name')->nullable()->after('status');
            $table->string('owner_last_name')->nullable()->after('owner_first_name');
            $table->date('owner_dob')->nullable()->after('owner_last_name');
        });

        Schema::create('business_ownership_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_ownership_members');
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['owner_first_name', 'owner_last_name', 'owner_dob']);
        });
    }
};
