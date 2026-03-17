<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Business;
use App\Models\BusinessOwnershipMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function index(Request $request): View
    {
        $businesses = $request->user()
            ->businesses()
            ->withCount('facilities')
            ->latest()
            ->paginate(10);

        $kpis = [
            'total' => $request->user()->businesses()->count(),
            'facilities' => \App\Models\Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())->count(),
        ];

        return view('businesses.index', compact('businesses', 'kpis'));
    }

    public function create(): View
    {
        return view('businesses.create');
    }

    public function store(StoreBusinessRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $members = $validated['members'] ?? [];
        unset($validated['members']);

        $business = $request->user()->businesses()->create($validated);

        foreach (array_values($members) as $i => $m) {
            $firstName = trim((string) ($m['first_name'] ?? ''));
            $lastName = trim((string) ($m['last_name'] ?? ''));
            if ($firstName !== '' || $lastName !== '') {
                $business->ownershipMembers()->create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $m['date_of_birth'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        return redirect()->route('businesses.index')
            ->with('status', __('Business registered successfully.'));
    }

    public function show(Request $request, Business $business): View
    {
        $userId = (int) $request->user()->id;
        $businessUserId = (int) $business->user_id;
        if ($businessUserId !== $userId) {
            abort(404, 'This business does not belong to your account. Business user_id=' . ($business->user_id ?? 'null') . ', your user_id=' . $userId . '. Fix in DB: UPDATE businesses SET user_id=' . $userId . ' WHERE id=' . $business->id . ';');
        }

        $business->load(['facilities', 'ownershipMembers', 'countryDivision', 'provinceDivision', 'districtDivision', 'sectorDivision', 'cellDivision', 'villageDivision']);

        return view('businesses.show', compact('business'));
    }

    public function edit(Request $request, Business $business): View
    {
        if ((int) $business->user_id !== (int) $request->user()->id) {
            abort(404, 'This business does not belong to your account.');
        }

        $business->load('ownershipMembers');

        return view('businesses.edit', compact('business'));
    }

    public function update(UpdateBusinessRequest $request, Business $business): RedirectResponse
    {
        if ((int) $business->user_id !== (int) $request->user()->id) {
            abort(404, 'This business does not belong to your account.');
        }

        $validated = $request->validated();
        $members = $validated['members'] ?? [];
        unset($validated['members']);

        $business->update($validated);

        $business->ownershipMembers()->delete();
        foreach (array_values($members) as $i => $m) {
            $firstName = trim((string) ($m['first_name'] ?? ''));
            $lastName = trim((string) ($m['last_name'] ?? ''));
            if ($firstName !== '' || $lastName !== '') {
                $business->ownershipMembers()->create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $m['date_of_birth'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        return redirect()->route('businesses.index')
            ->with('status', __('Business updated successfully.'));
    }

    public function destroy(Request $request, Business $business): RedirectResponse
    {
        if ((int) $business->user_id !== (int) $request->user()->id) {
            abort(404, 'This business does not belong to your account.');
        }

        $business->delete();

        return redirect()->route('businesses.index')
            ->with('status', __('Business removed.'));
    }
}
