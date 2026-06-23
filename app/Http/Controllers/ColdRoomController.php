<?php

namespace App\Http\Controllers;

use App\Models\ColdRoom;
use App\Models\ColdRoomStandard;
use App\Models\ColdRoomViolation;
use App\Models\Facility;
use App\Models\WarehouseStorage;
use Carbon\Carbon;
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
        $filters = $this->resolveHubFilters($request);

        $scopeStorages = function ($query) use ($facilityIds, $filters): void {
            $query->whereIn('warehouse_facility_id', $facilityIds);
            if ($filters['is_filtered']) {
                $query->whereDate('entry_date', '>=', $filters['start']->toDateString())
                    ->whereDate('entry_date', '<=', $filters['end']->toDateString());
            }
        };

        $scopeReleased = function ($query) use ($facilityIds, $filters): void {
            $query->whereIn('warehouse_facility_id', $facilityIds)
                ->where('status', WarehouseStorage::STATUS_RELEASED);
            if ($filters['is_filtered']) {
                $query->whereDate('released_date', '>=', $filters['start']->toDateString())
                    ->whereDate('released_date', '<=', $filters['end']->toDateString());
            }
        };

        $hubStats = [
            'total_rooms' => ColdRoom::whereIn('facility_id', $facilityIds)->count(),
            'released_label' => $filters['is_filtered'] ? __('Released in period') : __('Released'),
            'released_count' => WarehouseStorage::query()->where($scopeReleased)->count(),
            'storages_label' => $filters['is_filtered'] ? $filters['storages_label'] : __('In storage'),
            'storage_count' => $filters['is_filtered']
                ? WarehouseStorage::query()->where($scopeStorages)->count()
                : WarehouseStorage::whereHas('coldRoom',
                    fn ($q) => $q->whereIn('facility_id', $facilityIds)
                )->where('status', WarehouseStorage::STATUS_IN_STORAGE)->count(),
            'standards' => ColdRoomStandard::count(),
        ];

        $coldRooms = ColdRoom::whereIn('facility_id', $facilityIds)
            ->with([
                'standard',
                'facility',
                'violations' => fn ($q) => $q->where('status', ColdRoomViolation::STATUS_OPEN),
                'warehouseStorages' => fn ($q) => $q->where('status', WarehouseStorage::STATUS_IN_STORAGE),
            ])
            ->orderBy('facility_id')
            ->orderBy('name')
            ->get();

        $openViolations = ColdRoomViolation::whereHas('coldRoom',
            fn ($q) => $q->whereIn('facility_id', $facilityIds)
        )->where('status', ColdRoomViolation::STATUS_OPEN)
            ->with(['coldRoom.facility'])
            ->orderByDesc('start_time')
            ->get();

        $storageRecords = WarehouseStorage::query()
            ->where($scopeStorages)
            ->with([
                'warehouseFacility',
                'batch',
                'certificate',
                'intakeItem',
                'postMortemInspectionItem.intakeItem',
                'coldRoom',
            ])
            ->latest('entry_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('cold-rooms.hub', compact(
            'hubStats',
            'coldRooms',
            'openViolations',
            'storageRecords',
            'filters',
        ));
    }

    public function index(Request $request): RedirectResponse
    {
        $this->authorizeWarehouse($request);

        return redirect()->route('cold-rooms.hub', $request->query());
    }

    /**
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     start: null,
     *     end: null,
     *     range_label: string,
     *     storages_label: string,
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
            'storages_label' => __('Storage records'),
            'has_custom_range' => false,
            'is_filtered' => false,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, date_from: string, date_to: string, range_label: string, storages_label: string}
     */
    private function presetRangeForPeriod(string $period): array
    {
        $now = now();

        [$start, $end, $rangeLabel, $storagesLabel] = match ($period) {
            'day' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->format('M j, Y'),
                __('Storages today'),
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                (string) $now->year,
                __('Storages this year'),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                $now->format('F Y'),
                __('Storages this month'),
            ],
            default => throw new \InvalidArgumentException('Invalid preset period.'),
        };

        return [
            'start' => $start,
            'end' => $end,
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'range_label' => $rangeLabel,
            'storages_label' => $storagesLabel,
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
     *     storages_label: string,
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
                'storages_label' => __('Storages in range'),
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
                'storages_label' => $preset['storages_label'],
                'has_custom_range' => false,
                'is_filtered' => true,
            ];
        }

        return $this->hubFiltersAllTime();
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

        return redirect()->route('cold-rooms.hub')
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

        return redirect()->route('cold-rooms.hub')
            ->with('status', __('Cold room updated.'));
    }

    public function destroy(Request $request, ColdRoom $cold_room): RedirectResponse
    {
        $this->authorizeWarehouse($request);

        $this->assertRoomAccessible($request, $cold_room);

        $cold_room->delete();

        return redirect()->route('cold-rooms.hub')
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
