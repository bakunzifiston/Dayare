<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeDivision;
use App\Models\AnimalIntake;
use App\Models\AnteMortemInspection;
use App\Models\Batch;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DeliveryConfirmation;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\Supplier;
use App\Models\TemperatureLog;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\WarehouseStorage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperAdminDashboardController extends Controller
{
    /** Max storage days before "stored beyond allowed time" alert (configurable). */
    private const MAX_STORAGE_DAYS = 30;

    /** Supplier contract "expiring soon" within days. */
    private const CONTRACT_EXPIRING_DAYS = 30;

    public function index(Request $request): View
    {
        $platformKpis = $this->platformKpis();

        $compliance = $this->complianceAlerts();

        $demandTrends = $this->demandTrends();
        $charts = [
            'slaughter_activity' => $this->chartSlaughterActivity(),
            'species_distribution' => $this->chartSpeciesDistribution(),
            'demand_vs_supply' => $this->chartDemandVsSupply(),
            'deliveries_by_region' => $this->chartDeliveriesByRegion(),
            'demand_trends' => [
                'labels' => $demandTrends['labels'],
                'datasets' => [['label' => __('Demand requests'), 'data' => $demandTrends['data']]],
                'type' => 'bar',
            ],
        ];

        $crmInsights = [
            'top_suppliers' => $this->topSuppliersByVolume(),
            'supplier_rejection_rate' => $this->supplierRejectionRate(),
            'top_customers' => $this->topCustomersByVolume(),
        ];

        $allUsers = User::withCount('businesses')
            ->with(['businesses:id,user_id,type'])
            ->orderBy('name')
            ->get();
        $allBusinesses = Business::with(['user', 'facilities'])->orderBy('business_name')->get();

        return view('super-admin.dashboard', compact(
            'platformKpis',
            'compliance',
            'charts',
            'crmInsights',
            'allUsers',
            'allBusinesses'
        ));
    }

    private function platformKpis(): array
    {
        return [
            'businesses' => Business::count(),
            'facilities' => Facility::count(),
            'users' => User::count(),
            'inspectors' => Inspector::count(),
        ];
    }

    private function complianceAlerts(): array
    {
        $today = now()->toDateString();
        $expiringSoon = now()->addDays(self::CONTRACT_EXPIRING_DAYS)->toDateString();

        $facilitiesExpiredLicense = Facility::whereNotNull('license_expiry_date')
            ->where('license_expiry_date', '<', $today)
            ->count();

        $inspectorsExpiredAuth = Inspector::whereNotNull('authorization_expiry_date')
            ->where('authorization_expiry_date', '<', $today)
            ->count();

        $employeeContractsExpired = Contract::where('contract_category', Contract::CATEGORY_EMPLOYEE)
            ->whereNotNull('end_date')
            ->where('end_date', '<', $today)
            ->count();

        $supplierContractsExpiringSoon = Contract::where('contract_category', Contract::CATEGORY_SUPPLIER)
            ->where('status', Contract::STATUS_ACTIVE)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today, $expiringSoon])
            ->count();

        $planIdsWithAnteMortem = AnteMortemInspection::pluck('slaughter_plan_id')->unique()->filter();
        $sessionsWithoutAnteMortem = SlaughterExecution::whereNotIn('slaughter_plan_id', $planIdsWithAnteMortem)->count();

        $batchIdsWithPostMortem = PostMortemInspection::pluck('batch_id')->unique()->filter();
        $batchesWithoutPostMortem = Batch::whereNotIn('id', $batchIdsWithPostMortem)->count();

        $batchIdsWithCertificate = Certificate::pluck('batch_id')->unique()->filter();
        $batchesWithoutCertificate = Batch::whereNotIn('id', $batchIdsWithCertificate)->count();

        $temperatureViolations = TemperatureLog::whereIn('status', [TemperatureLog::STATUS_WARNING, TemperatureLog::STATUS_CRITICAL])->count();

        $storageThreshold = now()->subDays(self::MAX_STORAGE_DAYS)->toDateString();
        $batchesStoredBeyondTime = WarehouseStorage::where('status', WarehouseStorage::STATUS_IN_STORAGE)
            ->where('entry_date', '<=', $storageThreshold)
            ->count();

        return [
            'facilities_expired_license' => $facilitiesExpiredLicense,
            'inspectors_expired_authorization' => $inspectorsExpiredAuth,
            'employees_expired_contracts' => $employeeContractsExpired,
            'supplier_contracts_expiring_soon' => $supplierContractsExpiringSoon,
            'sessions_without_ante_mortem' => $sessionsWithoutAnteMortem,
            'batches_without_post_mortem' => $batchesWithoutPostMortem,
            'batches_without_certificate' => $batchesWithoutCertificate,
            'temperature_violations' => $temperatureViolations,
            'batches_stored_beyond_time' => $batchesStoredBeyondTime,
        ];
    }

    private function chartSlaughterActivity(): array
    {
        $days = 14;
        $start = now()->subDays($days)->startOfDay();
        $executions = SlaughterExecution::whereNotNull('slaughter_time')
            ->where('slaughter_time', '>=', $start)
            ->get();
        $byDate = $executions->groupBy(fn ($e) => Carbon::parse($e->slaughter_time)->toDateString())
            ->map(fn ($g) => $g->sum('actual_animals_slaughtered'));
        $labels = [];
        $data = [];
        for ($i = $days; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($d)->format('M j');
            $data[] = (int) ($byDate[$d] ?? 0);
        }
        return [
            'labels' => $labels,
            'datasets' => [['label' => __('Animals slaughtered'), 'data' => $data]],
            'type' => 'bar',
        ];
    }

    private function chartSpeciesDistribution(): array
    {
        $groups = AnimalIntake::query()
            ->select('species')
            ->selectRaw('COALESCE(SUM(number_of_animals), 0) as total')
            ->groupBy('species')
            ->orderByDesc('total')
            ->get();
        return [
            'labels' => $groups->pluck('species')->map(fn ($s) => $s ?: __('Other'))->values()->all(),
            'datasets' => [['label' => __('Animals'), 'data' => $groups->pluck('total')->map(fn ($n) => (int) $n)->values()->all()]],
            'type' => 'doughnut',
        ];
    }

    private function chartDemandVsSupply(): array
    {
        $months = 6;
        $start = now()->subMonths($months)->startOfMonth();
        $monthKeys = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKeys[] = now()->subMonths($i)->format('Y-m');
        }
        $demands = Demand::where('created_at', '>=', $start)->get();
        $demandByMonth = $demands->groupBy(fn ($d) => Carbon::parse($d->created_at)->format('Y-m'))->map->count()->all();
        $executions = SlaughterExecution::where('slaughter_time', '>=', $start)->get();
        $supplyByMonth = $executions->groupBy(fn ($e) => Carbon::parse($e->slaughter_time)->format('Y-m'))
            ->map(fn ($g) => $g->sum('actual_animals_slaughtered'))->all();
        $fill = fn ($arr) => array_map(fn ($k) => (int) ($arr[$k] ?? 0), $monthKeys);
        $labels = array_map(fn ($k) => Carbon::createFromFormat('Y-m', $k)->translatedFormat('M Y'), $monthKeys);
        return [
            'labels' => $labels,
            'datasets' => [
                ['label' => __('Demand requests'), 'data' => $fill($demandByMonth)],
                ['label' => __('Animals slaughtered (supply)'), 'data' => $fill($supplyByMonth)],
            ],
            'type' => 'line',
        ];
    }

    private function chartDeliveriesByRegion(): array
    {
        $deliveries = DeliveryConfirmation::with(['client', 'receivingFacility'])->get();
        $districtCounts = [];
        foreach ($deliveries as $d) {
            $districtId = $d->client?->district_id ?? $d->receivingFacility?->district_id;
            if ($districtId) {
                $districtCounts[$districtId] = ($districtCounts[$districtId] ?? 0) + 1;
            }
        }
        if (empty($districtCounts)) {
            return ['labels' => [], 'datasets' => [['label' => __('Deliveries'), 'data' => []]], 'type' => 'bar'];
        }
        arsort($districtCounts);
        $districtIds = array_keys($districtCounts);
        $names = AdministrativeDivision::whereIn('id', $districtIds)->pluck('name', 'id');
        $labels = array_map(fn ($id) => $names[$id] ?? (string) $id, $districtIds);
        $data = array_values(array_map('intval', $districtCounts));
        return [
            'labels' => $labels,
            'datasets' => [['label' => __('Deliveries'), 'data' => $data]],
            'type' => 'bar',
        ];
    }

    private function topSuppliersByVolume(int $limit = 10): array
    {
        $rows = AnimalIntake::query()
            ->whereNotNull('supplier_id')
            ->selectRaw('supplier_id, COALESCE(SUM(number_of_animals), 0) as total')
            ->groupBy('supplier_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
        $supplierIds = $rows->pluck('supplier_id')->unique();
        $suppliers = Supplier::whereIn('id', $supplierIds)->get()->keyBy('id');
        return $rows->map(function ($r) use ($suppliers) {
            $s = $suppliers->get($r->supplier_id);
            $name = $s ? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')) : (string) $r->supplier_id;
            return ['name' => $name ?: __('Unknown'), 'volume' => (int) $r->total];
        })->values()->all();
    }

    private function supplierRejectionRate(): array
    {
        $total = AnimalIntake::count();
        if ($total === 0) {
            return ['rate' => 0, 'rejected' => 0, 'total' => 0];
        }
        $rejected = AnimalIntake::where('status', AnimalIntake::STATUS_REJECTED)->count();
        return [
            'rate' => round(($rejected / $total) * 100, 1),
            'rejected' => $rejected,
            'total' => $total,
        ];
    }

    private function topCustomersByVolume(int $limit = 10): array
    {
        $rows = DeliveryConfirmation::query()
            ->whereNotNull('client_id')
            ->selectRaw('client_id, COALESCE(SUM(received_quantity), 0) as total')
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
        $clientIds = $rows->pluck('client_id')->unique();
        $clients = Client::whereIn('id', $clientIds)->get()->keyBy('id');
        return $rows->map(function ($r) use ($clients) {
            $c = $clients->get($r->client_id);
            return ['name' => $c ? $c->name : __('Unknown'), 'volume' => (float) $r->total];
        })->values()->all();
    }

    private function demandTrends(): array
    {
        $months = 6;
        $start = now()->subMonths($months)->startOfMonth();
        $monthKeys = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKeys[] = now()->subMonths($i)->format('Y-m');
        }
        $demands = Demand::where('created_at', '>=', $start)->get();
        $byMonth = $demands->groupBy(fn ($d) => Carbon::parse($d->created_at)->format('Y-m'))->map->count()->all();
        return [
            'labels' => array_map(fn ($k) => Carbon::createFromFormat('Y-m', $k)->translatedFormat('M Y'), $monthKeys),
            'data' => array_map(fn ($k) => (int) ($byMonth[$k] ?? 0), $monthKeys),
        ];
    }
}
