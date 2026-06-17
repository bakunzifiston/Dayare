<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherCutType;
use App\Models\ButcherDelivery;
use App\Models\ButcherExpense;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOutlet;
use App\Models\ButcherProduct;
use App\Models\ButcherSale;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherCatalogService;
use App\Services\Butcher\ButcherCuttingService;
use App\Services\Butcher\ButcherFinanceService;
use App\Services\Butcher\ButcherProcurementService;
use App\Services\Butcher\ButcherSalesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ButcherFinanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private ButcherOutlet $outlet;

    private ButcherSupplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-FIN-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

        $this->supplier = ButcherSupplier::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Supplier',
            'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
            'is_active' => true,
        ]);

        $this->outlet = ButcherOutlet::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Main',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);
    }

    public function test_finance_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.finance.index'))
            ->assertOk()
            ->assertSee(__('Finance & reporting'));
    }

    public function test_profit_and_loss_calculates_correctly(): void
    {
        $sale = $this->createSaleWithRevenue(20000, 2);
        $this->assertSame(ButcherSale::STATUS_COMPLETED, $sale->status);
        $this->assertEqualsWithDelta(20000, (float) $sale->total_amount, 0.01);
        $this->createExpense(10000);

        $from = now()->startOfMonth();
        $to = now()->endOfDay();
        $pl = app(ButcherFinanceService::class)->getProfitAndLoss($this->business, $from, $to);

        $this->assertEqualsWithDelta(20000, (float) $pl['revenue'], 0.01);
        $this->assertGreaterThan(0, (float) $pl['cogs']);
        $this->assertEqualsWithDelta(10000, (float) $pl['operating_expenses'], 0.01);
        $this->assertEqualsWithDelta(
            (float) $pl['revenue'] - (float) $pl['cogs'] - (float) $pl['operating_expenses'],
            (float) $pl['net_profit'],
            0.01
        );
    }

    public function test_cogs_from_cut_output_unit_cost(): void
    {
        $this->createSaleWithRevenue(20000, 2);

        $cogs = app(ButcherFinanceService::class)->getCOGS(
            $this->business,
            now()->startOfMonth(),
            now()->endOfDay()
        );

        $this->assertGreaterThan(0, $cogs);
    }

    public function test_expense_crud_via_http(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.finance.expenses.store'), [
                'category' => ButcherExpense::CATEGORY_RENT,
                'description' => 'Shop rent June',
                'amount' => 150000,
                'expense_date' => now()->toDateString(),
                'payment_method' => ButcherExpense::PAYMENT_BANK_TRANSFER,
            ])
            ->assertRedirect(route('butcher.finance.expenses.index'));

        $expense = $this->business->butcherExpenses()->first();
        $this->assertNotNull($expense);

        $this->actingAs($this->user)
            ->put(route('butcher.finance.expenses.update', $expense), [
                'category' => ButcherExpense::CATEGORY_RENT,
                'description' => 'Shop rent June (updated)',
                'amount' => 155000,
                'expense_date' => now()->toDateString(),
                'payment_method' => ButcherExpense::PAYMENT_BANK_TRANSFER,
            ])
            ->assertRedirect(route('butcher.finance.expenses.index'));

        $this->assertSame('Shop rent June (updated)', $expense->fresh()->description);

        $this->actingAs($this->user)
            ->delete(route('butcher.finance.expenses.destroy', $expense))
            ->assertRedirect(route('butcher.finance.expenses.index'));

        $this->assertDatabaseMissing('butcher_expenses', ['id' => $expense->id]);
    }

    public function test_export_pl_report(): void
    {
        $this->createSaleWithRevenue(20000, 2);

        $path = app(ButcherFinanceService::class)->exportReport(
            $this->business,
            'pl',
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfDay(),
            'csv'
        );

        Storage::disk('public')->assertExists($path);
    }

    private function createSaleWithRevenue(float $price, float $qtyKg): ButcherSale
    {
        $cutType = $this->business->butcherCutTypes()->create([
            'name' => 'Sirloin',
            'meat_type' => ButcherCutType::MEAT_BEEF,
            'expected_yield_pct' => 85,
            'is_active' => true,
        ]);

        $product = app(ButcherCatalogService::class)->createProduct($this->business, [
            'name' => 'Fresh Sirloin',
            'cut_type_id' => $cutType->id,
            'meat_type' => ButcherProduct::MEAT_BEEF,
            'unit' => ButcherProduct::UNIT_PER_KG,
            'default_price' => $price / $qtyKg,
        ]);

        $delivery = app(ButcherProcurementService::class)->receiveDelivery($this->business, [
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outlet->id,
            'meat_type' => ButcherDelivery::MEAT_BEEF,
            'received_weight_kg' => 48.25,
            'unit_cost_per_kg' => 3500,
            'condition' => ButcherDelivery::CONDITION_GOOD,
        ], $this->user);

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $cutting = app(ButcherCuttingService::class);
        $session = $cutting->openSession($this->business, [
            'outlet_id' => $this->outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 20,
        ]);
        $cutting->addCutOutput($session, ['cut_type_id' => $cutType->id, 'weight_kg' => 30]);
        $cutting->closeSession($session->fresh());

        return app(ButcherSalesService::class)->createSale($this->business, [
            'outlet_id' => $this->outlet->id,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => $price,
            'items' => [['product_id' => $product->id, 'quantity_kg' => $qtyKg]],
        ], $this->user);
    }

    private function createExpense(float $amount): void
    {
        app(ButcherFinanceService::class)->logExpense($this->business, [
            'category' => ButcherExpense::CATEGORY_UTILITIES,
            'description' => 'Electricity',
            'amount' => $amount,
            'expense_date' => now()->toDateString(),
            'payment_method' => ButcherExpense::PAYMENT_MOMO,
        ], $this->user);
    }
}
