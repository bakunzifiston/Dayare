<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('businesses', 'supporting_document_files')) {
            return;
        }

        Schema::table('businesses', function (Blueprint $table) {
            $column = $table->json('supporting_document_files')->nullable();

            if (Schema::hasColumn('businesses', 'supporting_documents_other')) {
                $column->after('supporting_documents_other');
            } elseif (Schema::hasColumn('businesses', 'supporting_documents')) {
                $column->after('supporting_documents');
            } elseif (Schema::hasColumn('businesses', 'digital_ledger_willingness')) {
                $column->after('digital_ledger_willingness');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('businesses', 'supporting_document_files')) {
            return;
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('supporting_document_files');
        });
    }
};
