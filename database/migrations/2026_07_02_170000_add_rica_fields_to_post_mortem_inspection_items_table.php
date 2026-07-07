<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_mortem_inspection_items', function (Blueprint $table) {
            $table->text('seized_part')->nullable()->after('outcome_notes');
            $table->text('reason')->nullable()->after('seized_part');
        });
    }

    public function down(): void
    {
        Schema::table('post_mortem_inspection_items', function (Blueprint $table) {
            $table->dropColumn(['seized_part', 'reason']);
        });
    }
};
