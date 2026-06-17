<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_staff_health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('medical_card_number');
            $table->date('issued_date');
            $table->date('expiry_date');
            $table->string('health_status', 32)->default('fit');
            $table->date('last_checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
            $table->index(['business_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_staff_health_records');
    }
};
