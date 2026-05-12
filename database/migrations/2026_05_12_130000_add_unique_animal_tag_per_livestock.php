<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            $table->unique(['livestock_id', 'tag_number'], 'animals_livestock_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            $table->dropUnique('animals_livestock_tag_unique');
        });
    }
};
