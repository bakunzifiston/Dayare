<?php

namespace App\Services\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\AnteMortemInspection;
use App\Models\Batch;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspectionItem;
use App\Models\RicaMonthlyInspectionReport;
use App\Models\RicaSetting;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\TemperatureLog;
use App\Models\TransportTrip;
use App\Models\WarehouseStorage;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RicaAlertsNotificationsDashboardService
{
    public const CATEGORY_MONTHLY_REPORTS = 'monthly_reports';

    public const CATEGORY_LICENCES = 'licences';

    public const CATEGORY_INSPECTORS = 'inspectors';

    public const CATEGORY_PIPELINE = 'pipeline';

    public const CATEGORY_CONDEMNATION = 'condemnation';

    public const CATEGORY_TRANSPORT = 'transport';

    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_INFO = 'info';

    private const PIPELINE_LOOKBACK_DAYS = 30;

    private const CONDEMNATION_LOOKBACK_DAYS = 7;

    private const TRANSPORT_DELAY_DAYS = 2;

    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $selectedDistrictId = $this->normalizeDistrictId($request->query('district_id'));
        $selectedCategory = $this->normalizeCategory($request->query('category'));
        $selectedSeverity = $this->normalizeSeverity($request->query('severity'));

        $alerts = $this->collectAlerts($selectedDistrictId);
        $filtered = $this->filterAlerts($alerts, $selectedCategory, $selectedSeverity);

        return [
            'districtOptions' => $this->districtOptions(),
            'selectedDistrictId' => $selectedDistrictId,
            'selectedCategory' => $selectedCategory,
            'selectedSeverity' => $selectedSeverity,
            'categories' => $this->categoryOptions($alerts),
            'kpis' => $this->kpis($alerts),
            'alerts' => $filtered->take(100)->values()->all(),
            'totalAlerts' => $alerts->count(),
            'filteredCount' => $filtered->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function collectAlerts(?int $districtId): Collection
    {
        return collect()
            ->merge($this->monthlyReportAlerts($districtId))
            ->merge($this->licenceAlerts($districtId))
            ->merge($this->inspectorAlerts($districtId))
            ->merge($this->pipelineAlerts($districtId))
            ->merge($this->condemnationAlerts($districtId))
            ->merge($this->transportAlerts($districtId))
            ->sortBy([
                fn (array $alert) => $this->severityRank($alert['severity']),
                fn (array $alert) => -($alert['occurred_at'] instanceof Carbon ? $alert['occurred_at']->timestamp : 0),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $alerts
     * @return Collection<int, array<string, mixed>>
     */
    private function filterAlerts(Collection $alerts, string $category, string $severity): Collection
    {
        return $alerts
            ->when($category !== 'all', fn (Collection $rows) => $rows->where('category', $category))
            ->when($severity !== 'all', fn (Collection $rows) => $rows->where('severity', $severity))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $alerts
     * @return array<string, mixed>
     */
    private function kpis(Collection $alerts): array
    {
        return [
            'total' => $alerts->count(),
            'critical' => $alerts->where('severity', self::SEVERITY_CRITICAL)->count(),
            'warning' => $alerts->where('severity', self::SEVERITY_WARNING)->count(),
            'info' => $alerts->where('severity', self::SEVERITY_INFO)->count(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $alerts
     * @return list<array{key: string, label: string, count: int}>
     */
    private function categoryOptions(Collection $alerts): array
    {
        $labels = [
            self::CATEGORY_MONTHLY_REPORTS => __('Monthly reports'),
            self::CATEGORY_LICENCES => __('Licences'),
            self::CATEGORY_INSPECTORS => __('PMI authorization'),
            self::CATEGORY_PIPELINE => __('Inspection pipeline'),
            self::CATEGORY_CONDEMNATION => __('Condemnations'),
            self::CATEGORY_TRANSPORT => __('Transport'),
        ];

        $options = [
            ['key' => 'all', 'label' => __('All alerts'), 'count' => $alerts->count()],
        ];

        foreach ($labels as $key => $label) {
            $options[] = [
                'key' => $key,
                'label' => $label,
                'count' => $alerts->where('category', $key)->count(),
            ];
        }

        return $options;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function monthlyReportAlerts(?int $districtId): array
    {
        $deadlineDay = max(1, min(28, (int) RicaSetting::get('monthly_report_deadline_day', '5')));
        $today = now();
        $alerts = [];

        foreach ($this->reportMonthsToCheck() as $monthStart) {
            $isCurrentMonth = $monthStart->isSameMonth($today);
            $pastDeadline = $isCurrentMonth
                ? $today->day > $deadlineDay
                : $monthStart->endOfMonth()->isPast();

            if (! $pastDeadline && $isCurrentMonth) {
                continue;
            }

            $facilityIds = $this->facilityIdsWithActivity((int) $monthStart->year, (int) $monthStart->month, $districtId);
            if ($facilityIds === []) {
                continue;
            }

            $facilities = Facility::query()
                ->whereIn('id', $facilityIds)
                ->with('districtDivision:id,name')
                ->get(['id', 'facility_name', 'district_id', 'district']);

            $submittedIds = RicaMonthlyInspectionReport::query()
                ->where('period_year', $monthStart->year)
                ->where('period_month', $monthStart->month)
                ->where('status', RicaMonthlyInspectionReport::STATUS_SUBMITTED)
                ->whereIn('facility_id', $facilityIds)
                ->pluck('facility_id')
                ->all();

            foreach ($facilities as $facility) {
                if (in_array((int) $facility->id, $submittedIds, true)) {
                    continue;
                }

                $periodLabel = $monthStart->format('F Y');
                $severity = $isCurrentMonth ? self::SEVERITY_WARNING : self::SEVERITY_CRITICAL;

                $alerts[] = $this->alertRow(
                    id: 'monthly-report:'.$facility->id.':'.$monthStart->format('Ym'),
                    category: self::CATEGORY_MONTHLY_REPORTS,
                    severity: $severity,
                    title: $isCurrentMonth
                        ? __('Monthly report pending')
                        : __('Overdue monthly report'),
                    message: __(':facility — FPU/FRM/018 report for :period not submitted', [
                        'facility' => $facility->facility_name,
                        'period' => $periodLabel,
                    ]),
                    facilityName: (string) $facility->facility_name,
                    districtName: $facility->districtDivision?->name ?? $facility->district,
                    occurredAt: $monthStart->copy()->endOfMonth(),
                    href: route('rica.monthly-reports.show', [
                        'facility' => $facility->id,
                        'year' => $monthStart->year,
                        'month' => $monthStart->month,
                    ]),
                );
            }
        }

        return $alerts;
    }

    /**
     * @return list<Carbon>
     */
    private function reportMonthsToCheck(): array
    {
        return [
            now()->copy()->startOfMonth(),
            now()->copy()->subMonth()->startOfMonth(),
            now()->copy()->subMonths(2)->startOfMonth(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function licenceAlerts(?int $districtId): array
    {
        $today = now()->toDateString();

        return $this->ricaFacilities($districtId)
            ->whereNotNull('license_expiry_date')
            ->where('license_expiry_date', '<', $today)
            ->with('districtDivision:id,name')
            ->orderBy('license_expiry_date')
            ->get(['id', 'facility_name', 'district_id', 'district', 'license_expiry_date', 'license_number'])
            ->map(fn (Facility $facility) => $this->alertRow(
                id: 'licence:'.$facility->id,
                category: self::CATEGORY_LICENCES,
                severity: self::SEVERITY_CRITICAL,
                title: __('Expired facility licence'),
                message: __(':facility — licence :number expired :date', [
                    'facility' => $facility->facility_name,
                    'number' => $facility->license_number ?? __('Unknown'),
                    'date' => Carbon::parse($facility->license_expiry_date)->format('M j, Y'),
                ]),
                facilityName: (string) $facility->facility_name,
                districtName: $facility->districtDivision?->name ?? $facility->district,
                occurredAt: Carbon::parse($facility->license_expiry_date)->endOfDay(),
                href: route('rica.monthly-reports.index', ['facility_id' => $facility->id]),
            ))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function inspectorAlerts(?int $districtId): array
    {
        $today = now()->toDateString();
        $facilityIds = $this->ricaFacilities($districtId)->pluck('id');

        if ($facilityIds->isEmpty()) {
            return [];
        }

        return TenantEnvironmentScope::applyToInspectors(
            Inspector::query()
                ->whereIn('facility_id', $facilityIds)
        )
            ->whereNotNull('authorization_expiry_date')
            ->where('authorization_expiry_date', '<', $today)
            ->with(['facility:id,facility_name,district_id,district', 'facility.districtDivision:id,name'])
            ->orderBy('authorization_expiry_date')
            ->get()
            ->map(function (Inspector $inspector): array {
                $facility = $inspector->facility;

                return $this->alertRow(
                    id: 'inspector:'.$inspector->id,
                    category: self::CATEGORY_INSPECTORS,
                    severity: self::SEVERITY_CRITICAL,
                    title: __('Expired PMI authorization'),
                    message: __(':inspector at :facility — authorization expired :date', [
                        'inspector' => trim($inspector->first_name.' '.$inspector->last_name),
                        'facility' => $facility?->facility_name ?? __('Unknown facility'),
                        'date' => Carbon::parse($inspector->authorization_expiry_date)->format('M j, Y'),
                    ]),
                    facilityName: $facility?->facility_name,
                    districtName: $facility?->districtDivision?->name ?? $facility?->district,
                    occurredAt: Carbon::parse($inspector->authorization_expiry_date)->endOfDay(),
                    href: $facility ? route('rica.monthly-reports.index', ['facility_id' => $facility->id]) : null,
                );
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pipelineAlerts(?int $districtId): array
    {
        $alerts = [];
        $since = now()->subDays(self::PIPELINE_LOOKBACK_DAYS)->startOfDay();

        $this->ricaBatches($districtId)
            ->whereDoesntHave('postMortemInspection')
            ->whereHas('slaughterExecution', fn (Builder $query) => $query
                ->where('slaughter_time', '>=', $since))
            ->with(['slaughterExecution.slaughterPlan.facility.districtDivision'])
            ->orderByDesc('id')
            ->limit(25)
            ->get(['id', 'batch_code'])
            ->each(function (Batch $batch) use (&$alerts): void {
                $facility = $batch->slaughterExecution?->slaughterPlan?->facility;

                $alerts[] = $this->alertRow(
                    id: 'pipeline:pm:'.$batch->id,
                    category: self::CATEGORY_PIPELINE,
                    severity: self::SEVERITY_WARNING,
                    title: __('Awaiting post-mortem'),
                    message: __('Batch :batch at :facility — post-mortem inspection not recorded', [
                        'batch' => $batch->batch_code,
                        'facility' => $facility?->facility_name ?? __('Unknown facility'),
                    ]),
                    facilityName: $facility?->facility_name,
                    districtName: $facility?->districtDivision?->name ?? $facility?->district,
                    occurredAt: $batch->slaughterExecution?->slaughter_time ?? now(),
                    href: route('rica.traceability', ['batch_id' => $batch->id]),
                );
            });

        $planIdsWithAnteMortem = AnteMortemInspection::query()->pluck('slaughter_plan_id')->unique()->filter();

        TenantEnvironmentScope::applyToSlaughterExecutions(SlaughterExecution::query())
            ->where('slaughter_time', '>=', $since)
            ->whereNotIn('slaughter_plan_id', $planIdsWithAnteMortem)
            ->whereHas('slaughterPlan.facility', fn (Builder $query) => $this->scopeRicaFacility($query, $districtId))
            ->with(['slaughterPlan.facility.districtDivision'])
            ->orderByDesc('slaughter_time')
            ->limit(25)
            ->get()
            ->each(function (SlaughterExecution $execution) use (&$alerts): void {
                $facility = $execution->slaughterPlan?->facility;

                $alerts[] = $this->alertRow(
                    id: 'pipeline:am:'.$execution->id,
                    category: self::CATEGORY_PIPELINE,
                    severity: self::SEVERITY_WARNING,
                    title: __('Missing ante-mortem'),
                    message: __(':facility — slaughter session #:id has no ante-mortem inspection', [
                        'facility' => $facility?->facility_name ?? __('Unknown facility'),
                        'id' => $execution->id,
                    ]),
                    facilityName: $facility?->facility_name,
                    districtName: $facility?->districtDivision?->name ?? $facility?->district,
                    occurredAt: $execution->slaughter_time ?? now(),
                    href: route('rica.traceability'),
                );
            });

        $coldRoomThreshold = now()->subDays(SuperAdminComplianceService::MAX_STORAGE_DAYS)->toDateString();

        TenantEnvironmentScope::applyToWarehouseStorages(WarehouseStorage::query())
            ->where('status', WarehouseStorage::STATUS_IN_STORAGE)
            ->where('entry_date', '<=', $coldRoomThreshold)
            ->whereHas('batch.slaughterExecution.slaughterPlan.facility', fn (Builder $query) => $this->scopeRicaFacility($query, $districtId))
            ->with(['batch.slaughterExecution.slaughterPlan.facility.districtDivision'])
            ->orderBy('entry_date')
            ->limit(25)
            ->get()
            ->each(function (WarehouseStorage $storage) use (&$alerts): void {
                $facility = $storage->batch?->slaughterExecution?->slaughterPlan?->facility;

                $alerts[] = $this->alertRow(
                    id: 'pipeline:cold:'.$storage->id,
                    category: self::CATEGORY_PIPELINE,
                    severity: self::SEVERITY_CRITICAL,
                    title: __('Overdue cold room storage'),
                    message: __(':facility — batch :batch stored beyond :days days', [
                        'facility' => $facility?->facility_name ?? __('Unknown facility'),
                        'batch' => $storage->batch?->batch_code ?? '#'.$storage->batch_id,
                        'days' => SuperAdminComplianceService::MAX_STORAGE_DAYS,
                    ]),
                    facilityName: $facility?->facility_name,
                    districtName: $facility?->districtDivision?->name ?? $facility?->district,
                    occurredAt: Carbon::parse($storage->entry_date)->startOfDay(),
                    href: $storage->batch_id ? route('rica.traceability', ['batch_id' => $storage->batch_id]) : route('rica.traceability'),
                );
            });

        TenantEnvironmentScope::applyToTemperatureLogs(TemperatureLog::query())
            ->whereIn('status', [TemperatureLog::STATUS_WARNING, TemperatureLog::STATUS_CRITICAL])
            ->where('recorded_at', '>=', now()->subDays(SuperAdminComplianceService::TEMP_VIOLATION_DAYS)->startOfDay())
            ->whereHas('warehouseStorage.batch.slaughterExecution.slaughterPlan.facility', fn (Builder $query) => $this->scopeRicaFacility($query, $districtId))
            ->with(['warehouseStorage.batch.slaughterExecution.slaughterPlan.facility.districtDivision'])
            ->orderByDesc('recorded_at')
            ->limit(25)
            ->get()
            ->each(function (TemperatureLog $log) use (&$alerts): void {
                $facility = $log->warehouseStorage?->batch?->slaughterExecution?->slaughterPlan?->facility;

                $alerts[] = $this->alertRow(
                    id: 'pipeline:temp:'.$log->id,
                    category: self::CATEGORY_PIPELINE,
                    severity: $log->status === TemperatureLog::STATUS_CRITICAL
                        ? self::SEVERITY_CRITICAL
                        : self::SEVERITY_WARNING,
                    title: __('Temperature violation'),
                    message: __(':facility — :status reading :temp°C recorded', [
                        'facility' => $facility?->facility_name ?? __('Unknown facility'),
                        'status' => ucfirst((string) $log->status),
                        'temp' => $log->recorded_temperature,
                    ]),
                    facilityName: $facility?->facility_name,
                    districtName: $facility?->districtDivision?->name ?? $facility?->district,
                    occurredAt: $log->recorded_at ?? now(),
                    href: route('rica.traceability', ['batch_id' => $log->warehouseStorage?->batch_id]),
                );
            });

        return $alerts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function condemnationAlerts(?int $districtId): array
    {
        $since = now()->subDays(self::CONDEMNATION_LOOKBACK_DAYS)->startOfDay()->toDateString();

        return TenantEnvironmentScope::applyToPostMortemInspectionItems(
            PostMortemInspectionItem::query()->condemned()
        )
            ->with([
                'inspection.batch.slaughterExecution.slaughterPlan.facility.districtDivision',
                'intakeItem:id,ear_tag',
            ])
            ->whereHas(
                'inspection',
                fn (Builder $query) => $query
                    ->where('inspection_date', '>=', $since)
                    ->whereHas(
                        'batch.slaughterExecution.slaughterPlan.facility',
                        fn (Builder $facilityQuery) => $this->scopeRicaFacility($facilityQuery, $districtId),
                    ),
            )
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (PostMortemInspectionItem $item): array {
                $facility = $item->inspection?->batch?->slaughterExecution?->slaughterPlan?->facility;
                $kg = round((float) ($item->condemned_weight_kg ?? $item->carcass_weight_kg ?? 0), 2);
                $earTag = $item->intakeItem?->ear_tag;

                return $this->alertRow(
                    id: 'condemnation:'.$item->id,
                    category: self::CATEGORY_CONDEMNATION,
                    severity: self::SEVERITY_INFO,
                    title: __('Condemned meat recorded'),
                    message: __(':facility — :kg kg condemned'.($earTag ? ' (ear tag :tag)' : ''), [
                        'facility' => $facility?->facility_name ?? __('Unknown facility'),
                        'kg' => number_format($kg, 2),
                        'tag' => $earTag,
                    ]),
                    facilityName: $facility?->facility_name,
                    districtName: $facility?->districtDivision?->name ?? $facility?->district,
                    occurredAt: Carbon::parse($item->inspection?->inspection_date ?? now())->startOfDay(),
                    href: route('rica.meat-condemnation', ['period' => 'month']),
                );
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function transportAlerts(?int $districtId): array
    {
        $threshold = now()->subDays(self::TRANSPORT_DELAY_DAYS)->startOfDay();

        return TransportTrip::query()
            ->where('status', TransportTrip::STATUS_IN_TRANSIT)
            ->where('departure_date', '<=', $threshold)
            ->where(function (Builder $query) use ($districtId): void {
                $query->whereHas('batch.slaughterExecution.slaughterPlan.facility', fn (Builder $facilityQuery) => $this->scopeRicaFacility($facilityQuery, $districtId))
                    ->orWhereHas('certificate.facility', fn (Builder $facilityQuery) => $this->scopeRicaFacility($facilityQuery, $districtId));
            })
            ->with(['batch.slaughterExecution.slaughterPlan.facility.districtDivision', 'certificate.facility.districtDivision'])
            ->orderBy('departure_date')
            ->limit(20)
            ->get()
            ->map(function (TransportTrip $trip): array {
                $facility = $trip->batch?->slaughterExecution?->slaughterPlan?->facility
                    ?? $trip->certificate?->facility;
                $batchCode = $trip->batch?->batch_code ?? $trip->certificate?->certificate_number ?? '#'.$trip->id;

                return $this->alertRow(
                    id: 'transport:'.$trip->id,
                    category: self::CATEGORY_TRANSPORT,
                    severity: self::SEVERITY_WARNING,
                    title: __('Transport delay'),
                    message: __(':facility — trip for :reference departed :date and is still in transit', [
                        'facility' => $facility?->facility_name ?? __('Unknown facility'),
                        'reference' => $batchCode,
                        'date' => optional($trip->departure_date)->format('M j, Y') ?? __('Unknown'),
                    ]),
                    facilityName: $facility?->facility_name,
                    districtName: $facility?->districtDivision?->name ?? $facility?->district,
                    occurredAt: $trip->departure_date ? Carbon::parse($trip->departure_date)->startOfDay() : now(),
                    href: $trip->batch_id ? route('rica.traceability', ['batch_id' => $trip->batch_id]) : route('rica.supply-chain'),
                );
            })
            ->all();
    }

    /**
     * @return list<int>
     */
    private function facilityIdsWithActivity(int $year, int $month, ?int $districtId): array
    {
        return SlaughterPlan::query()
            ->whereYear('slaughter_date', $year)
            ->whereMonth('slaughter_date', $month)
            ->whereHas('facility', fn (Builder $query) => $this->scopeRicaFacility($query, $districtId))
            ->pluck('facility_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Facility>  $query
     */
    private function scopeRicaFacility(Builder $query, ?int $districtId): void
    {
        TenantEnvironmentScope::applyToFacilities($query->eligibleForRicaMonthlyReport());

        if ($districtId !== null) {
            $query->where('district_id', $districtId);
        }
    }

    /**
     * @return Builder<Facility>
     */
    private function ricaFacilities(?int $districtId): Builder
    {
        $query = TenantEnvironmentScope::applyToFacilities(
            Facility::query()->eligibleForRicaMonthlyReport()
        );

        if ($districtId !== null) {
            $query->where('district_id', $districtId);
        }

        return $query;
    }

    /**
     * @return Builder<Batch>
     */
    private function ricaBatches(?int $districtId): Builder
    {
        return TenantEnvironmentScope::applyToBatches(
            Batch::query()->whereHas(
                'slaughterExecution.slaughterPlan.facility',
                fn (Builder $query) => $this->scopeRicaFacility($query, $districtId),
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function alertRow(
        string $id,
        string $category,
        string $severity,
        string $title,
        string $message,
        ?string $facilityName,
        ?string $districtName,
        Carbon $occurredAt,
        ?string $href,
    ): array {
        return [
            'id' => $id,
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'facility_name' => $facilityName,
            'district_name' => $districtName,
            'occurred_at' => $occurredAt,
            'occurred_label' => $occurredAt->diffForHumans(),
            'href' => $href,
        ];
    }

    private function severityRank(string $severity): int
    {
        return match ($severity) {
            self::SEVERITY_CRITICAL => 0,
            self::SEVERITY_WARNING => 1,
            default => 2,
        };
    }

    /**
     * @return array<int, string>
     */
    private function districtOptions(): array
    {
        return AdministrativeDivision::query()
            ->where('type', AdministrativeDivision::TYPE_DISTRICT)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function normalizeDistrictId(mixed $districtId): ?int
    {
        if ($districtId === null || $districtId === '' || $districtId === 'all') {
            return null;
        }

        return is_numeric($districtId) ? (int) $districtId : null;
    }

    private function normalizeCategory(mixed $category): string
    {
        $category = Str::lower(trim((string) $category));
        $allowed = [
            'all',
            self::CATEGORY_MONTHLY_REPORTS,
            self::CATEGORY_LICENCES,
            self::CATEGORY_INSPECTORS,
            self::CATEGORY_PIPELINE,
            self::CATEGORY_CONDEMNATION,
            self::CATEGORY_TRANSPORT,
        ];

        return in_array($category, $allowed, true) ? $category : 'all';
    }

    private function normalizeSeverity(mixed $severity): string
    {
        $severity = Str::lower(trim((string) $severity));
        $allowed = ['all', self::SEVERITY_CRITICAL, self::SEVERITY_WARNING, self::SEVERITY_INFO];

        return in_array($severity, $allowed, true) ? $severity : 'all';
    }
}
