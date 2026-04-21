<?php

namespace App\Http\Controllers;

use App\Models\ColdRoom;
use App\Models\ColdRoomStandard;
use App\Models\Facility;
use App\Models\WarehouseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ColdRoomController extends Controller
{
    private function userStorageFacilityIds(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->where('facility_type', Facility::TYPE_STORAGE)
            ->pluck('id');
    }

    private function authorizeWarehouse(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }
        abort_unless($user->canProcessorPermission('monitor_temperature_logs'), 403);
    }

    public function hub(Request $request): View
    {
        $this->authorizeWarehouse($request);

        $facilityIds = $this->userStorageFacilityIds($request);
        $roomCount = ColdRoom::query()->whereIn('facility_id', $facilityIds)->count();
        $standardCount = ColdRoomStandard::query()->count();
        $certificateIds = WarehouseStorage::accessibleCertificateIds($request);
        $storageCount = WarehouseStorage::query()->whereIn('certificate_id', $certificateIds)->count();

        return view('cold-rooms.hub', compact('roomCount', 'standardCount', 'storageCount'));
    }

    public function index(Request $request): View
    {
        $this->authorizeWarehouse($request);

        $facilityIds = $this->userStorageFacilityIds($request);

        $coldRooms = ColdRoom::query()
            ->whereIn('facility_id', $facilityIds)
            ->with(['facility', 'standard'])
            ->orderBy('facility_id')
            ->orderBy('name')
            ->paginate(20);

        return view('cold-rooms.index', compact('coldRooms'));
    }

    public function create(Request $request): View
    {
        $this->authorizeWarehouse($request);

        $facilities = $this->storageFacilitiesForForm($request);
        $standards = ColdRoomStandard::orderBy('name')->get();

        return view('cold-rooms.create', compact('facilities', 'standards'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeWarehouse($request);

        $facilityIds = $this->userStorageFacilityIds($request)->all();

        $valid = $request->validate([
            'facility_id' => ['required', 'integer', Rule::in($facilityIds)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in([ColdRoom::TYPE_CHILLER, ColdRoom::TYPE_FREEZER])],
            'capacity' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'standard_id' => ['nullable', 'integer', 'exists:cold_room_standards,id'],
        ]);

        ColdRoom::create($valid);

        return redirect()->route('cold-rooms.manage.index')
            ->with('status', __('Cold room saved. Assign it when recording storage to enable temperature monitoring.'));
    }

    public function edit(Request $request, ColdRoom $cold_room): View
    {
        $this->authorizeWarehouse($request);

        $this->assertRoomAccessible($request, $cold_room);

        $facilities = $this->storageFacilitiesForForm($request);
        $standards = ColdRoomStandard::orderBy('name')->get();

        return view('cold-rooms.edit', ['room' => $cold_room, 'facilities' => $facilities, 'standards' => $standards]);
    }

    public function update(Request $request, ColdRoom $cold_room): RedirectResponse
    {
        $this->authorizeWarehouse($request);

        $this->assertRoomAccessible($request, $cold_room);

        $facilityIds = $this->userStorageFacilityIds($request)->all();

        $valid = $request->validate([
            'facility_id' => ['required', 'integer', Rule::in($facilityIds)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in([ColdRoom::TYPE_CHILLER, ColdRoom::TYPE_FREEZER])],
            'capacity' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'standard_id' => ['nullable', 'integer', 'exists:cold_room_standards,id'],
        ]);

        $cold_room->update($valid);

        return redirect()->route('cold-rooms.manage.index')
            ->with('status', __('Cold room updated.'));
    }

    public function destroy(Request $request, ColdRoom $cold_room): RedirectResponse
    {
        $this->authorizeWarehouse($request);

        $this->assertRoomAccessible($request, $cold_room);

        $cold_room->delete();

        return redirect()->route('cold-rooms.manage.index')
            ->with('status', __('Cold room removed.'));
    }

    private function assertRoomAccessible(Request $request, ColdRoom $cold_room): void
    {
        $ids = $this->userStorageFacilityIds($request);
        abort_unless($ids->contains($cold_room->facility_id), 404);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Facility>
     */
    private function storageFacilitiesForForm(Request $request): \Illuminate\Support\Collection
    {
        return Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->where('facility_type', Facility::TYPE_STORAGE)
            ->orderBy('facility_name')
            ->get();
    }
}
