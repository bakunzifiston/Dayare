<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const KNOWN_INDEXES = [
        'businesses_business_name_unique',
        'businesses_business_name_normalized_unique',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('businesses')) {
            return;
        }

        foreach (self::KNOWN_INDEXES as $indexName) {
            $this->dropIndexIfExists('businesses', $indexName);
        }

        $connection = Schema::getConnection();
        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        $database = $connection->getDatabaseName();
        $indexes = $connection->select(
            'SELECT index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS columns
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND non_unique = 0
             GROUP BY index_name',
            [$database, 'businesses']
        );

        foreach ($indexes as $index) {
            $indexName = (string) ($index->index_name ?? '');
            $columns = (string) ($index->columns ?? '');

            if ($indexName === '' || $indexName === 'PRIMARY' || $columns === 'registration_number') {
                continue;
            }

            if ($columns === 'business_name' || $columns === 'business_name_normalized') {
                $this->dropIndexIfExists('businesses', $indexName);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('businesses')) {
            return;
        }

        Schema::table('businesses', function (Blueprint $table): void {
            if (! $this->indexExists('businesses', 'businesses_business_name_unique')) {
                $table->unique('business_name', 'businesses_business_name_unique');
            }

            if (Schema::hasColumn('businesses', 'business_name_normalized')
                && ! $this->indexExists('businesses', 'businesses_business_name_normalized_unique')) {
                $table->unique('business_name_normalized', 'businesses_business_name_normalized_unique');
            }
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName): void {
            $table->dropIndex($indexName);
        });
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
};
