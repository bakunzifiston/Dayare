<?php

namespace App\Http\Controllers;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Facility;
use App\Models\Supplier;
use App\Services\Processor\ProcessorFinanceSync;
use App\Support\AnimalIntakeMovementPermitStorage;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            $data['transport_vehicle_plate'] = null;
            $data['driver_name'] = null;
            $data['movement_permit_no'] = null;
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
        $filters = $this->resolveHubFilters($request);

        $scopeIntakes = function ($query) use ($facilityIds, $filters): void {
            $query->whereIn('facility_id', $facilityIds);
            if ($filters['is_filtered']) {
                $query->whereDate('intake_date', '>=', $filters['start']->toDateString())
                    ->whereDate('intake_date', '<=', $filters['end']->toDateString());
            }
        };

        $hubStats = [
            'heads_available' => AnimalIntakeItem::whereHas('intake', fn ($q) => $q
                ->whereIn('facility_id', $facilityIds)
                ->whereIn('status', [AnimalIntake::STATUS_RECEIVED, AnimalIntake::STATUS_APPROVED])
                ->where('is_draft', false)
            )->available()->count(),
            'intakes_in_period' => AnimalIntake::query()
                ->where('is_draft', false)
                ->where($scopeIntakes)
                ->count(),
            'intakes_label' => $filters['intakes_label'],
            'cattle_count' => $this->speciesHeadCountInPeriod($facilityIds, $filters, AnimalIntake::SPECIES_CATTLE),
            'goat_count' => $this->speciesHeadCountInPeriod($facilityIds, $filters, AnimalIntake::SPECIES_GOAT),
            'sheep_count' => $this->speciesHeadCountInPeriod($facilityIds, $filters, AnimalIntake::SPECIES_SHEEP),
        ];

        $intakes = AnimalIntake::query()
            ->with(['facility', 'supplier', 'client', 'items.slaughterPlan'])
            ->where($scopeIntakes)
            ->when($request->filled('reference'), fn ($q) => $q->where('reference', $request->string('reference')))
            ->latest('intake_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('animal-intakes.hub', compact('hubStats', 'intakes', 'filters'));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $facilityIds
     */
    private function speciesHeadCountInPeriod(\Illuminate\Support\Collection $facilityIds, array $filters, string $species): int
    {
        $intakeScope = function ($query) use ($facilityIds, $filters): void {
            $query->where('is_draft', false)
                ->whereIn('facility_id', $facilityIds);
            if ($filters['is_filtered']) {
                $query->whereDate('intake_date', '>=', $filters['start']->toDateString())
                    ->whereDate('intake_date', '<=', $filters['end']->toDateString());
            }
        };

        $fromItems = (int) AnimalIntakeItem::query()
            ->where('species', $species)
            ->whereHas('intake', $intakeScope)
            ->count();

        $fromLegacyIntakes = (int) AnimalIntake::query()
            ->where('species', $species)
            ->whereDoesntHave('items')
            ->where($intakeScope)
            ->sum('number_of_animals');

        return $fromItems + $fromLegacyIntakes;
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: null,
     *     end: null,
     *     range_label: string,
     *     intakes_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function hubFiltersAllTime(): array
    {
        return [
            'period' => 'all',
            'date_from' => '',
            'date_to' => '',
            'start' => null,
            'end' => null,
            'range_label' => __('All time'),
            'intakes_label' => __('Total intakes'),
            'has_custom_range' => false,
            'is_filtered' => false,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, date_from: string, date_to: string, range_label: string, intakes_label: string}
     */
    private function presetRangeForPeriod(string $period): array
    {
        $now = now();

        [$start, $end, $rangeLabel, $intakesLabel] = match ($period) {
            'day' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->format('M j, Y'),
                __('Intakes today'),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                (string) $now->year,
                __('Intakes this year'),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->format('F Y'),
                __('Intakes this month'),
            ],
            default => throw new \InvalidArgumentException('Invalid preset period.'),
        };

        return [
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'range_label' => $rangeLabel,
            'intakes_label' => $intakesLabel,
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
     *     intakes_label: string,
     *     has_custom_range: bool,
     *     is_filtered: bool
     * }
     */
    private function resolveHubFilters(Request $request): array
    {
        if (! $request->hasAny(['period', 'date_from', 'date_to'])) {
            return $this->hubFiltersAllTime();
        }

        $period = (string) $request->query('period', 'all');
        if (! in_array($period, ['all', 'day', 'month', 'year'], true)) {
            $period = 'all';
        }

        $rawFrom = trim((string) $request->query('date_from', ''));
        $rawTo = trim((string) $request->query('date_to', ''));

        if ($period === 'all' && $rawFrom === '' && $rawTo === '') {
            return $this->hubFiltersAllTime();
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
                'intakes_label' => __('Intakes in range'),
                'has_custom_range' => true,
                'is_filtered' => true,
            ];
        }

        if (in_array($period, ['day', 'month', 'year'], true)) {
            $preset = $this->presetRangeForPeriod($period);

            return [
                'period' => $period,
                'date_from' => $preset['date_from'],
                'date_to' => $preset['date_to'],
                'start' => $preset['start'],
                'end' => $preset['end'],
                'range_label' => $preset['range_label'],
                'intakes_label' => $preset['intakes_label'],
                'has_custom_range' => false,
                'is_filtered' => true,
            ];
        }

        return $this->hubFiltersAllTime();
    }

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('animal-intakes.hub', $request->query());
    }

    public function create(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $facilities = Facility::whereIn('id', $facilityIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'facility_type']);
        $businessIds = Facility::whereIn('id', $facilityIds)->pluck('business_id')->unique()->filter()->values();
        $clients = $businessIds->isNotEmpty()
            ? Client::whereIn('business_id', $businessIds)->where('is_active', true)->orderBy('name')->get(['id', 'business_id', 'name', 'email'])
            : collect();
        $clientsForIntake = $this->clientsPrefillData($clients);

        return view('animal-intakes.create', compact('facilities', 'clients', 'clientsForIntake'));
    }

    // --- Section 3: store (draft + submit) ---

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateIntakeSession($request);
        $isDraft = $request->boolean('is_draft');

        $facilityId = (int) $validated['facility_id'];
        if (! $this->userFacilityIds($request)->contains($facilityId)) {
            abort(404);
        }

        try {
            $intake = DB::transaction(function () use ($request, $validated, $isDraft): AnimalIntake {
                $data = $this->buildIntakeHeaderData($request, $validated, $isDraft, null);
                $intake = AnimalIntake::create($data);

                foreach ($validated['animals'] as $animal) {
                    $intake->items()->create($this->mapAnimalItemAttributes($animal));
                }

                $this->syncLegacyIntakeColumns($intake, $validated['animals']);
                $intake->refresh()->load('items');

                if (! $isDraft) {
                    $this->dispatchIntakeSubmitted($intake);
                }

                return $intake;
            });
        } catch (\Throwable $e) {
            Log::error('Animal intake store failed', ['exception' => $e]);

            return back()->withInput()->withErrors([
                'form' => __('Could not save intake. Please try again.'),
            ]);
        }

        $animalCount = count($validated['animals']);
        $financeWarning = ! $isDraft ? $this->syncFinanceSafe($intake) : null;

        if ($isDraft) {
            $redirect = redirect()
                ->route('animal-intakes.edit', $intake)
                ->with('status', __('Draft saved — :count animals recorded.', ['count' => $animalCount]));
            if ($financeWarning) {
                $redirect->with('warning', $financeWarning);
            }

            return $redirect;
        }

        $redirect = redirect()
            ->route('animal-intakes.hub')
            ->with('status', __('Intake :reference submitted — :count animals recorded.', [
                'reference' => $intake->reference,
                'count' => $animalCount,
            ]));
        if ($financeWarning) {
            $redirect->with('warning', $financeWarning);
        }

        return $redirect;
    }

    public function show(Request $request, AnimalIntake $animalIntake): View
    {
        $this->authorizeIntake($request, $animalIntake);
        $animalIntake->load(['facility', 'supplier', 'client', 'contract', 'country', 'province', 'district', 'sector', 'cell', 'village', 'slaughterPlans', 'items']);

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
        $clients = $businessIds->isNotEmpty()
            ? Client::whereIn('business_id', $businessIds)->where('is_active', true)->orderBy('name')->get(['id', 'business_id', 'name', 'email'])
            : collect();
        $clientsForIntake = $this->clientsPrefillData($clients);

        $animalIntake->load(['items', 'supplier', 'contract']);

        return view('animal-intakes.edit', [
            'intake' => $animalIntake,
            'facilities' => $facilities,
            'clients' => $clients,
            'clientsForIntake' => $clientsForIntake,
        ]);
    }

    // --- Section 3: update ---

    public function update(Request $request, AnimalIntake $animalIntake): RedirectResponse
    {
        $this->authorizeIntake($request, $animalIntake);

        if ($animalIntake->isSubmitted() && ! $this->userIsOrgAdminForIntake($request, $animalIntake)) {
            abort(403, __('Submitted intakes can only be edited by an org admin.'));
        }

        $validated = $this->validateIntakeSession($request, $animalIntake);
        $facilityId = (int) $validated['facility_id'];
        if (! $this->userFacilityIds($request)->contains($facilityId)) {
            abort(404);
        }

        $wasDraft = $animalIntake->isDraft();
        $isDraft = $request->boolean('is_draft');
        $skippedCount = 0;
        $financeWarning = null;

        try {
            DB::transaction(function () use ($request, $animalIntake, $validated, $isDraft, $wasDraft, &$skippedCount): void {
                $data = $this->buildIntakeHeaderData($request, $validated, $isDraft, $animalIntake);
                $animalIntake->update($data);

                $keptIds = [];
                foreach ($validated['animals'] as $animal) {
                    $itemData = $this->mapAnimalItemAttributes($animal);
                    if (! empty($animal['id'])) {
                        $item = $animalIntake->items()->whereKey($animal['id'])->first();
                        if ($item) {
                            $item->update($itemData);
                            $keptIds[] = (int) $item->id;
                        }

                        continue;
                    }

                    $created = $animalIntake->items()->create($itemData);
                    $keptIds[] = (int) $created->id;
                }

                foreach ($animalIntake->items()->whereNotIn('id', $keptIds)->get() as $orphan) {
                    if ($orphan->isAssignedToPlan()) {
                        $skippedCount++;

                        continue;
                    }
                    $orphan->delete();
                }

                $this->syncLegacyIntakeColumns($animalIntake, $validated['animals']);
                $animalIntake->refresh()->load('items');

                if ($wasDraft && ! $isDraft) {
                    $this->dispatchIntakeSubmitted($animalIntake);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Animal intake update failed', ['intake_id' => $animalIntake->id, 'exception' => $e]);

            return back()->withInput()->withErrors([
                'form' => __('Could not update intake. Please try again.'),
            ]);
        }

        if (! $isDraft) {
            $financeWarning = $this->syncFinanceSafe($animalIntake->fresh(['items', 'facility']));
        }

        $redirect = redirect()
            ->route('animal-intakes.hub')
            ->with('status', __('Intake :reference updated — :count animals recorded.', [
                'reference' => $animalIntake->reference,
                'count' => count($validated['animals']),
            ]));

        if ($skippedCount > 0) {
            $redirect->with(
                'warning',
                __(':count animal(s) could not be removed because they are already assigned to a slaughter plan.', ['count' => $skippedCount]),
            );
        }

        if ($financeWarning) {
            $redirect->with('warning', $financeWarning);
        }

        return $redirect;
    }

    // --- Section 3: submitDraft ---

    public function submitDraft(Request $request, AnimalIntake $animalIntake): RedirectResponse
    {
        $this->authorizeIntake($request, $animalIntake);

        if (! $animalIntake->isDraft()) {
            abort(422, __('Only draft intakes can be submitted.'));
        }

        if ($animalIntake->items()->count() === 0) {
            abort(422, __('Add at least one animal before submitting.'));
        }

        try {
            DB::transaction(function () use ($animalIntake): void {
                $animalIntake->update([
                    'is_draft' => false,
                    'submitted_at' => now(),
                    'status' => AnimalIntake::STATUS_APPROVED,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Animal intake submit failed', ['intake_id' => $animalIntake->id, 'exception' => $e]);

            return back()->withErrors([
                'form' => __('Could not submit intake. Please try again.'),
            ]);
        }

        $animalIntake->refresh()->load('items');
        $this->dispatchIntakeSubmitted($animalIntake);
        $financeWarning = $this->syncFinanceSafe($animalIntake);

        $redirect = redirect()
            ->route('animal-intakes.hub')
            ->with('status', __('Intake :reference submitted — :count animals.', [
                'reference' => $animalIntake->reference,
                'count' => $animalIntake->items->count(),
            ]));

        if ($financeWarning) {
            $redirect->with('warning', $financeWarning);
        }

        return $redirect;
    }

    // --- Section 3: destroy ---

    public function destroy(Request $request, AnimalIntake $animalIntake): RedirectResponse
    {
        $this->authorizeIntake($request, $animalIntake);

        $assignedCount = $animalIntake->items()
            ->whereNotNull('slaughter_plan_id')
            ->count();

        if ($assignedCount > 0) {
            abort(422, __('This intake cannot be deleted — some animals are assigned to a slaughter plan.'));
        }

        $animalIntake->delete();

        return redirect()
            ->route('animal-intakes.hub')
            ->with('status', __('Animal intake deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function intakeLocalTimezone(): string
    {
        return (string) config('app.display_timezone', 'Africa/Kigali');
    }

    private function validateIntakeSession(Request $request, ?AnimalIntake $intake = null): array
    {
        $isLegacySupplier = $intake?->isSupplierSource() ?? false;

        if (! $isLegacySupplier) {
            $request->merge([
                'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
                'supplier_id' => null,
                'contract_id' => null,
            ]);
        }

        $businessIds = $request->user()->accessibleBusinessIds();
        $facilityId = (int) $request->input('facility_id');
        $businessId = $facilityId > 0
            ? (int) Facility::query()->whereKey($facilityId)->value('business_id')
            : 0;
        $allowedSpecies = $request->user()?->configuredSpeciesNames($businessId > 0 ? [$businessId] : null)->all() ?? [];
        if ($allowedSpecies === []) {
            $allowedSpecies = AnimalIntake::SPECIES_OPTIONS;
        }

        $earTagUnique = $intake
            ? Rule::unique('animal_intake_items', 'ear_tag')->where(
                fn ($query) => $query->where('animal_intake_id', '!=', $intake->id),
            )
            : Rule::unique('animal_intake_items', 'ear_tag');

        $itemIdRule = $intake
            ? ['nullable', 'integer', Rule::exists('animal_intake_items', 'id')->where('animal_intake_id', $intake->id)]
            : ['nullable', 'integer'];

        $validated = $request->validate(
            [
                'facility_id' => [
                    'required',
                    Rule::exists('facilities', 'id')->where(
                        fn ($query) => $query->whereIn('business_id', $businessIds),
                    ),
                ],
                'source_type' => [
                    'required',
                    Rule::in($isLegacySupplier ? [AnimalIntake::SOURCE_TYPE_SUPPLIER] : AnimalIntake::SOURCE_TYPES),
                ],
                'supplier_id' => $isLegacySupplier
                    ? [
                        'required',
                        Rule::exists('suppliers', 'id')->where('supplier_status', Supplier::STATUS_APPROVED),
                    ]
                    : ['prohibited'],
                'client_id' => [
                    'nullable',
                    'prohibited_if:source_type,'.AnimalIntake::SOURCE_TYPE_SUPPLIER,
                    Rule::exists('clients', 'id')->where('is_active', true),
                ],
                'farm_name' => ['nullable', 'string', 'max:255'],
                'farm_registration_number' => ['nullable', 'string', 'max:100'],
                'country_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
                'province_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
                'district_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
                'sector_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
                'cell_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
                'village_id' => ['nullable', 'integer', 'exists:administrative_divisions,id'],
                'intake_date' => [
                    'required',
                    'date',
                    function (string $attribute, mixed $value, \Closure $fail): void {
                        $intakeAt = Carbon::parse((string) $value, $this->intakeLocalTimezone());
                        if ($intakeAt->gt(now($this->intakeLocalTimezone()))) {
                            $fail(__('The intake date cannot be in the future.'));
                        }
                    },
                ],
                'vehicle_plate' => ['nullable', 'string', 'max:50'],
                'driver_name' => ['nullable', 'string', 'max:100'],
                'health_certificate_number' => ['nullable', 'string', 'max:100'],
                'health_certificate_issue_date' => ['nullable', 'date'],
                'health_certificate_expiry_date' => ['nullable', 'date', 'after:health_certificate_issue_date'],
                'movement_permit_number' => ['nullable', 'string', 'max:100'],
                'movement_permit_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
                'contract_id' => [
                    'nullable',
                    'prohibited_unless:source_type,'.AnimalIntake::SOURCE_TYPE_SUPPLIER,
                    'exists:contracts,id',
                ],
                'manual_client_firstname' => ['nullable', 'string', 'max:255'],
                'manual_client_lastname' => ['nullable', 'string', 'max:255'],
                'manual_client_contact' => ['nullable', 'string', 'max:100'],
                'is_draft' => ['sometimes', 'boolean'],
                'animals' => ['required', 'array', 'min:1'],
                'animals.*.id' => $itemIdRule,
                'animals.*.ear_tag' => ['required', 'string', 'max:100', 'distinct', $earTagUnique],
                'animals.*.species' => ['required', 'string', 'max:50', Rule::in($allowedSpecies)],
                'animals.*.sex' => ['required', Rule::in([AnimalIntake::SEX_MALE, AnimalIntake::SEX_FEMALE])],
                'animals.*.age_months' => ['nullable', 'integer', 'min:1', 'max:600'],
                'animals.*.live_weight_kg' => ['nullable', 'numeric', 'min:0.1', 'max:9999'],
                'animals.*.body_condition_score' => ['nullable', Rule::in(AnimalIntakeItem::BODY_CONDITIONS)],
                'animals.*.unit_price' => ['nullable', 'numeric', 'min:0'],
                'animals.*.health_status' => ['required', Rule::in(AnimalIntakeItem::HEALTH_STATUSES)],
                'animals.*.notes' => ['nullable', 'string', 'max:1000'],
            ],
            [
                'animals.*.ear_tag.unique' => __('Ear tag :input is already registered in the system.'),
                'animals.*.ear_tag.distinct' => __('Each ear tag must be unique within this intake.'),
            ],
        );

        if (! $isLegacySupplier) {
            $hasClient = ! empty($validated['client_id']);
            $hasManual = filled($validated['manual_client_firstname'] ?? null)
                && filled($validated['manual_client_lastname'] ?? null);
            if (! $hasClient && ! $hasManual) {
                throw ValidationException::withMessages([
                    'client_id' => __('Select a client from the list or enter client first and last name manually.'),
                ]);
            }
        } elseif (empty($validated['supplier_id'])) {
            throw ValidationException::withMessages([
                'supplier_id' => __('Select a supplier.'),
            ]);
        }

        $validated['intake_date'] = Carbon::parse(
            (string) $validated['intake_date'],
            $this->intakeLocalTimezone(),
        );

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildIntakeHeaderData(
        Request $request,
        array $validated,
        bool $isDraft,
        ?AnimalIntake $existing,
    ): array {
        $data = array_merge($validated, [
            'transport_vehicle_plate' => $validated['vehicle_plate'] ?? null,
            'animal_health_certificate_number' => $validated['health_certificate_number'] ?? null,
            'movement_permit_no' => $validated['movement_permit_number'] ?? null,
            'status' => $isDraft ? AnimalIntake::STATUS_RECEIVED : AnimalIntake::STATUS_APPROVED,
            'is_draft' => $isDraft,
            'submitted_at' => $isDraft ? null : now(),
        ]);

        $uploadedFile = $request->file('movement_permit_document');
        $data = $this->hydrateIntakeSourceData($request, $data);

        if (($data['source_type'] ?? null) === AnimalIntake::SOURCE_TYPE_CLIENT) {
            if ($uploadedFile) {
                if ($existing?->movement_permit_document_path) {
                    AnimalIntakeMovementPermitStorage::delete($existing->movement_permit_document_path);
                }
                $data['movement_permit_document_path'] = AnimalIntakeMovementPermitStorage::store($uploadedFile);
            } elseif (! $existing) {
                $data['movement_permit_document_path'] = null;
            }
        } elseif ($existing?->movement_permit_document_path) {
            AnimalIntakeMovementPermitStorage::delete($existing->movement_permit_document_path);
            $data['movement_permit_document_path'] = null;
        } elseif (! $existing) {
            $data['movement_permit_document_path'] = null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $animal
     * @return array<string, mixed>
     */
    private function mapAnimalItemAttributes(array $animal): array
    {
        return [
            'ear_tag' => $animal['ear_tag'],
            'species' => $animal['species'],
            'sex' => $animal['sex'],
            'age_months' => $animal['age_months'] ?? null,
            'live_weight_kg' => $animal['live_weight_kg'] ?? null,
            'body_condition_score' => $animal['body_condition_score'] ?? null,
            'unit_price' => $animal['unit_price'] ?? 0,
            'health_status' => $animal['health_status'],
            'notes' => $animal['notes'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $animals
     */
    private function syncLegacyIntakeColumns(AnimalIntake $intake, array $animals): void
    {
        $count = count($animals);
        $totalPrice = round(collect($animals)->sum(fn (array $a) => (float) ($a['unit_price'] ?? 0)), 2);

        $intake->update([
            'number_of_animals' => $count,
            'total_price' => $totalPrice,
            'species' => $this->resolveMostCommonSpecies($animals),
            'unit_price' => $count > 0 ? round($totalPrice / $count, 2) : null,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $animals
     */
    private function resolveMostCommonSpecies(array $animals): ?string
    {
        if ($animals === []) {
            return null;
        }

        $grouped = collect($animals)
            ->countBy(fn (array $animal) => (string) $animal['species'])
            ->sortDesc();

        return (string) $grouped->keys()->first();
    }

    private function syncFinanceSafe(AnimalIntake $intake): ?string
    {
        try {
            ProcessorFinanceSync::syncIntakePayable($intake);

            return null;
        } catch (\Throwable $e) {
            Log::warning('Animal intake finance sync failed', [
                'intake_id' => $intake->id,
                'exception' => $e,
            ]);

            return __('Intake saved but finance sync failed — please check the finance module.');
        }
    }

    private function dispatchIntakeSubmitted(AnimalIntake $intake): void
    {
        $eventClass = 'App\\Events\\IntakeSubmitted';
        if (class_exists($eventClass)) {
            event(new $eventClass($intake));
        }
    }

    private function userIsOrgAdminForIntake(Request $request, AnimalIntake $intake): bool
    {
        $intake->loadMissing('facility');
        $businessId = (int) $intake->facility?->business_id;

        return $request->user()->processorRoleForBusiness($businessId) === BusinessUser::ROLE_ORG_ADMIN;
    }
}
