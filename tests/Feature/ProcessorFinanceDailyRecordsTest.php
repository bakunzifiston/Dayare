<?php

namespace Tests\Feature;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\Client;
use App\Models\Facility;
use App\Models\FinanceEbmRecord;
use App\Models\FinanceExpense;
use App\Models\FinanceInvoice;
use App\Models\FinanceInvoiceLine;
use App\Models\FinancePayable;
use App\Models\FinancePayment;
use App\Models\User;
use App\Services\Finance\FinanceEbmReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessorFinanceDailyRecordsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Business, 2: Facility, 3: Client}
     */
    private function processorContext(): array
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create([
            'type' => Business::TYPE_PROCESSOR,
        ]);
        $facility = Facility::factory()->create([
            'business_id' => $business->id,
        ]);
        $client = Client::factory()->create([
            'business_id' => $business->id,
        ]);

        return [$user, $business, $facility, $client];
    }

    public function test_daily_ar_invoice_can_be_created_without_intake(): void
    {
        [$user, $business, $facility, $client] = $this->processorContext();

        $response = $this->actingAs($user)->post(route('finance.invoices.store'), [
            'invoice_number' => '',
            'status' => 'issued',
            'currency' => 'RWF',
            'link_contract' => 'no',
            'client_id' => $client->id,
            'facility_id' => $facility->id,
            'animal_intake_id' => '',
            'issued_at' => now()->format('Y-m-d\TH:i'),
            'tax_amount' => 0,
            'discount_amount' => 0,
            'amount_paid' => 0,
            'line_description' => 'Daily beef sale',
            'quantity' => 10,
            'unit_price' => 4500,
            'quantity_unit' => 'kg',
        ]);

        $response->assertRedirect();
        $invoice = FinanceInvoice::query()->where('business_id', $business->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame((int) $client->id, (int) $invoice->client_id);
        $this->assertNull($invoice->animal_intake_id);
        $this->assertSame((int) $facility->id, (int) $invoice->facility_id);
        $this->assertSame(FinanceInvoice::SOURCE_MANUAL, $invoice->source_type);
        $this->assertNotSame('', (string) $invoice->invoice_number);
        $this->assertSame(45000.0, (float) $invoice->total_amount);
    }

    public function test_partial_payment_is_appended_and_leaves_pending_balance(): void
    {
        [$user, $business, $facility, $client] = $this->processorContext();
        $invoice = FinanceInvoice::query()->create([
            'business_id' => $business->id,
            'client_id' => $client->id,
            'facility_id' => $facility->id,
            'invoice_number' => 'AR-TEST-PAY-1',
            'source_type' => FinanceInvoice::SOURCE_MANUAL,
            'status' => 'issued',
            'currency' => 'RWF',
            'subtotal' => 10000,
            'total_amount' => 10000,
            'amount_paid' => 0,
            'issued_at' => now(),
        ]);
        FinanceInvoiceLine::query()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Sale',
            'quantity' => 1,
            'unit_price' => 10000,
            'line_total' => 10000,
        ]);

        $this->actingAs($user)->post(route('finance.payments.store'), [
            'document_type' => 'invoice',
            'document_id' => $invoice->id,
            'amount' => 4000,
            'method' => FinancePayment::METHOD_MOMO,
            'reference' => 'MOMO-123',
            'paid_at' => now()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame(4000.0, (float) $invoice->amount_paid);
        $this->assertSame(6000.0, $invoice->outstandingBalance());
        $this->assertSame(FinancePayment::STATE_PENDING, $invoice->paymentState());
        $this->assertSame(1, $invoice->financePayments()->count());
        $this->assertSame('MOMO-123', $invoice->financePayments()->first()->reference);
    }

    public function test_operating_expense_is_not_an_ap_payable(): void
    {
        [$user, $business, $facility] = $this->processorContext();

        $this->actingAs($user)->post(route('finance.expenses.store'), [
            'expense_date' => now()->toDateString(),
            'category' => FinanceExpense::CATEGORY_UTILITIES,
            'amount' => 25000,
            'description' => 'WASAC water bill',
            'facility_id' => $facility->id,
            'reference_number' => 'WASAC-88',
        ])->assertRedirect();

        $expense = FinanceExpense::query()->where('business_id', $business->id)->first();
        $this->assertNotNull($expense);
        $this->assertSame(FinanceExpense::STATUS_UNPAID, $expense->status);
        $this->assertNull($expense->payable_id);
        $this->assertSame(0, FinancePayable::query()->where('business_id', $business->id)->count());
    }

    public function test_paid_cash_expense_writes_payment_without_creating_ap(): void
    {
        [$user, $business, $facility] = $this->processorContext();

        $this->actingAs($user)->post(route('finance.expenses.store'), [
            'expense_date' => now()->toDateString(),
            'category' => FinanceExpense::CATEGORY_TRANSPORT,
            'amount' => 8000,
            'description' => 'Fuel',
            'facility_id' => $facility->id,
            'already_paid' => '1',
            'payment_method' => FinancePayment::METHOD_CASH,
            'payment_reference' => 'CASH-1',
        ])->assertRedirect();

        $expense = FinanceExpense::query()->where('business_id', $business->id)->first();
        $this->assertNotNull($expense);
        $this->assertSame(FinanceExpense::STATUS_PAID, $expense->status);
        $this->assertSame(8000.0, (float) $expense->amount_paid);
        $this->assertSame(1, $expense->financePayments()->count());
        $this->assertSame(FinancePayment::METHOD_CASH, $expense->financePayments()->first()->method);
        $this->assertNull($expense->payable_id);
        $this->assertSame(0, FinancePayable::query()->where('business_id', $business->id)->count());
    }

    public function test_ebm_reconciler_flags_missing_and_amount_mismatch(): void
    {
        [$user, $business, $facility, $client] = $this->processorContext();
        $invoice = FinanceInvoice::query()->create([
            'business_id' => $business->id,
            'client_id' => $client->id,
            'facility_id' => $facility->id,
            'invoice_number' => 'AR-EBM-1',
            'source_type' => FinanceInvoice::SOURCE_MANUAL,
            'status' => 'issued',
            'currency' => 'RWF',
            'subtotal' => 5000,
            'total_amount' => 5000,
            'amount_paid' => 0,
            'issued_at' => now(),
        ]);

        $summary = app(FinanceEbmReconciler::class)->summary($business->id);
        $this->assertSame(1, $summary['missing_ebm']);

        FinanceEbmRecord::query()->create([
            'business_id' => $business->id,
            'finance_invoice_id' => $invoice->id,
            'ebm_invoice_number' => 'EBM-DIFFERENT',
            'amount' => 9000,
            'status' => FinanceEbmRecord::STATUS_ISSUED,
        ]);

        $summary = app(FinanceEbmReconciler::class)->summary($business->id);
        $this->assertSame(0, $summary['missing_ebm']);
        $this->assertGreaterThan(0, $summary['amount_mismatch']);
        $this->assertTrue($invoice->fresh()->load('ebmRecord')->needsEbmFollowUp());
    }

    public function test_mark_paid_writes_a_payment_ledger_row(): void
    {
        [$user, $business, $facility, $client] = $this->processorContext();
        $invoice = FinanceInvoice::query()->create([
            'business_id' => $business->id,
            'client_id' => $client->id,
            'facility_id' => $facility->id,
            'invoice_number' => 'AR-TEST-PAY-2',
            'source_type' => FinanceInvoice::SOURCE_MANUAL,
            'status' => 'issued',
            'currency' => 'RWF',
            'subtotal' => 2000,
            'total_amount' => 2000,
            'amount_paid' => 0,
            'issued_at' => now(),
        ]);

        $this->actingAs($user)->post(route('finance.invoices.mark-paid', $invoice), [
            'method' => FinancePayment::METHOD_CASH,
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(2000.0, (float) $invoice->amount_paid);
        $this->assertSame(1, $invoice->financePayments()->count());
        $this->assertSame(FinancePayment::METHOD_CASH, $invoice->financePayments()->first()->method);
    }

    public function test_client_intake_invoice_still_resolves_client(): void
    {
        [$user, $business, $facility, $client] = $this->processorContext();
        $intake = AnimalIntake::factory()->create([
            'facility_id' => $facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
            'client_id' => $client->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 4,
        ]);

        $this->actingAs($user)->post(route('finance.invoices.store'), [
            'invoice_number' => 'AR-INTAKE-KEEP',
            'status' => 'draft',
            'currency' => 'RWF',
            'link_contract' => 'no',
            'animal_intake_id' => $intake->id,
            'issued_at' => now()->format('Y-m-d\TH:i'),
            'tax_amount' => 0,
            'discount_amount' => 0,
            'amount_paid' => 0,
            'line_description' => 'Intake billed',
            'quantity' => 2,
            'unit_price' => 100,
            'quantity_unit' => 'kg',
        ])->assertRedirect();

        $invoice = FinanceInvoice::query()->where('invoice_number', 'AR-INTAKE-KEEP')->first();
        $this->assertSame((int) $intake->id, (int) $invoice->animal_intake_id);
        $this->assertSame((int) $client->id, (int) $invoice->client_id);
        $this->assertSame(FinanceInvoice::SOURCE_INTAKE, $invoice->source_type);
    }

    public function test_sale_invoice_can_be_viewed_and_deleted(): void
    {
        [$user, $business, $facility, $client] = $this->processorContext();
        $invoice = FinanceInvoice::query()->create([
            'business_id' => $business->id,
            'client_id' => $client->id,
            'facility_id' => $facility->id,
            'invoice_number' => 'AR-TEST-DELETE-1',
            'source_type' => FinanceInvoice::SOURCE_MANUAL,
            'status' => 'issued',
            'currency' => 'RWF',
            'subtotal' => 8000,
            'total_amount' => 8000,
            'amount_paid' => 3000,
            'issued_at' => now(),
        ]);
        FinanceInvoiceLine::query()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Walk-in beef',
            'quantity' => 2,
            'unit_price' => 4000,
            'line_total' => 8000,
        ]);
        FinancePayment::query()->create([
            'business_id' => $business->id,
            'facility_id' => $facility->id,
            'payable_type' => FinanceInvoice::class,
            'payable_id' => $invoice->id,
            'amount' => 3000,
            'method' => FinancePayment::METHOD_CASH,
            'paid_at' => now(),
        ]);
        FinanceEbmRecord::query()->create([
            'business_id' => $business->id,
            'finance_invoice_id' => $invoice->id,
            'ebm_invoice_number' => 'EBM-DEL-1',
            'amount' => 8000,
            'status' => FinanceEbmRecord::STATUS_ISSUED,
        ]);

        $this->actingAs($user)
            ->get(route('finance.invoices.show', ['invoice' => $invoice, 'from' => 'sales']))
            ->assertOk()
            ->assertSee('AR-TEST-DELETE-1')
            ->assertSee('Walk-in beef');

        $this->actingAs($user)
            ->get(route('finance.sales.index'))
            ->assertOk()
            ->assertSee(__('View'))
            ->assertSee(__('Edit'))
            ->assertSee(__('Delete'));

        $this->actingAs($user)
            ->from(route('finance.sales.index'))
            ->delete(route('finance.invoices.destroy', $invoice), ['from' => 'sales'])
            ->assertRedirect(route('finance.sales.index'));

        $this->assertDatabaseMissing('finance_invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('finance_invoice_lines', ['invoice_id' => $invoice->id]);
        $this->assertDatabaseMissing('finance_payments', [
            'payable_type' => FinanceInvoice::class,
            'payable_id' => $invoice->id,
        ]);
        $this->assertDatabaseMissing('finance_ebm_records', ['ebm_invoice_number' => 'EBM-DEL-1']);
    }
}
