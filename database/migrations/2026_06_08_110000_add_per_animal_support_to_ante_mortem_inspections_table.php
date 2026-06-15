<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ante_mortem_inspections', function (Blueprint $table) {
            $table->string('examined_count_source', 30)->default('manual')->after('notes');
            $table->text('notes_for_under_observation')->nullable()->after('examined_count_source');
        });
    }

    public function down(): void
    {
        Schema::table('ante_mortem_inspections', function (Blueprint $table) {
            $table->dropColumn(['examined_count_source', 'notes_for_under_observation']);
        });
    }
};
