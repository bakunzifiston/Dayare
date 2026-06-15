<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Promote animal_intakes to session headers.
     * Legacy per-group columns are kept but made nullable for backward compatibility.
     */
    public function up(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->string('reference', 32)->nullable()->unique()->after('id');
            $table->boolean('is_draft')->default(false)->after('status');
            $table->timestamp('submitted_at')->nullable()->after('is_draft');
        });

        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->string('species', 50)->nullable()->change();
            $table->unsignedInteger('number_of_animals')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->dropColumn(['reference', 'is_draft', 'submitted_at']);
        });

        Schema::table('animal_intakes', function (Blueprint $table) {
            $table->string('species', 50)->nullable(false)->change();
            $table->unsignedInteger('number_of_animals')->nullable(false)->change();
        });
    }
};
