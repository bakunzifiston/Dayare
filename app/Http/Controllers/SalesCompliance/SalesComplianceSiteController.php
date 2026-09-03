<?php

namespace App\Http\Controllers\SalesCompliance;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesCompliance\StoreSalesComplianceSiteRequest;
use App\Models\AdministrativeDivision;
use App\Models\SalesComplianceSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesComplianceSiteController extends Controller
{
    public function index(Request $request): View
    {
        $businessId = $this->businessId($request);
        $sites = SalesComplianceSite::query()
            ->with(['province', 'district', 'sector'])
            ->where('business_id', $businessId)
            ->when($request->filled('site_type'), fn ($q) => $q->where('site_type', $request->query('site_type')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('sales-compliance.sites.index', [
            'sites' => $sites,
            'filters' => ['site_type' => (string) $request->query('site_type', '')],
        ]);
    }

    public function create(): View
    {
        return view('sales-compliance.sites.create', [
            'site' => null,
        ]);
    }

    public function store(StoreSalesComplianceSiteRequest $request): RedirectResponse
    {
        $site = SalesComplianceSite::query()->create(array_merge($this->siteAttributes($request), [
            'business_id' => $this->businessId($request),
        ]));

        return redirect()->route('sales-compliance.sites.show', $site)->with('status', __('Site created.'));
    }

    public function show(Request $request, SalesComplianceSite $site): View
    {
        $this->authorizeSite($request, $site);
        $site->load(['inspections' => fn ($q) => $q->latest('scheduled_date')->limit(20), 'country', 'province', 'district', 'sector']);

        return view('sales-compliance.sites.show', compact('site'));
    }

    public function edit(Request $request, SalesComplianceSite $site): View
    {
        $this->authorizeSite($request, $site);

        return view('sales-compliance.sites.edit', compact('site'));
    }

    public function update(StoreSalesComplianceSiteRequest $request, SalesComplianceSite $site): RedirectResponse
    {
        $this->authorizeSite($request, $site);
        $site->update($this->siteAttributes($request));

        return redirect()->route('sales-compliance.sites.show', $site)->with('status', __('Site updated.'));
    }

    public function destroy(Request $request, SalesComplianceSite $site): RedirectResponse
    {
        $this->authorizeSite($request, $site);
        $site->delete();

        return redirect()->route('sales-compliance.sites.index')->with('status', __('Site deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function siteAttributes(StoreSalesComplianceSiteRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['location_address'] = $this->composeLocationAddress(
            (int) $data['country_id'],
            (int) $data['province_id'],
            (int) $data['district_id'],
            (int) $data['sector_id'],
        );

        return $data;
    }

    private function composeLocationAddress(int $countryId, int $provinceId, int $districtId, int $sectorId): string
    {
        $names = AdministrativeDivision::query()
            ->whereIn('id', [$countryId, $provinceId, $districtId, $sectorId])
            ->get()
            ->keyBy('id');

        return collect([$provinceId, $districtId, $sectorId])
            ->map(fn (int $id): ?string => $names[$id]->name ?? null)
            ->filter()
            ->implode(', ');
    }

    private function businessId(Request $request): int
    {
        $businessId = $request->user()->activeProcessorBusinessId();
        abort_if($businessId === null, 403, __('Select a processor business first.'));

        return $businessId;
    }

    private function authorizeSite(Request $request, SalesComplianceSite $site): void
    {
        abort_unless((int) $site->business_id === $this->businessId($request), 404);
    }
}
