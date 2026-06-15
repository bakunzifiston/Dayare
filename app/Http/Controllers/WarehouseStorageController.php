<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseStorageRequest;
use App\Http\Requests\UpdateWarehouseStorageRequest;
use App\Models\ColdRoom;
use App\Models\Facility;
use App\Models\TemperatureLog;
use App\Models\WarehouseStorage;
use App\Services\ColdRoomMonitoringService;
use App\Support\StorablePostMortemMeat;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarehouseStorageController extends Controller
{
    private function userFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');
    }

    private function userStorageFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->where('facility_type', Facility::TYPE_STORAGE)
            ->pluck('id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, label: string}>
     */
    private function coldRoomOptionsForUser(Request $request): \Illuminate\Support\Collection
    {
        $storageFacilityIds = Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->where('facility_type', Facility::TYPE_STORAGE)
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

    private function authorizeStorage(Request $request, WarehouseStorage $storage): void
    {
        if (! WarehouseStorage::isAccessibleBy($request, $storage)) {
            abort(404);
        }
    }

    private function scopedStorageQuery(Request $request)
    {
        $query = WarehouseStorage::query()->forColdRoomUser($request);

        if ($request->filled('cold_room_id')) {
            $coldRoomId = $request->integer('cold_room_id');
            $allowedRoomIds = ColdRoom::query()
                ->whereIn('facility_id', $this->userStorageFacilityIds($request))
                ->pluck('id');

            if ($allowedRoomIds->contains($coldRoomId)) {
                $query->where('cold_room_id', $coldRoomId);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return $query;
    }

    public function index(Request $request): View
    {
        $storages = $this->scopedStorageQuery($request)
            ->with(['warehouseFacility', 'batch', 'certificate', 'intakeItem', 'coldRoom'])
            ->latest('entry_date')
            ->paginate(10)
            ->withQueryString();

        $baseQuery = $this->scopedStorageQuery($request);
        $kpis = [
            'total' => (clone $baseQuery)->count(),
            'in_storage' => (clone $baseQuery)->where('status', WarehouseStorage::STATUS_IN_STORAGE)->count(),
            'released' => (clone $baseQuery)->where('status', WarehouseStorage::STATUS_RELEASED)->count(),
        ];

        $filterColdRoom = null;
        if ($request->filled('cold_room_id')) {
            $filterColdRoom = ColdRoom::query()
                ->whereIn('facility_id', $this->userStorageFacilityIds($request))
                ->find($request->integer('cold_room_id'));
        }

        return view('warehouse-storages.index', compact('storages', 'kpis', 'filterColdRoom'));
    }

    public function create(Request $request): View
    {
        $facilityIds = $this->userFacilityIds($request);

        $warehouseFacilities = Facility::whereIn('id', $facilityIds)
            ->where('facility_type', Facility::TYPE_STORAGE)
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]);

        $storableMeat = StorablePostMortemMeat::optionsFor($request);

        $units = $request->user()->configuredUnitsForBusinessIds($request->user()->accessibleBusinessIds());

        $coldRoomsByFacility = ColdRoom::with('standard')
            ->whereIn('facility_id', $this->userStorageFacilityIds($request))
            ->get()
            ->groupBy('facility_id')
            ->map(fn ($rooms) => $rooms->map(fn (ColdRoom $r) => [
                'id' => $r->id,
                'name' => $r->name.' ('.ucfirst($r->type).')',
                'type' => $r->type,
            ])->values()->toArray());

        return view('warehouse-storages.create', compact('warehouseFacilities', 'storableMeat', 'units', 'coldRoomsByFacility'));
    }

    public function store(StoreWarehouseStorageRequest $request): RedirectResponse
    {
        $valid = $request->validated();
        $quantities = $valid['quantities'] ?? [];

        $pmItems = StorablePostMortemMeat::findStorableItems(
            $request,
            $valid['post_mortem_inspection_item_ids']
        );

        if ($pmItems->isEmpty()) {
            return back()
                ->withErrors(['post_mortem_inspection_item_ids' => __('Select at least one animal meat approved at post-mortem.')])
                ->withInput();
        }

        $shared = collect($valid)->except([
            'post_mortem_inspection_item_ids',
            'quantities',
        ])->all();

        $created = 0;

        DB::transaction(function () use ($pmItems, $shared, $quantities, &$created): void {
            foreach ($pmItems as $pmItem) {
                $batch = $pmItem->inspection?->batch;
                if (! $batch) {
                    continue;
                }

                $meatKg = StorablePostMortemMeat::meatKgForItem($pmItem);
                $quantity = $quantities[$pmItem->id] ?? null;
                $quantityStored = ($quantity !== null && $quantity !== '') ? (float) $quantity : $meatKg;

                if ($quantityStored <= 0) {
                    continue;
                }

                WarehouseStorage::create(array_merge($shared, [
                    'batch_id' => $batch->id,
                    'animal_intake_item_id' => $pmItem->animal_intake_item_id,
                    'post_mortem_inspection_item_id' => $pmItem->id,
                    'certificate_id' => $batch->certificate?->id,
                    'quantity_stored' => $quantityStored,
                    'status' => WarehouseStorage::STATUS_IN_STORAGE,
                ]));

                $created++;
            }
        });

        if ($created === 0) {
            return back()
                ->withErrors(['post_mortem_inspection_item_ids' => __('No storage records could be created.')])
                ->withInput();
        }

        $message = $created === 1
            ? __('Cold room storage recorded for 1 animal.')
            : __('Cold room storage recorded for :count animals.', ['count' => $created]);

        return redirect()->route('warehouse-storages.index')->with('status', $message);
    }

    public function show(Request $request, WarehouseStorage $warehouseStorage): View
    {
        $this->authorizeStorage($request, $warehouseStorage);
        $warehouseStorage->load([
            'warehouseFacility',
            'batch',
            'certificate',
            'intakeItem',
            'postMortemInspectionItem',
            'temperatureLogs',
            'coldRoom.standard',
        ]);

        return view('warehouse-storages.show', compact('warehouseStorage'));
    }

    public function edit(Request $request, WarehouseStorage $warehouseStorage): View
    {
        $this->authorizeStorage($request, $warehouseStorage);
        $facilityIds = $this->userFacilityIds($request);
        $warehouseFacilities = Facility::whereIn('id', $facilityIds)
            ->where('facility_type', Facility::TYPE_STORAGE)
            ->orderBy('facility_name')
            ->get()
            ->map(fn (Facility $f) => ['id' => $f->id, 'label' => $f->facility_name]);
        $units = $request->user()->configuredUnitsForBusinessIds($request->user()->accessibleBusinessIds());
        $coldRooms = $this->coldRoomOptionsForUser($request);

        return view('warehouse-storages.edit', compact('warehouseStorage', 'warehouseFacilities', 'units', 'coldRooms'));
    }

    public function update(UpdateWarehouseStorageRequest $request, WarehouseStorage $warehouseStorage): RedirectResponse
    {
        $this->authorizeStorage($request, $warehouseStorage);

        $valid = $request->validated();

        if (isset($valid['status']) && $valid['status'] === WarehouseStorage::STATUS_RELEASED && empty($valid['released_date'])) {
            $valid['released_date'] = now()->toDateString();
        }
        if (isset($valid['status']) && $valid['status'] !== WarehouseStorage::STATUS_RELEASED) {
            $valid['released_date'] = null;
        }

        $warehouseStorage->update($valid);

        return redirect()->route('warehouse-storages.show', $warehouseStorage)->with('status', __('Cold room storage updated.'));
    }

    public function destroy(Request $request, WarehouseStorage $warehouseStorage): RedirectResponse
    {
        $this->authorizeStorage($request, $warehouseStorage);

        if ($warehouseStorage->transportTrips()->exists()) {
            return back()->withErrors([
                'delete' => __('Cannot delete: this storage is linked to a transport trip.'),
            ]);
        }

        $warehouseStorage->delete();

        return redirect()->route('warehouse-storages.index')
            ->with('status', __('Cold room storage record removed.'));
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

        DB::transaction(function () use ($warehouseStorage, $valid): void {
            $valid['warehouse_storage_id'] = $warehouseStorage->id;
            TemperatureLog::create($valid);

            // --- Section 2 --- Bridge manual temperature log to cold room monitoring
            if ($warehouseStorage->cold_room_id) {
                $coldRoom = $warehouseStorage->coldRoom;
                if ($coldRoom) {
                    app(ColdRoomMonitoringService::class)->recordTemperature(
                        $coldRoom,
                        (float) $valid['recorded_temperature'],
                        Carbon::parse($valid['recorded_at'] ?? now())
                    );
                }
            }
        });

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
