<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->foreignId('requested_livestock_id')->nullable()->after('source_farm_id')->constrained('livestock')->nullOnDelete();
            $table->string('required_breed', 120)->nullable()->after('quantity_requested');
            $table->string('required_weight', 120)->nullable()->after('required_breed');
            $table->boolean('healthy_stock_required')->default(true)->after('required_weight');
            $table->boolean('certification_required')->default(false)->after('healthy_stock_required');
            $table->string('required_certification_type', 50)->nullable()->after('certification_required');

            $table->index(['requested_livestock_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->dropIndex(['requested_livestock_id', 'status']);
            $table->dropConstrainedForeignId('requested_livestock_id');
            $table->dropColumn([
                'required_breed',
                'required_weight',
                'healthy_stock_required',
                'certification_required',
                'required_certification_type',
            ]);
        });
    }
};

