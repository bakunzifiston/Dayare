<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Business;
use App\Models\Contract;
use App\Models\Facility;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractController extends Controller
{
    private function userBusinessIds(Request $request): \Illuminate\Support\Collection
    {
        return $request->user()->businesses()->pluck('id');
    }

    private function authorizeContract(Request $request, Contract $contract): void
    {
        if (! $this->userBusinessIds($request)->contains($contract->business_id)) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $businessIds = $this->userBusinessIds($request);
        $contracts = Contract::with(['business', 'supplier', 'facility'])
            ->whereIn('business_id', $businessIds)
            ->latest('start_date')
            ->paginate(10);

        return view('contracts.index', compact('contracts'));
    }

    public function create(Request $request): View
    {
        $businessIds = $this->userBusinessIds($request);
        $businesses = Business::whereIn('id', $businessIds)->orderBy('business_name')->get();
        $suppliers = Supplier::whereIn('business_id', $businessIds)->orderBy('id')->get();
        $facilities = Facility::whereIn('business_id', $businessIds)->orderBy('facility_name')->get();

        return view('contracts.create', compact('businesses', 'suppliers', 'facilities'));
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        $businessIds = $this->userBusinessIds($request);
        if (! $businessIds->contains((int) $request->validated('business_id'))) {
            abort(404);
        }
        $this->validateCounterparty($request, $request->validated());

        Contract::create($request->validated());

        return redirect()->route('contracts.index')->with('status', __('Contract created.'));
    }

    public function show(Request $request, Contract $contract): View
    {
        $this->authorizeContract($request, $contract);
        $contract->load(['business', 'supplier', 'facility']);

        return view('contracts.show', compact('contract'));
    }

    public function edit(Request $request, Contract $contract): View
    {
        $this->authorizeContract($request, $contract);
        $businessIds = $this->userBusinessIds($request);
        $businesses = Business::whereIn('id', $businessIds)->orderBy('business_name')->get();
        $suppliers = Supplier::whereIn('business_id', $businessIds)->orderBy('id')->get();
        $facilities = Facility::whereIn('business_id', $businessIds)->orderBy('facility_name')->get();

        return view('contracts.edit', compact('contract', 'businesses', 'suppliers', 'facilities'));
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        $businessIds = $this->userBusinessIds($request);
        if (! $businessIds->contains((int) $request->validated('business_id'))) {
            abort(404);
        }
        $this->validateCounterparty($request, $request->validated());

        $contract->update($request->validated());

        return redirect()->route('contracts.show', $contract)->with('status', __('Contract updated.'));
    }

    public function destroy(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        $contract->delete();

        return redirect()->route('contracts.index')->with('status', __('Contract deleted.'));
    }

    private function validateCounterparty(Request $request, array $data): void
    {
        $businessId = (int) $data['business_id'];
        if (! empty($data['supplier_id'])) {
            $s = Supplier::find($data['supplier_id']);
            if (! $s || $s->business_id != $businessId) {
                abort(404);
            }
        }
        if (! empty($data['facility_id'])) {
            $f = Facility::find($data['facility_id']);
            if (! $f || $f->business_id != $businessId) {
                abort(404);
            }
        }
    }
}
