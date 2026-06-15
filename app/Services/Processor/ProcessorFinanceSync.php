<?php

namespace App\Services\Processor;

use App\Models\AnimalIntake;
use App\Models\Batch;
use App\Models\FinancePayable;
use App\Models\FinancePayableLine;

class ProcessorFinanceSync
{
    public static function syncIntakePayable(AnimalIntake $intake): void
    {
        $intake->loadMissing(['facility', 'items']);

        $businessId = (int) $intake->facility?->business_id;
        if ($businessId <= 0) {
            return;
        }

        $computedTotal = round(max(0, (float) $intake->total_price), 2);
        if ($computedTotal <= 0) {
            $computedTotal = round(max(0, (float) ($intake->number_of_animals ?? 0) * (float) ($intake->unit_price ?? 0)), 2);
        }

        $payable = FinancePayable::query()->updateOrCreate(
            [
                'business_id' => $businessId,
                'animal_intake_id' => $intake->id,
            ],
            [
                'ap_bucket' => $intake->source_type === AnimalIntake::SOURCE_TYPE_SUPPLIER
                    ? FinancePayable::BUCKET_SUPPLIER
                    : FinancePayable::BUCKET_CLIENT,
                'supplier_id' => $intake->source_type === AnimalIntake::SOURCE_TYPE_SUPPLIER ? $intake->supplier_id : null,
                'client_id' => $intake->source_type === AnimalIntake::SOURCE_TYPE_CLIENT ? $intake->client_id : null,
                'employee_id' => null,
                'casual_worker_id' => null,
                'contract_id' => $intake->contract_id,
                'payable_number' => sprintf('AP-INTAKE-%06d', $intake->id),
                'status' => FinancePayable::query()
                    ->where('business_id', $businessId)
                    ->where('animal_intake_id', $intake->id)
                    ->value('status') ?? 'open',
                'currency' => 'RWF',
                'subtotal' => $computedTotal,
                'tax_amount' => 0,
                'total_amount' => $computedTotal,
                'amount_paid' => min(
                    (float) (FinancePayable::query()
                        ->where('business_id', $businessId)
                        ->where('animal_intake_id', $intake->id)
                        ->value('amount_paid') ?? 0),
                    $computedTotal,
                ),
                'issued_at' => $intake->intake_date ? $intake->intake_date->copy()->startOfDay() : now(),
                'due_date' => $intake->intake_date ? $intake->intake_date->copy()->addDays(14)->endOfDay() : now()->addDays(14),
                'notes' => 'Derived from animal intake #'.$intake->id,
            ],
        );

        $batchId = Batch::query()
            ->whereHas('slaughterExecution.slaughterPlan', fn ($q) => $q->where('animal_intake_id', $intake->id))
            ->value('id');
        $batch = $batchId !== null
            ? Batch::query()->with('certificate')->find($batchId)
            : null;

        $lineUnitPrice = (float) ($intake->unit_price ?? 0);
        if ($lineUnitPrice <= 0 && $intake->number_of_animals > 0) {
            $lineUnitPrice = round($computedTotal / max(1, $intake->number_of_animals), 2);
        }

        FinancePayableLine::query()->updateOrCreate(
            [
                'payable_id' => $payable->id,
                'description' => 'Animal intake #'.$intake->id,
            ],
            [
                'batch_id' => $batchId,
                'certificate_id' => $batch?->certificate?->id,
                'quantity' => max(1, (float) ($intake->number_of_animals ?? 0)),
                'quantity_unit' => $batch?->quantity_unit,
                'unit_price' => $lineUnitPrice,
                'line_total' => $computedTotal,
            ],
        );
    }
}
