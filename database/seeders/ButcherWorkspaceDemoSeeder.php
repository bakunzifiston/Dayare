<?php

namespace Database\Seeders;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\ButcherCutType;
use App\Models\ButcherCustomer;
use App\Models\ButcherDelivery;
use App\Models\ButcherDeliveryLine;
use App\Models\ButcherExpense;
use App\Models\ButcherHygieneLog;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherOrder;
use App\Models\ButcherOutlet;
use App\Models\ButcherPermit;
use App\Models\ButcherProduct;
use App\Models\ButcherPurchaseOrder;
use App\Models\ButcherSale;
use App\Models\ButcherSanitationRecord;
use App\Models\ButcherStaffHealthRecord;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherCatalogService;
use App\Services\Butcher\ButcherComplianceService;
use App\Services\Butcher\ButcherCuttingService;
use App\Services\Butcher\ButcherFinanceService;
use App\Services\Butcher\ButcherOnboardingService;
use App\Services\Butcher\ButcherProcurementService;
use App\Services\Butcher\ButcherSalesService;
use App\Services\Butcher\ButcherStockCountService;
use App\Services\Butcher\ButcherStockTransferService;
use App\Services\Butcher\ButcherStorageService;
use Carbon\Carbon;
use Database\Seeders\Support\RwandaSeederHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Connected demo data for owner.butcher@demo.rw — full butcher workspace (modules 1–8).
 * Seeds at least {@see self::MIN_ROWS} rows per module.
 *
 * Usage: php artisan db:seed --class=ButcherWorkspaceDemoSeeder
 * Idempotent: skips when SEED-MT-BU-001 already has 20+ completed sales and expenses.
 * Demo password: password
 */
class ButcherWorkspaceDemoSeeder extends Seeder
{
    private const OWNER_EMAIL = 'owner.butcher@demo.rw';

    private const BUSINESS_REG = 'SEED-MT-BU-001';

    private const PASSWORD = 'password';

    private const MIN_ROWS = 20;

    /** @var list<string> */
    private const CUT_NAMES = [
        'Sirloin', 'Ribeye', 'Beef Mince', 'T-Bone', 'Brisket', 'Chuck', 'Flank', 'Shank',
        'Liver', 'Kidney', 'Short Ribs', 'Fillet', 'Topside', 'Silverside', 'Oxtail', 'Neck',
        'Trotters', 'Heart', 'Tongue', 'Rump Steak',
    ];

    private User $owner;

    private Business $business;

    private Carbon $rangeStart;

    private Carbon $rangeEnd;

    /** @var Collection<int, ButcherOutlet> */
    private Collection $outlets;

    /** @var Collection<int, ButcherSupplier> */
    private Collection $suppliers;

    /** @var Collection<int, ButcherCutType> */
    private Collection $cutTypes;

    /** @var Collection<int, ButcherInventoryBatch> */
    private Collection $batches;

    /** @var Collection<int, ButcherProduct> */
    private Collection $products;

    /** @var Collection<int, ButcherCustomer> */
    private Collection $customers;

    /** @var Collection<int, User> */
    private Collection $staffUsers;

