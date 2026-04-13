<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_normalized')->nullable()->after('email');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->string('business_name_normalized')->nullable()->after('business_name');
        });

        DB::table('users')
            ->select(['id', 'email'])
            ->orderBy('id')
            ->chunkById(500, function ($users): void {
                foreach ($users as $user) {
                    $normalizedEmail = Str::lower(trim((string) $user->email));
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['email_normalized' => $normalizedEmail]);
                }
            });

        DB::table('businesses')
            ->select(['id', 'business_name'])
            ->orderBy('id')
            ->chunkById(500, function ($businesses): void {
                foreach ($businesses as $business) {
                    $normalizedBusinessName = Str::lower(
                        preg_replace('/\s+/', ' ', trim((string) $business->business_name)) ?? ''
                    );

                    DB::table('businesses')
                        ->where('id', $business->id)
                        ->update(['business_name_normalized' => $normalizedBusinessName]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email_normalized', 'users_email_normalized_unique');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->unique('business_name', 'businesses_business_name_unique');
            $table->unique('business_name_normalized', 'businesses_business_name_normalized_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique('businesses_business_name_normalized_unique');
            $table->dropUnique('businesses_business_name_unique');
            $table->dropColumn('business_name_normalized');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_normalized_unique');
            $table->dropColumn('email_normalized');
        });
    }
};
