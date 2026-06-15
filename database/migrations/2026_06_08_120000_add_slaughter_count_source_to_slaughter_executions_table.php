<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slaughter_executions', function (Blueprint $table) {
            $table->string('slaughter_count_source', 30)
                ->nullable()
                ->default('manual')
                ->after('actual_animals_slaughtered');
        });
    }

    public function down(): void
    {
        Schema::table('slaughter_executions', function (Blueprint $table) {
            $table->dropColumn('slaughter_count_source');
        });
    }
};
