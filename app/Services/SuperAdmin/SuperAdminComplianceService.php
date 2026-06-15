<?php

namespace App\Services\SuperAdmin;

use App\Models\AnteMortemInspection;
use App\Models\Batch;
use App\Models\Certificate;
use App\Models\Contract;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\SlaughterExecution;
use App\Models\TemperatureLog;
use App\Models\WarehouseStorage;
use App\Support\TenantEnvironmentScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SuperAdminComplianceService
{
    public const MAX_STORAGE_DAYS = 30;

    public const TEMP_VIOLATION_DAYS = 7;

    public const ALERT_AWAITING_CERTIFICATE = 'awaiting_certificate';

    public const ALERT_PENDING_COLD_ROOM = 'pending_cold_room_release';

    public const ALERT_AWAITING_POST_MORTEM = 'awaiting_post_mortem';

    public const ALERT_MISSING_ANTE_MORTEM = 'missing_ante_mortem';

    public const ALERT_OVERDUE_COLD_ROOM = 'overdue_cold_room';

    public const ALERT_RECENT_TEMP_VIOLATIONS = 'recent_temp_violations';

    public const ALERT_FACILITIES_EXPIRED_LICENSE = 'facilities_expired_license';

    public const ALERT_INSPECTORS_EXPIRED = 'inspectors_expired_authorization';

    public const ALERT_EMPLOYEE_CONTRACTS = 'employees_expired_contracts';

    public const ALERT_SUPPLIER_CONTRACTS = 'supplier_contracts_expiring_soon';

    /**
     * @return array<string, array{key: string, label: string, description: string, count: int, severity: string, icon: string, href: string|null}>
     */
    public function pipelineAlertCards(): array
    {
        $cards = [
            $this->card(
                self::ALERT_AWAITING_CERTIFICATE,
                __('Awaiting Certificate'),
                __('Released batches awaiting certificate'),
                __('Meat has been released from cold room but no veterinary certificate has been issued yet.'),
                $this->batches()->eligibleForCertificate()->count(),
                'amber',
                'certificate',
            ),
            $this->card(
                self::ALERT_PENDING_COLD_ROOM,
                __('Pending Cold Room Release'),
                __('PM complete, meat still in storage'),
                __('Post-mortem is recorded but approved meat remains in cold room storage.'),
                $this->pendingColdRoomReleaseQuery()->count(),
                'amber',
                'box',
            ),
            $this->card(
                self::ALERT_AWAITING_POST_MORTEM,
                __('Awaiting Post-Mortem'),
                __('Batches without post-mortem inspection'),
                __('Slaughter batches that do not yet have a post-mortem inspection record.'),
                $this->batches()->whereDoesntHave('postMortemInspection')->count(),
                'amber',
                'clipboard',
            ),
            $this->card(
                self::ALERT_MISSING_ANTE_MORTEM,
                __('Missing Ante-Mortem'),
                __('Slaughter sessions without ante-mortem'),
                __('Completed slaughter executions on plans with no ante-mortem inspection recorded.'),
                $this->missingAnteMortemQuery()->count(),
                'amber',
                'clipboard-list',
            ),
            $this->card(
                self::ALERT_OVERDUE_COLD_ROOM,
                __('Overdue in Cold Room'),
                __('Stored beyond :days days', ['days' => self::MAX_STORAGE_DAYS]),
                __('Warehouse storage still marked in storage after the allowed holding period.'),
                $this->overdueColdRoomQuery()->count(),
                'red',
                'box',
            ),
            $this->card(
                self::ALERT_RECENT_TEMP_VIOLATIONS,
                __('Recent Temp Violations'),
                __('Warning or critical readings (last :days days)', ['days' => self::TEMP_VIOLATION_DAYS]),
                __('Cold room temperature logs flagged as warning or critical in the last seven days.'),
                $this->recentTemperatureViolationsQuery()->count(),
                'red',
                'clipboard',
            ),
        ];

        return $cards;
    }

    /**
     * @return array<string, array{key: string, label: string, description: string, count: int, severity: string, icon: string, href: string|null}>
     */
    public function administrativeAlertCards(): array
    {
        $today = now()->toDateString();
        $expiringSoon = now()->addDays(30)->toDateString();

        return [
            $this->card(
                self::ALERT_FACILITIES_EXPIRED_LICENSE,
                __('Expired facility licenses'),
                __('Facilities past license expiry'),
                __('Registered facilities whose operating license has expired.'),
                $this->facilities()->whereNotNull('license_expiry_date')->where('license_expiry_date', '<', $today)->count(),
                'red',
                'building',
            ),
            $this->card(
                self::ALERT_INSPECTORS_EXPIRED,
                __('Expired inspector authorization'),
                __('Inspectors past authorization date'),
                __('Inspectors whose authorization certificate has expired.'),
                $this->inspectors()->whereNotNull('authorization_expiry_date')->where('authorization_expiry_date', '<', $today)->count(),
                'red',
                'user',
            ),
            $this->card(
                self::ALERT_EMPLOYEE_CONTRACTS,
                __('Expired employee contracts'),
                __('Employee contracts past end date'),
                __('Employment contracts that have passed their end date.'),
                $this->contracts()->where('contract_category', Contract::CATEGORY_EMPLOYEE)->whereNotNull('end_date')->where('end_date', '<', $today)->count(),
                'red',
                'users',
            ),
            $this->card(
                self::ALERT_SUPPLIER_CONTRACTS,
                __('Supplier contracts expiring soon'),
                __('Active supplier contracts ending within 30 days'),
                __('Supplier agreements that will expire within the next thirty days.'),
                $this->contracts()
                    ->where('contract_category', Contract::CATEGORY_SUPPLIER)
                    ->where('status', Contract::STATUS_ACTIVE)
                    ->whereNotNull('end_date')
                    ->whereBetween('end_date', [$today, $expiringSoon])
                    ->count(),
                'amber',
                'clipboard-list',
            ),
        ];
    }

    /**
     * @return array{active_facilities: int, batches_this_month: int, certificates_this_month: int}
     */
    public function summaryBar(): array
    {
        $month = now()->month;
        $year = now()->year;

        return [
            'active_facilities' => $this->facilities()->where('status', Facility::STATUS_ACTIVE)->count(),
            'batches_this_month' => $this->batches()
                ->whereHas('slaughterExecution', fn (Builder $q) => $q
                    ->whereMonth('slaughter_time', $month)
                    ->whereYear('slaughter_time', $year))
                ->count(),
            'certificates_this_month' => $this->certificates()
                ->whereMonth('issued_at', $month)
                ->whereYear('issued_at', $year)
                ->count(),
        ];
    }

    public function alertMeta(string $alert): ?array
    {
        $all = collect($this->pipelineAlertCards())
            ->merge($this->administrativeAlertCards())
            ->keyBy('key');

        return $all->get($alert);
    }

    public function paginatedList(string $alert, int $perPage = 20): LengthAwarePaginator
    {
        return match ($alert) {
            self::ALERT_AWAITING_CERTIFICATE => $this->batches()
                ->eligibleForCertificate()
                ->with(['slaughterExecution.slaughterPlan.facility.business', 'postMortemInspection'])
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString(),
            self::ALERT_PENDING_COLD_ROOM => $this->pendingColdRoomReleaseQuery()
                ->with(['slaughterExecution.slaughterPlan.facility.business', 'postMortemInspection', 'warehouseStorages'])
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString(),
            self::ALERT_AWAITING_POST_MORTEM => $this->batches()
                ->whereDoesntHave('postMortemInspection')
                ->with(['slaughterExecution.slaughterPlan.facility.business'])
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString(),
            self::ALERT_MISSING_ANTE_MORTEM => $this->missingAnteMortemQuery()
                ->with(['slaughterPlan.facility.business'])
                ->orderByDesc('slaughter_time')
                ->paginate($perPage)
                ->withQueryString(),
            self::ALERT_OVERDUE_COLD_ROOM => $this->overdueColdRoomQuery()
                ->with(['batch.slaughterExecution.slaughterPlan.facility', 'warehouseFacility'])
                ->orderBy('entry_date')
                ->paginate($perPage)
                ->withQueryString(),
            self::ALERT_RECENT_TEMP_VIOLATIONS => $this->recentTemperatureViolationsQuery()
                ->with(['warehouseStorage.batch.slaughterExecution.slaughterPlan.facility'])
                ->orderByDesc('recorded_at')
                ->paginate($perPage)
                ->withQueryString(),
            default => Batch::query()->whereRaw('1 = 0')->paginate($perPage),
        };
    }

    /**
     * @return Builder<Batch>
     */
    private function batches(): Builder
    {
        return TenantEnvironmentScope::applyToBatches(Batch::query());
    }

    /**
     * @return Builder<Facility>
     */
    private function facilities(): Builder
    {
        return TenantEnvironmentScope::applyToFacilities(Facility::query());
    }

    /**
     * @return Builder<Certificate>
     */
    private function certificates(): Builder
    {
        return TenantEnvironmentScope::applyToCertificates(Certificate::query());
    }

    /**
     * @return Builder<Contract>
     */
    private function contracts(): Builder
    {
        return TenantEnvironmentScope::applyToContracts(Contract::query());
    }

    /**
     * @return Builder<Inspector>
     */
    private function inspectors(): Builder
    {
        return TenantEnvironmentScope::applyToInspectors(Inspector::query());
    }

    /**
     * @return Builder<Batch>
     */
    private function pendingColdRoomReleaseQuery(): Builder
    {
        return $this->batches()
            ->whereDoesntHave('certificate')
            ->whereHas('postMortemInspection')
            ->whereHas('warehouseStorages', fn (Builder $q) => $q
                ->where('status', WarehouseStorage::STATUS_IN_STORAGE));
    }

    /**
     * @return Builder<SlaughterExecution>
     */
    private function missingAnteMortemQuery(): Builder
    {
        $planIdsWithAnteMortem = AnteMortemInspection::query()
            ->pluck('slaughter_plan_id')
            ->unique()
            ->filter();

        return TenantEnvironmentScope::applyToSlaughterExecutions(SlaughterExecution::query())
            ->whereNotIn('slaughter_plan_id', $planIdsWithAnteMortem);
    }

    /**
     * @return Builder<WarehouseStorage>
     */
    private function overdueColdRoomQuery(): Builder
    {
        $threshold = now()->subDays(self::MAX_STORAGE_DAYS)->toDateString();

        return TenantEnvironmentScope::applyToWarehouseStorages(WarehouseStorage::query())
            ->where('status', WarehouseStorage::STATUS_IN_STORAGE)
            ->where('entry_date', '<=', $threshold);
    }

    /**
     * @return Builder<TemperatureLog>
     */
    private function recentTemperatureViolationsQuery(): Builder
    {
        return TenantEnvironmentScope::applyToTemperatureLogs(TemperatureLog::query())
            ->whereIn('status', [TemperatureLog::STATUS_WARNING, TemperatureLog::STATUS_CRITICAL])
            ->where('recorded_at', '>=', now()->subDays(self::TEMP_VIOLATION_DAYS)->startOfDay());
    }

    /**
     * @return array{key: string, label: string, description: string, count: int, severity: string, icon: string, href: string|null}
     */
    private function card(
        string $key,
        string $label,
        string $shortDescription,
        string $description,
        int $count,
        string $severity,
        string $icon,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'short_description' => $shortDescription,
            'description' => $description,
            'count' => $count,
            'severity' => $severity,
            'icon' => $icon,
            'href' => $count > 0
                ? route('super-admin.compliance.index', array_merge(
                    ['alert' => $key],
                    TenantEnvironmentScope::queryParams()
                ))
                : null,
        ];
    }
}
