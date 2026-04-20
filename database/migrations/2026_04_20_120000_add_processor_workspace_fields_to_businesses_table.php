<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Owner profile additions.
            $table->string('owner_gender', 20)->nullable()->after('owner_dob');
            $table->string('owner_pwd_status', 30)->nullable()->after('owner_gender');

            // Processor business profile.
            $table->string('business_size', 20)->nullable()->after('ownership_type');
            $table->unsignedBigInteger('baseline_revenue')->nullable()->after('business_size');

            // VIBE metadata.
            $table->string('vibe_unique_id', 100)->nullable()->unique()->after('baseline_revenue');
            $table->date('vibe_commencement_date')->nullable()->after('vibe_unique_id');
            $table->string('pathway_status', 30)->default('active')->after('vibe_commencement_date');
            $table->text('vibe_comments')->nullable()->after('pathway_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique('businesses_vibe_unique_id_unique');
            $table->dropColumn([
                'owner_gender',
                'owner_pwd_status',
                'business_size',
                'baseline_revenue',
                'vibe_unique_id',
                'vibe_commencement_date',
                'pathway_status',
                'vibe_comments',
            ]);
        });
    }
};
