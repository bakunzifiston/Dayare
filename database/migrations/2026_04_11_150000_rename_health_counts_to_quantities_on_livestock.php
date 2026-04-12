<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('livestock', 'healthy_quantity')) {
            return;
        }

        Schema::table('livestock', function (Blueprint $table) {
            $table->unsignedInteger('healthy_quantity')->default(0)->after('health_status');
            $table->unsignedInteger('sick_quantity')->default(0)->after('healthy_quantity');
        });

        if (Schema::hasColumn('livestock', 'health_healthy_count')) {
            DB::statement('UPDATE livestock SET healthy_quantity = health_healthy_count, sick_quantity = health_sick_count');

            Schema::table('livestock', function (Blueprint $table) {
                $table->dropColumn(['health_healthy_count', 'health_sick_count']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('livestock', 'healthy_quantity')) {
            return;
        }

        Schema::table('livestock', function (Blueprint $table) {
            $table->unsignedInteger('health_healthy_count')->default(0)->after('health_status');
            $table->unsignedInteger('health_sick_count')->default(0)->after('health_healthy_count');
        });

        DB::statement('UPDATE livestock SET health_healthy_count = healthy_quantity, health_sick_count = sick_quantity');

        Schema::table('livestock', function (Blueprint $table) {
            $table->dropColumn(['healthy_quantity', 'sick_quantity']);
        });
    }
};
