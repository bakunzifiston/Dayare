<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_CONFIRM_DOC_TYPE = 'med_confirm_doc_type_idx';

    public function up(): void
    {
        if (! Schema::hasTable('meat_export_documents')) {
            Schema::create('meat_export_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_confirmation_id')
                    ->constrained('delivery_confirmations')
                    ->cascadeOnDelete();
                $table->string('document_type', 60);
                $table->string('document_number', 100)->nullable();
                $table->string('issuing_authority', 255)->nullable();
                $table->date('issued_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('status', 40)->default('pending');
                $table->text('notes')->nullable();
                $table->string('file_path', 500)->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('updated_by')->nullable()->constrained('users');
                $table->timestamps();

                $table->index(['delivery_confirmation_id', 'document_type'], self::INDEX_CONFIRM_DOC_TYPE);
            });
        }

        if (! $this->indexExists('meat_export_documents', self::INDEX_CONFIRM_DOC_TYPE)) {
            Schema::table('meat_export_documents', function (Blueprint $table): void {
                $table->index(['delivery_confirmation_id', 'document_type'], self::INDEX_CONFIRM_DOC_TYPE);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meat_export_documents');
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
