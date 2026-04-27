<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnimalIntakeRequest;
use App\Http\Requests\UpdateAnimalIntakeRequest;
use App\Models\AnimalIntake;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Facility;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnimalIntakeController extends Controller
{
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
    }

    private function authorizeIntake(Request $request, AnimalIntake $intake): void
    {
        if (! $this->userFacilityIds($request)->contains($intake->facility_id)) {
            abort(404);
        }
    }

    private function supplierFirstLastNames(Supplier $supplier): array
    {
        $first = $supplier->first_name ?? null;
        $last = $supplier->last_name ?? null;
        if (($first === null || $first === '') && ($last === null || $last === '') && ! empty($supplier->name ?? null)) {
            $parts = explode(' ', (string) $supplier->name, 2);
            $first = $parts[0] ?? '';
            $last = $parts[1] ?? '';
        }

        return ['first' => $first ?? '', 'last' => $last ?? ''];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Supplier>  $suppliers
     * @return array<int, array{first_name: string, last_name: string, phone: string, registration_number: string}>
     */
    private function suppliersPrefillData(\Illuminate\Support\Collection $suppliers): array
    {
        $out = [];
        foreach ($suppliers as $s) {
            $fn = $s->first_name ?? '';
            $ln = $s->last_name ?? '';
            if ($fn === '' && $ln === '' && ! empty($s->name ?? null)) {
                $parts = explode(' ', (string) $s->name, 2);
                $fn = $parts[0] ?? '';
                $ln = $parts[1] ?? '';
            }
            $out[$s->id] = [
                'first_name' => $fn,
                'last_name' => $ln,
                'phone' => $s->phone ?? '',
                'registration_number' => $s->registration_number ?? '',
            ];
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Client>  $clients
     * @return array<int, array{first_name: string, last_name: string, phone: string, country_id: int|null, province_id: int|null, district_id: int|null, sector_id: int|null, cell_id: int|null, village_id: int|null}>
     */
    private function clientsPrefillData(\Illuminate\Support\Collection $clients): array
    {
        $out = [];
        foreach ($clients as $client) {
            $parts = preg_split('/\s+/', trim((string) $client->name), 2) ?: [];
            $out[$client->id] = [
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? '',
                'phone' => $client->phone ?? '',
                'country_id' => $client->country_id,
                'province_id' => $client->province_id,
                'district_id' => $client->district_id,
                'sector_id' => $client->sector_id,
                'cell_id' => $client->cell_id,
                'village_id' => $client->village_id,
            ];
        }

        return $out;
    }

    private function hydrateIntakeSourceData(Request $request, array $data): array
    {
        $facilityId = (int) ($data['facility_id'] ?? 0);
        $facility = Facility::find($facilityId);
        if (! $facility) {
            abort(404);
        }

        if (($data['source_type'] ?? null) === AnimalIntake::SOURCE_TYPE_CLIENT) {
            $clientId = (int) ($data['client_id'] ?? 0);
            if ($clientId > 0) {
                $client = Client::query()
                    ->whereKey($clientId)
                    ->where('is_active', true)
                    ->first();
                if (! $client || (int) $client->business_id !== (int) $facility->business_id) {
                    abort(404);
                }
                $parts = preg_split('/\s+/', trim((string) $client->name), 2) ?: [];
                $data['supplier_firstname'] = $data['supplier_firstname'] ?? ($parts[0] ?? '');
                $data['supplier_lastname'] = $data['supplier_lastname'] ?? ($parts[1] ?? '');
                $data['supplier_contact'] = $data['supplier_contact'] ?? $client->phone;
                $data['country_id'] = $data['country_id'] ?? $client->country_id;
                $data['province_id'] = $data['province_id'] ?? $client->province_id;
                $data['district_id'] = $data['district_id'] ?? $client->district_id;
                $data['sector_id'] = $data['sector_id'] ?? $client->sector_id;
                $data['cell_id'] = $data['cell_id'] ?? $client->cell_id;
                $data['village_id'] = $data['village_id'] ?? $client->village_id;
            } else {
                $data['client_id'] = null;
                $data['supplier_firstname'] = $data['manual_client_firstname'] ?? $data['supplier_firstname'] ?? null;
                $data['supplier_lastname'] = $data['manual_client_lastname'] ?? $data['supplier_lastname'] ?? null;
                $data['supplier_contact'] = $data['manual_client_contact'] ?? $data['supplier_contact'] ?? null;
            }

            $data['supplier_id'] = null;
            $data['contract_id'] = null;
            $data['farm_registration_number'] = null;
        } else {
            $supplier = Supplier::find((int) ($data['supplier_id'] ?? 0));
            if (! $supplier || $supplier->business_id !== $facility->business_id || ! $supplier->isApproved()) {
                abort(404);
            }

            $names = $this->supplierFirstLastNames($supplier);
            $data['client_id'] = null;
            $data['supplier_firstname'] = $data['supplier_firstname'] ?? $names['first'];
            $data['supplier_lastname'] = $data['supplier_lastname'] ?? $names['last'];
            $data['supplier_contact'] = $data['supplier_contact'] ?? $supplier->phone;
            $data['farm_registration_number'] = $data['farm_registration_number'] ?? $supplier->registration_number;
            $data['country_id'] = $data['country_id'] ?? $supplier->country_id;
            $data['province_id'] = $data['province_id'] ?? $supplier->province_id;
            $data['district_id'] = $data['district_id'] ?? $supplier->district_id;
            $data['sector_id'] = $data['sector_id'] ?? $supplier->sector_id;
            $data['cell_id'] = $data['cell_id'] ?? $supplier->cell_id;
            $data['village_id'] = $data['village_id'] ?? $supplier->village_id;

            if (! empty($data['contract_id'])) {
                $contract = Contract::find($data['contract_id']);
                if (! $contract || ! $contract->isActiveSupplierContract() || ! $request->user()->accessibleBusinessIds()->contains($contract->business_id)) {
                    abort(404);
                }
            }
        }

        unset($data['manual_client_firstname'], $data['manual_client_lastname'], $data['manual_client_contact']);

        return $data;
    }

    public function hub(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $base = AnimalIntake::query()->whereIn('facility_id', $facilityIds);

        $totalIntakes = (clone $base)->count();
        $receivedCount = (clone $base)->where('status', AnimalIntake::STATUS_RECEIVED)->count();
        $approvedCount = (clone $base)->where('status', AnimalIntake::STATUS_APPROVED)->count();
        $rejectedCount = (clone $base)->where('status', AnimalIntake::STATUS_REJECTED)->count();
        $totalAnimals = (int) (clone $base)->sum('number_of_animals');
        $intakesWithPlansCount = (clone $base)->has('slaughterPlans')->count();

        return view('animal-intakes.hub', compact(
            'totalIntakes',
            'receivedCount',
            'approvedCount',
            'rejectedCount',
            'totalAnimals',
            'intakesWithPlansCount',
        ));
    }

    public function index(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $intakes = AnimalIntake::with(['facility', 'country', 'province', 'district'])
            ->whereIn('facility_id', $facilityIds)
            ->latest('intake_date')
            ->paginate(10);

        $kpis = [
            'total' => AnimalIntake::whereIn('facility_id', $facilityIds)->count(),
            'received' => AnimalIntake::whereIn('facility_id', $facilityIds)->where('status', AnimalIntake::STATUS_RECEIVED)->count(),
            'approved' => AnimalIntake::whereIn('facility_id', $facilityIds)->where('status', AnimalIntake::STATUS_APPROVED)->count(),
        ];

        return view('animal-intakes.index', compact('intakes', 'kpis'));
    }

    public function create(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type']);
        $businessIds = Facility::whereIn('id', $facilityIds)->pluck('business_id')->unique()->filter()->values();
        $suppliers = $businessIds->isNotEmpty()
            ? Supplier::whereIn('business_id', $businessIds)->where('supplier_status', Supplier::STATUS_APPROVED)->orderBy('id')->get()
            : collect();
        $clients = $businessIds->isNotEmpty()
            ? Client::whereIn('business_id', $businessIds)->where('is_active', true)->orderBy('name')->get(['id', 'business_id', 'name', 'email'])
            : collect();
        $suppliersForIntake = $this->suppliersPrefillData($suppliers);
        $clientsForIntake = $this->clientsPrefillData($clients);
        $supplierContracts = $businessIds->isNotEmpty()
            ? Contract::where('contract_category', Contract::CATEGORY_SUPPLIER)
                ->where('status', Contract::STATUS_ACTIVE)
                ->whereIn('business_id', $businessIds)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->with('supplier')
                ->orderBy('contract_number')
                ->get()
            : collect();

        return view('animal-intakes.create', compact('facilities', 'suppliers', 'clients', 'suppliersForIntake', 'clientsForIntake', 'supplierContracts'));
    }

    public function store(StoreAnimalIntakeRequest $request): RedirectResponse
    {
        $facilityId = (int) $request->validated('facility_id');
        if (! $this->userFacilityIds($request)->contains($facilityId)) {
            abort(404);
        }

        $data = $request->validated();
        $data = $this->hydrateIntakeSourceData($request, $data);

        AnimalIntake::create($data);

        return redirect()->route('animal-intakes.hub')
            ->with('status', __('Animal intake recorded.'));
    }

    public function show(Request $request, AnimalIntake $animalIntake): View
    {
        $this->authorizeIntake($request, $animalIntake);
        $animalIntake->load(['facility', 'supplier', 'client', 'contract', 'country', 'province', 'district', 'sector', 'cell', 'village', 'slaughterPlans']);

        return view('animal-intakes.show', ['intake' => $animalIntake]);
    }

    public function edit(Request $request, AnimalIntake $animalIntake): View
    {
        $this->authorizeIntake($request, $animalIntake);
        $facilityIds = $this->userFacilityIds($request);
        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type']);
        $businessIds = Facility::whereIn('id', $facilityIds)->pluck('business_id')->unique()->filter()->values();
        $suppliers = $businessIds->isNotEmpty()
            ? Supplier::whereIn('business_id', $businessIds)->where('supplier_status', Supplier::STATUS_APPROVED)->orderBy('id')->get()
            : collect();
        $clients = $businessIds->isNotEmpty()
            ? Client::whereIn('business_id', $businessIds)->where('is_active', true)->orderBy('name')->get(['id', 'business_id', 'name', 'email'])
            : collect();
        $suppliersForIntake = $this->suppliersPrefillData($suppliers);
        $clientsForIntake = $this->clientsPrefillData($clients);
        $supplierContracts = $businessIds->isNotEmpty()
            ? Contract::where('contract_category', Contract::CATEGORY_SUPPLIER)
                ->where('status', Contract::STATUS_ACTIVE)
                ->whereIn('business_id', $businessIds)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->with('supplier')
                ->orderBy('contract_number')
                ->get()
            : collect();

        return view('animal-intakes.edit', ['intake' => $animalIntake, 'facilities' => $facilities, 'suppliers' => $suppliers, 'clients' => $clients, 'suppliersForIntake' => $suppliersForIntake, 'clientsForIntake' => $clientsForIntake, 'supplierContracts' => $supplierContracts]);
    }

    public function update(UpdateAnimalIntakeRequest $request, AnimalIntake $animalIntake): RedirectResponse
    {
        $this->authorizeIntake($request, $animalIntake);
        $facilityId = (int) $request->validated('facility_id');
        if (! $this->userFacilityIds($request)->contains($facilityId)) {
            abort(404);
        }

        $data = $request->validated();
        $data = $this->hydrateIntakeSourceData($request, $data);

        $animalIntake->update($data);

        return redirect()->route('animal-intakes.show', $animalIntake)
            ->with('status', __('Animal intake updated.'));
    }
}
