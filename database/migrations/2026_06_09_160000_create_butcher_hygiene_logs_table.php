<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_hygiene_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('butcher_outlets')->cascadeOnDelete();
            $table->date('log_date');
            $table->json('checklist');
            $table->text('issues_found')->nullable();
            $table->text('corrective_action')->nullable();
            $table->foreignId('signed_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32);
            $table->timestamps();

            $table->unique(['outlet_id', 'log_date']);
            $table->index(['business_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_hygiene_logs');
    }
};
