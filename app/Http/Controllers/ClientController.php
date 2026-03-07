<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Business;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    private function userBusinessIds(Request $request): \Illuminate\Support\Collection
    {
        return $request->user()->businesses()->pluck('id');
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

        return view('clients.index', compact('clients'));
    }

    public function create(Request $request): View
    {
        $businesses = Business::whereIn('id', $this->userBusinessIds($request))
            ->orderBy('business_name')
            ->get();

        return view('clients.create', compact('businesses'));
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        if (! $this->userBusinessIds($request)->contains((int) $request->validated('business_id'))) {
            abort(404);
        }

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        Client::create($data);

        return redirect()->route('clients.index')->with('status', __('Client created successfully.'));
    }

    public function show(Request $request, Client $client): View
    {
        $this->authorizeClient($request, $client);
        $client->load(['business', 'deliveryConfirmations' => fn ($q) => $q->with('transportTrip')->latest('received_date')->limit(20)]);

        return view('clients.show', compact('client'));
    }

    public function edit(Request $request, Client $client): View
    {
        $this->authorizeClient($request, $client);
        $businesses = Business::whereIn('id', $this->userBusinessIds($request))
            ->orderBy('business_name')
            ->get();

        return view('clients.edit', compact('client', 'businesses'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorizeClient($request, $client);
        if (! $this->userBusinessIds($request)->contains((int) $request->validated('business_id'))) {
            abort(404);
        }

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

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
