<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestock_id')->constrained('livestock')->cascadeOnDelete();
            $table->string('animal_code', 40);
            $table->string('tag_number', 80)->nullable();
            $table->string('qr_code', 255)->nullable();
            $table->string('animal_name')->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->decimal('age', 8, 2)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('color_markings', 255)->nullable();
            $table->string('acquisition_type', 64)->nullable();
            $table->date('acquisition_date')->nullable();
            $table->string('source', 255)->nullable();
            $table->string('mother_tag', 80)->nullable();
            $table->string('father_tag', 80)->nullable();
            $table->string('health_status', 32)->default('healthy');
            $table->string('production_status', 32)->nullable();
            $table->string('lifecycle_status', 32)->default('active');
            $table->string('current_condition', 255)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['livestock_id', 'animal_code']);
            $table->index(['livestock_id', 'tag_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
