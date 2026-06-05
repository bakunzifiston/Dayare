<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('meat_export_documents')) {
            return;
        }

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

            $table->index(['delivery_confirmation_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meat_export_documents');
    }
};
