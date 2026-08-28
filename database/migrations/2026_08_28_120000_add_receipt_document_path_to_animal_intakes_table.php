<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            if (! Schema::hasColumn('animal_intakes', 'receipt_document_path')) {
                $table->string('receipt_document_path', 500)->nullable()->after('movement_permit_document_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('animal_intakes', function (Blueprint $table) {
            if (Schema::hasColumn('animal_intakes', 'receipt_document_path')) {
                $table->dropColumn('receipt_document_path');
            }
        });
    }
};