    public function run(): void
    {
        $this->rangeStart = Carbon::now()->subMonths(3)->startOfDay();
        $this->rangeEnd = Carbon::now()->endOfDay();
        $this->outlets = collect();
        $this->suppliers = collect();
        $this->cutTypes = collect();
        $this->batches = collect();
        $this->products = collect();
        $this->customers = collect();
        $this->staffUsers = collect();

        if (! $this->bootstrapAccount()) {
            return;
        }

        if ($this->workspaceAlreadyPopulated() && ! filter_var(env('FORCE_BUTCHER_RESEED', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('Butcher workspace demo already populated for '.self::BUSINESS_REG.'. Skipping.');
            $this->command?->warn('Re-run with FORCE_BUTCHER_RESEED=1 to rebuild demo data.');

            return;
        }

        if ($this->business->butcherSales()->exists() || $this->workspaceAlreadyPopulated()) {
            $this->command?->info('Refreshing butcher demo data…');
            $this->purgeButcherModuleData();
            $this->outlets = collect();
            $this->suppliers = collect();
            $this->cutTypes = collect();
            $this->batches = collect();
            $this->products = collect();
            $this->customers = collect();
            $this->staffUsers = collect();
        }

        $this->command?->info('Seeding butcher workspace demo ('.self::MIN_ROWS.'+ rows per module) for '.self::OWNER_EMAIL.'…');

        // Dompdf receipt/label generation is skipped via skip_documents; still raise ceiling for safety.
        ini_set('memory_limit', '512M');

        DB::transaction(function (): void {
            $this->seedOnboarding();
            $this->seedProcurement();
            $this->seedStorage();
            $this->seedTransfers();
            $this->seedCutting();
            $this->seedCatalog();
            $this->ensureSalesStock();
            $this->seedSales();
            $this->seedStockCounts();
            $this->seedCompliance();
            $this->seedFinance();
        });

        $this->printSummary();
    }

    private function bootstrapAccount(): bool
    {
        $this->owner = User::query()->where('email', self::OWNER_EMAIL)->first()
            ?? User::query()->create([
                'name' => 'Remera Prime Butchery (Owner)',
                'email' => self::OWNER_EMAIL,
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]);

        $existingBusiness = Business::query()
            ->where('registration_number', self::BUSINESS_REG)
            ->where('type', Business::TYPE_BUTCHER)
            ->first();

        if ($existingBusiness) {
            $this->business = $existingBusiness;
            BusinessUser::query()->updateOrCreate(
                ['business_id' => $this->business->id, 'user_id' => $this->owner->id],
                ['role' => BusinessUser::ROLE_BUTCHER_OWNER]
            );

            return true;
        }

        $country = AdministrativeDivision::ofType(AdministrativeDivision::TYPE_COUNTRY)->first();
        if (! $country) {
            $this->command?->error('Run AdministrativeDivisionSeeder first.');

            return false;
        }

        $province = AdministrativeDivision::byParent($country->id)
            ->where('name', 'City of Kigali')
            ->first()
            ?? AdministrativeDivision::byParent($country->id)->first();

        if (! $province) {
            $this->command?->error('No provinces found in administrative_divisions.');

            return false;
        }

        $district = AdministrativeDivision::byParent($province->id)->orderBy('name')->first();
        $sector = $district ? AdministrativeDivision::byParent($district->id)->orderBy('name')->first() : null;

        $this->business = Business::query()->create([
            'user_id' => $this->owner->id,
            'type' => Business::TYPE_BUTCHER,
            'business_name' => 'Remera Prime Butchery',
            'registration_number' => self::BUSINESS_REG,
            'tax_id' => '1000123456',
            'contact_phone' => '+250788600001',
            'email' => 'contact@remerabutchery.demo.rw',
            'status' => Business::STATUS_PENDING,
            'owner_first_name' => 'Eric',
            'owner_last_name' => 'Habimana',
            'ownership_type' => 'sole_proprietor',
            'business_size' => 'small',
            'baseline_revenue' => Business::BASELINE_REVENUE_BRACKET_LT_2M,
            'butcher_batch_shelf_life_days' => 14,
            'country_id' => $country->id,
            'province_id' => $province->id,
            'district_id' => $district?->id,
            'sector_id' => $sector?->id,
        ]);

        BusinessUser::query()->updateOrCreate(
            ['business_id' => $this->business->id, 'user_id' => $this->owner->id],
            ['role' => BusinessUser::ROLE_BUTCHER_OWNER]
        );

        return true;
    }

    private function workspaceAlreadyPopulated(): bool
    {
        return $this->business->butcherSales()
                ->where('status', ButcherSale::STATUS_COMPLETED)
                ->count() >= self::MIN_ROWS
            && $this->business->butcherExpenses()->count() >= self::MIN_ROWS;
    }

    private function seedOnboarding(): void
    {
        $onboarding = app(ButcherOnboardingService::class);
        $district = (string) ($this->business->district?->name
            ?? $onboarding->rwandaDistrictNames()[0]
            ?? 'Gasabo');

        if (! $onboarding->isProfileComplete($this->business)) {
            $onboarding->createBusinessProfile([
                'business_name' => 'Remera Prime Butchery',
                'butchery_type' => Business::BUTCHERY_TYPE_MIXED,
                'rdb_registration_number' => self::BUSINESS_REG,
                'tin_number' => '1000123456',
                'phone' => '+250788600001',
                'district' => $district,
                'sector' => $this->business->sector?->name ?? 'Remera',
                'cell' => 'Rukiri I',
                'rfa_permit_number' => 'RFA-BU-2025-001',
                'rfa_permit_expiry' => Carbon::now()->addMonths(8)->toDateString(),
            ], $this->owner);
            $this->business->refresh();
        }

        $sectors = ['Remera', 'Kacyiru', 'Gisozi', 'Kimironko', 'Nyamirambo', 'Gikondo', 'Kicukiro', 'Nyarugenge'];
        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $outlet = $this->business->butcherOutlets()->firstOrCreate(
                ['name' => sprintf('Outlet %02d — %s', $i, $sectors[($i - 1) % count($sectors)])],
                [
                    'district' => $district,
                    'sector' => $sectors[($i - 1) % count($sectors)],
                    'phone' => sprintf('+2507886%05d', 10000 + $i),
                    'is_primary' => $i === 1,
                    'status' => ButcherOutlet::STATUS_ACTIVE,
                ]
            );
            $this->outlets->push($outlet);
        }

        $supplierTypes = [
            ButcherSupplier::TYPE_ABATTOIR,
            ButcherSupplier::TYPE_FARM,
            ButcherSupplier::TYPE_MARKET,
            ButcherSupplier::TYPE_INDIVIDUAL,
            ButcherSupplier::TYPE_OTHER,
        ];

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $supplier = $this->business->butcherSuppliers()->firstOrCreate(
                ['name' => sprintf('Supplier %02d — %s', $i, RwandaSeederHelper::fullName(200 + $i))],
                [
                    'contact_person' => RwandaSeederHelper::fullName(200 + $i),
                    'phone' => RwandaSeederHelper::phone(200 + $i),
                    'email' => sprintf('supplier%02d@butcher-demo.rw', $i),
                    'supplier_type' => $supplierTypes[($i - 1) % count($supplierTypes)],
                    'district' => $district,
                    'is_active' => true,
                ]
            );
            $this->suppliers->push($supplier);
        }

        $permitTypes = ButcherPermit::PERMIT_TYPES;
        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $this->business->butcherPermits()->firstOrCreate(
                ['permit_number' => sprintf('PERMIT-BU-%03d', $i)],
                [
                    'permit_type' => $permitTypes[($i - 1) % count($permitTypes)],
                    'issued_by' => $i % 3 === 0 ? 'RICA' : ($i % 2 === 0 ? 'RFA' : 'City of Kigali'),
                    'issue_date' => Carbon::now()->subMonths(6 + ($i % 6))->toDateString(),
                    'expiry_date' => Carbon::now()->addDays(15 + ($i * 7))->toDateString(),
                    'status' => $i === 3 ? ButcherPermit::STATUS_PENDING_RENEWAL : ButcherPermit::STATUS_VALID,
                ]
            );
        }

