<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movement_permits', function (Blueprint $table) {
            if (! Schema::hasColumn('movement_permits', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('movement_reason');
            }
            if (! Schema::hasColumn('movement_permits', 'owner_national_id')) {
                $table->string('owner_national_id', 32)->nullable()->after('owner_name');
            }
            if (! Schema::hasColumn('movement_permits', 'owner_phone')) {
                $table->string('owner_phone', 50)->nullable()->after('owner_national_id');
            }
            if (! Schema::hasColumn('movement_permits', 'owner_address')) {
                $table->text('owner_address')->nullable()->after('owner_phone');
            }
            if (! Schema::hasColumn('movement_permits', 'imported_from_pdf')) {
                $table->boolean('imported_from_pdf')->default(false)->after('created_by');
            }
        });

        Schema::table('movement_permit_animals', function (Blueprint $table) {
            if (! Schema::hasColumn('movement_permit_animals', 'species')) {
                $table->string('species', 64)->nullable()->after('animal_identifier');
            }
            if (! Schema::hasColumn('movement_permit_animals', 'breed')) {
                $table->string('breed', 120)->nullable()->after('species');
            }
            if (! Schema::hasColumn('movement_permit_animals', 'sex')) {
                $table->string('sex', 16)->nullable()->after('breed');
            }
            if (! Schema::hasColumn('movement_permit_animals', 'age_description')) {
                $table->string('age_description', 64)->nullable()->after('sex');
            }
        });
    }

    public function down(): void
    {
        Schema::table('movement_permit_animals', function (Blueprint $table) {
            foreach (['species', 'breed', 'sex', 'age_description'] as $column) {
                if (Schema::hasColumn('movement_permit_animals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('movement_permits', function (Blueprint $table) {
            foreach (['owner_name', 'owner_national_id', 'owner_phone', 'owner_address', 'imported_from_pdf'] as $column) {
                if (Schema::hasColumn('movement_permits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
