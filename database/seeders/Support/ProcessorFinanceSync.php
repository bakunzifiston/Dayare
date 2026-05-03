<?php

namespace Database\Seeders\Support;

use App\Models\AnimalIntake;
use App\Models\Batch;
use App\Models\Business;
use App\Models\DeliveryConfirmation;
use App\Models\FinanceCostAllocation;
use App\Models\FinanceInvoice;
use App\Models\FinanceInvoiceLine;
use App\Models\FinancePayable;
use App\Models\FinancePayableLine;
use App\Models\SlaughterExecution;
use App\Models\TransportTrip;
use Illuminate\Support\Collection;

class ProcessorFinanceSync
{
    /**
     * Build finance data from existing processor workflow records.
     *
     * @param  Collection<int, int>|null  $businessIds
     */
    public static function sync(?Collection $businessIds = null): void
    {
        $processorBusinessIds = $businessIds
            ? $businessIds->map(fn ($id) => (int) $id)->unique()->values()
            : Business::query()
                ->where('type', Business::TYPE_PROCESSOR)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values();

        foreach ($processorBusinessIds as $businessId) {
            self::syncPayablesForBusiness($businessId);
            self::syncInvoicesForBusiness($businessId);
            self::syncCostAllocationsForBusiness($businessId);
        }
    }

    private static function syncPayablesForBusiness(int $businessId): void
    {
        $intakes = AnimalIntake::query()
            ->whereHas('facility', fn ($q) => $q->where('business_id', $businessId))
            ->get();

        foreach ($intakes as $intake) {
            $computedTotal = (float) ($intake->total_price ?? 0);
            if ($computedTotal <= 0) {
                $computedTotal = (float) ($intake->number_of_animals ?? 0) * (float) ($intake->unit_price ?? 0);
            }
            $computedTotal = round(max(0, $computedTotal), 2);

            $payable = FinancePayable::query()->updateOrCreate(
                [
                    'business_id' => $businessId,
                    'animal_intake_id' => $intake->id,
                ],
                [
                    'supplier_id' => $intake->source_type === AnimalIntake::SOURCE_TYPE_SUPPLIER ? $intake->supplier_id : null,
                    'client_id' => $intake->source_type === AnimalIntake::SOURCE_TYPE_CLIENT ? $intake->client_id : null,
                    'contract_id' => $intake->contract_id,
                    'payable_number' => sprintf('AP-INTAKE-%06d', $intake->id),
                    'status' => FinancePayable::query()->where('business_id', $businessId)->where('animal_intake_id', $intake->id)->value('status') ?? 'open',
                    'currency' => 'RWF',
                    'subtotal' => $computedTotal,
                    'tax_amount' => 0,
                    'total_amount' => $computedTotal,
                    'amount_paid' => min((float) (FinancePayable::query()->where('business_id', $businessId)->where('animal_intake_id', $intake->id)->value('amount_paid') ?? 0), $computedTotal),
                    'issued_at' => $intake->intake_date ? $intake->intake_date->copy()->startOfDay() : now(),
                    'due_date' => $intake->intake_date ? $intake->intake_date->copy()->addDays(14)->endOfDay() : now()->addDays(14),
                    'notes' => 'Derived from animal intake #'.$intake->id,
                ]
            );

            $batchId = Batch::query()
                ->whereHas('slaughterExecution.slaughterPlan', fn ($q) => $q->where('animal_intake_id', $intake->id))
                ->value('id');

            FinancePayableLine::query()->updateOrCreate(
                [
                    'payable_id' => $payable->id,
                    'description' => 'Animal intake #'.$intake->id,
                ],
                [
                    'batch_id' => $batchId,
                    'quantity' => max(1, (float) ($intake->number_of_animals ?? 0)),
                    'unit_price' => (float) ($intake->unit_price ?? 0),
                    'line_total' => $computedTotal,
                ]
            );
        }
    }

