<?php

use App\Models\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('businesses')) {
            return;
        }

        if (! Schema::hasColumn('businesses', 'baseline_revenue')) {
            return;
        }

        Schema::table('businesses', function (Blueprint $table) {
            if (! Schema::hasColumn('businesses', 'baseline_revenue_new')) {
                $table->string('baseline_revenue_new', 32)->nullable()->after('business_size');
            }
        });

        $rows = DB::table('businesses')->select('id', 'baseline_revenue')->get();
        foreach ($rows as $row) {
            $val = $row->baseline_revenue;
            if ($val === null) {
                continue;
            }
            if (in_array((string) $val, Business::BASELINE_REVENUE_BRACKETS, true)) {
                $bracket = (string) $val;
            } elseif (is_numeric($val)) {
                $bracket = Business::mapLegacyBaselineRevenueIntegerToBracket((int) $val);
            } else {
                $bracket = null;
            }
            DB::table('businesses')->where('id', $row->id)->update([
                'baseline_revenue_new' => $bracket,
            ]);
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('baseline_revenue');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->renameColumn('baseline_revenue_new', 'baseline_revenue');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('businesses') || ! Schema::hasColumn('businesses', 'baseline_revenue')) {
            return;
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedBigInteger('baseline_revenue_int')->nullable()->after('business_size');
        });

        $rows = DB::table('businesses')->select('id', 'baseline_revenue')->get();
        foreach ($rows as $row) {
            $mid = Business::baselineRevenueMidpointRwf($row->baseline_revenue !== null && $row->baseline_revenue !== '' ? (string) $row->baseline_revenue : null);
            DB::table('businesses')->where('id', $row->id)->update([
                'baseline_revenue_int' => $mid !== null ? (int) round($mid) : null,
            ]);
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('baseline_revenue');
        });
        Schema::table('businesses', function (Blueprint $table) {
            $table->renameColumn('baseline_revenue_int', 'baseline_revenue');
        });
    }
};
