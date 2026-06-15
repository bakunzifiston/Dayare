<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\SuperAdmin\SuperAdminVibeAnalyticsService;
use App\Support\TenantEnvironmentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperAdminVibeProgrammeController extends Controller
{
    public function __construct(
        private readonly SuperAdminVibeAnalyticsService $analytics,
    ) {}
    public function index(Request $request)
    {
        TenantEnvironmentScope::setFilter(TenantEnvironmentScope::resolveFromRequest($request));

        $query = $this->filteredBusinessesQuery($request)
            ->with(['user:id,name,email'])
            ->withCount('facilities')
            ->orderByDesc('id');

        $businesses = $query->paginate(20)->withQueryString();

        $globalSummaryQuery = TenantEnvironmentScope::applyToBusinesses(Business::query());
        $summary = [
            'total_businesses' => (clone $globalSummaryQuery)->count(),
            'active_businesses' => (clone $globalSummaryQuery)->where('status', Business::STATUS_ACTIVE)->count(),
            'pathway_active' => (clone $globalSummaryQuery)->where('pathway_status', 'active')->count(),
            'pathway_verification' => (clone $globalSummaryQuery)->where('pathway_status', 'verification')->count(),
            'filtered_businesses' => (clone $this->filteredBusinessesQuery($request))->count(),
        ];

        return view('super-admin.vibe-programme.index', [
            'businesses' => $businesses,
            'summary' => $summary,
            'tenantEnvironmentFilter' => TenantEnvironmentScope::current(),
            'filters' => [
                'search' => (string) $request->query('search', ''),
                'type' => (string) $request->query('type', ''),
                'pathway_status' => (string) $request->query('pathway_status', ''),
                'status' => (string) $request->query('status', ''),
            ],
        ]);
    }

    public function show(Business $business)
    {
        $business->load([
            'user:id,name,email',
            'facilities:id,business_id,facility_name,facility_type',
            'countryDivision:id,name',
            'provinceDivision:id,name',
            'districtDivision:id,name',
            'sectorDivision:id,name',
            'cellDivision:id,name',
            'villageDivision:id,name',
        ]);

        $analytics = $this->analytics->businessAnalytics($business);
        $completeness = $this->analytics->businessDataCompleteness($business);

        return view('super-admin.vibe-programme.show', [
            'business' => $business,
            'analytics' => $analytics,
            'completeness' => $completeness,
        ]);
    }

    public function exportBusinessCsv(Business $business): StreamedResponse
    {
        $filename = 'vibe-business-analytics-'.$business->id.'-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($business): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->analytics->analyticsCsvHeaders());
            fputcsv($out, $this->analytics->analyticsCsvRow($business));
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportProgrammeCsv(Request $request): StreamedResponse
    {
        TenantEnvironmentScope::setFilter(TenantEnvironmentScope::resolveFromRequest($request));

        $filename = 'vibe-programme-analytics-'.now()->format('Ymd_His').'.csv';
        $query = $this->filteredBusinessesQuery($request)->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->analytics->analyticsCsvHeaders());

            $query->chunkById(150, function (Collection $chunk) use ($out): void {
                foreach ($chunk as $business) {
                    if (! $business instanceof Business) {
                        continue;
                    }
                    fputcsv($out, $this->analytics->analyticsCsvRow($business));
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function filteredBusinessesQuery(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $pathwayStatus = trim((string) $request->query('pathway_status', ''));
        $status = trim((string) $request->query('status', ''));

        return TenantEnvironmentScope::applyToBusinesses(Business::query())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('business_name', 'like', '%'.$search.'%')
                        ->orWhere('registration_number', 'like', '%'.$search.'%')
                        ->orWhere('vibe_unique_id', 'like', '%'.$search.'%')
                        ->orWhere('owner_first_name', 'like', '%'.$search.'%')
                        ->orWhere('owner_last_name', 'like', '%'.$search.'%');
                });
            })
            ->when($type !== '', fn (Builder $query) => $query->where('type', $type))
            ->when($pathwayStatus !== '', fn (Builder $query) => $query->where('pathway_status', $pathwayStatus))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status));
    }
}

