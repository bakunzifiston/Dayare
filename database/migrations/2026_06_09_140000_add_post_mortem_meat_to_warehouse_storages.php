<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_storages', function (Blueprint $table) {
            $table->foreignId('animal_intake_item_id')
                ->nullable()
                ->after('certificate_id')
                ->constrained('animal_intake_items')
                ->nullOnDelete();
            $table->foreignId('post_mortem_inspection_item_id')
                ->nullable()
                ->after('animal_intake_item_id')
                ->constrained('post_mortem_inspection_items')
                ->nullOnDelete();
        });

        Schema::table('warehouse_storages', function (Blueprint $table) {
            $table->dropForeign(['certificate_id']);
        });

        Schema::table('warehouse_storages', function (Blueprint $table) {
            $table->unsignedBigInteger('certificate_id')->nullable()->change();
            $table->foreign('certificate_id')
                ->references('id')
                ->on('certificates')
                ->nullOnDelete();
            $table->decimal('quantity_stored', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_storages', function (Blueprint $table) {
            $table->dropForeign(['post_mortem_inspection_item_id']);
            $table->dropForeign(['animal_intake_item_id']);
            $table->dropColumn(['post_mortem_inspection_item_id', 'animal_intake_item_id']);
        });

        Schema::table('warehouse_storages', function (Blueprint $table) {
            $table->dropForeign(['certificate_id']);
        });

        Schema::table('warehouse_storages', function (Blueprint $table) {
            $table->unsignedBigInteger('certificate_id')->nullable(false)->change();
            $table->foreign('certificate_id')
                ->references('id')
                ->on('certificates')
                ->cascadeOnDelete();
            $table->unsignedInteger('quantity_stored')->default(0)->change();
        });
    }
};
