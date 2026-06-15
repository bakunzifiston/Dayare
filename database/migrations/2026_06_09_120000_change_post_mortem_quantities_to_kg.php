<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_mortem_inspections', function (Blueprint $table) {
            $table->decimal('total_examined', 10, 2)->default(0)->change();
            $table->decimal('approved_quantity', 10, 2)->default(0)->change();
            $table->decimal('condemned_quantity', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('post_mortem_inspections', function (Blueprint $table) {
            $table->unsignedInteger('total_examined')->default(0)->change();
            $table->unsignedInteger('approved_quantity')->default(0)->change();
            $table->unsignedInteger('condemned_quantity')->default(0)->change();
        });
    }
};
