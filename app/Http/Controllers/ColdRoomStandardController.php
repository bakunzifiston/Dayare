<?php

namespace App\Http\Controllers;

use App\Models\ColdRoomStandard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ColdRoomStandardController extends Controller
{
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

    public function index(Request $request): View
    {
        $this->authorizeWarehouse($request);

        $standards = ColdRoomStandard::query()
            ->withCount('coldRooms')
            ->orderBy('name')
            ->paginate(15);

        return view('cold-room-standards.index', compact('standards'));
    }

    public function create(Request $request): View
    {
        $this->authorizeWarehouse($request);

        return view('cold-room-standards.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeWarehouse($request);

        $valid = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in([ColdRoomStandard::TYPE_CHILLER, ColdRoomStandard::TYPE_FREEZER])],
            'min_temperature' => ['required', 'numeric', 'between:-80,40'],
            'max_temperature' => ['required', 'numeric', 'between:-80,40', 'gte:min_temperature'],
            'tolerance_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
        ]);

        ColdRoomStandard::create($valid);

        return redirect()->route('cold-room-standards.index')
            ->with('status', __('Cold room standard saved.'));
    }

    public function edit(Request $request, ColdRoomStandard $cold_room_standard): View
    {
        $this->authorizeWarehouse($request);

        return view('cold-room-standards.edit', ['standard' => $cold_room_standard]);
    }

    public function update(Request $request, ColdRoomStandard $cold_room_standard): RedirectResponse
    {
        $this->authorizeWarehouse($request);

        $valid = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in([ColdRoomStandard::TYPE_CHILLER, ColdRoomStandard::TYPE_FREEZER])],
            'min_temperature' => ['required', 'numeric', 'between:-80,40'],
            'max_temperature' => ['required', 'numeric', 'between:-80,40', 'gte:min_temperature'],
            'tolerance_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
        ]);

        $cold_room_standard->update($valid);

        return redirect()->route('cold-room-standards.index')
            ->with('status', __('Cold room standard updated.'));
    }

    public function destroy(Request $request, ColdRoomStandard $cold_room_standard): RedirectResponse
    {
        $this->authorizeWarehouse($request);

        $cold_room_standard->delete();

        return redirect()->route('cold-room-standards.index')
            ->with('status', __('Cold room standard removed.'));
    }
}
