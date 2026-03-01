<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\UpdateBusinessRequest;
use App\Models\Business;
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

        return view('businesses.index', compact('businesses'));
    }

    public function create(): View
    {
        return view('businesses.create');
    }

    public function store(StoreBusinessRequest $request): RedirectResponse
    {
        $request->user()->businesses()->create($request->validated());

        return redirect()->route('businesses.index')
            ->with('status', __('Business registered successfully.'));
    }

    public function show(Request $request, Business $business): View|RedirectResponse
    {
        if ($business->user_id !== $request->user()->id) {
            abort(404);
        }

        $business->load('facilities');

        return view('businesses.show', compact('business'));
    }

    public function edit(Request $request, Business $business): View|RedirectResponse
    {
        if ($business->user_id !== $request->user()->id) {
            abort(404);
        }

        return view('businesses.edit', compact('business'));
    }

    public function update(UpdateBusinessRequest $request, Business $business): RedirectResponse
    {
        if ($business->user_id !== $request->user()->id) {
            abort(404);
        }

        $business->update($request->validated());

        return redirect()->route('businesses.index')
            ->with('status', __('Business updated successfully.'));
    }

    public function destroy(Request $request, Business $business): RedirectResponse
    {
        if ($business->user_id !== $request->user()->id) {
            abort(404);
        }

        $business->delete();

        return redirect()->route('businesses.index')
            ->with('status', __('Business removed.'));
    }
}
