<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_payables', function (Blueprint $table) {
            $table->string('ap_bucket', 32)->default('supplier')->after('business_id');
            $table->foreignId('employee_id')
                ->nullable()
                ->after('client_id')
                ->constrained('employees')
                ->nullOnDelete();
            $table->foreignId('casual_worker_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('casual_workers')
                ->nullOnDelete();
            $table->index(['business_id', 'ap_bucket']);
        });

        DB::table('finance_payables')->whereNotNull('supplier_id')->update(['ap_bucket' => 'supplier']);
        DB::table('finance_payables')->whereNull('supplier_id')->whereNotNull('client_id')->update(['ap_bucket' => 'client']);
    }

    public function down(): void
    {
        Schema::table('finance_payables', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['casual_worker_id']);
            $table->dropIndex(['business_id', 'ap_bucket']);
            $table->dropColumn(['ap_bucket', 'employee_id', 'casual_worker_id']);
        });
    }
};