    private static function syncInvoicesForBusiness(int $businessId): void
    {
        $deliveries = DeliveryConfirmation::query()
            ->with(['transportTrip.batch.slaughterExecution.slaughterPlan.animalIntake'])
            ->whereHas('transportTrip.originFacility', fn ($q) => $q->where('business_id', $businessId))
            ->get();

        foreach ($deliveries as $delivery) {
            $trip = $delivery->transportTrip;
            $batch = $trip?->batch;
            $intakeUnitPrice = (float) ($batch?->slaughterExecution?->slaughterPlan?->animalIntake?->unit_price ?? 0);
            $quantity = (float) ($delivery->received_quantity ?? $batch?->quantity ?? 0);
            $quantity = max(0, $quantity);
            $invoiceUnitPrice = $intakeUnitPrice > 0 ? round($intakeUnitPrice * 1.25, 2) : 0.0;
            $totalAmount = round($quantity * $invoiceUnitPrice, 2);

            $invoice = FinanceInvoice::query()->updateOrCreate(
                [
                    'business_id' => $businessId,
                    'delivery_confirmation_id' => $delivery->id,
                ],
                [
                    'client_id' => $delivery->client_id,
                    'contract_id' => $delivery->contract_id,
                    'invoice_number' => sprintf('AR-DEL-%06d', $delivery->id),
                    'status' => FinanceInvoice::query()->where('business_id', $businessId)->where('delivery_confirmation_id', $delivery->id)->value('status') ?? 'issued',
                    'currency' => 'RWF',
                    'subtotal' => $totalAmount,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_amount' => $totalAmount,
                    'amount_paid' => min((float) (FinanceInvoice::query()->where('business_id', $businessId)->where('delivery_confirmation_id', $delivery->id)->value('amount_paid') ?? 0), $totalAmount),
                    'issued_at' => $delivery->received_date ? $delivery->received_date->copy()->startOfDay() : now(),
                    'due_date' => $delivery->received_date ? $delivery->received_date->copy()->addDays(14)->endOfDay() : now()->addDays(14),
                    'notes' => 'Derived from delivery confirmation #'.$delivery->id,
                ]
            );

            FinanceInvoiceLine::query()->updateOrCreate(
                [
                    'invoice_id' => $invoice->id,
                    'description' => 'Delivery #'.$delivery->id,
                ],
                [
                    'batch_id' => $trip?->batch_id,
                    'certificate_id' => $trip?->certificate_id,
                    'quantity' => $quantity,
                    'unit_price' => $invoiceUnitPrice,
                    'line_total' => $totalAmount,
                ]
            );
        }
    }

    private static function syncCostAllocationsForBusiness(int $businessId): void
    {
        $batches = Batch::query()
            ->with(['slaughterExecution', 'transportTrips'])
            ->whereHas('slaughterExecution.slaughterPlan.facility', fn ($q) => $q->where('business_id', $businessId))
            ->get();

        foreach ($batches as $batch) {
            $execution = $batch->slaughterExecution;
            $baseDate = $execution?->slaughter_time?->toDateString() ?? now()->toDateString();

            $laborAnimals = (float) ($execution?->actual_animals_slaughtered ?? 0);
            $laborAmount = round(max(0, $laborAnimals * 1500), 2);
            if ($laborAmount > 0) {
                FinanceCostAllocation::query()->updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'batch_id' => $batch->id,
                        'category' => 'labor',
                        'allocation_date' => $baseDate,
                        'source_type' => SlaughterExecution::class,
                        'source_id' => $execution?->id,
                    ],
                    [
                        'created_by' => null,
                        'amount' => $laborAmount,
                        'notes' => 'Derived labor allocation from slaughter execution',
                    ]
                );
            }

            $tripCount = (int) $batch->transportTrips->count();
            $transportAmount = round(max(0, $tripCount * 25000), 2);
            if ($transportAmount > 0) {
                $firstTripId = $batch->transportTrips->first()?->id;
                FinanceCostAllocation::query()->updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'batch_id' => $batch->id,
                        'category' => 'logistics',
                        'allocation_date' => $baseDate,
                        'source_type' => TransportTrip::class,
                        'source_id' => $firstTripId,
                    ],
                    [
                        'created_by' => null,
                        'amount' => $transportAmount,
                        'notes' => 'Derived transport allocation from linked trips',
                    ]
                );
            }
        }
    }
}
