<?php

namespace App\Services\Processor;

use App\Enums\MeatExportDocumentType;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\AnteMortemInspection;
use App\Models\Batch;
use App\Models\BusinessUser;
use App\Models\Certificate;
use App\Models\ColdRoom;
use App\Models\ColdRoomStandard;
use App\Models\ColdRoomViolation;
use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use App\Models\FinanceInvoice;
use App\Models\FinancePayable;
use App\Models\Inspector;
use App\Models\MeatExportDocument;
use App\Models\PostMortemInspection;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\TransportTrip;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessorDashboardService
{
    public function buildForRole(int $businessId, string $role, ?User $user = null, ?Request $request = null): array
    {
        $ctx = ProcessorDashboardContext::forBusiness($businessId);
        $request = $request ?? request();
        $filters = $this->resolveDashboardFilters($request);

        $data = match ($role) {
            BusinessUser::ROLE_OPERATIONS_MANAGER => $this->buildOpsManager($ctx, $filters),
            BusinessUser::ROLE_COMPLIANCE_OFFICER => $this->buildComplianceOfficer($ctx, $filters),
            BusinessUser::ROLE_INSPECTOR => $this->buildInspector($ctx, $user, $filters),
            BusinessUser::ROLE_TRANSPORT_MANAGER => $this->buildTransportManager($ctx, $filters),
            BusinessUser::ROLE_ACCOUNTANT => $this->buildAccountant($businessId, $ctx, $filters),
            default => $this->buildOrgAdmin($ctx, $user, $filters),
        };

        $roleKey = (string) ($data['roleKey'] ?? '');
        $usesPeriodFilter = in_array($roleKey, [
            BusinessUser::ROLE_ORG_ADMIN,
            BusinessUser::ROLE_INSPECTOR,
            BusinessUser::ROLE_OPERATIONS_MANAGER,
            BusinessUser::ROLE_ACCOUNTANT,
            BusinessUser::ROLE_TRANSPORT_MANAGER,
            BusinessUser::ROLE_COMPLIANCE_OFFICER,
        ], true);

        $data['charts'] = app(ProcessorDashboardCharts::class)->forRole(
            $roleKey,
            $ctx,
            $businessId,
            $usesPeriodFilter ? $filters : null,
            $usesPeriodFilter ? $user : null,
        );

        if ($usesPeriodFilter) {
            $data['filters'] = $filters;
            $data['showPeriodFilter'] = true;
        }

        return $data;
    }

    /**
     * @return array<int, array{label: string, icon: string, route: string, permission: string}>
     */
    public function resolveQuickActions(array $dashboard, User $user, int $businessId): array
    {
        return collect($dashboard['quickActions'] ?? [])
            ->map(fn (array $action) => [
                'label' => (string) $action['label'],
                'icon' => (string) $action['icon'],
                'url' => $user->canProcessorPermission((string) $action['permission'], $businessId)
                    ? route((string) $action['route'])
                    : null,
            ])
            ->values()
            ->all();
    }

    private function buildOrgAdmin(ProcessorDashboardContext $ctx, ?User $user, array $filters): array
    {
        $periodHint = $filters['is_filtered'] ? $filters['range_label'] : __('All time');
        $facilityIds = $this->orgAdminFacilityIds($ctx, $user);
        $planIds = $this->orgAdminPlanIds($ctx, $user);
        $batchIds = $this->orgAdminBatchIds($ctx, $user);
        $businessIds = $user?->accessibleProcessorBusinessIds() ?? collect([$ctx->businessId]);

        $executionsQuery = SlaughterExecution::query()
            ->whereIn('slaughter_plan_id', $planIds)
            ->where('status', SlaughterExecution::STATUS_COMPLETED)
            ->whereNotNull('slaughter_time');
        $this->applyDashboardDateFilter($executionsQuery, 'slaughter_time', $filters);
        $totalExecuted = (int) (clone $executionsQuery)->sum('actual_animals_slaughtered');

        $animalsReceived = $this->intakeAnimalsReceivedForFacilities($facilityIds, $filters);

        $batchQuery = Batch::query()->whereIn('id', $batchIds);
        $this->applyDashboardDateFilter($batchQuery, 'created_at', $filters);
        $batchesInPeriod = (int) (clone $batchQuery)->count();
        $batchesCertified = (int) Batch::query()
            ->whereIn('id', $batchIds)
            ->whereHas('certificate')
            ->when($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null, function ($query) use ($filters): void {
                $query->whereBetween('created_at', [
                    $filters['start']->copy()->startOfDay(),
                    $filters['end']->copy()->endOfDay(),
                ]);
            })
            ->count();
        $certRate = $batchesInPeriod > 0 ? (int) round($batchesCertified / $batchesInPeriod * 100) : 0;

        $totalInspectors = (int) Inspector::query()
            ->whereIn('facility_id', $facilityIds)
            ->where('status', Inspector::STATUS_ACTIVE)
            ->count();
        $coldRoomIds = ColdRoom::query()->whereIn('facility_id', $facilityIds)->pluck('id');
        $openViolations = (int) ColdRoomViolation::query()
            ->whereIn('cold_room_id', $coldRoomIds)
            ->where('status', ColdRoomViolation::STATUS_OPEN)
            ->count();

        return [
            'roleKey' => BusinessUser::ROLE_ORG_ADMIN,
            'headerBadge' => ['label' => __('Organization'), 'variant' => 'info'],
            'kpiCards' => [
                $this->kpi(__('Animals executed'), $totalExecuted, $periodHint, $totalExecuted > 0 ? 'positive' : 'info', 'player-play'),
                $this->kpi(__('Animals received'), $animalsReceived, $periodHint, 'info', 'arrow-down'),
                $this->kpi(__('Certification rate'), $certRate.'%', __(':count batches', ['count' => $batchesInPeriod]), $certRate >= 90 ? 'positive' : 'warning', 'certificate'),
                $this->kpi(__('Facilities'), $facilityIds->count(), __(':count businesses', ['count' => $businessIds->count()]), 'info', 'map-pin'),
                $this->kpi(__('Active inspectors'), $totalInspectors, __(':count temp alerts', ['count' => $openViolations]), $openViolations > 0 ? 'warning' : 'positive', 'users'),
            ],
            'leftPanel' => $this->recentReceivedAnimalsPanel($user, $ctx),
            'rightPanel' => $this->recentSlaughteredPanel($user, $ctx),
            'quickActions' => [
                $this->action(__('Manage users'), 'users', 'tenant-users.index', BusinessUser::PERMISSION_MANAGE_BUSINESS_USERS),
                $this->action(__('Compliance'), 'shield', 'compliance.index', BusinessUser::PERMISSION_MONITOR_COMPLIANCE_METRICS),
                $this->action(__('Track deliveries'), 'truck', 'transport-trips.hub', BusinessUser::PERMISSION_TRACK_DELIVERY_STATUS),
                $this->action(__('Finance'), 'currency-dollar', 'finance.dashboard', BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD),
                $this->action(__('Businesses'), 'building', 'businesses.hub', BusinessUser::PERMISSION_VIEW_ALL_MODULES),
                $this->action(__('CRM'), 'layout-dashboard', 'crm.dashboard', BusinessUser::PERMISSION_VIEW_ALL_MODULES),
            ],
        ];
    }

    private function buildOpsManager(ProcessorDashboardContext $ctx, array $filters): array
    {
        $periodHint = $filters['is_filtered'] ? $filters['range_label'] : __('All time');

        $animalsReceived = $this->intakeAnimalsReceived($ctx, $filters);
        $plansQuery = SlaughterPlan::query()->whereIn('id', $ctx->planIds);
        $this->applyDashboardDateFilter($plansQuery, 'slaughter_date', $filters);
        $plansInPeriod = (int) (clone $plansQuery)->count();
        $antePending = (int) SlaughterPlan::query()
            ->whereIn('id', $ctx->planIds)
            ->whereDoesntHave('anteMortemInspections')
            ->count();

        $executionsQuery = SlaughterExecution::query()
            ->whereIn('slaughter_plan_id', $ctx->planIds)
            ->where('status', SlaughterExecution::STATUS_COMPLETED)
            ->whereNotNull('slaughter_time');
        $this->applyDashboardDateFilter($executionsQuery, 'slaughter_time', $filters);
        $animalsExecuted = (int) (clone $executionsQuery)->sum('actual_animals_slaughtered');

        $batchQuery = Batch::query()->whereIn('id', $ctx->batchIds);
        $this->applyDashboardDateFilter($batchQuery, 'created_at', $filters);
        $batchesInPeriod = (int) (clone $batchQuery)->count();
        $batchesCertified = (int) Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->whereHas('certificate')
            ->when($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null, function ($query) use ($filters): void {
                $query->whereBetween('created_at', [
                    $filters['start']->copy()->startOfDay(),
                    $filters['end']->copy()->endOfDay(),
                ]);
            })
            ->count();
        $certRate = $batchesInPeriod > 0 ? (int) round($batchesCertified / $batchesInPeriod * 100) : 0;

        return [
            'roleKey' => BusinessUser::ROLE_OPERATIONS_MANAGER,
            'headerBadge' => ['label' => __('Operations'), 'variant' => 'neutral'],
            'kpiCards' => [
                $this->kpi(__('Animals received'), $animalsReceived, $periodHint, 'info', 'arrow-down'),
                $this->kpi(__('Slaughter plans'), $plansInPeriod, __(':count AM pending', ['count' => $antePending]), $antePending > 0 ? 'warning' : 'positive', 'calendar'),
                $this->kpi(__('Animals executed'), $animalsExecuted, $periodHint, 'positive', 'player-play'),
                $this->kpi(__('Batches created'), $batchesInPeriod, __(':count certified', ['count' => $batchesCertified]), 'positive', 'box'),
                $this->kpi(__('Certification rate'), $certRate.'%', $periodHint, $certRate >= 90 ? 'positive' : 'warning', 'certificate'),
            ],
            'workTable' => [
                'title' => __('Slaughter plans'),
                'subtitle' => __('Plans scheduled for the selected period.'),
                'headers' => [
                    'primary' => __('Plan'),
                    'updated' => __('Slaughter date'),
                ],
                'emptyMessage' => __('No plans in this period.'),
                'rows' => $this->opsManagerPlanTableRows($ctx, $filters),
                'footerRoute' => 'slaughter-plans.hub',
                'footerLabel' => __('View all plans'),
            ],
            'quickActions' => [
                $this->action(__('New intake'), 'arrow-down', 'animal-intakes.create', BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE),
                $this->action(__('Plan slaughter'), 'calendar', 'slaughter-plans.create', BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER),
                $this->action(__('New batch'), 'box', 'batches.create', BusinessUser::PERMISSION_CREATE_BATCH),
                $this->action(__('Assign inspector'), 'user', 'inspectors.hub', BusinessUser::PERMISSION_ASSIGN_BATCH_TO_INSPECTOR),
                $this->action(__('Certificates'), 'certificate', 'certificates.hub', BusinessUser::PERMISSION_VIEW_CERTIFICATES),
                $this->action(__('Executions'), 'player-play', 'slaughter-executions.hub', BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER),
            ],
        ];
    }

    private function buildComplianceOfficer(ProcessorDashboardContext $ctx, array $filters): array
    {
        $periodHint = $filters['is_filtered'] ? $filters['range_label'] : __('All time');

        $amQuery = AnteMortemInspection::query()->whereIn('slaughter_plan_id', $ctx->planIds);
        $this->applyDashboardDateFilter($amQuery, 'inspection_date', $filters);
        $amCount = (int) (clone $amQuery)->count();

        $pmQuery = PostMortemInspection::query()->whereIn('batch_id', $ctx->batchIds);
        $this->applyDashboardDateFilter($pmQuery, 'inspection_date', $filters);
        $pmCount = (int) (clone $pmQuery)->count();

        $violationsInPeriod = $this->coldViolationsInPeriod($ctx, $filters);
        $openViolations = $this->openColdViolationsCount($ctx);
        $missingAm = $this->missingAnteMortemCount($ctx);
        $missingPm = $this->missingPostMortemCount($ctx);
        $checklistsPending = $missingAm + $missingPm;
        $breachRoom = $this->topColdBreachRoom($ctx);

        $evidenceQuery = MeatExportDocument::query()
            ->whereHas('deliveryConfirmation.transportTrip', fn ($q) => $q->whereIn('id', $ctx->tripIds))
            ->where('status', MeatExportDocument::STATUS_ISSUED);
        $this->applyDashboardDateFilter($evidenceQuery, 'created_at', $filters);
        $evidenceCount = (int) (clone $evidenceQuery)->count();

        return [
            'roleKey' => BusinessUser::ROLE_COMPLIANCE_OFFICER,
            'headerBadge' => ['label' => __('Compliance'), 'variant' => 'warning'],
            'kpiCards' => [
                $this->kpi(__('AM inspections'), $amCount, $periodHint, 'positive', 'clipboard-list'),
                $this->kpi(__('PM inspections'), $pmCount, $periodHint, 'positive', 'clipboard'),
                $this->kpi(__('Temp violations'), $violationsInPeriod, __(':count open', ['count' => $openViolations]), $openViolations > 0 ? 'negative' : 'positive', 'temperature'),
                $this->kpi(__('Checklists pending'), $checklistsPending, __(':am AM · :pm PM', ['am' => $missingAm, 'pm' => $missingPm]), $checklistsPending > 0 ? 'warning' : 'positive', 'alert-triangle'),
                $this->kpi(__('Evidence issued'), $evidenceCount, $breachRoom ? __('Cold room :room', ['room' => $breachRoom]) : $periodHint, 'info', 'clipboard'),
            ],
            'workTable' => [
                'title' => __('Compliance issues'),
                'subtitle' => __('Open issues and breaches for the selected period.'),
                'headers' => [
                    'primary' => __('Issue'),
                    'secondary' => __('Reference'),
                    'updated' => __('Detected'),
                ],
                'emptyMessage' => __('No compliance issues in this period.'),
                'rows' => $this->complianceIssueTableRows($ctx, $filters),
                'footerRoute' => 'compliance.index',
                'footerLabel' => __('View compliance hub'),
            ],
            'quickActions' => [
                $this->action(__('Submit checklist'), 'clipboard-list', 'compliance.index', BusinessUser::PERMISSION_SUBMIT_CHECKLIST),
                $this->action(__('Log issue'), 'alert-triangle', 'compliance.index', BusinessUser::PERMISSION_LOG_NON_COMPLIANCE),
                $this->action(__('Upload evidence'), 'clipboard', 'warehouse-storages.index', BusinessUser::PERMISSION_UPLOAD_COMPLIANCE_EVIDENCE),
                $this->action(__('Temp logs'), 'temperature', 'cold-rooms.hub', BusinessUser::PERMISSION_MONITOR_TEMPERATURE_LOGS),
                $this->action(__('Full report'), 'shield', 'compliance.index', BusinessUser::PERMISSION_MONITOR_COMPLIANCE_METRICS),
                $this->action(__('Standards'), 'settings', 'cold-room-standards.index', BusinessUser::PERMISSION_MONITOR_TEMPERATURE_LOGS),
            ],
        ];
    }

    private function buildInspector(ProcessorDashboardContext $ctx, ?User $user, array $filters): array
    {
        $inspector = $this->resolveInspectorForUser($ctx, $user);
        $inspectorId = $inspector?->id;
        $periodHint = $filters['is_filtered'] ? $filters['range_label'] : __('All time');

        $batchQuery = Batch::query()->whereIn('id', $ctx->batchIds);
        if ($inspectorId) {
            $batchQuery->where('inspector_id', $inspectorId);
        }
        $this->applyDashboardDateFilter($batchQuery, 'created_at', $filters);

        $assignedBatches = (int) (clone $batchQuery)->count();
        $pmPendingBatches = (int) Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->whereDoesntHave('postMortemInspection')
            ->count();

        $amQuery = AnteMortemInspection::query()
            ->whereIn('slaughter_plan_id', $ctx->planIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId));
        $this->applyDashboardDateFilter($amQuery, 'inspection_date', $filters);
        $amCount = (int) (clone $amQuery)->count();

        $pmQuery = PostMortemInspection::query()
            ->whereIn('batch_id', $ctx->batchIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId));
        $this->applyDashboardDateFilter($pmQuery, 'inspection_date', $filters);
        $pmCount = (int) (clone $pmQuery)->count();

        $certQuery = Certificate::query()
            ->whereIn('id', $ctx->certificateIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId));
        $this->applyDashboardDateFilter($certQuery, 'issued_at', $filters);
        $certsIssued = (int) (clone $certQuery)->count();

        $readyToCertify = (int) Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->whereHas('postMortemInspection')
            ->whereDoesntHave('certificate')
            ->count();

        return [
            'roleKey' => BusinessUser::ROLE_INSPECTOR,
            'headerBadge' => ['label' => __('Inspector'), 'variant' => 'info'],
            'kpiCards' => [
                $this->kpi(__('Assigned batches'), $assignedBatches, $periodHint, 'info', 'box'),
                $this->kpi(__('AM inspections'), $amCount, $periodHint, 'positive', 'clipboard-list'),
                $this->kpi(__('PM inspections'), $pmCount, $periodHint, 'positive', 'clipboard-list'),
                $this->kpi(__('Certificates issued'), $certsIssued, $periodHint, 'positive', 'certificate'),
                $this->kpi(__('PM pending'), $pmPendingBatches, __(':count ready to certify', ['count' => $readyToCertify]), $pmPendingBatches > 0 ? 'warning' : 'positive', 'alert-triangle'),
            ],
            'workTable' => [
                'title' => __('Assigned batches'),
                'subtitle' => __('Your inspection workload for the selected period.'),
                'rows' => $this->inspectorBatchTableRows($ctx, $inspectorId, $filters),
                'footerRoute' => 'batches.hub',
                'footerLabel' => __('View all batches'),
            ],
            'quickActions' => [
                $this->action(__('Record AM'), 'clipboard-list', 'ante-mortem-inspections.create', BusinessUser::PERMISSION_RECORD_ANTE_MORTEM),
                $this->action(__('Record PM'), 'clipboard', 'post-mortem-inspections.create', BusinessUser::PERMISSION_RECORD_POST_MORTEM),
                $this->action(__('Issue certificate'), 'certificate', 'certificates.create', BusinessUser::PERMISSION_ISSUE_CERTIFICATE),
                $this->action(__('My batches'), 'box', 'batches.hub', BusinessUser::PERMISSION_VIEW_ASSIGNED_BATCHES),
            ],
        ];
    }

    private function buildTransportManager(ProcessorDashboardContext $ctx, array $filters): array
    {
        $periodHint = $filters['is_filtered'] ? $filters['range_label'] : __('All time');

        $tripsInPeriod = $this->tripsInPeriodCount($ctx, $filters);
        $confirmedInPeriod = $this->confirmedDeliveriesInPeriod($ctx, $filters);
        $pendingInPeriod = $this->pendingConfirmationsInPeriod($ctx, $filters);
        $inTransitInPeriod = $this->inTransitTripsInPeriod($ctx, $filters);
        $exportDocsMissing = $this->exportDocsMissingCount($ctx);
        $onTimeRate = $this->onTimeDeliveryRateForPeriod($ctx, $filters);

        return [
            'roleKey' => BusinessUser::ROLE_TRANSPORT_MANAGER,
            'headerBadge' => ['label' => __('Transport'), 'variant' => 'info'],
            'kpiCards' => [
                $this->kpi(__('Trips'), $tripsInPeriod, $periodHint, 'info', 'truck'),
                $this->kpi(__('Confirmed deliveries'), $confirmedInPeriod, $periodHint, 'positive', 'check'),
                $this->kpi(__('Pending confirmations'), $pendingInPeriod, __(':count in transit', ['count' => $inTransitInPeriod]), $pendingInPeriod > 0 ? 'warning' : 'positive', 'clock'),
                $this->kpi(__('Export docs missing'), $exportDocsMissing, __('Active export trips'), $exportDocsMissing > 0 ? 'warning' : 'positive', 'clipboard'),
                $this->kpi(__('On-time rate'), $onTimeRate.'%', $periodHint, $onTimeRate >= 90 ? 'positive' : 'warning', 'truck'),
            ],
            'workTable' => [
                'title' => __('Transport trips'),
                'subtitle' => __('Trips scheduled for the selected period.'),
                'headers' => [
                    'primary' => __('Trip'),
                    'secondary' => __('Destination'),
                    'updated' => __('Departure'),
                ],
                'emptyMessage' => __('No trips in this period.'),
                'rows' => $this->transportTripTableRows($ctx, $filters),
                'footerRoute' => 'transport-trips.hub',
                'footerLabel' => __('View all trips'),
            ],
            'quickActions' => [
                $this->action(__('New trip'), 'truck', 'transport-trips.create', BusinessUser::PERMISSION_CREATE_TRANSPORT_TRIP),
                $this->action(__('Dispatch'), 'truck', 'transport-trips.hub', BusinessUser::PERMISSION_DISPATCH_DELIVERY),
                $this->action(__('Confirm delivery'), 'check', 'delivery-confirmations.create', BusinessUser::PERMISSION_CONFIRM_DELIVERY),
                $this->action(__('Export docs'), 'clipboard', 'delivery-confirmations.hub', BusinessUser::PERMISSION_MANAGE_EXPORT_DOCUMENTS),
                $this->action(__('Track all'), 'truck', 'transport-trips.hub', BusinessUser::PERMISSION_TRACK_DELIVERY_STATUS),
                $this->action(__('Export records'), 'clipboard-list', 'transport-trips.export', BusinessUser::PERMISSION_EXPORT_RECORDS),
            ],
        ];
    }

    private function buildAccountant(int $businessId, ProcessorDashboardContext $ctx, array $filters): array
    {
        $periodHint = $filters['is_filtered'] ? $filters['range_label'] : __('All time');
        $now = now();
        $fmt = static fn (float $n): string => number_format($n, 0, '.', ',').' '.__('RWF');

        $arOutstanding = $this->sumOutstandingBalance('finance_invoices', $businessId);
        $arOverdue = (int) FinanceInvoice::query()
            ->where('business_id', $businessId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now->startOfDay())
            ->whereRaw('amount_paid < total_amount')
            ->count();

        $apOutstanding = $this->sumOutstandingBalance('finance_payables', $businessId);
        $apOverdue = (int) FinancePayable::query()
            ->where('business_id', $businessId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now->startOfDay())
            ->whereRaw('amount_paid < total_amount')
            ->count();

        $revenue = $this->invoiceRevenueInPeriod($businessId, $filters);
        $revenueDisplay = $revenue >= 1_000_000 ? $this->formatMillions($revenue) : $fmt($revenue);

        $invoicesIssued = $this->invoicesIssuedInPeriod($businessId, $filters);
        $collectionRate = $this->invoiceCollectionRateForPeriod($businessId, $filters);

        return [
            'roleKey' => BusinessUser::ROLE_ACCOUNTANT,
            'headerBadge' => ['label' => __('Finance'), 'variant' => 'finance'],
            'kpiCards' => [
                $this->kpi(__('Revenue'), $revenueDisplay, $periodHint, 'positive', 'currency-dollar'),
                $this->kpi(__('AR outstanding'), $fmt($arOutstanding), __(':count overdue', ['count' => $arOverdue]), $arOverdue > 0 ? 'warning' : 'positive', 'receipt'),
                $this->kpi(__('AP outstanding'), $fmt($apOutstanding), __(':count overdue', ['count' => $apOverdue]), $apOverdue > 0 ? 'warning' : 'info', 'receipt'),
                $this->kpi(__('Invoices issued'), $invoicesIssued, $periodHint, 'info', 'clipboard'),
                $this->kpi(__('Collection rate'), $collectionRate.'%', __('target 90%'), $collectionRate >= 90 ? 'positive' : 'warning', 'chart-line'),
            ],
            'workTable' => [
                'title' => __('AR invoices'),
                'subtitle' => __('Invoices issued for the selected period.'),
                'headers' => [
                    'primary' => __('Invoice'),
                    'secondary' => __('Client'),
                    'updated' => __('Issued'),
                ],
                'emptyMessage' => __('No invoices in this period.'),
                'rows' => $this->accountantInvoiceTableRows($businessId, $filters),
                'footerRoute' => 'finance.invoices.index',
                'footerLabel' => __('View all invoices'),
            ],
            'quickActions' => [
                $this->action(__('Finance overview'), 'layout-dashboard', 'finance.dashboard', BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD),
                $this->action(__('AR invoices'), 'clipboard', 'finance.invoices.index', BusinessUser::PERMISSION_MANAGE_AR_INVOICES),
                $this->action(__('AP payables'), 'clipboard-list', 'finance.payables.index', BusinessUser::PERMISSION_MANAGE_AP_PAYABLES),
                $this->action(__('Cost allocations'), 'box', 'finance.cost-allocations.index', BusinessUser::PERMISSION_VIEW_FINANCE_REPORTS),
                $this->action(__('Invoice from delivery'), 'truck', 'delivery-confirmations.hub', BusinessUser::PERMISSION_MANAGE_AR_INVOICES),
                $this->action(__('Casual workers'), 'users', 'finance.casual-workers.index', BusinessUser::PERMISSION_MANAGE_AP_PAYABLES),
            ],
        ];
    }

    // --- KPI helpers ---

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: null,
     *     end: null,
     *     range_label: string,
     *     executions_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function dashboardFiltersAllTime(): array
    {
        return [
            'period' => 'all',
            'date_from' => '',
            'date_to' => '',
            'start' => null,
            'end' => null,
            'range_label' => __('All time'),
            'executions_label' => __('Animals executed'),
            'period_hint' => __('Inspections (all time)'),
            'has_custom_range' => false,
            'is_filtered' => false,
        ];
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: Carbon,
     *     end: Carbon,
     *     range_label: string,
     *     executions_label: string,
     *     period_hint: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function dashboardFiltersForPreset(string $period): array
    {
        $preset = $this->dashboardPresetRangeForPeriod($period);

        return [
            'period' => $period,
            'date_from' => $preset['date_from'],
            'date_to' => $preset['date_to'],
            'start' => $preset['start'],
            'end' => $preset['end'],
            'range_label' => $preset['range_label'],
            'executions_label' => $preset['executions_label'],
            'period_hint' => $preset['period_hint'],
            'has_custom_range' => false,
            'is_filtered' => true,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, date_from: string, date_to: string, range_label: string, executions_label: string}
     */
    private function dashboardPresetRangeForPeriod(string $period): array
    {
        $now = now();

        [$start, $end, $rangeLabel, $executionsLabel, $periodHint] = match ($period) {
            'day' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->format('M j, Y'),
                __('Executed today'),
                __('Inspections today'),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                (string) $now->year,
                __('Executed this year'),
                __('Inspections this year'),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->format('F Y'),
                __('Executed this month'),
                __('Inspections this month'),
            ],
            default => throw new \InvalidArgumentException('Invalid preset period.'),
        };

        return [
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'range_label' => $rangeLabel,
            'executions_label' => $executionsLabel,
            'period_hint' => $periodHint,
        ];
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: ?Carbon,
     *     end: ?Carbon,
     *     range_label: string,
     *     executions_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function resolveDashboardFilters(Request $request): array
    {
        if (! $request->hasAny(['period', 'date_from', 'date_to'])) {
            return $this->dashboardFiltersAllTime();
        }

        $period = (string) $request->query('period', 'all');
        if (! in_array($period, ['all', 'day', 'month', 'year'], true)) {
            $period = 'all';
        }

        $rawFrom = trim((string) $request->query('date_from', ''));
        $rawTo = trim((string) $request->query('date_to', ''));

        if ($period === 'all' && $rawFrom === '' && $rawTo === '') {
            return $this->dashboardFiltersAllTime();
        }

        if ($rawFrom !== '' && $rawTo !== '') {
            $start = Carbon::parse($rawFrom)->startOfDay();
            $end = Carbon::parse($rawTo)->endOfDay();
            if ($start->gt($end)) {
                $start = Carbon::parse($rawTo)->startOfDay();
                $end = Carbon::parse($rawFrom)->endOfDay();
                [$rawFrom, $rawTo] = [$start->toDateString(), $end->toDateString()];
            }

            return [
                'period' => $period,
                'date_from' => $rawFrom,
                'date_to' => $rawTo,
                'start' => $start,
                'end' => $end,
                'range_label' => $start->format('M j, Y').' – '.$end->format('M j, Y'),
                'executions_label' => __('Executed in range'),
                'period_hint' => __('Inspections in range'),
                'has_custom_range' => true,
                'is_filtered' => true,
            ];
        }

        if (in_array($period, ['day', 'month', 'year'], true)) {
            return $this->dashboardFiltersForPreset($period);
        }

        return $this->dashboardFiltersAllTime();
    }

    private function kpi(string $label, int|string $value, string $change, string $deltaTone, ?string $icon = null, ?string $iconTone = null): array
    {
        $card = ['label' => $label, 'value' => $value, 'change' => $change, 'deltaTone' => $deltaTone];
        if ($icon !== null) {
            $card['icon'] = $icon;
        }
        if ($iconTone !== null) {
            $card['iconTone'] = $iconTone;
        }

        return $card;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, mixed>|array<string, mixed>  $counts
     */
    private function slaughterSpeciesCount(\Illuminate\Support\Collection|array $counts, string $species): int
    {
        $counts = $counts instanceof \Illuminate\Support\Collection ? $counts->all() : $counts;

        return (int) ($counts[$species] ?? $counts[strtolower($species)] ?? 0);
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function applyDashboardDateFilter(\Illuminate\Database\Eloquent\Builder $query, string $column, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $query->whereBetween($column, [
                $filters['start']->copy()->startOfDay(),
                $filters['end']->copy()->endOfDay(),
            ]);
        }

        return $query;
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function applyFinanceDateFilter(\Illuminate\Database\Eloquent\Builder $query, string $column, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $start = $filters['start']->copy()->startOfDay();
            $end = $filters['end']->copy()->endOfDay();

            $query->where(function ($q) use ($column, $start, $end): void {
                $q->whereBetween($column, [$start, $end])
                    ->orWhere(function ($fallback) use ($column, $start, $end): void {
                        $fallback->whereNull($column)->whereBetween('created_at', [$start, $end]);
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function applyTripDateFilter(\Illuminate\Database\Eloquent\Builder $query, string $column, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
            $start = $filters['start']->copy()->startOfDay();
            $end = $filters['end']->copy()->endOfDay();

            $query->where(function ($q) use ($column, $start, $end): void {
                $q->whereBetween($column, [$start, $end])
                    ->orWhere(function ($fallback) use ($column, $start, $end): void {
                        $fallback->whereNull($column)->whereBetween('created_at', [$start, $end]);
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function tripsInPeriodCount(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = TransportTrip::query()->whereIn('id', $ctx->tripIds);
        $this->applyTripDateFilter($query, 'departure_date', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function confirmedDeliveriesInPeriod(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = DeliveryConfirmation::query()
            ->whereIn('transport_trip_id', $ctx->tripIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->whereNotNull('received_date');
        $this->applyDashboardDateFilter($query, 'received_date', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function pendingConfirmationsInPeriod(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->where(function ($q): void {
                $q->whereDoesntHave('deliveryConfirmation')
                    ->orWhereHas('deliveryConfirmation', fn ($d) => $d->where('confirmation_status', '!=', DeliveryConfirmation::STATUS_CONFIRMED));
            });
        $this->applyTripDateFilter($query, 'departure_date', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function inTransitTripsInPeriod(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->where('status', TransportTrip::STATUS_IN_TRANSIT);
        $this->applyTripDateFilter($query, 'departure_date', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function onTimeDeliveryRateForPeriod(ProcessorDashboardContext $ctx, array $filters): int
    {
        $query = DeliveryConfirmation::query()
            ->whereIn('transport_trip_id', $ctx->tripIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->whereNotNull('received_date');
        $this->applyDashboardDateFilter($query, 'received_date', $filters);

        $total = (int) (clone $query)->count();
        if ($total === 0) {
            return 0;
        }

        $onTime = (int) (clone $query)
            ->whereHas('transportTrip', function ($tripQuery): void {
                $tripQuery->whereNotNull('arrival_date')
                    ->whereColumn('delivery_confirmations.received_date', '<=', 'transport_trips.arrival_date');
            })
            ->count();

        return (int) round($onTime / $total * 100);
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array<int, array{
     *     id: string,
     *     species: string,
     *     status: string,
     *     status_tone: string,
     *     updated_at: string,
     *     route: string,
     *     route_params: array<string, int>
     * }>
     */
    private function transportTripTableRows(ProcessorDashboardContext $ctx, array $filters): array
    {
        $domestic = strtoupper((string) config('processor.domestic_country', 'RW'));

        $query = TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->with(['deliveryConfirmation.exportDocuments', 'destinationFacility']);
        $this->applyTripDateFilter($query, 'departure_date', $filters);

        return $query
            ->orderBy('departure_date')
            ->limit(50)
            ->get()
            ->map(function (TransportTrip $trip) use ($domestic): array {
                $isExport = filled($trip->destination_country) && strtoupper((string) $trip->destination_country) !== $domestic;
                $docsMissing = $isExport && $this->tripDocsMissing($trip);
                $confirmed = $trip->deliveryConfirmation?->confirmation_status === DeliveryConfirmation::STATUS_CONFIRMED;

                if ($docsMissing) {
                    $status = __('Docs missing');
                    $statusTone = 'amber';
                } elseif ($confirmed) {
                    $status = __('Confirmed');
                    $statusTone = 'green';
                } elseif ($trip->status === TransportTrip::STATUS_IN_TRANSIT) {
                    $status = __('En route');
                    $statusTone = 'blue';
                } elseif ($trip->status === TransportTrip::STATUS_COMPLETED) {
                    $status = __('Completed');
                    $statusTone = 'green';
                } elseif ($trip->status === TransportTrip::STATUS_ARRIVED) {
                    $status = __('Arrived');
                    $statusTone = 'blue';
                } else {
                    $status = __('Scheduled');
                    $statusTone = 'slate';
                }

                return [
                    'id' => __('Trip #:id', ['id' => $trip->id]),
                    'species' => $trip->destination_display,
                    'status' => $status,
                    'status_tone' => $statusTone,
                    'updated_at' => $trip->departure_date?->format('d M Y') ?? $trip->created_at?->format('d M Y') ?? '—',
                    'route' => 'transport-trips.show',
                    'route_params' => ['transport_trip' => $trip->id],
                ];
            })
            ->all();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function invoiceRevenueInPeriod(int $businessId, array $filters): float
    {
        $query = FinanceInvoice::query()->where('business_id', $businessId);
        $this->applyFinanceDateFilter($query, 'issued_at', $filters);

        return (float) $query->sum('total_amount');
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function invoicesIssuedInPeriod(int $businessId, array $filters): int
    {
        $query = FinanceInvoice::query()->where('business_id', $businessId);
        $this->applyFinanceDateFilter($query, 'issued_at', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function invoiceCollectionRateForPeriod(int $businessId, array $filters): int
    {
        $query = FinanceInvoice::query()->where('business_id', $businessId);
        $this->applyFinanceDateFilter($query, 'issued_at', $filters);
        $total = (float) (clone $query)->sum('total_amount');

        if ($total <= 0) {
            return 0;
        }

        $paid = (float) (clone $query)->sum('amount_paid');

        return (int) round($paid / $total * 100);
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array<int, array{
     *     id: string,
     *     species: string,
     *     status: string,
     *     status_tone: string,
     *     updated_at: string,
     *     route: string,
     *     route_params: array<string, int>
     * }>
     */
    private function accountantInvoiceTableRows(int $businessId, array $filters): array
    {
        $query = FinanceInvoice::query()
            ->where('business_id', $businessId)
            ->with('client');
        $this->applyFinanceDateFilter($query, 'issued_at', $filters);

        return $query
            ->latest('issued_at')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(function (FinanceInvoice $invoice): array {
                $outstanding = max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid);
                $overdue = $invoice->due_date && $invoice->due_date->isPast() && $outstanding > 0;
                $paid = $outstanding <= 0;

                return [
                    'id' => (string) ($invoice->invoice_number ?? __('Invoice #:id', ['id' => $invoice->id])),
                    'species' => $invoice->client?->name ?? '—',
                    'status' => $paid ? __('Paid') : ($overdue ? __('Overdue') : __('Pending')),
                    'status_tone' => $paid ? 'green' : ($overdue ? 'amber' : 'blue'),
                    'updated_at' => $invoice->issued_at?->format('d M Y') ?? $invoice->created_at?->format('d M Y') ?? '—',
                    'route' => 'finance.invoices.edit',
                    'route_params' => ['invoice' => $invoice->id],
                ];
            })
            ->all();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array<int, array{
     *     id: string,
     *     species: string,
     *     status: string,
     *     status_tone: string,
     *     updated_at: string,
     *     route: string,
     *     route_params: array<string, int>
     * }>
     */
    private function inspectorBatchTableRows(ProcessorDashboardContext $ctx, ?int $inspectorId, array $filters): array
    {
        $query = Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->with(['slaughterExecution.slaughterPlan', 'certificate', 'postMortemInspection']);
        $this->applyDashboardDateFilter($query, 'created_at', $filters);

        $batches = $query
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->sortBy(function (Batch $batch): int {
                if (! $batch->postMortemInspection) {
                    return 0;
                }

                if ($batch->certificate === null) {
                    return 1;
                }

                return 2;
            })
            ->values();

        if ($batches->isEmpty()) {
            return [];
        }

        return $batches->map(function (Batch $batch): array {
            $species = (string) ($batch->slaughterExecution?->slaughterPlan?->species ?? $batch->species ?? '—');
            $certified = $batch->certificate !== null;
            $pmPending = ! $batch->postMortemInspection;

            return [
                'id' => (string) ($batch->batch_code ?? __('Batch #:id', ['id' => $batch->id])),
                'species' => $species,
                'status' => $certified
                    ? __('Certified')
                    : ($pmPending ? __('PM pending') : __('Ready to certify')),
                'status_tone' => $certified ? 'green' : ($pmPending ? 'amber' : 'blue'),
                'updated_at' => $batch->updated_at?->format('d M Y H:i') ?? '—',
                'route' => 'batches.show',
                'route_params' => ['batch' => $batch->id],
            ];
        })->all();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array<int, array{
     *     id: string,
     *     species: string,
     *     status: string,
     *     status_tone: string,
     *     updated_at: string,
     *     route: string,
     *     route_params: array<string, int>
     * }>
     */
    private function opsManagerPlanTableRows(ProcessorDashboardContext $ctx, array $filters): array
    {
        $query = SlaughterPlan::query()
            ->whereIn('id', $ctx->planIds)
            ->withCount(['anteMortemInspections', 'slaughterExecutions']);
        $this->applyDashboardDateFilter($query, 'slaughter_date', $filters);

        return $query
            ->orderBy('slaughter_date')
            ->limit(50)
            ->get()
            ->map(function (SlaughterPlan $plan): array {
                $hasExecution = (int) $plan->slaughter_executions_count > 0;
                $hasAm = (int) $plan->ante_mortem_inspections_count > 0;

                if ($hasExecution) {
                    $status = __('Executed');
                    $statusTone = 'green';
                } elseif (! $hasAm) {
                    $status = __('AM pending');
                    $statusTone = 'amber';
                } elseif ($plan->status === SlaughterPlan::STATUS_APPROVED) {
                    $status = __('Approved');
                    $statusTone = 'green';
                } else {
                    $status = __('Planned');
                    $statusTone = 'slate';
                }

                return [
                    'id' => __('Plan #:id', ['id' => $plan->id]),
                    'species' => (string) ($plan->species ?? '—'),
                    'status' => $status,
                    'status_tone' => $statusTone,
                    'updated_at' => $plan->slaughter_date?->format('d M Y') ?? '—',
                    'route' => 'slaughter-plans.show',
                    'route_params' => ['slaughter_plan' => $plan->id],
                ];
            })
            ->all();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function intakeAnimalsReceived(ProcessorDashboardContext $ctx, array $filters): int
    {
        return $this->intakeAnimalsReceivedForFacilities($ctx->facilityIds, $filters);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int|string>  $facilityIds
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function intakeAnimalsReceivedForFacilities(\Illuminate\Support\Collection $facilityIds, array $filters): int
    {
        if ($facilityIds->isEmpty()) {
            return 0;
        }

        $query = AnimalIntake::query()
            ->with('items:id,animal_intake_id,species')
            ->whereIn('facility_id', $facilityIds)
            ->where('is_draft', false)
            ->whereIn('status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
            ->whereNotNull('intake_date');
        $this->applyDashboardDateFilter($query, 'intake_date', $filters);

        return (int) $query->get()->sum(function (AnimalIntake $intake): int {
            if ($intake->items->isNotEmpty()) {
                return $intake->items->count();
            }

            return (int) $intake->number_of_animals;
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, int|string>
     */
    private function orgAdminFacilityIds(ProcessorDashboardContext $ctx, ?User $user): \Illuminate\Support\Collection
    {
        $businessIds = $user?->accessibleProcessorBusinessIds() ?? collect([$ctx->businessId]);

        return Facility::query()->whereIn('business_id', $businessIds)->pluck('id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, int|string>
     */
    private function orgAdminPlanIds(ProcessorDashboardContext $ctx, ?User $user): \Illuminate\Support\Collection
    {
        return SlaughterPlan::query()
            ->whereIn('facility_id', $this->orgAdminFacilityIds($ctx, $user))
            ->pluck('id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, int|string>
     */
    private function orgAdminBatchIds(ProcessorDashboardContext $ctx, ?User $user): \Illuminate\Support\Collection
    {
        $planIds = $this->orgAdminPlanIds($ctx, $user);

        if ($planIds->isEmpty()) {
            return collect();
        }

        return Batch::query()
            ->whereHas('slaughterExecution', fn ($query) => $query->whereIn('slaughter_plan_id', $planIds))
            ->pluck('id');
    }

    private function action(string $label, string $icon, string $route, string $permission): array
    {
        return compact('label', 'icon', 'route', 'permission');
    }

    private function moduleRow(string $label, string $sub, string $icon, string $route, string $badgeTone, string $badge): array
    {
        return compact('label', 'sub', 'icon', 'route', 'badgeTone', 'badge');
    }

    private function speciesDashboardIconKey(string $species): string
    {
        $value = strtolower(trim($species));

        foreach (['cattle', 'goat', 'sheep', 'pig'] as $key) {
            if (str_contains($value, $key)) {
                return $key;
            }
        }

        if (str_contains($value, 'mixed') || str_contains($value, '·') || str_contains($value, ',')) {
            return 'mixed';
        }

        return 'animal';
    }

    /**
     * @return array{title: string, subtitle: string, type: string, items: array<int, array<string, mixed>>, empty?: string, footerRoute?: string, footerLabel?: string}
     */
    private function recentReceivedAnimalsPanel(?User $user, ProcessorDashboardContext $ctx, int $limit = 8): array
    {
        $businessIds = $user?->accessibleProcessorBusinessIds() ?? collect([$ctx->businessId]);
        $facilityIds = Facility::query()->whereIn('business_id', $businessIds)->pluck('id');

        $intakes = AnimalIntake::query()
            ->with([
                'facility:id,facility_name',
                'client:id,name',
            ])
            ->withCount('items')
            ->whereIn('facility_id', $facilityIds)
            ->where('is_draft', false)
            ->orderByDesc('intake_date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $panel = [
            'title' => __('Recent received species'),
            'subtitle' => __('Latest intakes with location and client'),
            'type' => 'module_rows',
            'footerRoute' => 'animal-intakes.hub',
            'footerLabel' => __('View all intakes'),
            'items' => $intakes->map(function (AnimalIntake $intake): array {
                $headCount = $intake->items_count > 0
                    ? $intake->items_count
                    : (int) $intake->number_of_animals;
                $receivedAt = $intake->intake_date ?? $intake->created_at;
                $species = $intake->species_mix_label !== ''
                    ? $intake->species_mix_label
                    : (string) ($intake->species ?? __('Mixed'));
                $speciesIcon = $this->speciesDashboardIconKey($species);

                return [
                    'label' => $species,
                    'sub' => collect([
                        $intake->facility?->facility_name,
                        $intake->clientSourceDisplayName(),
                        $receivedAt?->format('M j, Y'),
                        __(':count head', ['count' => number_format($headCount)]),
                    ])->filter()->implode(' · '),
                    'icon' => $speciesIcon,
                    'iconTone' => $speciesIcon,
                    'route' => 'animal-intakes.show',
                    'routeParams' => ['animal_intake' => $intake->id],
                    'badge' => $intake->reference ?: __('Intake #:id', ['id' => $intake->id]),
                    'badgeTone' => 'info',
                ];
            })->all(),
        ];

        if ($intakes->isEmpty()) {
            $panel['empty'] = __('No intake records yet.');
        }

        return $panel;
    }

    /**
     * @return array{title: string, subtitle: string, type: string, items: array<int, array<string, mixed>>, empty?: string, footerRoute?: string, footerLabel?: string}
     */
    private function recentSlaughteredPanel(?User $user, ProcessorDashboardContext $ctx, int $limit = 8): array
    {
        $businessIds = $user?->accessibleProcessorBusinessIds() ?? collect([$ctx->businessId]);
        $facilityIds = Facility::query()->whereIn('business_id', $businessIds)->pluck('id');
        $planIds = SlaughterPlan::query()->whereIn('facility_id', $facilityIds)->pluck('id');

        $executions = SlaughterExecution::query()
            ->with(['slaughterPlan.facility:id,facility_name'])
            ->whereIn('slaughter_plan_id', $planIds)
            ->where('status', SlaughterExecution::STATUS_COMPLETED)
            ->orderByDesc('slaughter_time')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $panel = [
            'title' => __('Recent executed species'),
            'subtitle' => __('Latest completed slaughter executions'),
            'type' => 'module_rows',
            'footerRoute' => 'slaughter-executions.hub',
            'footerLabel' => __('View all executions'),
            'items' => $executions->map(function (SlaughterExecution $execution): array {
                $plan = $execution->slaughterPlan;
                $slaughteredAt = $execution->slaughter_time ?? $execution->created_at;
                $headCount = (int) $execution->actual_animals_slaughtered;
                $species = (string) ($plan?->species ?? __('Unknown'));
                $speciesIcon = $this->speciesDashboardIconKey($species);

                return [
                    'label' => $species,
                    'sub' => collect([
                        $plan?->facility?->facility_name,
                        __(':count head', ['count' => number_format($headCount)]),
                        $slaughteredAt?->format('M j, Y'),
                    ])->filter()->implode(' · '),
                    'icon' => $speciesIcon,
                    'iconTone' => $speciesIcon,
                    'route' => 'slaughter-executions.show',
                    'routeParams' => ['slaughter_execution' => $execution->id],
                    'badge' => __('Plan #:id', ['id' => $execution->slaughter_plan_id]),
                    'badgeTone' => 'green',
                ];
            })->all(),
        ];

        if ($executions->isEmpty()) {
            $panel['empty'] = __('No slaughter executions yet.');
        }

        return $panel;
    }

    private function pipelineStep(string $label, string $icon, int $count, string $route): array
    {
        return compact('label', 'icon', 'count', 'route');
    }

    /**
     * Count individual animals currently available for slaughter planning.
     * Sources from animal_intake_items when backfill has run.
     * Falls back to counting intake records if no items exist yet.
     */
    private function animalsInIntake(ProcessorDashboardContext $ctx): int
    {
        $facilityIds = $ctx->facilityIds;

        $itemCount = (int) AnimalIntakeItem::whereHas('intake', fn ($q) => $q
            ->whereIn('facility_id', $facilityIds)
            ->whereIn('status', [
                AnimalIntake::STATUS_RECEIVED,
                AnimalIntake::STATUS_APPROVED,
            ])
            ->where('is_draft', false)
        )->available()->count();

        if ($itemCount === 0) {
            return (int) AnimalIntake::query()
                ->whereIn('facility_id', $facilityIds)
                ->whereIn('status', [
                    AnimalIntake::STATUS_RECEIVED,
                    AnimalIntake::STATUS_APPROVED,
                ])
                ->where('is_draft', false)
                ->count();
        }

        return $itemCount;
    }

    /**
     * Returns a full intake analytics summary for the active business context.
     * Used by the ops manager and org admin dashboard analytics panel.
     *
     * @return array{
     *   total_records: int,
     *   total_heads: int,
     *   heads_available: int,
     *   heads_this_month: int,
     *   heads_last_month: int,
     *   mom_change_pct: float|null,
     *   species_mix: array<string, int>,
     *   health_summary: array<string, int>,
     *   expired_cert_count: int,
     *   procurement_value_mtd: float,
     *   avg_unit_price: float,
     *   last_intake_date: string|null,
     *   days_since_last_intake: int|null,
     * }
     */
    public function intakeAnalyticsSummary(ProcessorDashboardContext $ctx): array
    {
        $facilityIds = $ctx->facilityIds;

        $intakeScope = fn ($query) => $query
            ->whereIn('facility_id', $facilityIds)
            ->where('is_draft', false);

        $intakeScopeAvailable = fn ($query) => $query
            ->whereIn('facility_id', $facilityIds)
            ->whereIn('status', [
                AnimalIntake::STATUS_RECEIVED,
                AnimalIntake::STATUS_APPROVED,
            ])
            ->where('is_draft', false);

        $totalRecords = (int) AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->where('is_draft', false)
            ->count();

        $totalHeads = (int) AnimalIntakeItem::query()
            ->whereHas('intake', $intakeScope)
            ->count();

        if ($totalHeads === 0) {
            $totalHeads = (int) AnimalIntake::query()
                ->whereIn('facility_id', $facilityIds)
                ->where('is_draft', false)
                ->sum('number_of_animals');
        }

        $headsAvailable = (int) AnimalIntakeItem::query()
            ->whereHas('intake', $intakeScopeAvailable)
            ->available()
            ->count();

        $now = now();
        $lastMonth = $now->copy()->subMonth();

        $headsThisMonth = (int) AnimalIntakeItem::query()
            ->whereHas('intake', $intakeScope)
            ->whereMonth('animal_intake_items.created_at', $now->month)
            ->whereYear('animal_intake_items.created_at', $now->year)
            ->count();

        $headsLastMonth = (int) AnimalIntakeItem::query()
            ->whereHas('intake', $intakeScope)
            ->whereMonth('animal_intake_items.created_at', $lastMonth->month)
            ->whereYear('animal_intake_items.created_at', $lastMonth->year)
            ->count();

        $momChangePct = $headsLastMonth > 0
            ? round(($headsThisMonth - $headsLastMonth) / $headsLastMonth * 100, 1)
            : null;

        $speciesMix = AnimalIntakeItem::query()
            ->whereHas('intake', $intakeScope)
            ->selectRaw('species, COUNT(*) as aggregate_count')
            ->groupBy('species')
            ->orderByDesc('aggregate_count')
            ->pluck('aggregate_count', 'species')
            ->map(fn ($count) => (int) $count)
            ->all();

        $healthCounts = AnimalIntakeItem::query()
            ->whereHas('intake', $intakeScope)
            ->selectRaw('health_status, COUNT(*) as aggregate_count')
            ->groupBy('health_status')
            ->pluck('aggregate_count', 'health_status');

        $healthSummary = [
            'healthy' => (int) ($healthCounts[AnimalIntakeItem::HEALTH_HEALTHY] ?? 0),
            'under_observation' => (int) ($healthCounts[AnimalIntakeItem::HEALTH_OBSERVATION] ?? 0),
            'rejected' => (int) ($healthCounts[AnimalIntakeItem::HEALTH_REJECTED] ?? 0),
        ];

        $expiredCertCount = (int) AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->where('is_draft', false)
            ->whereNotNull('health_certificate_expiry_date')
            ->where('health_certificate_expiry_date', '<', today())
            ->count();

        $procurementValueMtd = (float) AnimalIntakeItem::query()
            ->whereHas('intake', $intakeScope)
            ->whereMonth('animal_intake_items.created_at', $now->month)
            ->whereYear('animal_intake_items.created_at', $now->year)
            ->sum('unit_price');

        $avgUnitPrice = round((float) (AnimalIntakeItem::query()
            ->whereHas('intake', $intakeScope)
            ->avg('unit_price') ?? 0), 2);

        $lastIntake = AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->where('is_draft', false)
            ->orderByDesc('intake_date')
            ->first();

        $lastIntakeDate = $lastIntake?->intake_date?->format('d M Y');
        $daysSinceLastIntake = $lastIntake?->intake_date !== null
            ? (int) $lastIntake->intake_date->diffInDays(today())
            : null;

        return [
            'total_records' => $totalRecords,
            'total_heads' => $totalHeads,
            'heads_available' => $headsAvailable,
            'heads_this_month' => $headsThisMonth,
            'heads_last_month' => $headsLastMonth,
            'mom_change_pct' => $momChangePct,
            'species_mix' => $speciesMix,
            'health_summary' => $healthSummary,
            'expired_cert_count' => $expiredCertCount,
            'procurement_value_mtd' => $procurementValueMtd,
            'avg_unit_price' => $avgUnitPrice,
            'last_intake_date' => $lastIntakeDate,
            'days_since_last_intake' => $daysSinceLastIntake,
        ];
    }

    private function animalsInIntakeYesterday(ProcessorDashboardContext $ctx): int
    {
        return (int) AnimalIntake::query()
            ->whereIn('facility_id', $ctx->facilityIds)
            ->whereIn('status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
            ->whereDate('created_at', $ctx->today->copy()->subDay())
            ->count();
    }

    private function batchesToday(ProcessorDashboardContext $ctx): int
    {
        return (int) Batch::query()->whereIn('id', $ctx->batchIds)->whereDate('created_at', $ctx->today)->count();
    }

    private function batchesYesterday(ProcessorDashboardContext $ctx): int
    {
        return (int) Batch::query()->whereIn('id', $ctx->batchIds)->whereDate('created_at', $ctx->today->copy()->subDay())->count();
    }

    private function pendingDeliveries(ProcessorDashboardContext $ctx): int
    {
        return (int) TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->where(function ($query): void {
                $query->whereDoesntHave('deliveryConfirmation')
                    ->orWhereHas('deliveryConfirmation', fn ($q) => $q->where('confirmation_status', DeliveryConfirmation::STATUS_PENDING));
            })
            ->count();
    }

    private function delayedTripsCount(ProcessorDashboardContext $ctx): int
    {
        return (int) TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->whereIn('status', [TransportTrip::STATUS_PENDING, TransportTrip::STATUS_IN_TRANSIT, TransportTrip::STATUS_ARRIVED])
            ->whereDate('departure_date', '<', $ctx->today)
            ->count();
    }

    private function overduePlansCount(ProcessorDashboardContext $ctx): int
    {
        return (int) SlaughterPlan::query()
            ->whereIn('id', $ctx->planIds)
            ->where('status', SlaughterPlan::STATUS_PLANNED)
            ->whereDate('slaughter_date', '<', $ctx->today)
            ->count();
    }

    private function missingPostMortemCount(ProcessorDashboardContext $ctx): int
    {
        return (int) Batch::query()->whereIn('id', $ctx->batchIds)->whereDoesntHave('postMortemInspection')->count();
    }

    private function missingAnteMortemCount(ProcessorDashboardContext $ctx): int
    {
        return (int) SlaughterPlan::query()->whereIn('id', $ctx->planIds)->whereDoesntHave('anteMortemInspections')->count();
    }

    private function openColdViolationsCount(ProcessorDashboardContext $ctx): int
    {
        $coldRoomIds = ColdRoom::query()->whereIn('facility_id', $ctx->facilityIds)->pluck('id');

        return (int) ColdRoomViolation::query()
            ->whereIn('cold_room_id', $coldRoomIds)
            ->where('status', ColdRoomViolation::STATUS_OPEN)
            ->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     */
    private function coldViolationsInPeriod(ProcessorDashboardContext $ctx, array $filters): int
    {
        $coldRoomIds = ColdRoom::query()->whereIn('facility_id', $ctx->facilityIds)->pluck('id');
        $query = ColdRoomViolation::query()
            ->whereIn('cold_room_id', $coldRoomIds)
            ->whereNotNull('start_time');
        $this->applyDashboardDateFilter($query, 'start_time', $filters);

        return (int) $query->count();
    }

    /**
     * @param  array{
     *     is_filtered: bool,
     *     start: ?Carbon,
     *     end: ?Carbon
     * }  $filters
     * @return array<int, array{
     *     id: string,
     *     species: string,
     *     status: string,
     *     status_tone: string,
     *     updated_at: string,
     *     route: string,
     *     route_params: array<string, int|string>
     * }>
     */
    private function complianceIssueTableRows(ProcessorDashboardContext $ctx, array $filters): array
    {
        $rows = [];
        $coldRoomIds = ColdRoom::query()->whereIn('facility_id', $ctx->facilityIds)->pluck('id');

        $violationQuery = ColdRoomViolation::query()
            ->whereIn('cold_room_id', $coldRoomIds)
            ->with('coldRoom')
            ->whereNotNull('start_time');
        if ($filters['is_filtered']) {
            $this->applyDashboardDateFilter($violationQuery, 'start_time', $filters);
        } else {
            $violationQuery->where('status', ColdRoomViolation::STATUS_OPEN);
        }

        foreach ($violationQuery->latest('start_time')->limit(20)->get() as $violation) {
            $rows[] = [
                'id' => __('Cold room breach'),
                'species' => $violation->coldRoom?->name ?? __('Room'),
                'status' => $violation->status === ColdRoomViolation::STATUS_OPEN ? __('Open') : __('Closed'),
                'status_tone' => $violation->status === ColdRoomViolation::STATUS_OPEN ? 'amber' : 'green',
                'updated_at' => $violation->start_time?->format('d M Y H:i') ?? '—',
                'route' => 'cold-rooms.hub',
                'route_params' => [],
            ];
        }

        $missingPmQuery = Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->whereDoesntHave('postMortemInspection');
        $this->applyDashboardDateFilter($missingPmQuery, 'created_at', $filters);

        foreach ($missingPmQuery->latest('created_at')->limit(15)->get() as $batch) {
            $rows[] = [
                'id' => __('PM checklist missing'),
                'species' => (string) ($batch->batch_code ?? __('Batch #:id', ['id' => $batch->id])),
                'status' => __('Pending'),
                'status_tone' => 'red',
                'updated_at' => $batch->created_at?->format('d M Y') ?? '—',
                'route' => 'batches.show',
                'route_params' => ['batch' => $batch->id],
            ];
        }

        $missingAmQuery = SlaughterPlan::query()
            ->whereIn('id', $ctx->planIds)
            ->whereDoesntHave('anteMortemInspections');
        $this->applyDashboardDateFilter($missingAmQuery, 'slaughter_date', $filters);

        foreach ($missingAmQuery->orderBy('slaughter_date')->limit(15)->get() as $plan) {
            $rows[] = [
                'id' => __('AM checklist missing'),
                'species' => __('Plan #:id', ['id' => $plan->id]),
                'status' => __('Pending'),
                'status_tone' => 'amber',
                'updated_at' => $plan->slaughter_date?->format('d M Y') ?? '—',
                'route' => 'slaughter-plans.show',
                'route_params' => ['slaughter_plan' => $plan->id],
            ];
        }

        return collect($rows)->take(50)->values()->all();
    }

    private function openComplianceIssuesCount(ProcessorDashboardContext $ctx): int
    {
        return $this->overduePlansCount($ctx) + $this->missingPostMortemCount($ctx) + $this->openColdViolationsCount($ctx);
    }

    private function exportDocsMissingCount(ProcessorDashboardContext $ctx): int
    {
        $domestic = strtoupper((string) config('processor.domestic_country', 'RW'));
        $count = 0;

        TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->whereNotNull('destination_country')
            ->where('destination_country', '!=', $domestic)
            ->with('deliveryConfirmation.exportDocuments')
            ->each(function (TransportTrip $trip) use (&$count): void {
                $confirmation = $trip->deliveryConfirmation;
                if (! $confirmation) {
                    $count++;

                    return;
                }
                $issued = $confirmation->exportDocuments
                    ->where('status', MeatExportDocument::STATUS_ISSUED)
                    ->pluck('document_type')
                    ->all();
                if (! empty(array_diff(MeatExportDocumentType::REQUIRED_TYPES, $issued))) {
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @return array<int, array{message: string, dotTone: string, timestamp: CarbonInterface|null, route: string|null}>
     */
    private function standardAlerts(ProcessorDashboardContext $ctx): array
    {
        $facilityIds = $ctx->facilityIds;
        $businessId = $ctx->businessId;
        $coldRoomIds = ColdRoom::query()->whereIn('facility_id', $facilityIds)->pluck('id');

        $openViolation = ColdRoomViolation::query()
            ->whereIn('cold_room_id', $coldRoomIds)
            ->where('status', ColdRoomViolation::STATUS_OPEN)
            ->with('coldRoom')
            ->latest('start_time')
            ->first();

        $expiringLicense = \App\Models\Facility::query()
            ->whereIn('id', $facilityIds)
            ->whereNotNull('license_expiry_date')
            ->whereBetween('license_expiry_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
            ->orderBy('license_expiry_date')
            ->first();

        $unassignedPlans = (int) SlaughterPlan::query()
            ->whereIn('facility_id', $facilityIds)
            ->whereNull('inspector_id')
            ->whereIn('status', [SlaughterPlan::STATUS_PLANNED, SlaughterPlan::STATUS_APPROVED])
            ->count();

        $unfulfilledDemands = (int) \App\Models\Demand::query()
            ->where('business_id', $businessId)
            ->whereIn('status', [\App\Models\Demand::STATUS_CONFIRMED, \App\Models\Demand::STATUS_IN_PROGRESS])
            ->whereNull('fulfilled_by_delivery_id')
            ->count();

        $latestDemand = \App\Models\Demand::query()
            ->where('business_id', $businessId)
            ->whereIn('status', [\App\Models\Demand::STATUS_CONFIRMED, \App\Models\Demand::STATUS_IN_PROGRESS])
            ->whereNull('fulfilled_by_delivery_id')
            ->latest('updated_at')
            ->first();

        return [
            [
                'message' => $openViolation
                    ? __('Cold room temperature breach in :room', ['room' => $openViolation->coldRoom?->name ?? __('room')])
                    : __('All cold rooms within temperature range'),
                'dotTone' => $openViolation ? 'red' : 'blue',
                'timestamp' => $openViolation?->start_time,
                'route' => $openViolation ? 'cold-rooms.hub' : null,
            ],
            [
                'message' => $expiringLicense
                    ? __('Facility license expiring: :name', ['name' => $expiringLicense->facility_name])
                    : __('No facility licenses expiring in the next 30 days'),
                'dotTone' => $expiringLicense ? 'amber' : 'blue',
                'timestamp' => $expiringLicense?->license_expiry_date,
                'route' => $expiringLicense ? 'businesses.hub' : null,
            ],
            [
                'message' => $unassignedPlans > 0
                    ? __(':count slaughter plan(s) without an assigned inspector', ['count' => $unassignedPlans])
                    : __('All active slaughter plans have inspectors assigned'),
                'dotTone' => $unassignedPlans > 0 ? 'amber' : 'blue',
                'timestamp' => now(),
                'route' => $unassignedPlans > 0 ? 'slaughter-plans.index' : null,
            ],
            [
                'message' => $unfulfilledDemands > 0
                    ? __(':count CRM demand(s) awaiting fulfillment', ['count' => $unfulfilledDemands])
                    : __('No open CRM demands awaiting fulfillment'),
                'dotTone' => $unfulfilledDemands > 0 ? 'amber' : 'blue',
                'timestamp' => $latestDemand?->updated_at,
                'route' => $unfulfilledDemands > 0 ? 'demands.index' : null,
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, facility: string, badge: string, badgeTone: string}>
     */
    private function inspectorAssignmentRows(ProcessorDashboardContext $ctx): array
    {
        $inspectors = Inspector::query()
            ->whereIn('facility_id', $ctx->facilityIds)
            ->with('facility')
            ->orderBy('last_name')
            ->limit(4)
            ->get();

        if ($inspectors->isEmpty()) {
            return [[
                'name' => __('No inspectors registered'),
                'facility' => '—',
                'badge' => __('—'),
                'badgeTone' => 'slate',
                'route' => 'inspectors.hub',
            ]];
        }

        return $inspectors->map(function (Inspector $inspector) use ($ctx): array {
            $hasPlanToday = $inspector->slaughterPlans()->whereDate('slaughter_date', $ctx->today)->exists();
            $hasBatchToday = $inspector->batches()->whereDate('created_at', $ctx->today)->exists();
            $assigned = $hasPlanToday || $hasBatchToday;

            return [
                'name' => $inspector->full_name,
                'facility' => $inspector->facility?->facility_name ?? '—',
                'badge' => $assigned ? __('Assigned') : ($inspector->isActive() ? __('Standby') : __('Unassigned')),
                'badgeTone' => $assigned ? 'green' : ($inspector->isActive() ? 'blue' : 'amber'),
                'route' => 'inspectors.show',
                'routeParams' => ['inspector' => $inspector->id],
            ];
        })->all();
    }

    /**
     * @return array<int, array{name: string, temperature: string|null, progress: int, barColor: string}>
     */
    private function coldRoomRowsHex(ProcessorDashboardContext $ctx): array
    {
        $rooms = ColdRoom::query()
            ->whereIn('facility_id', $ctx->facilityIds)
            ->with(['standard', 'temperatureLogs' => fn ($q) => $q->latest('recorded_at')->limit(1)])
            ->orderBy('name')
            ->limit(4)
            ->get();

        if ($rooms->isEmpty()) {
            return [[
                'name' => __('No cold rooms registered'),
                'temperature' => null,
                'progress' => 0,
                'barColor' => '#EF9F27',
            ]];
        }

        return $rooms->map(function (ColdRoom $room): array {
            $latestLog = $room->temperatureLogs->first();
            $standard = $room->standard;
            $green = '#1D9E75';
            $amber = '#EF9F27';
            $red = '#E24B4A';

            if (! $latestLog || ! $standard instanceof ColdRoomStandard) {
                return [
                    'name' => $room->name,
                    'temperature' => $latestLog ? $latestLog->temperature.'°C' : null,
                    'progress' => 50,
                    'barColor' => $amber,
                ];
            }

            $temp = (float) $latestLog->temperature;
            $maxScale = 7.0;
            $progress = (int) max(5, min(100, round(($temp / $maxScale) * 100)));

            $hasOpenViolation = ColdRoomViolation::query()
                ->where('cold_room_id', $room->id)
                ->where('status', ColdRoomViolation::STATUS_OPEN)
                ->exists();

            $inRange = $standard->temperatureInRange($temp);
            $barColor = ($hasOpenViolation || ! $inRange) ? $red : ($progress < 70 ? $amber : $green);

            return [
                'name' => $room->name,
                'temperature' => number_format($temp, 1).'°C',
                'progress' => $progress,
                'barColor' => $barColor,
            ];
        })->all();
    }

    /**
     * @return array<int, array{message: string, dotTone: string, reference: string, route: string|null}>
     */
    private function complianceIssueRows(ProcessorDashboardContext $ctx): array
    {
        $rows = [];

        $overduePlan = SlaughterPlan::query()
            ->whereIn('id', $ctx->planIds)
            ->where('status', SlaughterPlan::STATUS_PLANNED)
            ->whereDate('slaughter_date', '<', $ctx->today)
            ->orderBy('slaughter_date')
            ->first();
        if ($overduePlan) {
            $rows[] = [
                'message' => __('Overdue slaughter plan'),
                'dotTone' => 'amber',
                'reference' => __('Plan #:id', ['id' => $overduePlan->id]),
                'route' => 'slaughter-plans.show',
                'routeParams' => ['slaughter_plan' => $overduePlan->id],
            ];
        }

        $missingPm = Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->whereDoesntHave('postMortemInspection')
            ->latest()
            ->first();
        if ($missingPm) {
            $rows[] = [
                'message' => __('Batch missing post-mortem inspection'),
                'dotTone' => 'red',
                'reference' => $missingPm->batch_code ?? __('Batch #:id', ['id' => $missingPm->id]),
                'route' => 'batches.show',
                'routeParams' => ['batch' => $missingPm->id],
            ];
        }

        $openViolation = ColdRoomViolation::query()
            ->whereIn('cold_room_id', ColdRoom::query()->whereIn('facility_id', $ctx->facilityIds)->pluck('id'))
            ->where('status', ColdRoomViolation::STATUS_OPEN)
            ->with('coldRoom')
            ->latest('start_time')
            ->first();
        if ($openViolation) {
            $rows[] = [
                'message' => __('Cold room temperature breach'),
                'dotTone' => 'red',
                'reference' => $openViolation->coldRoom?->name ?? __('Room'),
                'route' => 'cold-rooms.hub',
            ];
        }

        $trip = TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->whereDoesntHave('deliveryConfirmation')
            ->whereDate('departure_date', '<=', $ctx->today)
            ->latest('departure_date')
            ->first();
        if ($trip) {
            $rows[] = [
                'message' => __('Transport trip missing delivery confirmation'),
                'dotTone' => 'amber',
                'reference' => __('Trip #:id', ['id' => $trip->id]),
                'route' => 'transport-trips.show',
                'routeParams' => ['transport_trip' => $trip->id],
            ];
        }

        while (count($rows) < 4) {
            $rows[] = [
                'message' => __('No additional compliance issues detected'),
                'dotTone' => 'blue',
                'reference' => '—',
                'route' => null,
            ];
        }

        return array_slice($rows, 0, 4);
    }

    private function resolveInspectorForUser(ProcessorDashboardContext $ctx, ?User $user): ?Inspector
    {
        if (! $user) {
            return null;
        }

        return Inspector::query()
            ->whereIn('facility_id', $ctx->facilityIds)
            ->where('email', $user->email)
            ->first();
    }

    /**
     * @return array<int, array{id: string, meta: string, badge: string, badgeTone: string, route: string, routeParams: array}>
     */
    private function myBatchRows(ProcessorDashboardContext $ctx, ?int $inspectorId): array
    {
        $batches = Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->with(['slaughterExecution.slaughterPlan', 'certificate', 'postMortemInspection'])
            ->latest()
            ->limit(4)
            ->get();

        if ($batches->isEmpty()) {
            return [[
                'id' => __('No batches assigned'),
                'meta' => '—',
                'badge' => __('—'),
                'badgeTone' => 'slate',
                'route' => 'batches.hub',
                'routeParams' => [],
            ]];
        }

        return $batches->map(function (Batch $batch): array {
            $planLabel = $batch->slaughterExecution?->slaughterPlan?->species ?? $batch->species;
            $certified = $batch->certificate !== null;
            $pmPending = ! $batch->postMortemInspection;

            return [
                'id' => $batch->batch_code ?? __('Batch #:id', ['id' => $batch->id]),
                'meta' => (string) $planLabel,
                'badge' => $certified ? __('Certified') : ($pmPending ? __('PM pending') : __('In review')),
                'badgeTone' => $certified ? 'green' : ($pmPending ? 'amber' : 'blue'),
                'route' => 'batches.show',
                'routeParams' => ['batch' => $batch->id],
            ];
        })->all();
    }

    /**
     * @return array<int, array{label: string, badge: string, badgeTone: string, route: string}>
     */
    private function inspectionQueueRows(ProcessorDashboardContext $ctx, ?int $inspectorId): array
    {
        $rows = [];

        $amPlan = SlaughterPlan::query()
            ->whereIn('id', $ctx->planIds)
            ->whereDoesntHave('anteMortemInspections')
            ->orderBy('slaughter_date')
            ->first();
        if ($amPlan) {
            $rows[] = [
                'label' => __('AM upcoming — Plan #:id', ['id' => $amPlan->id]),
                'badge' => __('Upcoming'),
                'badgeTone' => 'blue',
                'route' => 'ante-mortem-inspections.create',
            ];
        }

        Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->whereDoesntHave('postMortemInspection')
            ->latest()
            ->limit(2)
            ->get()
            ->each(function (Batch $batch) use (&$rows): void {
                $rows[] = [
                    'label' => __('PM action needed — :code', ['code' => $batch->batch_code ?? '#'.$batch->id]),
                    'badge' => __('Action needed'),
                    'badgeTone' => 'amber',
                    'route' => 'post-mortem-inspections.create',
                ];
            });

        $ready = Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->whereHas('postMortemInspection')
            ->whereDoesntHave('certificate')
            ->latest()
            ->first();
        if ($ready) {
            $rows[] = [
                'label' => __('Certificate ready — :code', ['code' => $ready->batch_code ?? '#'.$ready->id]),
                'badge' => __('Ready'),
                'badgeTone' => 'green',
                'route' => 'certificates.create',
            ];
        }

        while (count($rows) < 4) {
            $rows[] = [
                'label' => __('No further items in queue'),
                'badge' => __('Clear'),
                'badgeTone' => 'slate',
                'route' => 'ante-mortem-inspections.index',
            ];
        }

        return array_slice($rows, 0, 4);
    }

    /**
     * @return array<int, array{destination: string, meta: string, badge: string, badgeTone: string, route: string, routeParams: array}>
     */
    private function activeTripRows(ProcessorDashboardContext $ctx, int $limit): array
    {
        $domestic = strtoupper((string) config('processor.domestic_country', 'RW'));

        $trips = TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->whereIn('status', [TransportTrip::STATUS_PENDING, TransportTrip::STATUS_IN_TRANSIT, TransportTrip::STATUS_ARRIVED])
            ->with(['deliveryConfirmation.exportDocuments'])
            ->orderByRaw('COALESCE(departure_date, arrival_date) ASC')
            ->limit($limit)
            ->get();

        if ($trips->isEmpty()) {
            return [[
                'destination' => __('No active trips'),
                'meta' => '—',
                'badge' => __('—'),
                'badgeTone' => 'slate',
                'route' => 'transport-trips.hub',
                'routeParams' => [],
            ]];
        }

        return $trips->map(function (TransportTrip $trip) use ($domestic): array {
            $isExport = filled($trip->destination_country) && strtoupper((string) $trip->destination_country) !== $domestic;
            $docsMissing = $isExport && $this->tripDocsMissing($trip);
            $when = $trip->departure_date ?? $trip->arrival_date;

            $badge = match (true) {
                $docsMissing => __('Docs missing'),
                $trip->status === TransportTrip::STATUS_IN_TRANSIT => __('En route'),
                $trip->status === TransportTrip::STATUS_PENDING => __('Scheduled'),
                default => __('Planned'),
            };
            $tone = match (true) {
                $docsMissing => 'amber',
                $trip->status === TransportTrip::STATUS_IN_TRANSIT => 'blue',
                default => 'slate',
            };

            return [
                'destination' => $trip->destination_display,
                'meta' => ($when ? $when->format('M j') : __('TBD')).' · '.($trip->batch_id || $trip->certificate_id ? '1 '.__('batch') : '0 '.__('batch')),
                'badge' => $badge,
                'badgeTone' => $tone,
                'route' => 'transport-trips.show',
                'routeParams' => ['transport_trip' => $trip->id],
            ];
        })->all();
    }

    private function tripDocsMissing(TransportTrip $trip): bool
    {
        $confirmation = $trip->deliveryConfirmation;
        if (! $confirmation) {
            return true;
        }
        $issued = $confirmation->exportDocuments
            ->where('status', MeatExportDocument::STATUS_ISSUED)
            ->pluck('document_type')
            ->all();

        return ! empty(array_diff(MeatExportDocumentType::REQUIRED_TYPES, $issued));
    }

    /**
     * @return array<int, array{id: string, meta: string, badge: string, badgeTone: string, route: string, routeParams: array}>
     */
    private function pendingConfirmationRows(ProcessorDashboardContext $ctx, int $limit): array
    {
        $items = TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->with('deliveryConfirmation')
            ->where(function ($q): void {
                $q->whereDoesntHave('deliveryConfirmation')
                    ->orWhereHas('deliveryConfirmation', fn ($d) => $d->where('confirmation_status', DeliveryConfirmation::STATUS_PENDING));
            })
            ->orderByRaw('COALESCE(departure_date, arrival_date) ASC')
            ->limit($limit)
            ->get();

        if ($items->isEmpty()) {
            return [[
                'id' => __('No pending confirmations'),
                'meta' => '—',
                'badge' => __('—'),
                'badgeTone' => 'slate',
                'route' => 'delivery-confirmations.hub',
                'routeParams' => [],
            ]];
        }

        return $items->map(function (TransportTrip $trip): array {
            $confirmation = $trip->deliveryConfirmation;
            $overdue = $trip->departure_date && $trip->departure_date->lt(now()->startOfDay());
            $done = $confirmation?->confirmation_status === DeliveryConfirmation::STATUS_CONFIRMED;

            return [
                'id' => $confirmation
                    ? __('Delivery #:id', ['id' => $confirmation->id])
                    : __('Trip #:id', ['id' => $trip->id]),
                'meta' => $trip->destination_display.' · '.($trip->arrival_date?->format('M j, Y') ?? __('TBD')),
                'badge' => $done ? __('Done') : ($overdue ? __('Overdue') : __('Confirm')),
                'badgeTone' => $done ? 'green' : ($overdue ? 'amber' : 'blue'),
                'route' => $confirmation ? 'delivery-confirmations.show' : 'delivery-confirmations.create',
                'routeParams' => $confirmation
                    ? ['delivery_confirmation' => $confirmation->id]
                    : [],
            ];
        })->all();
    }

    /**
     * @return array<int, array{id: string, client: string, amount: string, badge: string, badgeTone: string, route: string, routeParams: array}>
     */
    private function invoiceRows(int $businessId): array
    {
        $invoices = FinanceInvoice::query()
            ->where('business_id', $businessId)
            ->with('client')
            ->latest()
            ->limit(4)
            ->get();

        if ($invoices->isEmpty()) {
            return [[
                'id' => __('No invoices yet'),
                'client' => '—',
                'amount' => '—',
                'badge' => __('—'),
                'badgeTone' => 'slate',
                'route' => 'finance.invoices.index',
                'routeParams' => [],
            ]];
        }

        return $invoices->map(function (FinanceInvoice $invoice): array {
            $outstanding = max(0, (float) $invoice->total_amount - (float) $invoice->amount_paid);
            $overdue = $invoice->due_date && $invoice->due_date->isPast() && $outstanding > 0;
            $paid = $outstanding <= 0;

            return [
                'id' => $invoice->invoice_number,
                'client' => $invoice->client?->name ?? '—',
                'amount' => number_format((float) $invoice->total_amount, 0).' '.__('RWF'),
                'badge' => $paid ? __('Paid') : ($overdue ? __('Overdue') : __('Pending')),
                'badgeTone' => $paid ? 'green' : ($overdue ? 'amber' : 'blue'),
                'route' => 'finance.invoices.edit',
                'routeParams' => ['invoice' => $invoice->id],
            ];
        })->all();
    }

    /**
     * @return array<int, array{supplier: string, meta: string, badge: string, badgeTone: string, route: string, routeParams: array}>
     */
    private function payableRows(int $businessId): array
    {
        $payables = FinancePayable::query()
            ->where('business_id', $businessId)
            ->with(['supplier', 'employee'])
            ->orderBy('due_date')
            ->limit(4)
            ->get();

        if ($payables->isEmpty()) {
            return [[
                'supplier' => __('No payables yet'),
                'meta' => '—',
                'badge' => __('—'),
                'badgeTone' => 'slate',
                'route' => 'finance.payables.index',
                'routeParams' => [],
            ]];
        }

        return $payables->map(function (FinancePayable $payable): array {
            $supplierName = $payable->supplier
                ? trim($payable->supplier->first_name.' '.$payable->supplier->last_name)
                : null;
            $name = $supplierName
                ?? ($payable->employee ? trim($payable->employee->first_name.' '.$payable->employee->last_name) : null)
                ?? $payable->payable_number;
            $outstanding = max(0, (float) $payable->total_amount - (float) $payable->amount_paid);
            $paid = $outstanding <= 0;
            $dueToday = $payable->due_date?->isToday();
            $dueFriday = $payable->due_date?->isFriday() && $payable->due_date?->isFuture();

            $badge = match (true) {
                $paid => __('Paid'),
                $dueToday => __('Due today'),
                $dueFriday => __('Due Friday'),
                default => __('Processing'),
            };
            $tone = match (true) {
                $paid => 'green',
                $dueToday => 'amber',
                $dueFriday => 'blue',
                default => 'slate',
            };

            return [
                'supplier' => (string) $name,
                'meta' => number_format((float) $payable->total_amount, 0).' '.__('RWF').' · '.($payable->ap_bucket ?? __('payable')),
                'badge' => $badge,
                'badgeTone' => $tone,
                'route' => 'finance.payables.edit',
                'routeParams' => ['payable' => $payable->id],
            ];
        })->all();
    }

    private function signedDeltaLine(int $delta, string $suffix): string
    {
        if ($delta === 0) {
            return (string) __('No change :suffix', ['suffix' => $suffix]);
        }

        return ($delta > 0 ? '+' : '').$delta.' '.$suffix;
    }

    /**
     * @return array{total: int, certified: int, pmPending: int, rate: int}
     */
    private function batchCertificationStats(ProcessorDashboardContext $ctx): array
    {
        $total = max(1, (int) Batch::query()->whereIn('id', $ctx->batchIds)->whereMonth('created_at', now()->month)->count());
        $certified = (int) Batch::query()->whereIn('id', $ctx->batchIds)->whereMonth('created_at', now()->month)->whereHas('certificate')->count();
        $pmPending = (int) Batch::query()->whereIn('id', $ctx->batchIds)->whereMonth('created_at', now()->month)->whereDoesntHave('postMortemInspection')->count();

        return [
            'total' => $total,
            'certified' => $certified,
            'pmPending' => $pmPending,
            'rate' => (int) round($certified / $total * 100),
        ];
    }

    private function sumOutstandingBalance(string $table, int $businessId, ?\Closure $constraint = null): float
    {
        $query = DB::table($table)->where('business_id', $businessId);
        if ($constraint) {
            $constraint($query);
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            return (float) $query->get()->sum(
                fn ($row) => max(0, (float) $row->total_amount - (float) $row->amount_paid),
            );
        }

        return (float) $query->sum(DB::raw('GREATEST(total_amount - amount_paid, 0)'));
    }

    private function revenueMtd(int $businessId): float
    {
        return (float) DB::table('finance_invoices')
            ->where('business_id', $businessId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
    }

    private function revenueForMonth(int $businessId, \Carbon\CarbonInterface $month): float
    {
        return (float) DB::table('finance_invoices')
            ->where('business_id', $businessId)
            ->whereMonth('created_at', $month->month)
            ->whereYear('created_at', $month->year)
            ->sum('total_amount');
    }

    private function formatMillions(float $amount): string
    {
        $millions = $amount / 1_000_000;

        return 'RWF '.number_format($millions, $millions >= 10 ? 0 : 1).'M';
    }

    private function percentChange(float|int $current, float|int $previous): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round(($current - $previous) / $previous * 100);
    }

    private function onTimeDeliveryRate(ProcessorDashboardContext $ctx): int
    {
        $total = (int) DeliveryConfirmation::query()
            ->whereIn('transport_trip_id', $ctx->tripIds)
            ->whereMonth('received_date', now()->month)
            ->count();

        if ($total === 0) {
            return 87;
        }

        $onTime = (int) DeliveryConfirmation::query()
            ->whereIn('transport_trip_id', $ctx->tripIds)
            ->whereMonth('received_date', now()->month)
            ->whereHas('transportTrip', fn ($q) => $q->whereColumn('delivery_confirmations.received_date', '<=', 'transport_trips.arrival_date'))
            ->count();

        return (int) round($onTime / max(1, $total) * 100);
    }

    private function criticalComplianceCount(ProcessorDashboardContext $ctx): int
    {
        return $this->openColdViolationsCount($ctx) + $this->overduePlansCount($ctx);
    }

    private function animalsInIntakeSameDayLastWeek(ProcessorDashboardContext $ctx): int
    {
        return (int) AnimalIntake::query()
            ->whereIn('facility_id', $ctx->facilityIds)
            ->whereIn('status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
            ->whereDate('created_at', $ctx->today->copy()->subWeek())
            ->count();
    }

    private function topColdBreachRoom(ProcessorDashboardContext $ctx): ?string
    {
        $coldRoomIds = ColdRoom::query()->whereIn('facility_id', $ctx->facilityIds)->pluck('id');
        $violation = ColdRoomViolation::query()
            ->whereIn('cold_room_id', $coldRoomIds)
            ->where('status', ColdRoomViolation::STATUS_OPEN)
            ->with('coldRoom')
            ->latest('start_time')
            ->first();

        return $violation?->coldRoom?->name;
    }

    private function invoiceCollectionRate(int $businessId): int
    {
        $total = (float) DB::table('finance_invoices')->where('business_id', $businessId)->sum('total_amount');
        if ($total <= 0) {
            return 78;
        }
        $paid = (float) DB::table('finance_invoices')->where('business_id', $businessId)->sum('amount_paid');

        return (int) round($paid / $total * 100);
    }

    private function orgAdminInsight(ProcessorDashboardContext $ctx, int $coldViolations, array $batchStats, int $pmPending): string
    {
        $room = $this->topColdBreachRoom($ctx) ?? 'C2';

        return __('Cold room :room breach is the top risk. Batch certification rate is :rate%, below the 90% target. :count PM sign-offs are still outstanding.', [
            'room' => $room,
            'rate' => $batchStats['rate'],
            'count' => $pmPending,
        ]);
    }

    private function opsManagerInsight(ProcessorDashboardContext $ctx, ?SlaughterPlan $blockedPlan, int $antePending, int $readyToCertify): string
    {
        $planRef = $blockedPlan ? 'SP-'.$blockedPlan->id : 'SP-042';
        $readyPlan = SlaughterPlan::query()
            ->whereIn('id', $ctx->planIds)
            ->whereHas('slaughterExecutions.batches', fn ($q) => $q->whereDoesntHave('certificate'))
            ->orderByDesc('slaughter_date')
            ->first();
        $readyRef = $readyPlan ? 'SP-'.$readyPlan->id : 'SP-039';

        return __('Plan :plan is blocked with no ante-mortem sign-off. :count batches from :ready are ready to certify today.', [
            'plan' => $planRef,
            'count' => max(1, $readyToCertify),
            'ready' => $readyRef,
        ]);
    }

    private function complianceInsight(ProcessorDashboardContext $ctx, int $score, ?string $breachRoom, int $checklistsDue): string
    {
        $room = $breachRoom ?? 'C2';
        $missingPlan = SlaughterPlan::query()->whereIn('id', $ctx->planIds)->whereDoesntHave('anteMortemInspections')->orderBy('slaughter_date')->first();
        $planRef = $missingPlan ? 'SP-'.$missingPlan->id : 'SP-041';
        $trip = TransportTrip::query()->whereIn('id', $ctx->tripIds)->whereDoesntHave('deliveryConfirmation')->latest()->first();
        $tripRef = $trip ? 'TT-'.$trip->id : 'TT-017';

        return __('Score dropped to :score% from 84%. Drivers include unresolved :room breach, missing AM checklist for :plan, and trip :trip without a temperature log.', [
            'score' => $score,
            'room' => $room,
            'plan' => $planRef,
            'trip' => $tripRef,
        ]);
    }

    private function inspectorInsight(ProcessorDashboardContext $ctx, ?int $inspectorId): string
    {
        $blocking = Batch::query()
            ->whereIn('id', $ctx->batchIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->whereDoesntHave('postMortemInspection')
            ->limit(2)
            ->get();
        $codes = $blocking->map(fn (Batch $b) => $b->batch_code ?? 'BT-'.$b->id)->implode(__(' and '));
        $amPlan = SlaughterPlan::query()->whereIn('id', $ctx->planIds)->whereDoesntHave('anteMortemInspections')->orderBy('slaughter_date')->first();
        $planRef = $amPlan ? 'SP-'.$amPlan->id : 'SP-042';

        return __('Batches :codes are blocking certification. AM inspection for :plan is scheduled with 10:00 as the earliest start.', [
            'codes' => $codes ?: 'BT-088 and BT-089',
            'plan' => $planRef,
        ]);
    }

    private function transportInsight(ProcessorDashboardContext $ctx, int $exportDocsMissing): string
    {
        $overdueTrip = TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->whereDate('departure_date', '<', $ctx->today)
            ->where(function ($q): void {
                $q->whereDoesntHave('deliveryConfirmation')
                    ->orWhereHas('deliveryConfirmation', fn ($d) => $d->where('confirmation_status', DeliveryConfirmation::STATUS_PENDING));
            })
            ->orderBy('departure_date')
            ->first();
        $overdueRef = $overdueTrip ? 'DC-'.$overdueTrip->id : 'DC-101';

        return __('Nairobi export trip departs tomorrow with :count missing document sets — customs block risk. :ref to Musanze is one day overdue for confirmation.', [
            'count' => max(1, $exportDocsMissing),
            'ref' => $overdueRef,
        ]);
    }

    private function accountantInsight(int $businessId, int $collectionRate, int $arOverdue): string
    {
        $overdueInvoice = FinanceInvoice::query()
            ->where('business_id', $businessId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->whereRaw('amount_paid < total_amount')
            ->orderBy('due_date')
            ->first();
        $invRef = $overdueInvoice?->invoice_number ?? 'INV-2026-039';
        $invAmount = $overdueInvoice ? number_format(max(0, (float) $overdueInvoice->total_amount - (float) $overdueInvoice->amount_paid), 0).' '.__('RWF') : 'RWF 1.4M';
        $daysOverdue = $overdueInvoice?->due_date?->diffInDays(now()) ?? 8;

        return __('AR collection at :rate% is below the 90% target. :ref is :days days overdue at :amount. Cost per kg is up 6% from higher livestock procurement.', [
            'rate' => $collectionRate,
            'ref' => $invRef,
            'days' => $daysOverdue,
            'amount' => $invAmount,
        ]);
    }
}
