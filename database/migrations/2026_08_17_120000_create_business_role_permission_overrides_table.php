<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_role_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32);
            $table->string('permission', 64);
            $table->boolean('is_allowed');
            $table->timestamps();

            $table->unique(
                ['business_id', 'role', 'permission'],
                'business_role_permission_unique'
            );
            $table->index(['business_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_role_permission_overrides');
    }
};
