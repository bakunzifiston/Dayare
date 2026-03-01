<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('batch_id')->after('id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->after('inspector_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('expiry_date')->nullable()->after('issued_at');
            $table->string('status', 50)->default('active')->after('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropForeign(['facility_id']);
            $table->dropColumn(['batch_id', 'facility_id', 'expiry_date', 'status']);
        });
    }
};
