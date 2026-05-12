<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('buyers')) {
            Schema::create('buyers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->string('buyer_code', 40);
                $table->string('buyer_name');
                $table->string('buyer_type', 32);
                $table->string('contact_person')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email')->nullable();
                $table->string('national_id', 50)->nullable();
                $table->string('company_registration', 100)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->string('district', 100)->nullable();
                $table->text('address')->nullable();
                $table->string('preferred_payment_method', 32)->nullable();
                $table->string('trust_level', 32)->default('new_buyer');
                $table->text('notes')->nullable();
                $table->string('status', 32)->default('active');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['business_id', 'buyer_code']);
                $table->index(['business_id', 'status']);
            });
        }

        if (! Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
                $table->string('sale_number', 40)->unique();
                $table->foreignId('buyer_id')->constrained()->restrictOnDelete();
                $table->string('sale_type', 32);
                $table->date('sale_date');
                $table->string('sale_status', 32)->default('draft');
                $table->string('payment_status', 32)->default('pending');
                $table->string('payment_method', 32)->nullable();
                $table->decimal('subtotal_amount', 14, 2)->default(0);
                $table->decimal('discount_amount', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->string('currency', 8)->default('RWF');
                $table->string('delivery_method', 64)->nullable();
                $table->string('destination')->nullable();
                $table->foreignId('movement_permit_id')->nullable()->constrained('movement_permits')->nullOnDelete();
                $table->string('certificate_status', 32)->default('unverified');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->string('attachment_path')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['farm_id', 'sale_status']);
                $table->index(['buyer_id', 'sale_date']);
                $table->index('payment_status');
            });
        }

        if (! Schema::hasTable('sale_animals')) {
            Schema::create('sale_animals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('livestock_id')->nullable()->constrained('livestock')->nullOnDelete();
                $table->decimal('sale_price', 14, 2)->default(0);
                $table->decimal('live_weight', 12, 2)->nullable();
                $table->decimal('price_per_kg', 12, 2)->nullable();
                $table->string('animal_condition', 32)->default('healthy');
                $table->boolean('certificate_verified')->default(false);
                $table->boolean('movement_permit_verified')->default(false);
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['sale_id', 'animal_id']);
                $table->index('livestock_id');
            });
        }

        if (! Schema::hasTable('sale_payments')) {
            Schema::create('sale_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $table->string('payment_reference', 40);
                $table->date('payment_date');
                $table->string('payment_method', 32);
                $table->decimal('amount_paid', 14, 2);
                $table->decimal('remaining_balance', 14, 2)->default(0);
                $table->string('transaction_reference')->nullable();
                $table->string('payment_status', 32)->default('paid');
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['sale_id', 'payment_reference']);
                $table->index(['sale_id', 'payment_date']);
            });
        }

        if (! Schema::hasTable('sale_documents')) {
            Schema::create('sale_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $table->string('document_type', 32);
                $table->string('document_number', 40);
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('document_path')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['sale_id', 'document_type', 'document_number'], 'sale_doc_type_number_unique');
                $table->index(['sale_id', 'document_type']);
            });
        }

        if (! Schema::hasTable('sale_logs')) {
            Schema::create('sale_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $table->string('action_type', 32);
                $table->foreignId('action_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('action_date');
                $table->string('ip_address', 45)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['sale_id', 'action_date'], 'sale_logs_sale_date_idx');
                $table->index('action_type', 'sale_logs_action_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_logs');
        Schema::dropIfExists('sale_documents');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_animals');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('buyers');
    }
};
