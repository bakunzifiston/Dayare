<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rica_monthly_inspection_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->text('challenges')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('inspector_signatures')->nullable();
            $table->string('operator_name')->nullable();
            $table->timestamp('operator_signed_at')->nullable();
            $table->boolean('stamp_acknowledged')->default(false);
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['facility_id', 'period_year', 'period_month'], 'rica_monthly_report_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rica_monthly_inspection_reports');
    }
};
