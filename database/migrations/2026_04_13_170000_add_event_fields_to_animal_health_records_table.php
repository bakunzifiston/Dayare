<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animal_health_records', function (Blueprint $table) {
            $table->string('event_type', 32)->default('disease_diagnosis')->after('record_date');
            $table->date('next_due_date')->nullable()->after('condition');
            $table->string('batch_reference', 100)->nullable()->after('livestock_id');
            $table->text('treatment_given')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('animal_health_records', function (Blueprint $table) {
            $table->dropColumn([
                'event_type',
                'next_due_date',
                'batch_reference',
                'treatment_given',
            ]);
        });
    }
};

