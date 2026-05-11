<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('finance_payables', 'ap_bucket')) {
            Schema::table('finance_payables', function (Blueprint $table) {
                $table->string('ap_bucket', 32)->default('supplier')->after('business_id');
            });
        }

        if (! Schema::hasColumn('finance_payables', 'employee_id')) {
            Schema::table('finance_payables', function (Blueprint $table) {
                $table->foreignId('employee_id')
                    ->nullable()
                    ->after('client_id')
                    ->constrained('employees')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('finance_payables', 'casual_worker_id')) {
            Schema::table('finance_payables', function (Blueprint $table) {
                $table->foreignId('casual_worker_id')
                    ->nullable()
                    ->after('employee_id')
                    ->constrained('casual_workers')
                    ->nullOnDelete();
            });
        }

        if (! $this->indexExists('finance_payables', 'finance_payables_business_id_ap_bucket_index')) {
            Schema::table('finance_payables', function (Blueprint $table) {
                $table->index(['business_id', 'ap_bucket']);
            });
        }

        if (Schema::hasColumn('finance_payables', 'ap_bucket')) {
            DB::table('finance_payables')->whereNotNull('supplier_id')->update(['ap_bucket' => 'supplier']);
            DB::table('finance_payables')->whereNull('supplier_id')->whereNotNull('client_id')->update(['ap_bucket' => 'client']);
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $rows = $connection->select("PRAGMA index_list('{$table}')");
            foreach ($rows as $row) {
                if (($row->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $database = $connection->getDatabaseName();
            $rows = $connection->select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$database, $table, $indexName]
            );

            return $rows !== [];
        }

        return false;
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
