<?php

namespace App\Http\Controllers;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\Demand;
use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuperAdminVibeProgrammeController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredBusinessesQuery($request)
            ->with(['user:id,name,email'])
            ->withCount('facilities')
            ->orderByDesc('id');

        $businesses = $query->paginate(20)->withQueryString();

        $globalSummaryQuery = Business::query();
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

        $analytics = $this->businessAnalytics($business);
        $completeness = $this->businessDataCompleteness($business);

        return view('super-admin.vibe-programme.show', [
            'business' => $business,
            'analytics' => $analytics,
            'completeness' => $completeness,
        ]);
    }

    public function exportBusinessCsv(Business $business): StreamedResponse
    {
        $business->loadMissing([
            'user:id,name,email',
            'ownershipMembers',
            'countryDivision:id,name',
            'provinceDivision:id,name',
            'districtDivision:id,name',
            'sectorDivision:id,name',
            'cellDivision:id,name',
            'villageDivision:id,name',
        ]);
        $filename = 'vibe-business-'.$business->id.'-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($business): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->registrationCsvHeaders());
            fputcsv($out, $this->registrationCsvRow($business));
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportProgrammeCsv(Request $request): StreamedResponse
    {
        $filename = 'vibe-programme-'.now()->format('Ymd_His').'.csv';
        $query = $this->filteredBusinessesQuery($request)
            ->with([
                'user:id,name,email',
                'countryDivision:id,name',
                'provinceDivision:id,name',
                'districtDivision:id,name',
                'sectorDivision:id,name',
                'cellDivision:id,name',
                'villageDivision:id,name',
                'ownershipMembers:id,business_id,first_name,last_name,date_of_birth,gender,pwd_status,phone,email,sort_order',
            ])
            ->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->registrationCsvHeaders());

            $query->chunkById(150, function (Collection $chunk) use ($out): void {
                foreach ($chunk as $business) {
                    if (! $business instanceof Business) {
                        continue;
                    }
                    fputcsv($out, $this->registrationCsvRow($business));
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Column headers for registration-data CSV exports (one header row, one value row per business).
     *
     * @return list<string>
     */
    private function registrationCsvHeaders(): array
    {
        return [
            'business_id',
            'account_owner_name',
            'account_owner_email',
            'business_type',
            'business_name',
            'rdb_registration_number',
            'tax_id',
            'business_contact_phone',
            'business_email',
            'business_status',
            'owner_first_name',
            'owner_last_name',
            'owner_dob',
            'owner_gender',
            'owner_pwd_status',
            'owner_phone',
            'owner_profile_email',
            'ownership_type',
            'ownership_members',
            'ownership_members_detail',
            'business_size',
            'baseline_revenue',
            'vibe_unique_id',
            'vibe_commencement_date',
            'pathway_status',
            'vibe_comments',
            'country_id',
            'country_name',
            'province_id',
            'province_name',
            'district_id',
            'district_name',
            'sector_id',
            'sector_name',
            'cell_id',
            'cell_name',
            'village_id',
            'village_name',
            'city',
            'state_region',
            'postal_code',
            'country_free_text',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * One CSV data row for a business (same column order as registrationCsvHeaders).
     *
     * @return list<string>
     */
    private function baselineRevenueCsvValue(Business $business): string
    {
        $b = $business->baseline_revenue;
        if ($b === null || $b === '') {
            return '';
        }
        $key = (string) $b;
        $options = Business::baselineRevenueBracketOptions();

        return (string) ($options[$key] ?? $key);
    }

    private function registrationCsvRow(Business $business): array
    {
        $sortedMembers = $business->relationLoaded('ownershipMembers')
            ? $business->ownershipMembers->sortBy('sort_order')->values()
            : collect();

        $membersNames = $sortedMembers
            ->map(fn ($member) => trim((string) (($member->first_name ?? '').' '.($member->last_name ?? ''))))
            ->filter()
            ->implode('; ');

        $membersDetail = $sortedMembers
            ->map(function ($member): string {
                $parts = [
                    (string) ($member->first_name ?? ''),
                    (string) ($member->last_name ?? ''),
                    (string) optional($member->date_of_birth)->toDateString(),
                    (string) ($member->gender ?? ''),
                    (string) ($member->pwd_status ?? ''),
                    (string) ($member->phone ?? ''),
                    (string) ($member->email ?? ''),
                ];

                return implode('|', $parts);
            })
            ->filter(fn (string $row) => trim(str_replace('|', '', $row)) !== '')
            ->implode(' ;; ');

        return [
            (string) $business->id,
            (string) ($business->user?->name ?? ''),
            (string) ($business->user?->email ?? ''),
            (string) ($business->type ?? ''),
            (string) $business->business_name,
            (string) ($business->registration_number ?? ''),
            (string) ($business->tax_id ?? ''),
            (string) ($business->contact_phone ?? ''),
            (string) ($business->email ?? ''),
            (string) ($business->status ?? ''),
            (string) ($business->owner_first_name ?? ''),
            (string) ($business->owner_last_name ?? ''),
            (string) optional($business->owner_dob)->toDateString(),
            (string) ($business->owner_gender ?? ''),
            (string) ($business->owner_pwd_status ?? ''),
            (string) ($business->owner_phone ?? ''),
            (string) ($business->owner_email ?? ''),
            (string) ($business->ownership_type ?? ''),
            (string) $membersNames,
            (string) $membersDetail,
            (string) ($business->business_size ?? ''),
            $this->baselineRevenueCsvValue($business),
            (string) ($business->vibe_unique_id ?? ''),
            (string) optional($business->vibe_commencement_date)->toDateString(),
            (string) ($business->pathway_status ?? ''),
            (string) ($business->vibe_comments ?? ''),
            (string) ($business->country_id ?? ''),
            (string) ($business->countryDivision?->name ?? ''),
            (string) ($business->province_id ?? ''),
            (string) ($business->provinceDivision?->name ?? ''),
            (string) ($business->district_id ?? ''),
            (string) ($business->districtDivision?->name ?? ''),
            (string) ($business->sector_id ?? ''),
            (string) ($business->sectorDivision?->name ?? ''),
            (string) ($business->cell_id ?? ''),
            (string) ($business->cellDivision?->name ?? ''),
            (string) ($business->village_id ?? ''),
            (string) ($business->villageDivision?->name ?? ''),
            (string) ($business->city ?? ''),
            (string) ($business->state_region ?? ''),
            (string) ($business->postal_code ?? ''),
            (string) ($business->country ?? ''),
            (string) optional($business->created_at)->toDateTimeString(),
            (string) optional($business->updated_at)->toDateTimeString(),
        ];
    }

    private function filteredBusinessesQuery(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $pathwayStatus = trim((string) $request->query('pathway_status', ''));
        $status = trim((string) $request->query('status', ''));

        return Business::query()
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

    private function businessAnalytics(Business $business): array
    {
        $facilityIds = Facility::query()
            ->where('business_id', $business->id)
            ->pluck('id');

        $animalIntakeRecords = (int) AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->count();
        $certificatesIssued = (int) Certificate::query()
            ->whereIn('facility_id', $facilityIds)
            ->count();
        $compliantCertificates = (int) Certificate::query()
            ->whereIn('facility_id', $facilityIds)
            ->compliant()
            ->count();
        $confirmedDeliveries = (int) DeliveryConfirmation::query()
            ->whereIn('receiving_facility_id', $facilityIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->count();
        $demandsTotal = (int) Demand::query()
            ->where('business_id', $business->id)
            ->count();
        $demandsFulfilled = (int) Demand::query()
            ->where('business_id', $business->id)
            ->where('status', Demand::STATUS_FULFILLED)
            ->count();

        $demandFulfillmentRate = $demandsTotal > 0
            ? round(($demandsFulfilled / $demandsTotal) * 100, 1)
            : 0.0;
        $complianceScore = $certificatesIssued > 0
            ? round(($compliantCertificates / $certificatesIssued) * 100, 1)
            : 0.0;

        $beforeTurnover = (float) (Business::baselineRevenueMidpointRwf(
            $business->baseline_revenue !== null && $business->baseline_revenue !== ''
                ? (string) $business->baseline_revenue
                : null
        ) ?? 0);
        $afterTurnover = (float) AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->whereDate('intake_date', '>=', now()->subMonths(12)->startOfMonth())
            ->sum('total_price');
        $growthPct = $beforeTurnover > 0
            ? round((($afterTurnover - $beforeTurnover) / $beforeTurnover) * 100, 1)
            : ($afterTurnover > 0 ? 100.0 : 0.0);

        $trend = $this->businessTrendData($business, $facilityIds);

        $insights = [
            __('Demand fulfillment is :value%.', ['value' => number_format($demandFulfillmentRate, 1)]),
            __('Compliance score is :value%.', ['value' => number_format($complianceScore, 1)]),
            __('Estimated turnover growth is :value%.', ['value' => number_format($growthPct, 1)]),
        ];

        return [
            'kpis' => [
                'facilities' => (int) $facilityIds->count(),
                'animal_intake_records' => $animalIntakeRecords,
                'certificates_issued' => $certificatesIssued,
                'confirmed_deliveries' => $confirmedDeliveries,
                'demand_fulfillment_rate' => $demandFulfillmentRate,
                'compliance_score' => $complianceScore,
            ],
            'turnover' => [
                'before' => $beforeTurnover,
                'after' => $afterTurnover,
                'growth_pct' => $growthPct,
            ],
            'trend' => $trend,
            'insights' => $insights,
        ];
    }

    private function businessTrendData(Business $business, Collection $facilityIds): array
    {
        $months = 6;
        $windowStart = now()->subMonths($months - 1)->startOfMonth();
        $monthKeys = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKeys[] = now()->subMonths($i)->format('Y-m');
        }

        $intakes = AnimalIntake::query()
            ->whereIn('facility_id', $facilityIds)
            ->whereDate('intake_date', '>=', $windowStart)
            ->get();
        $intakeByMonth = $intakes
            ->groupBy(fn ($row) => Carbon::parse($row->intake_date)->format('Y-m'))
            ->map->count();
        $intakeValueByMonth = $intakes
            ->groupBy(fn ($row) => Carbon::parse($row->intake_date)->format('Y-m'))
            ->map(fn (Collection $rows) => (float) $rows->sum('total_price'));

        $certificates = Certificate::query()
            ->whereIn('facility_id', $facilityIds)
            ->whereDate('issued_at', '>=', $windowStart)
            ->get();
        $certByMonth = $certificates
            ->groupBy(fn ($row) => Carbon::parse($row->issued_at)->format('Y-m'))
            ->map->count();

        $deliveries = DeliveryConfirmation::query()
            ->whereIn('receiving_facility_id', $facilityIds)
            ->where('confirmation_status', DeliveryConfirmation::STATUS_CONFIRMED)
            ->whereDate('received_date', '>=', $windowStart)
            ->get();
        $deliveryByMonth = $deliveries
            ->groupBy(fn ($row) => Carbon::parse($row->received_date)->format('Y-m'))
            ->map->count();

        $labels = array_map(
            fn (string $month) => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y'),
            $monthKeys
        );

        $fill = static fn (Collection $values) => array_map(
            fn (string $month) => (float) ($values[$month] ?? 0),
            $monthKeys
        );

        return [
            'charts' => [
                'vibe_kpi_progress' => [
                    'type' => 'line',
                    'labels' => $labels,
                    'datasets' => [
                        ['label' => __('Intakes'), 'data' => $fill($intakeByMonth)],
                        ['label' => __('Certificates'), 'data' => $fill($certByMonth)],
                        ['label' => __('Deliveries'), 'data' => $fill($deliveryByMonth)],
                    ],
                    'yTickPrecision' => 0,
                ],
                'vibe_turnover_progress' => [
                    'type' => 'bar',
                    'labels' => $labels,
                    'datasets' => [
                        ['label' => __('Turnover (estimated)'), 'data' => $fill($intakeValueByMonth)],
                    ],
                    'yTickPrecision' => 0,
                ],
            ],
        ];
    }

    private function businessDataCompleteness(Business $business): array
    {
        $checks = [
            'business_name' => filled($business->business_name),
            'registration_number' => filled($business->registration_number),
            'contact_phone' => filled($business->contact_phone),
            'email' => filled($business->email),
            'type' => filled($business->type),
            'owner_first_name' => filled($business->owner_first_name),
            'owner_last_name' => filled($business->owner_last_name),
            'ownership_type' => filled($business->ownership_type),
            'pathway_status' => filled($business->pathway_status),
            'vibe_unique_id' => filled($business->vibe_unique_id),
            'vibe_commencement_date' => ! empty($business->vibe_commencement_date),
            'baseline_revenue' => filled($business->baseline_revenue),
            'country_id' => ! empty($business->country_id),
            'district_id' => ! empty($business->district_id),
            'sector_id' => ! empty($business->sector_id),
        ];

        $total = count($checks);
        $completed = count(array_filter($checks));
        $percent = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $percent,
            'checks' => $checks,
        ];
    }

}

