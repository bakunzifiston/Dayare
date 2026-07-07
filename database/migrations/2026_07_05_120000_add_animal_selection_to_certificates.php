<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropUnique(['batch_id']);
            $table->json('animal_intake_item_ids')->nullable()->after('batch_id');
            $table->foreign('batch_id')->references('id')->on('batches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('animal_intake_item_ids');
            $table->unique('batch_id');
            $table->foreign('batch_id')->references('id')->on('batches')->cascadeOnDelete();
        });
    }
};
