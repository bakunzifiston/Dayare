<?php

namespace App\Http\Controllers;

use App\Models\AnimalIntake;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterPlan;
use App\Models\TemperatureLog;
use App\Models\TransportTrip;
use App\Models\WarehouseStorage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Compliance Monitoring – system auto-control and alerts.
 * Tracks: expired licenses, expired authorizations, over capacity, missing inspections/certificates/transport, etc.
 */
class ComplianceController extends Controller
{
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
    }

    public function index(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $today = Carbon::today();

        // Expired facility licenses
        $expiredFacilityLicenses = Facility::whereIn('id', $facilityIds)
            ->whereNotNull('license_expiry_date')
            ->where('license_expiry_date', '<', $today)
            ->orderBy('license_expiry_date')
            ->get();

        // Expired inspector authorizations (assigned to user's facilities)
        $expiredInspectorAuthorizations = Inspector::whereIn('facility_id', $facilityIds)
            ->where(function ($q) use ($today) {
                $q->where('status', 'expired')
                    ->orWhere(function ($q2) use ($today) {
                        $q2->whereNotNull('authorization_expiry_date')
                            ->where('authorization_expiry_date', '<', $today);
                    });
            })
            ->orderBy('authorization_expiry_date')
            ->get();

        // Over capacity scheduling: plans where scheduled > facility daily_capacity
        $overCapacityPlans = SlaughterPlan::with('facility')
            ->whereIn('facility_id', $facilityIds)
            ->whereHas('facility', fn ($q) => $q->whereNotNull('daily_capacity')->where('daily_capacity', '>', 0))
            ->get()
            ->filter(fn (SlaughterPlan $p) => $p->facility && $p->number_of_animals_scheduled > (int) $p->facility->daily_capacity)
            ->values();

        // Missing ante-mortem: slaughter plans without any ante-mortem inspection
        $missingAnteMortemPlans = SlaughterPlan::with('facility')
            ->whereIn('facility_id', $facilityIds)
            ->whereDoesntHave('anteMortemInspections')
            ->orderByDesc('slaughter_date')
            ->get();

        // Missing post-mortem: batches without post-mortem inspection
        $userBatchIds = Batch::whereIn('slaughter_execution_id',
            \App\Models\SlaughterExecution::whereIn('slaughter_plan_id',
                SlaughterPlan::whereIn('facility_id', $facilityIds)->pluck('id')
            )->pluck('id')
        )->pluck('id');
        $missingPostMortemBatches = Batch::with('slaughterExecution.slaughterPlan.facility')
            ->whereIn('id', $userBatchIds)
            ->whereDoesntHave('postMortemInspection')
            ->latest()
            ->get();

        // Missing certificates: batches that can have one (post-mortem approved > 0) but don't
        $missingCertificateBatches = Batch::with(['postMortemInspection', 'slaughterExecution.slaughterPlan.facility'])
            ->whereIn('id', $userBatchIds)
            ->whereHas('postMortemInspection', fn ($q) => $q->where('approved_quantity', '>', 0))
            ->whereDoesntHave('certificate')
            ->latest()
            ->get();

        // Missing transport: certificates with no transport trip
        $userCertificateIds = Certificate::where(function ($q) use ($userBatchIds, $facilityIds) {
            $q->whereIn('batch_id', $userBatchIds)
                ->orWhere(fn ($q2) => $q2->whereNull('batch_id')->whereIn('facility_id', $facilityIds));
        })->pluck('id');
        $missingTransportCertificates = Certificate::with('batch', 'facility')
            ->whereIn('id', $userCertificateIds)
            ->whereDoesntHave('transportTrips')
            ->latest('issued_at')
            ->get();

        // Warehouse: temperature alerts (warning/critical)
        $userWarehouseStorageIds = WarehouseStorage::whereIn('certificate_id', $userCertificateIds)->pluck('id');
        $temperatureAlerts = TemperatureLog::with('warehouseStorage.batch')
            ->whereIn('warehouse_storage_id', $userWarehouseStorageIds)
            ->whereIn('status', [TemperatureLog::STATUS_WARNING, TemperatureLog::STATUS_CRITICAL])
            ->latest('recorded_at')
            ->get();

        // Warehouse: storage duration exceeded (e.g. > 30 days in storage)
        $maxStorageDays = (int) config('warehouse.max_storage_days', 30);
        $storageDurationExceeded = WarehouseStorage::with(['warehouseFacility', 'batch'])
            ->whereIn('certificate_id', $userCertificateIds)
            ->where('status', WarehouseStorage::STATUS_IN_STORAGE)
            ->get()
            ->filter(fn (WarehouseStorage $ws) => $ws->entry_date->diffInDays(Carbon::today()) > $maxStorageDays)
            ->values();

        // Animal intakes with expired health certificate (block slaughter)
        $intakesWithExpiredHealthCert = AnimalIntake::with('facility')
            ->whereIn('facility_id', $facilityIds)
            ->where('status', AnimalIntake::STATUS_APPROVED)
            ->get()
            ->filter(fn (AnimalIntake $i) => $i->isHealthCertificateExpired())
            ->values();

        $kpis = [
            'expired_licenses' => $expiredFacilityLicenses->count(),
            'expired_authorizations' => $expiredInspectorAuthorizations->count(),
            'over_capacity_plans' => $overCapacityPlans->count(),
            'missing_ante_mortem' => $missingAnteMortemPlans->count(),
            'missing_post_mortem' => $missingPostMortemBatches->count(),
            'missing_certificates' => $missingCertificateBatches->count(),
            'missing_transport' => $missingTransportCertificates->count(),
            'temperature_alerts' => $temperatureAlerts->count(),
            'storage_duration_exceeded' => $storageDurationExceeded->count(),
            'intakes_expired_health_cert' => $intakesWithExpiredHealthCert->count(),
            'total_issues' => $expiredFacilityLicenses->count() + $expiredInspectorAuthorizations->count()
                + $overCapacityPlans->count() + $missingAnteMortemPlans->count()
                + $missingPostMortemBatches->count() + $missingCertificateBatches->count()
                + $missingTransportCertificates->count()
                + $temperatureAlerts->count() + $storageDurationExceeded->count()
                + $intakesWithExpiredHealthCert->count(),
        ];

        return view('compliance.index', [
            'expiredFacilityLicenses' => $expiredFacilityLicenses,
            'expiredInspectorAuthorizations' => $expiredInspectorAuthorizations,
            'overCapacityPlans' => $overCapacityPlans,
            'missingAnteMortemPlans' => $missingAnteMortemPlans,
            'missingPostMortemBatches' => $missingPostMortemBatches,
            'missingCertificateBatches' => $missingCertificateBatches,
            'missingTransportCertificates' => $missingTransportCertificates,
            'temperatureAlerts' => $temperatureAlerts,
            'storageDurationExceeded' => $storageDurationExceeded,
            'intakesWithExpiredHealthCert' => $intakesWithExpiredHealthCert,
            'kpis' => $kpis,
        ]);
    }
}
