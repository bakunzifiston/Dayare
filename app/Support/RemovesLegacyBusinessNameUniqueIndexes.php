<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemovesLegacyBusinessNameUniqueIndexes
{
    /**
     * @var list<string>
     */
    private const KNOWN_INDEXES = [
        'businesses_business_name_unique',
        'businesses_business_name_normalized_unique',
    ];

    public static function remove(): void
    {
        if (! Schema::hasTable('businesses')) {
            return;
        }

        foreach (self::KNOWN_INDEXES as $indexName) {
            self::dropIndexIfExists('businesses', $indexName);
        }

        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            self::removeSqliteBusinessNameUniqueIndexes('businesses');

            return;
        }

        if ($driver !== 'mysql') {
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
                self::dropIndexIfExists('businesses', $indexName);
            }
        }
    }

    private static function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! self::indexExists($table, $indexName)) {
            return;
        }

        $connection = Schema::getConnection();

        try {
            if ($connection->getDriverName() === 'mysql') {
                DB::statement(sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $indexName));

                return;
            }

            Schema::table($table, function ($blueprint) use ($indexName): void {
                $blueprint->dropIndex($indexName);
            });
        } catch (QueryException) {
            // Another request may have dropped the index already.
        }
    }

    private static function removeSqliteBusinessNameUniqueIndexes(string $table): void
    {
        $connection = Schema::getConnection();
        $rows = $connection->select("PRAGMA index_list('{$table}')");

        foreach ($rows as $row) {
            $indexName = (string) ($row->name ?? '');
            $isUnique = (int) ($row->unique ?? 0) === 1;

            if ($indexName === '' || $indexName === 'PRIMARY' || ! $isUnique) {
                continue;
            }

            $info = $connection->select("PRAGMA index_info('{$indexName}')");
            $columns = array_values(array_filter(array_map(
                static fn ($column): string => (string) ($column->name ?? ''),
                $info
            )));

            if ($columns === ['business_name'] || $columns === ['business_name_normalized']) {
                self::dropIndexIfExists($table, $indexName);
            }
        }
    }

    private static function indexExists(string $table, string $indexName): bool
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
}
