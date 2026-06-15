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
use App\Models\FinanceInvoice;
use App\Models\FinancePayable;
use App\Models\Inspector;
use App\Models\MeatExportDocument;
use App\Models\PostMortemInspection;
use App\Models\SlaughterExecution;
use App\Models\SlaughterPlan;
use App\Models\TransportTrip;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ProcessorDashboardService
{
    public function buildForRole(int $businessId, string $role, ?User $user = null): array
    {
        $ctx = ProcessorDashboardContext::forBusiness($businessId);

        $data = match ($role) {
            BusinessUser::ROLE_OPERATIONS_MANAGER => $this->buildOpsManager($ctx),
            BusinessUser::ROLE_COMPLIANCE_OFFICER => $this->buildComplianceOfficer($ctx),
            BusinessUser::ROLE_INSPECTOR => $this->buildInspector($ctx, $user),
            BusinessUser::ROLE_TRANSPORT_MANAGER => $this->buildTransportManager($ctx),
            BusinessUser::ROLE_ACCOUNTANT => $this->buildAccountant($businessId, $ctx),
            default => $this->buildOrgAdmin($ctx),
        };

        $data['charts'] = app(ProcessorDashboardCharts::class)->forRole(
            (string) $data['roleKey'],
            $ctx,
            $businessId,
        );

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

    private function buildOrgAdmin(ProcessorDashboardContext $ctx): array
    {
        $animalsToday = $this->animalsInIntake($ctx);
        $animalsLastWeek = $this->animalsInIntakeSameDayLastWeek($ctx);
        $wowPct = $this->percentChange($animalsToday, $animalsLastWeek);

        $batchStats = $this->batchCertificationStats($ctx);
        $openCompliance = $this->openComplianceIssuesCount($ctx);
        $criticalIssues = $this->criticalComplianceCount($ctx);
        $onTimeRate = $this->onTimeDeliveryRate($ctx);
        $revenueMtd = $this->revenueMtd($ctx->businessId);
        $revenueLastMonth = $this->revenueForMonth($ctx->businessId, now()->subMonth());
        $revenueMom = $this->percentChange($revenueMtd, $revenueLastMonth);

        $coldViolations = $this->openColdViolationsCount($ctx);
        $delayedPlans = $this->overduePlansCount($ctx);
        $pendingDeliveries = $this->pendingDeliveries($ctx);
        $pmPending = $batchStats['pmPending'];

        return [
            'roleKey' => BusinessUser::ROLE_ORG_ADMIN,
            'headerBadge' => ['label' => __('Full access'), 'variant' => 'info'],
            'insight' => $this->orgAdminInsight($ctx, $coldViolations, $batchStats, $pmPending),
            'kpiCards' => [
                $this->kpi(__('Animals today'), $animalsToday, ($wowPct >= 0 ? '+' : '').$wowPct.'% '.__('wow'), $wowPct >= 0 ? 'positive' : 'negative'),
                $this->kpi(__('Batches certified'), $batchStats['certified'].'/'.$batchStats['total'], __(':count pending PM', ['count' => $pmPending]), $batchStats['rate'] >= 90 ? 'positive' : 'warning'),
                $this->kpi(__('On-time delivery rate'), $onTimeRate.'%', '-5% '.__('mom'), 'negative'),
                $this->kpi(__('Open compliance issues'), $openCompliance, __(':count critical', ['count' => $criticalIssues]), $criticalIssues > 0 ? 'warning' : 'info'),
                $this->kpi(__('Revenue MTD'), $this->formatMillions($revenueMtd), ($revenueMom >= 0 ? '+' : '').$revenueMom.'% '.__('mom'), $revenueMom >= 0 ? 'positive' : 'negative'),
            ],
            'leftPanel' => [
                'title' => __('Module overview'),
                'subtitle' => __('Cross-module health at a glance'),
                'type' => 'module_rows',
                'items' => [
                    $this->moduleRow(__('Operations'), __('Intake, slaughter, batches'), 'box', 'animal-intakes.hub', $delayedPlans > 0 ? 'warning' : 'healthy', __('Operations healthy')),
                    $this->moduleRow(__('Cold chain'), __('Storage and temperature'), 'temperature', 'cold-rooms.hub', $coldViolations > 0 ? 'action_needed' : 'on_track', $coldViolations > 0 ? __('Cold chain warning') : __('On track')),
                    $this->moduleRow(__('Transport'), __('Trips and deliveries'), 'truck', 'transport-trips.hub', $pendingDeliveries > 0 ? 'warning' : 'healthy', __('Transport on-track')),
                    $this->moduleRow(__('Compliance'), __('Licenses and inspections'), 'shield', 'compliance.index', $openCompliance > 0 ? 'action_needed' : 'healthy', $openCompliance > 0 ? __('Compliance action-needed') : __('Healthy')),
                ],
            ],
            'rightPanel' => [
                'title' => __('Alerts'),
                'subtitle' => __('Items requiring attention'),
                'type' => 'alerts',
                'items' => $this->standardAlerts($ctx),
            ],
            'quickActions' => [
                $this->action(__('Manage users'), 'users', 'tenant-users.index', BusinessUser::PERMISSION_MANAGE_BUSINESS_USERS),
                $this->action(__('Compliance'), 'shield', 'compliance.index', BusinessUser::PERMISSION_MONITOR_COMPLIANCE_METRICS),
                $this->action(__('Track deliveries'), 'truck', 'transport-trips.index', BusinessUser::PERMISSION_TRACK_DELIVERY_STATUS),
                $this->action(__('Finance'), 'currency-dollar', 'finance.dashboard', BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD),
                $this->action(__('Businesses'), 'building', 'businesses.hub', BusinessUser::PERMISSION_VIEW_ALL_MODULES),
                $this->action(__('CRM'), 'layout-dashboard', 'crm.dashboard', BusinessUser::PERMISSION_VIEW_ALL_MODULES),
            ],
        ];
    }

    private function buildOpsManager(ProcessorDashboardContext $ctx): array
    {
        $plansToday = (int) SlaughterPlan::query()
            ->whereIn('id', $ctx->planIds)
            ->whereDate('slaughter_date', $ctx->today)
            ->count();
        $executionsToday = (int) SlaughterExecution::query()
            ->whereIn('id', $ctx->executionIds)
            ->whereDate('created_at', $ctx->today)
            ->count();
        $inspectorsOnDuty = (int) Inspector::query()
            ->whereIn('facility_id', $ctx->facilityIds)
            ->where('status', Inspector::STATUS_ACTIVE)
            ->where(function ($q) use ($ctx): void {
                $q->whereHas('slaughterPlans', fn ($p) => $p->whereDate('slaughter_date', $ctx->today))
                    ->orWhereHas('batches', fn ($b) => $b->whereDate('created_at', $ctx->today));
            })
            ->count();

        $intakeCount = $this->animalsInIntake($ctx);
        $plansActive = (int) SlaughterPlan::query()->whereIn('id', $ctx->planIds)->whereIn('status', [SlaughterPlan::STATUS_PLANNED, SlaughterPlan::STATUS_APPROVED])->count();
        $antePending = (int) SlaughterPlan::query()->whereIn('id', $ctx->planIds)->whereDoesntHave('anteMortemInspections')->count();
        $batchesToday = $this->batchesToday($ctx);
        $batchesCertified = (int) Batch::query()->whereIn('id', $ctx->batchIds)->whereDate('created_at', $ctx->today)->whereHas('certificate')->count();
        $inspectorTotal = (int) Inspector::query()->whereIn('facility_id', $ctx->facilityIds)->where('status', Inspector::STATUS_ACTIVE)->count();
        $coveragePct = $inspectorTotal > 0 ? (int) round($inspectorsOnDuty / $inspectorTotal * 100) : 0;
        $blockedPlan = SlaughterPlan::query()->whereIn('id', $ctx->planIds)->whereDoesntHave('anteMortemInspections')->orderBy('slaughter_date')->first();

        return [
            'roleKey' => BusinessUser::ROLE_OPERATIONS_MANAGER,
            'headerBadge' => ['label' => __('Operations only'), 'variant' => 'neutral'],
            'insight' => $this->opsManagerInsight($ctx, $blockedPlan, $antePending, $batchesToday - $batchesCertified),
            'kpiCards' => [
                $this->kpi(__('Animals in intake'), $intakeCount, __(':count plans active', ['count' => $plansActive]), 'info'),
                $this->kpi(__('Plans scheduled'), $plansToday, __(':count AM pending', ['count' => $antePending]), $antePending > 0 ? 'warning' : 'positive'),
                $this->kpi(__('Batches today'), $batchesToday, __(':count certified', ['count' => $batchesCertified]), 'positive'),
                $this->kpi(__('Inspector coverage'), $coveragePct.'%', __(':assigned/:total assigned', ['assigned' => $inspectorsOnDuty, 'total' => $inspectorTotal]), $coveragePct >= 75 ? 'positive' : 'warning'),
                $this->kpi(__('Throughput efficiency'), '82%', '-3% '.__('wow'), 'negative'),
            ],
            'leftPanel' => [
                'title' => __('Slaughter pipeline'),
                'subtitle' => __("Today's operational flow"),
                'type' => 'pipeline',
                'items' => [
                    $this->pipelineStep(__('Intake'), 'arrow-down', $intakeCount, 'animal-intakes.index'),
                    $this->pipelineStep(__('Plans'), 'calendar', $plansActive, 'slaughter-plans.index'),
                    $this->pipelineStep(__('Ante-mortem'), 'clipboard-list', $antePending, 'ante-mortem-inspections.index'),
                    $this->pipelineStep(__('Executions'), 'player-play', $executionsToday, 'slaughter-executions.index'),
                    $this->pipelineStep(__('Batches'), 'box', $batchesToday, 'batches.index'),
                ],
            ],
            'rightPanel' => [
                'title' => __('Inspector assignments'),
                'subtitle' => __('Facility coverage today'),
                'type' => 'inspectors',
                'items' => $this->inspectorAssignmentRows($ctx),
            ],
            'quickActions' => [
                $this->action(__('New intake'), 'arrow-down', 'animal-intakes.create', BusinessUser::PERMISSION_CREATE_ANIMAL_INTAKE),
                $this->action(__('Plan slaughter'), 'calendar', 'slaughter-plans.create', BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER),
                $this->action(__('New batch'), 'box', 'batches.create', BusinessUser::PERMISSION_CREATE_BATCH),
                $this->action(__('Assign inspector'), 'user', 'inspectors.hub', BusinessUser::PERMISSION_ASSIGN_BATCH_TO_INSPECTOR),
                $this->action(__('Certificates'), 'certificate', 'certificates.index', BusinessUser::PERMISSION_VIEW_CERTIFICATES),
                $this->action(__('Executions'), 'player-play', 'slaughter-executions.index', BusinessUser::PERMISSION_SCHEDULE_SLAUGHTER),
            ],
        ];
    }

    private function buildComplianceOfficer(ProcessorDashboardContext $ctx): array
    {
        $openIssues = $this->openComplianceIssuesCount($ctx);
        $criticalIssues = $this->criticalComplianceCount($ctx);
        $checklistsDue = $this->missingAnteMortemCount($ctx) + $this->missingPostMortemCount($ctx);
        $tempViolations = $this->openColdViolationsCount($ctx);
        $breachRoom = $this->topColdBreachRoom($ctx);
        $evidenceCount = (int) MeatExportDocument::query()
            ->whereHas('deliveryConfirmation.transportTrip', fn ($q) => $q->whereIn('id', $ctx->tripIds))
            ->where('status', MeatExportDocument::STATUS_ISSUED)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
        $complianceScore = max(60, 95 - ($openIssues * 4));

        return [
            'roleKey' => BusinessUser::ROLE_COMPLIANCE_OFFICER,
            'headerBadge' => ['label' => __('Compliance + temperature'), 'variant' => 'warning'],
            'insight' => $this->complianceInsight($ctx, $complianceScore, $breachRoom, $checklistsDue),
            'kpiCards' => [
                $this->kpi(__('Open issues'), $openIssues, __(':count critical', ['count' => $criticalIssues]), $criticalIssues > 0 ? 'warning' : 'info'),
                $this->kpi(__('Checklists due today'), $checklistsDue, __('Inspections pending'), $checklistsDue > 0 ? 'warning' : 'positive'),
                $this->kpi(__('Temp violations'), $tempViolations, $breachRoom ? __('Cold room :room', ['room' => $breachRoom]) : __('No breaches'), $tempViolations > 0 ? 'negative' : 'positive'),
                $this->kpi(__('Compliance score'), $complianceScore.'%', __('target 95%'), $complianceScore >= 95 ? 'positive' : 'warning'),
                $this->kpi(__('Evidence uploads this week'), $evidenceCount, __('Submitted files'), 'info'),
            ],
            'leftPanel' => [
                'title' => __('Cold room status'),
                'subtitle' => __('Temperature compliance'),
                'type' => 'cold_rooms_hex',
                'items' => $this->coldRoomRowsHex($ctx),
            ],
            'rightPanel' => [
                'title' => __('Compliance issues'),
                'subtitle' => __('Reference-linked alerts'),
                'type' => 'compliance_issues',
                'items' => $this->complianceIssueRows($ctx),
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

    private function buildInspector(ProcessorDashboardContext $ctx, ?User $user): array
    {
        $inspector = $this->resolveInspectorForUser($ctx, $user);
        $inspectorId = $inspector?->id;

        $batchQuery = Batch::query()->whereIn('id', $ctx->batchIds);
        if ($inspectorId) {
            $batchQuery->where('inspector_id', $inspectorId);
        }

        $assignedBatches = (int) (clone $batchQuery)->count();
        $pmPendingBatches = (int) (clone $batchQuery)->whereDoesntHave('postMortemInspection')->count();
        $amToday = (int) AnteMortemInspection::query()
            ->whereIn('slaughter_plan_id', $ctx->planIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->whereDate('inspection_date', $ctx->today)
            ->count();
        $amComplete = (int) AnteMortemInspection::query()
            ->whereIn('slaughter_plan_id', $ctx->planIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->whereDate('inspection_date', $ctx->today)
            ->where('number_examined', '>', 0)
            ->whereRaw('(number_approved + number_rejected) = number_examined')
            ->count();
        $pmToday = (int) PostMortemInspection::query()
            ->whereIn('batch_id', $ctx->batchIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->whereDate('inspection_date', $ctx->today)
            ->count();
        $pmComplete = (int) PostMortemInspection::query()
            ->whereIn('batch_id', $ctx->batchIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->whereDate('inspection_date', $ctx->today)
            ->whereIn('result', [
                PostMortemInspection::RESULT_APPROVED,
                PostMortemInspection::RESULT_PARTIAL,
                PostMortemInspection::RESULT_REJECTED,
            ])
            ->count();
        $certsToday = (int) Certificate::query()
            ->whereIn('id', $ctx->certificateIds)
            ->when($inspectorId, fn ($q) => $q->where('inspector_id', $inspectorId))
            ->whereDate('issued_at', $ctx->today)
            ->count();
        $rejectedBatches = (int) (clone $batchQuery)->where('status', Batch::STATUS_REJECTED)->count();
        $rejectionRate = $assignedBatches > 0 ? (int) round($rejectedBatches / $assignedBatches * 100) : 0;

        return [
            'roleKey' => BusinessUser::ROLE_INSPECTOR,
            'headerBadge' => ['label' => __('Own workload'), 'variant' => 'success'],
            'insight' => $this->inspectorInsight($ctx, $inspectorId),
            'kpiCards' => [
                $this->kpi(__('Assigned batches'), $assignedBatches, __(':count awaiting PM', ['count' => $pmPendingBatches]), 'info'),
                $this->kpi(__('AM inspections'), $amToday, __(':count complete', ['count' => $amComplete]), 'positive'),
                $this->kpi(__('PM inspections'), $pmToday, __(':count complete', ['count' => $pmComplete]), 'positive'),
                $this->kpi(__('Certs issued today'), $certsToday, __('Issued today'), 'positive'),
                $this->kpi(__('Rejection rate'), $rejectionRate.'%', __(':count batch', ['count' => $rejectedBatches]), $rejectionRate > 10 ? 'warning' : 'info'),
            ],
            'leftPanel' => [
                'title' => __('Inspection queue'),
                'subtitle' => __('Upcoming actions'),
                'type' => 'inspection_queue',
                'items' => $this->inspectionQueueRows($ctx, $inspectorId),
            ],
            'rightPanel' => [
                'title' => __('Assigned batches'),
                'subtitle' => __('Production batches in your queue'),
                'type' => 'batches',
                'items' => $this->myBatchRows($ctx, $inspectorId),
            ],
            'quickActions' => [
                $this->action(__('Record AM'), 'clipboard-list', 'ante-mortem-inspections.create', BusinessUser::PERMISSION_RECORD_ANTE_MORTEM),
                $this->action(__('Record PM'), 'clipboard', 'post-mortem-inspections.create', BusinessUser::PERMISSION_RECORD_POST_MORTEM),
                $this->action(__('Issue certificate'), 'certificate', 'certificates.create', BusinessUser::PERMISSION_ISSUE_CERTIFICATE),
                $this->action(__('My batches'), 'box', 'batches.index', BusinessUser::PERMISSION_VIEW_ASSIGNED_BATCHES),
                $this->action(__('All inspections'), 'clipboard-list', 'ante-mortem-inspections.index', BusinessUser::PERMISSION_VIEW_INSPECTIONS),
                $this->action(__('Export certs'), 'certificate', 'certificates.export', BusinessUser::PERMISSION_VIEW_CERTIFICATES),
            ],
        ];
    }

    private function buildTransportManager(ProcessorDashboardContext $ctx): array
    {
        $activeTrips = (int) TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->whereIn('status', [TransportTrip::STATUS_PENDING, TransportTrip::STATUS_IN_TRANSIT, TransportTrip::STATUS_ARRIVED])
            ->count();
        $enRoute = (int) TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->where('status', TransportTrip::STATUS_IN_TRANSIT)
            ->count();
        $confirmedToday = (int) DeliveryConfirmation::query()
            ->whereIn('transport_trip_id', $ctx->tripIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->whereDate('received_date', $ctx->today)
            ->count();
        $unconfirmedToday = (int) TransportTrip::query()
            ->whereIn('id', $ctx->tripIds)
            ->whereDate('departure_date', $ctx->today)
            ->where(function ($q): void {
                $q->whereDoesntHave('deliveryConfirmation')
                    ->orWhereHas('deliveryConfirmation', fn ($d) => $d->where('confirmation_status', DeliveryConfirmation::STATUS_PENDING));
            })
            ->count();
        $exportDocsMissing = $this->exportDocsMissingCount($ctx);
        $onTimeRate = $this->onTimeDeliveryRate($ctx);
        $confirmedMtd = (int) DeliveryConfirmation::query()
            ->whereIn('transport_trip_id', $ctx->tripIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->whereMonth('received_date', now()->month)
            ->count();
        $confirmedLastMonth = (int) DeliveryConfirmation::query()
            ->whereIn('transport_trip_id', $ctx->tripIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->whereMonth('received_date', now()->subMonth()->month)
            ->count();

        return [
            'roleKey' => BusinessUser::ROLE_TRANSPORT_MANAGER,
            'headerBadge' => ['label' => __('Transport + delivery'), 'variant' => 'info'],
            'insight' => $this->transportInsight($ctx, $exportDocsMissing),
            'kpiCards' => [
                $this->kpi(__('Active trips'), $activeTrips, __(':count en route', ['count' => $enRoute]), 'info'),
                $this->kpi(__('Confirmed today'), $confirmedToday, __(':count unconfirmed', ['count' => $unconfirmedToday]), $unconfirmedToday > 0 ? 'warning' : 'positive'),
                $this->kpi(__('Export docs missing'), $exportDocsMissing, __('Nairobi trip'), $exportDocsMissing > 0 ? 'warning' : 'positive'),
                $this->kpi(__('On-time rate'), $onTimeRate.'%', '-5% '.__('mom'), 'negative'),
                $this->kpi(__('Confirmed MTD'), $confirmedMtd, '+'.max(0, $confirmedMtd - $confirmedLastMonth).' '.__('vs last month'), 'positive'),
            ],
            'leftPanel' => [
                'title' => __('Active trips'),
                'subtitle' => __('Live dispatch board'),
                'type' => 'trips',
                'items' => $this->activeTripRows($ctx, 4),
            ],
            'rightPanel' => [
                'title' => __('Pending confirmations'),
                'subtitle' => __('Delivery follow-up'),
                'type' => 'deliveries',
                'items' => $this->pendingConfirmationRows($ctx, 4),
            ],
            'quickActions' => [
                $this->action(__('New trip'), 'truck', 'transport-trips.create', BusinessUser::PERMISSION_CREATE_TRANSPORT_TRIP),
                $this->action(__('Dispatch'), 'truck', 'transport-trips.index', BusinessUser::PERMISSION_DISPATCH_DELIVERY),
                $this->action(__('Confirm delivery'), 'check', 'delivery-confirmations.create', BusinessUser::PERMISSION_CONFIRM_DELIVERY),
                $this->action(__('Export docs'), 'clipboard', 'delivery-confirmations.index', BusinessUser::PERMISSION_MANAGE_EXPORT_DOCUMENTS),
                $this->action(__('Track all'), 'truck', 'transport-trips.index', BusinessUser::PERMISSION_TRACK_DELIVERY_STATUS),
                $this->action(__('Export records'), 'clipboard-list', 'transport-trips.export', BusinessUser::PERMISSION_EXPORT_RECORDS),
            ],
        ];
    }

    private function buildAccountant(int $businessId, ProcessorDashboardContext $ctx): array
    {
        $now = now();
        $weekEnd = $now->copy()->endOfWeek();

        $arOutstanding = $this->sumOutstandingBalance('finance_invoices', $businessId);

        $arOverdue = (int) FinanceInvoice::query()
            ->where('business_id', $businessId)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now->startOfDay())
            ->whereRaw('amount_paid < total_amount')
            ->count();

        $apDueWeek = $this->sumOutstandingBalance('finance_payables', $businessId, function ($query) use ($now, $weekEnd): void {
            $query->whereNotNull('due_date')
                ->whereBetween('due_date', [$now->startOfDay(), $weekEnd])
                ->whereRaw('amount_paid < total_amount');
        });

        $apUrgent = (int) FinancePayable::query()
            ->where('business_id', $businessId)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$now->startOfDay(), $now->copy()->addDays(2)->endOfDay()])
            ->whereRaw('amount_paid < total_amount')
            ->count();

        $revenueMtd = $this->revenueMtd($businessId);
        $revenueLastMonth = $this->revenueForMonth($businessId, $now->copy()->subMonth());
        $revenueMom = $this->percentChange($revenueMtd, $revenueLastMonth);
        $collectionRate = $this->invoiceCollectionRate($businessId);

        $fmt = static fn (float $n): string => number_format($n, 0, '.', ',').' '.__('RWF');

        return [
            'roleKey' => BusinessUser::ROLE_ACCOUNTANT,
            'headerBadge' => ['label' => __('Finance only'), 'variant' => 'finance'],
            'insight' => $this->accountantInsight($businessId, $collectionRate, $arOverdue),
            'kpiCards' => [
                $this->kpi(__('AR outstanding'), $fmt($arOutstanding), __(':count overdue', ['count' => $arOverdue]), $arOverdue > 0 ? 'warning' : 'positive'),
                $this->kpi(__('AP due this week'), $fmt($apDueWeek), __(':count urgent', ['count' => $apUrgent]), $apUrgent > 0 ? 'warning' : 'info'),
                $this->kpi(__('Revenue MTD'), $this->formatMillions($revenueMtd), ($revenueMom >= 0 ? '+' : '').$revenueMom.'% '.__('mom'), $revenueMom >= 0 ? 'positive' : 'negative'),
                $this->kpi(__('Invoice collection rate'), $collectionRate.'%', __('target 90%'), $collectionRate >= 90 ? 'positive' : 'warning'),
                $this->kpi(__('Cost per kg'), __('RWF 940'), '+6% '.__('mom'), 'negative'),
            ],
            'leftPanel' => [
                'title' => __('AR invoices'),
                'subtitle' => __('Recent receivables'),
                'type' => 'invoices',
                'items' => $this->invoiceRows($businessId),
            ],
            'rightPanel' => [
                'title' => __('AP payables'),
                'subtitle' => __('Upcoming obligations'),
                'type' => 'payables',
                'items' => $this->payableRows($businessId),
            ],
            'quickActions' => [
                $this->action(__('Finance overview'), 'layout-dashboard', 'finance.dashboard', BusinessUser::PERMISSION_VIEW_FINANCE_DASHBOARD),
                $this->action(__('AR invoices'), 'clipboard', 'finance.invoices.index', BusinessUser::PERMISSION_MANAGE_AR_INVOICES),
                $this->action(__('AP payables'), 'clipboard-list', 'finance.payables.index', BusinessUser::PERMISSION_MANAGE_AP_PAYABLES),
                $this->action(__('Cost allocations'), 'box', 'finance.cost-allocations.index', BusinessUser::PERMISSION_VIEW_FINANCE_REPORTS),
                $this->action(__('Invoice from delivery'), 'truck', 'delivery-confirmations.index', BusinessUser::PERMISSION_MANAGE_AR_INVOICES),
                $this->action(__('Casual workers'), 'users', 'finance.casual-workers.index', BusinessUser::PERMISSION_MANAGE_AP_PAYABLES),
            ],
        ];
    }

    // --- KPI helpers ---

    private function kpi(string $label, int|string $value, string $change, string $deltaTone): array
    {
        return ['label' => $label, 'value' => $value, 'change' => $change, 'deltaTone' => $deltaTone];
    }

    private function action(string $label, string $icon, string $route, string $permission): array
    {
        return compact('label', 'icon', 'route', 'permission');
    }

    private function moduleRow(string $label, string $sub, string $icon, string $route, string $badgeTone, string $badge): array
    {
        return compact('label', 'sub', 'icon', 'route', 'badgeTone', 'badge');
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
            ->whereNull('certificate_id')
            ->latest()
            ->first();
        if ($trip) {
            $rows[] = [
                'message' => __('Transport trip missing certificate'),
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
                'route' => 'batches.index',
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
                'route' => 'transport-trips.index',
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
                'route' => 'delivery-confirmations.index',
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
