<?php

namespace App\Services\SalesCompliance;

use App\Models\SalesComplianceEscalation;
use App\Models\SalesComplianceInspection;
use App\Models\SalesComplianceSite;
use App\Support\SalesComplianceCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SalesComplianceDashboardService
{
    /**
     * @return array{
     *     upcoming: LengthAwarePaginator,
     *     completed: LengthAwarePaginator,
     *     followUp: Collection,
     *     missingCertificates: Collection,
     *     repeatNonCompliant: Collection,
     *     escalations: Collection,
     *     kpis: array<string, int>
     * }
     */
    public function build(int $businessId, Request $request): array
    {
        $base = SalesComplianceInspection::query()
            ->with(['site', 'inspector', 'assignedUser'])
            ->where('business_id', $businessId);

        $this->applyFilters($base, $request);

        $upcoming = (clone $base)
            ->where('status', SalesComplianceCatalog::STATUS_PENDING)
            ->whereDate('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->paginate(10, ['*'], 'upcoming_page')
            ->withQueryString();

        $completed = (clone $base)
            ->whereIn('status', [SalesComplianceCatalog::STATUS_PASSED, SalesComplianceCatalog::STATUS_FAILED])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'completed_page')
            ->withQueryString();

        $repeatSiteIds = SalesComplianceInspection::query()
            ->where('business_id', $businessId)
            ->where('status', SalesComplianceCatalog::STATUS_FAILED)
            ->selectRaw('site_id, COUNT(*) as fail_count')
            ->groupBy('site_id')
            ->havingRaw('COUNT(*) >= 2')
            ->pluck('site_id');

        $repeatNonCompliant = SalesComplianceSite::query()
            ->where('business_id', $businessId)
            ->whereIn('id', $repeatSiteIds)
            ->withCount([
                'inspections as failed_inspections_count' => fn ($q) => $q->where('status', SalesComplianceCatalog::STATUS_FAILED),
            ])
            ->orderBy('name')
            ->get();

        $followUp = SalesComplianceInspection::query()
            ->with(['site', 'inspector', 'assignedUser'])
            ->where('business_id', $businessId)
            ->where(function ($q) use ($repeatSiteIds): void {
                $q->where('status', SalesComplianceCatalog::STATUS_FAILED)
                    ->orWhere(function ($overdue): void {
                        $overdue->where('status', SalesComplianceCatalog::STATUS_PENDING)
                            ->whereDate('scheduled_date', '<', now()->toDateString());
                    })
                    ->orWhereIn('site_id', $repeatSiteIds);
            })
            ->orderByDesc('scheduled_date')
            ->limit(50)
            ->get();

        $certificateKeys = SalesComplianceCatalog::certificateItemKeys();
        $missingCertificates = SalesComplianceInspection::query()
            ->with(['site'])
            ->where('business_id', $businessId)
            ->where(function ($q) use ($certificateKeys): void {
                $q->whereHas('responses', fn ($r) => $r->whereIn('item_key', $certificateKeys)->where('result', SalesComplianceCatalog::RESULT_MISSING))
                    ->orWhereHas('productLines', fn ($p) => $p->where('certificate_status', SalesComplianceCatalog::RESULT_MISSING));
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $escalations = SalesComplianceEscalation::query()
            ->with(['site', 'inspection', 'createdBy'])
            ->where('business_id', $businessId)
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'in_review' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $kpis = [
            'upcoming' => SalesComplianceInspection::query()->where('business_id', $businessId)->where('status', SalesComplianceCatalog::STATUS_PENDING)->whereDate('scheduled_date', '>=', now()->toDateString())->count(),
            'completed' => SalesComplianceInspection::query()->where('business_id', $businessId)->whereIn('status', [SalesComplianceCatalog::STATUS_PASSED, SalesComplianceCatalog::STATUS_FAILED])->count(),
            'failed' => SalesComplianceInspection::query()->where('business_id', $businessId)->where('status', SalesComplianceCatalog::STATUS_FAILED)->count(),
            'follow_up' => $followUp->count(),
            'missing_certs' => $missingCertificates->count(),
            'repeat' => $repeatNonCompliant->count(),
            'open_escalations' => SalesComplianceEscalation::query()->where('business_id', $businessId)->where('status', '!=', SalesComplianceCatalog::ESCALATION_RESOLVED)->count(),
            'sites' => SalesComplianceSite::query()->where('business_id', $businessId)->count(),
        ];

        return compact(
            'upcoming',
            'completed',
            'followUp',
            'missingCertificates',
            'repeatNonCompliant',
            'escalations',
            'kpis',
        );
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('assignee')) {
            $this->applyAssigneeFilter($query, (string) $request->query('assignee'));
        }
        if ($request->filled('inspector_id')) {
            $query->where('inspector_id', (int) $request->query('inspector_id'));
        }
        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', (int) $request->query('assigned_user_id'));
        }
        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->query('site_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }
        if ($request->filled('from')) {
            $query->whereDate('scheduled_date', '>=', (string) $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('scheduled_date', '<=', (string) $request->query('to'));
        }
    }

    private function applyAssigneeFilter($query, string $assignee): void
    {
        if (str_starts_with($assignee, 'inspector:')) {
            $query->where('inspector_id', (int) substr($assignee, 10));
        } elseif (str_starts_with($assignee, 'user:')) {
            $query->where('assigned_user_id', (int) substr($assignee, 5));
        }
    }
}
