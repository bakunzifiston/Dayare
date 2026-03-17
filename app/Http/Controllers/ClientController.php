<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientActivityRequest;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\Client;
use App\Models\ClientActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    private function userBusinessIds(Request $request): \Illuminate\Support\Collection
    {
        return $request->user()->accessibleBusinessIds();
    }

    private function authorizeClient(Request $request, Client $client): void
    {
        if (! $this->userBusinessIds($request)->contains($client->business_id)) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $businessIds = $this->userBusinessIds($request);
        $clients = Client::with('business')
            ->whereIn('business_id', $businessIds)
            ->orderBy('name')
            ->paginate(10);

        $baseQuery = Client::whereIn('business_id', $businessIds);
        $kpis = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
        ];

        return view('clients.index', compact('clients', 'kpis'));
    }

    public function create(Request $request): View
    {
        $businessIds = $this->userBusinessIds($request);
        $businesses = Business::whereIn('id', $businessIds)->orderBy('business_name')->get();
        $facilities = \App\Models\Facility::whereIn('business_id', $businessIds)->orderBy('facility_name')->get();

        return view('clients.create', compact('businesses', 'facilities'));
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        if (! $this->userBusinessIds($request)->contains((int) $request->validated('business_id'))) {
            abort(404);
        }

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        if (! empty($data['country_id'])) {
            $data['country'] = AdministrativeDivision::find($data['country_id'])?->name ?? $data['country'] ?? null;
        }

        Client::create($data);

        return redirect()->route('clients.index')->with('status', __('Client created successfully.'));
    }

    public function show(Request $request, Client $client): View
    {
        $this->authorizeClient($request, $client);
        $client->load([
            'business',
            'preferredFacility',
            'countryDivision',
            'province',
            'districtDivision',
            'sectorDivision',
            'cell',
            'village',
            'deliveryConfirmations' => fn ($q) => $q->with('transportTrip')->latest('received_date')->limit(20),
            'demands' => fn ($q) => $q->latest('requested_delivery_date')->limit(20),
            'activities' => fn ($q) => $q->with('user')->latest('occurred_at')->limit(50),
        ]);

        return view('clients.show', compact('client'));
    }

    public function storeActivity(StoreClientActivityRequest $request, Client $client): RedirectResponse
    {
        $this->authorizeClient($request, $client);
        ClientActivity::create([
            'business_id' => $client->business_id,
            'client_id' => $client->id,
            'activity_type' => $request->validated('activity_type'),
            'subject' => $request->validated('subject'),
            'notes' => $request->validated('notes'),
            'occurred_at' => $request->validated('occurred_at'),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('clients.show', $client)->with('status', __('Activity logged.'));
    }

    public function edit(Request $request, Client $client): View
    {
        $this->authorizeClient($request, $client);
        $businessIds = $this->userBusinessIds($request);
        $businesses = Business::whereIn('id', $businessIds)->orderBy('business_name')->get();
        $facilities = \App\Models\Facility::whereIn('business_id', $businessIds)->orderBy('facility_name')->get();

        return view('clients.edit', compact('client', 'businesses', 'facilities'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorizeClient($request, $client);
        if (! $this->userBusinessIds($request)->contains((int) $request->validated('business_id'))) {
            abort(404);
        }

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        if (! empty($data['country_id'])) {
            $data['country'] = AdministrativeDivision::find($data['country_id'])?->name ?? $data['country'] ?? null;
        }

        $client->update($data);

        return redirect()->route('clients.show', $client)->with('status', __('Client updated successfully.'));
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorizeClient($request, $client);
        $client->delete();

        return redirect()->route('clients.index')->with('status', __('Client deleted.'));
    }
}
