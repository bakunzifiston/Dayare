<?php

use App\Models\FinanceInvoice;
use App\Models\FinancePayable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('finance_invoices', 'facility_id')) {
            Schema::table('finance_invoices', function (Blueprint $table) {
                $table->foreignId('facility_id')->nullable()->after('delivery_confirmation_id')->constrained('facilities')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('finance_invoices', 'source_type')) {
            Schema::table('finance_invoices', function (Blueprint $table) {
                $table->string('source_type', 32)->default('manual')->after('invoice_number');
            });
        }

        if (! Schema::hasColumn('finance_payables', 'facility_id')) {
            Schema::table('finance_payables', function (Blueprint $table) {
                $table->foreignId('facility_id')->nullable()->after('animal_intake_id')->constrained('facilities')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('finance_payments')) {
            Schema::create('finance_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
                $table->morphs('payable');
                $table->decimal('amount', 14, 2);
                $table->string('method', 32);
                $table->string('reference', 80)->nullable();
                $table->dateTime('paid_at');
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['business_id', 'paid_at']);
            });
        }

        $this->ensureExpensesTable();
        $this->ensureEbmTable();
        $this->backfillSitesAndLegacyPayments();
    }

    public function down(): void
    {
        if (Schema::hasTable('finance_payments')) {
            Schema::drop('finance_payments');
        }

        if (Schema::hasColumn('finance_invoices', 'source_type')) {
            Schema::table('finance_invoices', function (Blueprint $table) {
                $table->dropColumn('source_type');
            });
        }
    }

    private function ensureExpensesTable(): void
    {
        if (! Schema::hasTable('finance_expenses')) {
            Schema::create('finance_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('payable_id')->nullable()->constrained('finance_payables')->nullOnDelete();
                $table->string('expense_number', 40)->unique();
                $table->string('category', 32);
                $table->string('description');
                $table->decimal('amount', 14, 2);
                $table->string('currency', 8)->default('RWF');
                $table->date('expense_date');
                $table->string('reference_number', 80)->nullable();
                $table->string('attachment_path')->nullable();
                $table->string('status', 32)->default('unpaid');
                $table->decimal('amount_paid', 14, 2)->default(0);
                $table->dateTime('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['business_id', 'expense_date']);
                $table->index(['business_id', 'category']);
                $table->index(['business_id', 'status']);
            });

            return;
        }

        Schema::table('finance_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('finance_expenses', 'payable_id')) {
                $table->foreignId('payable_id')->nullable()->constrained('finance_payables')->nullOnDelete();
            }
            if (! Schema::hasColumn('finance_expenses', 'attachment_path')) {
                $table->string('attachment_path')->nullable();
            }
            if (! Schema::hasColumn('finance_expenses', 'status')) {
                $table->string('status', 32)->default('unpaid');
            }
            if (! Schema::hasColumn('finance_expenses', 'amount_paid')) {
                $table->decimal('amount_paid', 14, 2)->default(0);
            }
        });

        if (Schema::hasColumn('finance_expenses', 'payment_status') && Schema::hasColumn('finance_expenses', 'status')) {
            DB::table('finance_expenses')->where(function ($q): void {
                $q->whereNull('status')->orWhere('status', '');
            })->update([
                'status' => DB::raw("COALESCE(NULLIF(payment_status, ''), 'unpaid')"),
            ]);
        }

        if (Schema::hasColumn('finance_expenses', 'document_path') && Schema::hasColumn('finance_expenses', 'attachment_path')) {
            DB::table('finance_expenses')->whereNull('attachment_path')->whereNotNull('document_path')->update([
                'attachment_path' => DB::raw('document_path'),
            ]);
        }
    }

    private function ensureEbmTable(): void
    {
        if (! Schema::hasTable('finance_ebm_records')) {
            Schema::create('finance_ebm_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('finance_invoice_id')->nullable()->constrained('finance_invoices')->nullOnDelete();
                $table->foreignId('facility_id')->nullable()->constrained('facilities')->nullOnDelete();
                $table->string('ebm_invoice_number', 80);
                $table->string('ebm_receipt_number', 80)->nullable();
                $table->dateTime('issued_at')->nullable();
                $table->decimal('amount', 14, 2)->nullable();
                $table->string('status', 32)->default('issued');
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['business_id', 'status']);
                $table->unique(['business_id', 'ebm_invoice_number']);
            });

            return;
        }

        Schema::table('finance_ebm_records', function (Blueprint $table) {
            if (! Schema::hasColumn('finance_ebm_records', 'business_id')) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('finance_ebm_records', 'facility_id')) {
                $table->foreignId('facility_id')->nullable()->after('finance_invoice_id')->constrained('facilities')->nullOnDelete();
            }
            if (! Schema::hasColumn('finance_ebm_records', 'recorded_by')) {
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    private function backfillSitesAndLegacyPayments(): void
    {
        $intakeFacilities = Schema::hasTable('animal_intakes')
            ? DB::table('animal_intakes')->pluck('facility_id', 'id')
            : collect();

        if (Schema::hasColumn('finance_invoices', 'source_type')) {
            $hasInvoiceType = Schema::hasColumn('finance_invoices', 'invoice_type');
            foreach (DB::table('finance_invoices')->get() as $invoice) {
                $updates = [];
                $source = $invoice->source_type ?? null;
                if ($source === null || $source === '' || $source === 'manual') {
                    if ($hasInvoiceType && in_array((string) $invoice->invoice_type, ['intake', 'delivery', 'manual'], true)) {
                        $updates['source_type'] = $invoice->invoice_type;
                    } elseif ($invoice->delivery_confirmation_id) {
                        $updates['source_type'] = 'delivery';
                    } elseif ($invoice->animal_intake_id) {
                        $updates['source_type'] = 'intake';
                    }
                }
                if (empty($invoice->facility_id) && $invoice->animal_intake_id) {
                    $facilityId = $intakeFacilities[$invoice->animal_intake_id] ?? null;
                    if ($facilityId) {
                        $updates['facility_id'] = $facilityId;
                    }
                }
                if ($updates !== []) {
                    DB::table('finance_invoices')->where('id', $invoice->id)->update($updates);
                }
            }
        }

        foreach (DB::table('finance_payables')->whereNull('facility_id')->whereNotNull('animal_intake_id')->get() as $payable) {
            $facilityId = $intakeFacilities[$payable->animal_intake_id] ?? null;
            if ($facilityId) {
                DB::table('finance_payables')->where('id', $payable->id)->update(['facility_id' => $facilityId]);
            }
        }

        if (! Schema::hasTable('finance_payments') || DB::table('finance_payments')->exists()) {
            return;
        }

        $now = now();
        foreach (DB::table('finance_invoices')->where('amount_paid', '>', 0)->get() as $invoice) {
            DB::table('finance_payments')->insert([
                'business_id' => $invoice->business_id,
                'facility_id' => $invoice->facility_id,
                'payable_type' => FinanceInvoice::class,
                'payable_id' => $invoice->id,
                'amount' => $invoice->amount_paid,
                'method' => in_array((string) ($invoice->payment_method ?? ''), ['cash', 'momo', 'bank_transfer'], true)
                    ? $invoice->payment_method
                    : 'cash',
                'reference' => 'LEGACY-AR-'.$invoice->id,
                'paid_at' => $invoice->paid_at ?? $invoice->issued_at ?? $now,
                'notes' => 'Backfilled from invoice amount_paid',
                'recorded_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (DB::table('finance_payables')->where('amount_paid', '>', 0)->get() as $payable) {
            DB::table('finance_payments')->insert([
                'business_id' => $payable->business_id,
                'facility_id' => $payable->facility_id,
                'payable_type' => FinancePayable::class,
                'payable_id' => $payable->id,
                'amount' => $payable->amount_paid,
                'method' => in_array((string) ($payable->payment_method ?? ''), ['cash', 'momo', 'bank_transfer'], true)
                    ? $payable->payment_method
                    : 'cash',
                'reference' => 'LEGACY-AP-'.$payable->id,
                'paid_at' => $payable->paid_at ?? $payable->issued_at ?? $now,
                'notes' => 'Backfilled from payable amount_paid',
                'recorded_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
