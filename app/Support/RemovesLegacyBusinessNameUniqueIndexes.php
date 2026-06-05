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

        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $table = self::tableName();

        foreach (self::KNOWN_INDEXES as $indexName) {
            self::dropIndexIfExists($table, $indexName);
        }

        if ($driver === 'sqlite') {
            self::removeSqliteBusinessNameUniqueIndexes($table);

            return;
        }

        if (! self::isMysqlFamily($driver)) {
            return;
        }

        $database = $connection->getDatabaseName();
        $indexes = $connection->select(
            'SELECT index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS columns
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND non_unique = 0
             GROUP BY index_name',
            [$database, $table]
        );

        foreach ($indexes as $index) {
            $indexName = (string) ($index->index_name ?? '');
            $columns = (string) ($index->columns ?? '');

            if ($indexName === '' || $indexName === 'PRIMARY' || $columns === 'registration_number') {
                continue;
            }

            if (self::isBusinessNameIndexColumns($columns)) {
                self::dropIndexIfExists($table, $indexName);
            }
        }
    }

    private static function tableName(): string
    {
        return Schema::getConnection()->getTablePrefix().'businesses';
    }

    private static function isMysqlFamily(string $driver): bool
    {
        return in_array($driver, ['mysql', 'mariadb'], true);
    }

    private static function isBusinessNameIndexColumns(string $columns): bool
    {
        if ($columns === 'business_name' || $columns === 'business_name_normalized') {
            return true;
        }

        $parts = array_map('trim', explode(',', $columns));

        return $parts !== []
            && count(array_diff($parts, ['business_name', 'business_name_normalized'])) === 0;
    }

    private static function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! self::indexExists($table, $indexName)) {
            return;
        }

        $connection = Schema::getConnection();

        if (self::isMysqlFamily($connection->getDriverName())) {
            self::dropMysqlIndex($table, $indexName);

            return;
        }

        try {
            Schema::table($table, function ($blueprint) use ($indexName): void {
                $blueprint->dropIndex($indexName);
            });
        } catch (QueryException) {
            // Another request may have dropped the index already.
        }
    }

    private static function dropMysqlIndex(string $table, string $indexName): void
    {
        $statements = [
            sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $table, $indexName),
            sprintf('ALTER TABLE `%s` DROP INDEX IF EXISTS `%s`', $table, $indexName),
        ];

        foreach ($statements as $statement) {
            try {
                DB::statement($statement);

                if (! self::indexExists($table, $indexName)) {
                    return;
                }
            } catch (QueryException) {
                // Try the next drop syntax (older MySQL/MariaDB versions differ).
            }
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

            if (self::isBusinessNameIndexColumns(implode(',', $columns))) {
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

        if (self::isMysqlFamily($driver)) {
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
