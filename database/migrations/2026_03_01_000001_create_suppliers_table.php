<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('type', 50)->default('farm'); // farm, company, cooperative, individual

            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();

            $table->foreignId('country_id')->nullable()
                ->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('province_id')->nullable()
                ->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()
                ->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()
                ->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('cell_id')->nullable()
                ->constrained('administrative_divisions')->nullOnDelete();
            $table->foreignId('village_id')->nullable()
                ->constrained('administrative_divisions')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};