        $meatTypes = [ButcherCutType::MEAT_BEEF, ButcherCutType::MEAT_GOAT, ButcherCutType::MEAT_PORK];
        foreach (self::CUT_NAMES as $index => $name) {
            $cutType = $this->business->butcherCutTypes()->firstOrCreate(
                ['name' => $name],
                [
                    'meat_type' => $meatTypes[$index % count($meatTypes)],
                    'expected_yield_pct' => 75 + ($index % 20),
                    'is_active' => true,
                ]
            );
            $this->cutTypes->push($cutType);
        }
    }

    private function seedProcurement(): void
    {
        $procurement = app(ButcherProcurementService::class);
        $meatTypes = [ButcherDelivery::MEAT_BEEF, ButcherDelivery::MEAT_GOAT, ButcherDelivery::MEAT_PORK];
        // Target end-states; transitions must follow draft → sent → confirmed (→ delivered via receiving).
        $poTargets = [
            ButcherPurchaseOrder::STATUS_DRAFT,
            ButcherPurchaseOrder::STATUS_SENT,
            ButcherPurchaseOrder::STATUS_CONFIRMED,
            ButcherPurchaseOrder::STATUS_DELIVERED,
            ButcherPurchaseOrder::STATUS_CANCELLED,
        ];

        $purchaseOrders = collect();
        $deliverablePoIds = collect();

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $supplier = $this->suppliers[($i - 1) % $this->suppliers->count()];
            $target = $poTargets[($i - 1) % count($poTargets)];

            $po = $procurement->createPurchaseOrder($this->business, [
                'supplier_id' => $supplier->id,
                'meat_type' => $meatTypes[($i - 1) % count($meatTypes)],
                'requested_weight_kg' => 40 + ($i * 2),
                'requested_date' => Carbon::now()->subDays(self::MIN_ROWS - $i)->toDateString(),
                'notes' => sprintf('Demo PO #%02d', $i),
            ]);

            $this->advancePurchaseOrderToward($procurement, $po, $target);

            $po = $po->fresh();
            $purchaseOrders->push($po);

            if (in_array($po->status, [
                ButcherPurchaseOrder::STATUS_CONFIRMED,
                ButcherPurchaseOrder::STATUS_SENT,
            ], true) || $target === ButcherPurchaseOrder::STATUS_DELIVERED) {
                $deliverablePoIds->push($po->id);
            }
        }

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $supplier = $this->suppliers[($i - 1) % $this->suppliers->count()];
            $outlet = $this->outlets[($i - 1) % $this->outlets->count()];
            $poId = $deliverablePoIds->isNotEmpty()
                ? $deliverablePoIds[($i - 1) % $deliverablePoIds->count()]
                : null;

            // Each PO should only be linked once so receiving can mark it delivered cleanly.
            if ($poId !== null && $i > $deliverablePoIds->count()) {
                $poId = null;
            }

            $weight = 35 + ($i * 1.5);
            $unitCost = 3200 + ($i * 50);
            $isPartial = $i % 7 === 0;
            $accepted = $isPartial ? round($weight * 0.85, 3) : $weight;
            $rejected = $isPartial ? round($weight - $accepted, 3) : 0.0;

            $delivery = $procurement->receiveDelivery($this->business, [
                'purchase_order_id' => $poId,
                'supplier_id' => $supplier->id,
                'outlet_id' => $outlet->id,
                'received_at' => Carbon::now()->subHours(self::MIN_ROWS - $i)->toDateTimeString(),
                'lines' => [[
                    'meat_type' => $meatTypes[($i - 1) % count($meatTypes)],
                    'expected_weight_kg' => $poId
                        ? (float) ButcherPurchaseOrder::query()->find($poId)?->requested_weight_kg
                        : $weight,
                    'received_weight_kg' => $weight,
                    'unit_cost' => $unitCost,
                    'outcome' => $isPartial
                        ? ButcherDeliveryLine::OUTCOME_PARTIALLY_ACCEPTED
                        : ButcherDeliveryLine::OUTCOME_ACCEPTED,
                    'accepted_weight_kg' => $accepted,
                    'rejected_weight_kg' => $rejected,
                    'temperature_c' => 2 + ($i % 4),
                ]],
            ], $this->owner);

            if ($delivery->createsInventory()) {
                $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->first();
                if ($batch) {
                    $this->batches->push($batch);
                }
            }
        }
    }

    /**
     * Walk legal PO transitions toward a target end-state.
     * "delivered" stops at confirmed — receiving marks delivered.
     */
    private function advancePurchaseOrderToward(
        ButcherProcurementService $procurement,
        ButcherPurchaseOrder $po,
        string $target,
    ): void {
        $steps = match ($target) {
            ButcherPurchaseOrder::STATUS_SENT => [
                ButcherPurchaseOrder::STATUS_SENT,
            ],
            ButcherPurchaseOrder::STATUS_CONFIRMED,
            ButcherPurchaseOrder::STATUS_DELIVERED => [
                ButcherPurchaseOrder::STATUS_SENT,
                ButcherPurchaseOrder::STATUS_CONFIRMED,
            ],
            ButcherPurchaseOrder::STATUS_CANCELLED => [
                ButcherPurchaseOrder::STATUS_CANCELLED,
            ],
            default => [],
        };

        foreach ($steps as $status) {
            $procurement->updateOrderStatus($po->fresh(), $status);
        }
    }

    private function seedStorage(): void
    {
        $storage = app(ButcherStorageService::class);
        $locations = ['Cold room A', 'Cold room B', 'Display chiller', 'Walk-in freezer', 'Prep bench fridge'];

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $outlet = $this->outlets[($i - 1) % $this->outlets->count()];
            $storage->logTemperature($this->business, [
                'outlet_id' => $outlet->id,
                'storage_location' => $locations[($i - 1) % count($locations)],
                'storage_type' => $i % 4 === 0
                    ? \App\Models\ButcherTemperatureLog::TYPE_FROZEN
                    : \App\Models\ButcherTemperatureLog::TYPE_FRESH,
                'temperature_celsius' => $i % 5 === 0
                    ? ($i % 4 === 0 ? -16.0 : 3.5) // stay within thresholds; no auto-breach of active batches
                    : ($i % 4 === 0 ? -17.5 : 2.0 + ($i % 3) * 0.4),
                'logged_at' => Carbon::now()->subHours($i * 3)->toDateTimeString(),
            ], $this->owner);
        }

        $disposableBatches = $this->batches->slice(-2);
        foreach ($disposableBatches->values() as $index => $batch) {
            if ((float) $batch->remaining_weight_kg < 2) {
                continue;
            }

            $storage->logDisposal($batch->fresh(), [
                'weight_disposed_kg' => 1.0,
                'reason' => \App\Models\ButcherDisposalLog::REASONS[$index % 4],
                'disposed_at' => Carbon::now()->subDays($index + 1)->toDateTimeString(),
                'notes' => 'Demo disposal record',
            ], $this->owner);
        }
    }

    private function seedTransfers(): void
    {
        if ($this->outlets->count() < 2 || $this->batches->isEmpty()) {
            return;
        }

        $transfers = app(ButcherStockTransferService::class);
        $primary = $this->outlets->first();
        $destinations = $this->outlets->slice(1)->values();

        $sourceBatches = ButcherInventoryBatch::query()
            ->where('business_id', $this->business->id)
            ->whereIn('status', [
                ButcherInventoryBatch::STATUS_IN_STORAGE,
                ButcherInventoryBatch::STATUS_PARTIALLY_USED,
            ])
            ->where('remaining_weight_kg', '>=', 5)
            ->orderBy('received_at')
            ->get();

        if ($sourceBatches->isEmpty()) {
            return;
        }

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $batch = $sourceBatches[($i - 1) % $sourceBatches->count()]->fresh();
            $available = (float) $batch->remaining_weight_kg;
            if ($available < 3) {
                continue;
            }

            $fromOutletId = (int) $batch->outlet_id;
            $toOutlet = $destinations[($i - 1) % $destinations->count()];
            if ((int) $toOutlet->id === $fromOutletId) {
                $toOutlet = $destinations->first(fn ($o) => (int) $o->id !== $fromOutletId) ?? $primary;
                if ((int) $toOutlet->id === $fromOutletId) {
                    continue;
                }
            }

            $qty = min(2.5 + ($i % 4) * 0.5, round($available * 0.25, 3));
            if ($qty < 1) {
                continue;
            }

            try {
                $transfer = $transfers->transfer($this->business, [
                    'from_outlet_id' => $fromOutletId,
                    'to_outlet_id' => $toOutlet->id,
                    'batch_id' => $batch->id,
                    'quantity_kg' => $qty,
                    'transferred_at' => Carbon::now()->subDays(self::MIN_ROWS - $i)->setTime(9 + ($i % 6), 15)->toDateTimeString(),
                    'storage_location' => 'Transfer staging — '.($toOutlet->name ?? 'outlet'),
                    'notes' => sprintf('Demo transfer #%02d Remera → %s', $i, $toOutlet->name),
                ], $this->owner);

                if ($transfer->destinationBatch) {
                    $this->batches->push($transfer->destinationBatch);
                }
            } catch (\Throwable $e) {
                $this->command?->warn('Skipped demo transfer #'.$i.': '.$e->getMessage());
            }
        }
    }

    private function seedStockCounts(): void
    {
        $counts = app(ButcherStockCountService::class);
        $outletsWithStock = $this->outlets->filter(function ($outlet) {
            return ButcherInventoryBatch::query()
                ->where('business_id', $this->business->id)
                ->where('outlet_id', $outlet->id)
                ->where('remaining_weight_kg', '>', 0)
                ->whereIn('status', [
                    ButcherInventoryBatch::STATUS_IN_STORAGE,
                    ButcherInventoryBatch::STATUS_PARTIALLY_USED,
                    ButcherInventoryBatch::STATUS_EXPIRED,
                ])
                ->exists();
        })->values();

        if ($outletsWithStock->isEmpty()) {
            return;
        }

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $outlet = $outletsWithStock[($i - 1) % $outletsWithStock->count()];

            try {
                $count = $counts->startCount($this->business, [
                    'outlet_id' => $outlet->id,
                    'count_date' => Carbon::now()->subDays(self::MIN_ROWS - $i)->toDateString(),
                    'notes' => sprintf('Demo stock count #%02d — %s', $i, $outlet->name),
                ], $this->owner);

                $count->load('lines');
                if ($count->lines->isEmpty()) {
                    continue;
                }

                $path = $i % 4;
                // 0 = leave draft uncounted; 1 = draft with counts; 2 = complete exact; 3 = complete with variance
                if ($path === 0) {
                    continue;
                }

                $linesPayload = $count->lines->map(function ($line) use ($path, $i) {
                    $system = (float) $line->system_weight_kg;
                    $counted = match ($path) {
                        1, 2 => $system,
                        3 => max(0, round($system + (($i % 2 === 0) ? -0.5 : 0.75), 3)),
                        default => $system,
                    };

                    return [
                        'id' => $line->id,
                        'counted_weight_kg' => $counted,
                        'notes' => $path === 3 ? 'Scale variance noted' : null,
                    ];
                })->all();

                $counts->updateLines($count, $linesPayload);

                if ($path >= 2) {
                    $counts->completeCount($count->fresh(['lines']), $this->owner, applyVariances: $path === 3);
                }
            } catch (\Throwable $e) {
                $this->command?->warn('Skipped demo stock count #'.$i.': '.$e->getMessage());
            }
        }
    }

    private function seedCutting(): void
    {
        $cutting = app(ButcherCuttingService::class);
        $usableBatches = $this->batches->filter(
            fn (ButcherInventoryBatch $batch) => (float) $batch->remaining_weight_kg >= 15
        )->take(self::MIN_ROWS);

        if ($usableBatches->count() < self::MIN_ROWS) {
            $usableBatches = $this->batches->take(self::MIN_ROWS);
        }

        foreach ($usableBatches->values() as $index => $batch) {
            $batch = $batch->fresh();
            if ((float) $batch->remaining_weight_kg < 15) {
                continue;
            }

            try {
                $session = $cutting->openSession($this->business, [
                    'outlet_id' => $batch->outlet_id,
                    'batch_id' => $batch->id,
                    'source_weight_kg' => min(30, (float) $batch->remaining_weight_kg),
                ]);

                $cutA = $this->cutTypes[$index % $this->cutTypes->count()];
                $cutB = $this->cutTypes[($index + 1) % $this->cutTypes->count()];

                $cutting->addCutOutput($session, ['cut_type_id' => $cutA->id, 'weight_kg' => 25]);
                $cutting->addCutOutput($session, ['cut_type_id' => $cutB->id, 'weight_kg' => 22]);
                $cutting->closeSession($session->fresh());
            } catch (\Throwable) {
                continue;
            }
        }
    }

    private function seedCatalog(): void
    {
        $catalog = app(ButcherCatalogService::class);

        foreach ($this->cutTypes as $index => $cutType) {
            $product = $this->business->butcherProducts()
                ->where('cut_type_id', $cutType->id)
                ->first()
                ?? $catalog->createProduct($this->business, [
                    'name' => 'Fresh '.$cutType->name,
                    'cut_type_id' => $cutType->id,
                    'meat_type' => $cutType->meat_type,
                    'unit' => ButcherProduct::UNIT_PER_KG,
                    'default_price' => 5500 + ($index * 250),
                ]);

            $catalog->setPriceRule($this->business, [
                'product_id' => $product->id,
                'customer_tier' => \App\Models\ButcherPriceRule::TIER_RETAIL,
                'price' => (float) $product->default_price,
                'valid_from' => $this->rangeStart->toDateString(),
                'is_active' => true,
            ]);

            $catalog->updateProduct($product->fresh(), [
                'name' => $product->name,
                'cut_type_id' => $product->cut_type_id,
                'meat_type' => $product->meat_type,
                'unit' => $product->unit,
                'default_price' => $product->default_price,
                'is_active' => true,
            ]);

            $this->products->push($product->fresh());
        }

        for ($i = 0; $i < self::MIN_ROWS; $i++) {
            $product = $this->products[$i % $this->products->count()];
            $outlet = $this->outlets[$i % $this->outlets->count()];

            $catalog->setPriceRule($this->business, [
                'product_id' => $product->id,
                'outlet_id' => $outlet->id,
                'customer_tier' => $i % 3 === 0 ? ButcherCustomer::TIER_WHOLESALE : null,
                'price' => (float) $product->default_price + ($i * 100),
                'valid_from' => $this->rangeStart->copy()->addDays($i)->toDateString(),
                'valid_until' => $i % 4 === 0 ? Carbon::now()->addMonth()->toDateString() : null,
            ]);
        }
    }

    private function ensureSalesStock(): void
    {
        $procurement = app(ButcherProcurementService::class);
        $cutting = app(ButcherCuttingService::class);
        $supplier = $this->suppliers->first();
        $outlet = $this->outlets->first();

        if (! $supplier || ! $outlet) {
            return;
        }

        $delivery = $procurement->receiveDelivery($this->business, [
            'supplier_id' => $supplier->id,
            'outlet_id' => $outlet->id,
            'lines' => [[
                'meat_type' => ButcherDelivery::MEAT_BEEF,
                'received_weight_kg' => 500,
                'unit_cost' => 3400,
                'outcome' => ButcherDeliveryLine::OUTCOME_ACCEPTED,
                'accepted_weight_kg' => 500,
                'rejected_weight_kg' => 0,
                'temperature_c' => 3,
            ]],
        ], $this->owner);

        if (! $delivery->createsInventory()) {
            return;
        }

        $batch = ButcherInventoryBatch::query()->where('delivery_id', $delivery->id)->firstOrFail();
        $session = $cutting->openSession($this->business, [
            'outlet_id' => $outlet->id,
            'batch_id' => $batch->id,
            'source_weight_kg' => 450,
        ]);

        foreach ($this->cutTypes as $cutType) {
            $cutting->addCutOutput($session, [
                'cut_type_id' => $cutType->id,
                'weight_kg' => 20,
            ]);
        }

        $cutting->closeSession($session->fresh());
    }

    private function seedSales(): void
    {
        $sales = app(ButcherSalesService::class);
        $catalog = app(ButcherCatalogService::class);
        $tiers = [ButcherCustomer::TIER_RETAIL, ButcherCustomer::TIER_WHOLESALE, ButcherCustomer::TIER_LOYALTY];

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $customer = ButcherCustomer::query()->firstOrCreate(
                [
                    'business_id' => $this->business->id,
                    'phone' => sprintf('+2507887%05d', 10000 + $i),
                ],
                [
                    'name' => sprintf('Customer %02d — %s', $i, RwandaSeederHelper::fullName(300 + $i)),
                    'tier' => $tiers[($i - 1) % count($tiers)],
                    // Enough headroom for demo sales + order confirmations (RWF).
                    'credit_limit' => $i % 3 === 0 ? 1_500_000 + ($i * 50_000) : 750_000,
                ]
            );
            $this->customers->push($customer);
        }

        $paymentMethods = [
            ButcherSale::PAYMENT_CASH,
            ButcherSale::PAYMENT_MOMO,
            ButcherSale::PAYMENT_CARD,
            ButcherSale::PAYMENT_CREDIT,
        ];

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $product = $this->products[($i - 1) % $this->products->count()];
            // Stock is seeded on the primary outlet via ensureSalesStock().
            $outlet = $this->outlets->first();
            $qty = 1.0 + ($i % 3) * 0.5;
            $saleDate = Carbon::now()->subDays(self::MIN_ROWS - $i)->toDateString();
            $isCredit = $i % 5 === 0;
            $creditCustomer = $this->customers->first(fn (ButcherCustomer $c) => (float) $c->credit_limit > 0);
            $customer = $isCredit ? $creditCustomer : null;
            $unitPrice = $catalog->resolvePrice($product, $outlet->id, $customer?->tier);
            $total = round($unitPrice * $qty, 2);

            try {
                $sales->createSale($this->business, [
                    'outlet_id' => $outlet->id,
                    'customer_id' => $customer?->id,
                    'sale_date' => $saleDate,
                    'payment_method' => $isCredit ? ButcherSale::PAYMENT_CREDIT : $paymentMethods[($i - 1) % 3],
                    'amount_paid' => $isCredit ? 0 : $total,
                    'skip_documents' => true,
                    'items' => [
                        ['product_id' => $product->id, 'quantity_kg' => $qty],
                    ],
                ], $this->owner);
            } catch (\Throwable $e) {
                $this->command?->warn('Skipped demo sale #'.$i.': '.$e->getMessage());
            }
        }

        $fulfillment = app(\App\Services\Butcher\ButcherOrderFulfillmentService::class);

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $customer = $this->customers[($i - 1) % $this->customers->count()];
            $product = $this->products[($i - 1) % $this->products->count()];
            $primaryOutlet = $this->outlets->first();

            // Prefer higher-limit wholesale customers for confirmed+ paths.
            if ($i % 5 !== 4) {
                $customer = $this->customers->first(fn (ButcherCustomer $c) => (float) $c->credit_limit >= 1_000_000)
                    ?? $customer;
            }

            $order = $sales->createOrder($this->business, [
                'customer_id' => $customer->id,
                'outlet_id' => $primaryOutlet->id,
                'order_date' => Carbon::now()->subDays($i)->toDateString(),
                'delivery_date' => Carbon::now()->addDays($i % 7)->toDateString(),
                'deposit_paid' => $i % 2 === 0 ? 5000 : 0,
                'items' => [
                    ['product_id' => $product->id, 'quantity_kg' => 2 + ($i % 3)],
                ],
            ]);

            $path = $i % 5;
            try {
                if ($path === 0) {
                    // leave pending
                } elseif ($path === 1) {
                    $sales->updateOrderStatus($order, ButcherOrder::STATUS_CONFIRMED);
                } elseif ($path === 2) {
                    $sales->updateOrderStatus($order, ButcherOrder::STATUS_CONFIRMED);
                    $sales->updateOrderStatus($order->fresh(), ButcherOrder::STATUS_READY);
                } elseif ($path === 3) {
                    $sales->updateOrderStatus($order, ButcherOrder::STATUS_CONFIRMED);
                    $sales->updateOrderStatus($order->fresh(), ButcherOrder::STATUS_READY);
                    $fulfillment->fulfill($order->fresh(), [
                        'outlet_id' => $primaryOutlet->id,
                        'payment_method' => ButcherSale::PAYMENT_CASH,
                        'amount_paid' => (float) $order->fresh()->total_amount,
                        'skip_documents' => true,
                    ], $this->owner);
                } else {
                    $sales->updateOrderStatus($order, ButcherOrder::STATUS_CANCELLED);
                }
            } catch (\Throwable $e) {
                $this->command?->warn('Skipped demo order #'.$i.': '.$e->getMessage());
            }
        }
    }

    private function seedCompliance(): void
    {
        $compliance = app(ButcherComplianceService::class);

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $outlet = $this->outlets[($i - 1) % $this->outlets->count()];
            $date = Carbon::now()->subDays($i)->toDateString();

            if ($this->business->butcherHygieneLogs()
                ->where('outlet_id', $outlet->id)
                ->whereDate('log_date', $date)
                ->exists()) {
                continue;
            }

            $checklist = array_fill_keys(
                array_keys(ButcherHygieneLog::DEFAULT_CHECKLIST),
                $i % 4 !== 0
            );

            $compliance->logHygiene($this->business, [
                'outlet_id' => $outlet->id,
                'log_date' => $date,
                'checklist' => $checklist,
                'issues_found' => $i % 4 === 0 ? 'Minor cold chain deviation noted.' : null,
                'corrective_action' => $i % 4 === 0 ? 'Thermometer recalibrated.' : null,
            ], $this->owner);
        }

        $equipment = ['Band saw', 'Mincer', 'Cutting block', 'Display fridge', 'Scale', 'Slicer', 'Grinder', 'Tables', 'Knife rack', 'Sink'];
        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $compliance->logSanitation($this->business, [
                'outlet_id' => $this->outlets[($i - 1) % $this->outlets->count()]->id,
                'equipment_name' => $equipment[($i - 1) % count($equipment)].' #'.ceil($i / count($equipment)),
                'cleaning_type' => ButcherSanitationRecord::CLEANING_TYPES[($i - 1) % count(ButcherSanitationRecord::CLEANING_TYPES)],
                'chemical_used' => 'Food-safe sanitizer',
                'performed_at' => Carbon::now()->subDays($i)->setTime(8 + ($i % 6), 0)->format('Y-m-d H:i:s'),
                'next_due_at' => Carbon::now()->addDays($i % 7)->format('Y-m-d H:i:s'),
            ], $this->owner);
        }

        $this->staffUsers->push($this->owner);
        for ($i = 1; $i < self::MIN_ROWS; $i++) {
            $user = User::query()->updateOrCreate(
                ['email' => sprintf('staff.butcher.%02d@demo.rw', $i)],
                [
                    'name' => 'Staff '.sprintf('%02d', $i).' — '.RwandaSeederHelper::fullName(400 + $i),
                    'password' => self::PASSWORD,
                    'email_verified_at' => now(),
                    'is_super_admin' => false,
                ]
            );
            BusinessUser::query()->updateOrCreate(
                ['business_id' => $this->business->id, 'user_id' => $user->id],
                ['role' => BusinessUser::ROLE_BUTCHER_MANAGER]
            );
            $this->staffUsers->push($user);
        }

        foreach ($this->staffUsers->take(self::MIN_ROWS) as $index => $user) {
            $compliance->upsertStaffHealth($this->business, [
                'user_id' => $user->id,
                'medical_card_number' => sprintf('MED-BU-%03d', $index + 1),
                'issued_date' => Carbon::now()->subYear()->addDays($index)->toDateString(),
                'expiry_date' => Carbon::now()->addDays(10 + ($index * 5))->toDateString(),
                'health_status' => $index % 6 === 0
                    ? ButcherStaffHealthRecord::STATUS_RESTRICTED
                    : ButcherStaffHealthRecord::STATUS_FIT,
            ]);
        }
    }

    private function seedFinance(): void
    {
        $finance = app(ButcherFinanceService::class);
        $categories = ButcherExpense::CATEGORIES;
        $payments = ButcherExpense::PAYMENT_METHODS;

        for ($i = 1; $i <= self::MIN_ROWS; $i++) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $category = $categories[($i - 1) % count($categories)];

            $finance->logExpense($this->business, [
                'outlet_id' => $i % 3 === 0 ? $this->outlets[($i - 1) % $this->outlets->count()]->id : null,
                'category' => $category,
                'description' => sprintf('Demo expense %02d — %s', $i, str_replace('_', ' ', $category)),
                'amount' => 25000 + ($i * 8500),
                'expense_date' => $date,
                'payment_method' => $payments[($i - 1) % count($payments)],
            ], $this->owner);
        }
    }

    private function purgeButcherModuleData(): void
    {
        $businessId = $this->business->id;

        $deleteIfExists = static function (string $table, \Closure $callback): void {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                return;
            }
            $callback(DB::table($table));
        };

        $deleteIfExists('butcher_returns', function ($q) use ($businessId) {
            $q->whereIn('sale_item_id', function ($sub) use ($businessId) {
                $sub->select('butcher_sale_items.id')
                    ->from('butcher_sale_items')
                    ->join('butcher_sales', 'butcher_sales.id', '=', 'butcher_sale_items.sale_id')
                    ->where('butcher_sales.business_id', $businessId);
            })->delete();
        });

        $deleteIfExists('butcher_compliance_overrides', fn ($q) => $q->where('business_id', $businessId)->delete());
        $deleteIfExists('butcher_inventory_movements', fn ($q) => $q->where('business_id', $businessId)->delete());

        $deleteIfExists('butcher_stock_count_lines', function ($q) use ($businessId) {
            $q->whereIn('stock_count_id', function ($sub) use ($businessId) {
                $sub->select('id')->from('butcher_stock_counts')->where('business_id', $businessId);
            })->delete();
        });
        if (method_exists($this->business, 'butcherStockCounts')) {
            $this->business->butcherStockCounts()->delete();
        }
        if (method_exists($this->business, 'butcherStockTransfers')) {
            $this->business->butcherStockTransfers()->delete();
        }
        if (method_exists($this->business, 'butcherInventoryAdjustments')) {
            $this->business->butcherInventoryAdjustments()->delete();
        }

        $deleteIfExists('butcher_cutting_session_sources', function ($q) use ($businessId) {
            $q->whereIn('session_id', function ($sub) use ($businessId) {
                $sub->select('id')->from('butcher_cutting_sessions')->where('business_id', $businessId);
            })->delete();
        });

        DB::table('butcher_sale_items')->whereIn('sale_id', function ($q) use ($businessId) {
            $q->select('id')->from('butcher_sales')->where('business_id', $businessId);
        })->delete();
        DB::table('butcher_sale_payments')->whereIn('sale_id', function ($q) use ($businessId) {
            $q->select('id')->from('butcher_sales')->where('business_id', $businessId);
        })->delete();
        $this->business->butcherSales()->delete();
        DB::table('butcher_order_items')->whereIn('order_id', function ($q) use ($businessId) {
            $q->select('id')->from('butcher_orders')->where('business_id', $businessId);
        })->delete();
        $this->business->butcherOrders()->delete();
        $this->business->butcherCustomers()->delete();
        DB::table('butcher_cut_outputs')->where('business_id', $businessId)->delete();
        $this->business->butcherCuttingSessions()->delete();
        $this->business->butcherPriceRules()->delete();
        $this->business->butcherProducts()->delete();
        $this->business->butcherDisposalLogs()->delete();
        $this->business->butcherTemperatureLogs()->delete();
        $this->business->butcherInventoryBatches()->delete();
        $deleteIfExists('butcher_delivery_lines', function ($q) use ($businessId) {
            $q->whereIn('delivery_id', function ($sub) use ($businessId) {
                $sub->select('id')->from('butcher_deliveries')->where('business_id', $businessId);
            })->delete();
        });
        DB::table('butcher_delivery_rejections')->whereIn('delivery_id', function ($q) use ($businessId) {
            $q->select('id')->from('butcher_deliveries')->where('business_id', $businessId);
        })->delete();
        $this->business->butcherDeliveries()->delete();
        $this->business->butcherPurchaseOrders()->delete();
        $this->business->butcherExpenses()->delete();
        $this->business->butcherHygieneLogs()->delete();
        $this->business->butcherSanitationRecords()->delete();
        $this->business->butcherStaffHealthRecords()->delete();
        $this->business->butcherPermits()->delete();
        $this->business->butcherSuppliers()->delete();
        $this->business->butcherCutTypes()->delete();
        $this->business->butcherOutlets()->delete();
    }

    private function printSummary(): void
    {
        $counts = [
            'outlets' => $this->business->butcherOutlets()->count(),
            'suppliers' => $this->business->butcherSuppliers()->count(),
            'permits' => $this->business->butcherPermits()->count(),
            'purchase_orders' => $this->business->butcherPurchaseOrders()->count(),
            'deliveries' => $this->business->butcherDeliveries()->count(),
            'batches' => $this->business->butcherInventoryBatches()->count(),
            'stock_transfers' => $this->business->butcherStockTransfers()->count(),
            'stock_counts' => $this->business->butcherStockCounts()->count(),
            'temperature_logs' => $this->business->butcherTemperatureLogs()->count(),
            'cutting_sessions' => $this->business->butcherCuttingSessions()->count(),
            'cut_outputs' => \App\Models\ButcherCutOutput::query()->where('business_id', $this->business->id)->count(),
            'products' => $this->business->butcherProducts()->count(),
            'price_rules' => $this->business->butcherPriceRules()->count(),
            'customers' => $this->business->butcherCustomers()->count(),
            'sales' => $this->business->butcherSales()->where('status', ButcherSale::STATUS_COMPLETED)->count(),
            'orders' => $this->business->butcherOrders()->count(),
            'hygiene_logs' => $this->business->butcherHygieneLogs()->count(),
            'sanitation_records' => $this->business->butcherSanitationRecords()->count(),
            'staff_health' => $this->business->butcherStaffHealthRecords()->count(),
            'expenses' => $this->business->butcherExpenses()->count(),
        ];

        $this->command?->info('Butcher workspace demo ready: '.$this->business->business_name);
        foreach ($counts as $label => $count) {
            $this->command?->line(sprintf('  %-20s %d', $label.':', $count));
        }
        $this->command?->info('Login: '.self::OWNER_EMAIL.' — password: '.self::PASSWORD);
    }
}
