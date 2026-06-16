<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\AnteMortemInspectionItem;
use App\Models\AnteMortemObservation;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\ColdRoom;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\FinanceCostAllocation;
use App\Models\FinanceInvoice;
use App\Models\FinancePayable;
use App\Models\Inspector;
use App\Models\MeatExportDocument;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\PostMortemObservation;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\Supplier;
use App\Models\TemperatureLog;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\WarehouseStorage;
use Illuminate\Support\Facades\DB;

/**
 * Removes all data created by {@see ProcessorWorkspaceSeedBuilder} (PWS-RDB-* businesses).
 */
class ProcessorWorkspacePurge
{
    public static function run(): int
    {
        $businessIds = Business::query()
            ->where('registration_number', 'like', ProcessorWorkspaceSeedBuilder::REG_PREFIX.'%')
            ->pluck('id');

        if ($businessIds->isEmpty()) {
            return 0;
        }

        $facilityIds = Facility::query()->whereIn('business_id', $businessIds)->pluck('id');
        $intakeIds = AnimalIntake::query()->whereIn('facility_id', $facilityIds)->pluck('id');
        $planIds = SlaughterPlan::query()->whereIn('facility_id', $facilityIds)->pluck('id');
        $executionIds = SlaughterExecution::query()->whereIn('slaughter_plan_id', $planIds)->pluck('id');
        $batchIds = Batch::query()->whereIn('slaughter_execution_id', $executionIds)->pluck('id');
        $certIds = Certificate::query()
            ->where('certificate_number', 'like', ProcessorWorkspaceSeedBuilder::CERT_NUMBER_PREFIX.'%')
            ->pluck('id');
        $tripIds = TransportTrip::query()->whereIn('certificate_id', $certIds)->pluck('id');
        $confirmationIds = DeliveryConfirmation::query()->whereIn('transport_trip_id', $tripIds)->pluck('id');
        $storageIds = WarehouseStorage::query()->whereIn('certificate_id', $certIds)->pluck('id');
        $anteIds = AnteMortemInspection::query()->whereIn('slaughter_plan_id', $planIds)->pluck('id');
        $pmIds = PostMortemInspection::query()->whereIn('batch_id', $batchIds)->pluck('id');

        DB::transaction(function () use (
            $businessIds,
            $facilityIds,
            $intakeIds,
            $planIds,
            $executionIds,
            $batchIds,
            $certIds,
            $tripIds,
            $confirmationIds,
            $storageIds,
            $anteIds,
            $pmIds,
        ): void {
            MeatExportDocument::query()->whereIn('delivery_confirmation_id', $confirmationIds)->delete();
            DeliveryConfirmation::query()->whereIn('id', $confirmationIds)->delete();
            TransportTrip::query()->whereIn('id', $tripIds)->delete();
            TemperatureLog::query()->whereIn('warehouse_storage_id', $storageIds)->delete();
            WarehouseStorage::query()->whereIn('id', $storageIds)->delete();

            FinanceInvoice::query()->whereIn('business_id', $businessIds)->delete();
            FinancePayable::query()->whereIn('business_id', $businessIds)->delete();
            FinanceCostAllocation::query()->whereIn('business_id', $businessIds)->delete();

            Certificate::query()->whereIn('id', $certIds)->delete();

            PostMortemObservation::query()->whereIn('post_mortem_inspection_id', $pmIds)->delete();
            PostMortemInspectionItem::query()->whereIn('post_mortem_inspection_id', $pmIds)->delete();
            PostMortemInspection::query()->whereIn('id', $pmIds)->delete();
            BatchItem::query()->whereIn('batch_id', $batchIds)->delete();
            Batch::query()->whereIn('id', $batchIds)->delete();
            SlaughterExecutionItem::query()->whereIn('slaughter_execution_id', $executionIds)->delete();
            SlaughterExecution::query()->whereIn('id', $executionIds)->delete();

            AnteMortemObservation::query()->whereIn('ante_mortem_inspection_id', $anteIds)->delete();
            AnteMortemInspectionItem::query()->whereIn('ante_mortem_inspection_id', $anteIds)->delete();
            AnteMortemInspection::query()->whereIn('id', $anteIds)->delete();
            SlaughterPlan::query()->whereIn('id', $planIds)->delete();

            AnimalIntakeItem::query()->whereIn('animal_intake_id', $intakeIds)->delete();
            AnimalIntake::query()->whereIn('id', $intakeIds)->delete();

            ClientActivity::query()->whereIn('business_id', $businessIds)->delete();
            Demand::query()->whereIn('business_id', $businessIds)->delete();
            Contract::query()->whereIn('business_id', $businessIds)->delete();
            Employee::query()->whereIn('business_id', $businessIds)->delete();
            Client::query()->whereIn('business_id', $businessIds)->delete();
            Supplier::query()->whereIn('business_id', $businessIds)->delete();
            ColdRoom::query()->whereIn('facility_id', $facilityIds)->delete();
            Inspector::query()->whereIn('facility_id', $facilityIds)->delete();
            Facility::query()->whereIn('id', $facilityIds)->delete();

            BusinessUser::query()->whereIn('business_id', $businessIds)->delete();
            Business::query()->whereIn('id', $businessIds)->delete();

            User::query()
                ->where(function ($q): void {
                    $q->where('email', 'like', 'owner.pws.%@processor.rw')
                        ->orWhere('email', 'like', 'team.pws.%@processor.rw');
                })
                ->delete();
        });

        return $businessIds->count();
    }
}
