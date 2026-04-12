<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MySQL: UNIQUE(farm_id, type) backs the farm_id FK; add the wider unique first, then drop the old one
     * so farm_id remains indexed for the FK (left-prefix of UNIQUE(farm_id, type, breed)).
     */
    public function up(): void
    {
        Schema::table('livestock', function (Blueprint $table) {
            $table->string('breed', 120)->default('')->after('type');
            $table->string('feeding_type', 32)->nullable()->after('breed');
            $table->decimal('base_price', 12, 2)->nullable()->after('available_quantity');
            $table->string('health_status', 32)->nullable()->after('base_price');
        });

        Schema::table('livestock', function (Blueprint $table) {
            $table->unique(['farm_id', 'type', 'breed']);
        });

        Schema::table('livestock', function (Blueprint $table) {
            $table->dropUnique(['farm_id', 'type']);
        });

        Schema::create('livestock_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livestock_id')->constrained('livestock')->cascadeOnDelete();
            $table->string('age_range', 80)->nullable();
            $table->string('weight_range', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestock_details');

        Schema::table('livestock', function (Blueprint $table) {
            $table->dropUnique(['farm_id', 'type', 'breed']);
        });

        Schema::table('livestock', function (Blueprint $table) {
            $table->unique(['farm_id', 'type']);
        });

        Schema::table('livestock', function (Blueprint $table) {
            $table->dropColumn(['breed', 'feeding_type', 'base_price', 'health_status']);
        });
    }
};
