<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_mortem_inspections', function (Blueprint $table) {
            $table->foreignId('inspector_id')->after('batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_examined')->after('inspector_id')->default(0);
            $table->unsignedInteger('approved_quantity')->after('total_examined')->default(0);
            $table->unsignedInteger('condemned_quantity')->after('approved_quantity')->default(0);
            $table->text('notes')->nullable()->after('condemned_quantity');
            $table->date('inspection_date')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('post_mortem_inspections', function (Blueprint $table) {
            $table->dropForeign(['inspector_id']);
            $table->dropColumn([
                'inspector_id',
                'total_examined',
                'approved_quantity',
                'condemned_quantity',
                'notes',
                'inspection_date',
            ]);
        });
    }
};
