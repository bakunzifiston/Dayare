<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livestock_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('livestock_id')->nullable()->constrained('livestock')->nullOnDelete();
            $table->foreignId('movement_permit_id')->nullable()->constrained('movement_permits')->nullOnDelete();
            $table->string('event_type', 50);
            $table->unsignedInteger('quantity')->default(0);
            $table->date('event_date');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'event_type', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestock_events');
    }
};

