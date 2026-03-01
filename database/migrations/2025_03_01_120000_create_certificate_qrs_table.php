<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Certificate (1) → One QR. QR links to traceability data.
     */
    public function up(): void
    {
        Schema::create('certificate_qrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug', 64)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_qrs');
    }
};
