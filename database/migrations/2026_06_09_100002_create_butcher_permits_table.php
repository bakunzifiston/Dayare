<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('butcher_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('permit_type', 64);
            $table->string('permit_number', 120);
            $table->string('issued_by', 255);
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->string('document_path')->nullable();
            $table->string('status', 32)->default('valid');
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('butcher_permits');
    }
};
