<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\ColdRoom;
use App\Models\Demand;
use App\Models\Facility;
use App\Models\TemperatureLog;
use App\Models\WarehouseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseStorageController extends Controller
{
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, label: string}>
     */
    private function coldRoomOptionsForUser(Request $request): \Illuminate\Support\Collection
    {
        $storageFacilityIds = Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->where('facility_type', Facility::TYPE_COLD_ROOM)
            ->pluck('id');

        return ColdRoom::query()
            ->whereIn('facility_id', $storageFacilityIds)
            ->with('facility')
            ->orderBy('facility_id')
            ->orderBy('name')
            ->get()
            ->map(fn (ColdRoom $r) => [
                'id' => $r->id,
                'label' => ($r->facility->facility_name ?? '').' — '.$r->name,
            ]);
    }

    private function userCertificateIds(Request $request): \Illuminate\Support\Collection
    {
        return WarehouseStorage::accessibleCertificateIds($request);
    }

    private function authorizeStorage(Request $request, WarehouseStorage $storage): void
    {
        if (! $this->userCertificateIds($request)->contains($storage->certificate_id)) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $certificateIds = $this->userCertificateIds($request);
        $storages = WarehouseStorage::with(['warehouseFacility', 'batch', 'certificate'])
            ->whereIn('certificate_id', $certificateIds)
            ->latest('entry_date')
            ->paginate(10);

        $kpis = [
            'total' => WarehouseStorage::whereIn('certificate_id', $certificateIds)->count(),
            'in_storage' => WarehouseStorage::whereIn('certificate_id', $certificateIds)->where('status', WarehouseStorage::STATUS_IN_STORAGE)->count(),
            'released' => WarehouseStorage::whereIn('certificate_id', $certificateIds)->where('status', WarehouseStorage::STATUS_RELEASED)->count(),
        ];

        return view('warehouse-storages.index', compact('storages', 'kpis'));
    }

    public function create(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);
        $certificateIds = $this->userCertificateIds($request);

        $warehouseFacilities = Facility::whereIn('id', $facilityIds)
            ->where('facility_type', Facility::TYPE_COLD_ROOM)
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]);

        // Certificates that are active, have batch, and batch has no current in_storage
        $certificates = Certificate::with(['batch', 'facility'])
            ->whereIn('id', $certificateIds)
            ->where('status', Certificate::STATUS_ACTIVE)
            ->whereNotNull('batch_id')
            ->whereDoesntHave('warehouseStorages', fn ($q) => $q->where('status', WarehouseStorage::STATUS_IN_STORAGE))
            ->latest('issued_at')
            ->get()
            ->map(fn (Certificate $c) => [
                'id' => $c->id,
                'label' => ($c->certificate_number ?: '#'.$c->id).' — '.($c->batch?->batch_code ?? '—').' ('.($c->batch?->quantity ?? 0).' '.__('carcasses').')',
                'batch_id' => $c->batch_id,
            ]);

        $units = $request->user()->configuredUnitsForBusinessIds($request->user()->accessibleBusinessIds());
        $coldRooms = $this->coldRoomOptionsForUser($request);

        return view('warehouse-storages.create', compact('warehouseFacilities', 'certificates', 'units', 'coldRooms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $facilityIds = $this->userFacilityIds($request);
        $certificateIds = $this->userCertificateIds($request);

        $allowedUnits = $request->user()->configuredUnitsForBusinessIds($request->user()->accessibleBusinessIds())->pluck('code')->all();
        $allowedUnits = empty($allowedUnits) ? array_keys(Demand::QUANTITY_UNITS) : array_values(array_unique(array_merge($allowedUnits, array_keys(Demand::QUANTITY_UNITS))));

        $valid = $request->validate([
            'warehouse_facility_id' => ['required', Rule::in($facilityIds->all())],
            'cold_room_id' => [
                'nullable',
                'integer',
                Rule::exists('cold_rooms', 'id')->where(
                    fn ($q) => $q->where('facility_id', (int) $request->input('warehouse_facility_id'))
                ),
            ],
            'certificate_id' => ['required', Rule::in($certificateIds->all())],
            'entry_date' => ['required', 'date'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'temperature_at_entry' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'quantity_stored' => ['required', 'integer', 'min:1'],
            'quantity_unit' => ['required', 'string', Rule::in($allowedUnits)],
        ]);

        $cert = Certificate::findOrFail($valid['certificate_id']);
        if ($cert->status !== Certificate::STATUS_ACTIVE) {
            return back()->withErrors(['certificate_id' => __('Cannot store: certificate must be active.')])->withInput();
        }
        if (WarehouseStorage::where('certificate_id', $cert->id)->where('status', WarehouseStorage::STATUS_IN_STORAGE)->exists()) {
            return back()->withErrors(['certificate_id' => __('This batch is already in storage.')])->withInput();
        }

        $valid['batch_id'] = $cert->batch_id;
        $valid['status'] = WarehouseStorage::STATUS_IN_STORAGE;

        WarehouseStorage::create($valid);

        return redirect()->route('warehouse-storages.index')->with('status', __('Cold room storage recorded.'));
    }

    public function show(Request $request, WarehouseStorage $warehouseStorage): View
    {
        $this->authorizeStorage($request, $warehouseStorage);
        $warehouseStorage->load(['warehouseFacility', 'batch', 'certificate', 'temperatureLogs', 'coldRoom.standard']);

        return view('warehouse-storages.show', compact('warehouseStorage'));
    }

    public function edit(Request $request, WarehouseStorage $warehouseStorage): View
    {
        $this->authorizeStorage($request, $warehouseStorage);
        $facilityIds = $this->userFacilityIds($request);
        $warehouseFacilities = Facility::whereIn('id', $facilityIds)
            ->where('facility_type', Facility::TYPE_COLD_ROOM)
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]);
        $units = $request->user()->configuredUnitsForBusinessIds($request->user()->accessibleBusinessIds());
        $coldRooms = $this->coldRoomOptionsForUser($request);

        return view('warehouse-storages.edit', compact('warehouseStorage', 'warehouseFacilities', 'units', 'coldRooms'));
    }

    public function update(Request $request, WarehouseStorage $warehouseStorage): RedirectResponse
    {
        $this->authorizeStorage($request, $warehouseStorage);
        $facilityIds = $this->userFacilityIds($request);

        $allowedUnits = $request->user()->configuredUnitsForBusinessIds($request->user()->accessibleBusinessIds())->pluck('code')->all();
        $allowedUnits = empty($allowedUnits) ? array_keys(Demand::QUANTITY_UNITS) : array_values(array_unique(array_merge($allowedUnits, array_keys(Demand::QUANTITY_UNITS))));

        $valid = $request->validate([
            'warehouse_facility_id' => ['required', Rule::in($facilityIds->all())],
            'cold_room_id' => [
                'nullable',
                'integer',
                Rule::exists('cold_rooms', 'id')->where(
                    fn ($q) => $q->where('facility_id', (int) $request->input('warehouse_facility_id'))
                ),
            ],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'temperature_at_entry' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'quantity_stored' => ['required', 'integer', 'min:0'],
            'quantity_unit' => ['required', 'string', Rule::in($allowedUnits)],
            'status' => ['required', Rule::in(WarehouseStorage::STATUSES)],
            'released_date' => ['nullable', 'required_if:status,released', 'date'],
        ]);

        if (isset($valid['status']) && $valid['status'] === WarehouseStorage::STATUS_RELEASED && empty($valid['released_date'])) {
            $valid['released_date'] = now()->toDateString();
        }
        if (isset($valid['status']) && $valid['status'] !== WarehouseStorage::STATUS_RELEASED) {
            $valid['released_date'] = null;
        }

        $warehouseStorage->update($valid);

        return redirect()->route('warehouse-storages.show', $warehouseStorage)->with('status', __('Cold room storage updated.'));
    }

    public function storeTemperatureLog(Request $request, WarehouseStorage $warehouseStorage): RedirectResponse
    {
        $this->authorizeStorage($request, $warehouseStorage);

        $valid = $request->validate([
            'recorded_temperature' => ['required', 'numeric', 'min:-50', 'max:50'],
            'recorded_at' => ['required', 'date'],
            'recorded_by' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(TemperatureLog::STATUSES)],
        ]);

        $valid['warehouse_storage_id'] = $warehouseStorage->id;
        TemperatureLog::create($valid);

        return back()->with('status', __('Temperature log added.'));
    }

    public function destroyTemperatureLog(Request $request, WarehouseStorage $warehouseStorage, TemperatureLog $temperatureLog): RedirectResponse
    {
        $this->authorizeStorage($request, $warehouseStorage);
        if ($temperatureLog->warehouse_storage_id !== $warehouseStorage->id) {
            abort(404);
        }
        $temperatureLog->delete();

        return back()->with('status', __('Temperature log removed.'));
    }
}
