<?php

use App\Models\AnimalHealthRecord;
use App\Models\Livestock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livestock', function (Blueprint $table) {
            $table->unsignedInteger('health_healthy_count')->default(0)->after('health_status');
            $table->unsignedInteger('health_sick_count')->default(0)->after('health_healthy_count');
        });

        Livestock::query()->with('latestHealthRecord')->lazyById()->each(function (Livestock $row): void {
            $total = (int) $row->total_quantity;
            if ($row->latestHealthRecord?->condition === AnimalHealthRecord::CONDITION_SICK) {
                $row->update(['health_healthy_count' => 0, 'health_sick_count' => $total]);
            } else {
                $row->update(['health_healthy_count' => $total, 'health_sick_count' => 0]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('livestock', function (Blueprint $table) {
            $table->dropColumn(['health_healthy_count', 'health_sick_count']);
        });
    }
};
