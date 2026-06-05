<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livestock', function (Blueprint $table) {
            $table->unsignedInteger('health_healthy_count')->default(0)->after('health_status');
            $table->unsignedInteger('health_sick_count')->default(0)->after('health_healthy_count');
        });

        if (Schema::hasColumn('livestock', 'total_quantity')) {
            DB::table('livestock')->orderBy('id')->lazyById()->each(function (object $row): void {
                $total = (int) ($row->total_quantity ?? 0);
                DB::table('livestock')->where('id', $row->id)->update([
                    'health_healthy_count' => $total,
                    'health_sick_count' => 0,
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('livestock', function (Blueprint $table) {
            $table->dropColumn(['health_healthy_count', 'health_sick_count']);
        });
    }
};
