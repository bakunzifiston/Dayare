<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Business;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    private function userBusinessIds(Request $request): \Illuminate\Support\Collection
    {
        return $request->user()->accessibleBusinessIds();
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
        $query = Contract::with(['business', 'supplier', 'employee', 'facility'])
            ->whereIn('business_id', $businessIds);

        if ($request->filled('category') && in_array($request->category, [Contract::CATEGORY_EMPLOYEE, Contract::CATEGORY_SUPPLIER])) {
            $query->where('contract_category', $request->category);
        }
        $contracts = $query->latest('start_date')->paginate(10)->withQueryString();

        $baseQuery = Contract::whereIn('business_id', $businessIds);
        $kpis = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', Contract::STATUS_ACTIVE)->count(),
            'draft' => (clone $baseQuery)->where('status', Contract::STATUS_DRAFT)->count(),
        ];

        return view('contracts.index', compact('contracts', 'kpis'));
    }

    public function create(Request $request): View
    {
        $businessIds = $this->userBusinessIds($request);
        $businesses = Business::whereIn('id', $businessIds)->orderBy('business_name')->get();
        $employees = Employee::whereIn('business_id', $businessIds)->orderBy('first_name')->orderBy('last_name')->get();
        $suppliers = Supplier::whereIn('business_id', $businessIds)->orderBy('id')->get();
        $facilities = Facility::whereIn('business_id', $businessIds)->orderBy('facility_name')->get();
        $users = \App\Models\User::whereHas('businesses', fn ($q) => $q->whereIn('businesses.id', $businessIds))->orderBy('name')->get();

        return view('contracts.create', [
            'businesses' => $businesses,
            'employees' => $employees,
            'suppliers' => $suppliers,
            'facilities' => $facilities,
            'users' => $users,
            'category' => $request->query('category'),
        ]);
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        $businessIds = $this->userBusinessIds($request);
        if (! $businessIds->contains((int) $request->validated('business_id'))) {
            abort(404);
        }
        $this->validateCounterparty($request, $request->validated());

        $data = $request->safe()->except(['signed_contract_file', 'supporting_documents']);
        $contract = Contract::create($data);

        $this->storeContractFiles($request, $contract);

        return redirect()->route('contracts.index')->with('status', __('Contract created.'));
    }

    public function show(Request $request, Contract $contract): View
    {
        $this->authorizeContract($request, $contract);
        $contract->load(['business', 'supplier', 'employee', 'facility', 'contractOwner']);

        return view('contracts.show', compact('contract'));
    }

    public function edit(Request $request, Contract $contract): View
    {
        $this->authorizeContract($request, $contract);
        $businessIds = $this->userBusinessIds($request);
        $businesses = Business::whereIn('id', $businessIds)->orderBy('business_name')->get();
        $employees = Employee::whereIn('business_id', $businessIds)->orderBy('first_name')->orderBy('last_name')->get();
        $suppliers = Supplier::whereIn('business_id', $businessIds)->orderBy('id')->get();
        $facilities = Facility::whereIn('business_id', $businessIds)->orderBy('facility_name')->get();
        $users = \App\Models\User::whereHas('businesses', fn ($q) => $q->whereIn('businesses.id', $businessIds))->orderBy('name')->get();

        return view('contracts.edit', compact('contract', 'businesses', 'employees', 'suppliers', 'facilities', 'users'));
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        $this->authorizeContract($request, $contract);
        $businessIds = $this->userBusinessIds($request);
        if (! $businessIds->contains((int) $request->validated('business_id'))) {
            abort(404);
        }
        $this->validateCounterparty($request, $request->validated());

        $data = $request->safe()->except(['signed_contract_file', 'supporting_documents']);
        $contract->update($data);

        $this->storeContractFiles($request, $contract);

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
        $category = $data['contract_category'] ?? null;

        if ($category === Contract::CATEGORY_EMPLOYEE && ! empty($data['employee_id'])) {
            $e = Employee::find($data['employee_id']);
            if (! $e || $e->business_id != $businessId) {
                abort(404);
            }
        }
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
        if (! empty($data['contract_owner_id'])) {
            $u = \App\Models\User::find($data['contract_owner_id']);
            if (! $u || ! $u->businesses()->where('businesses.id', $businessId)->exists()) {
                abort(404);
            }
        }
    }

    private function storeContractFiles(Request $request, Contract $contract): void
    {
        $disk = 'local';
        $baseDir = 'contracts/'.$contract->id;

        if ($request->hasFile('signed_contract_file')) {
            $file = $request->file('signed_contract_file');
            if ($contract->signed_contract_file) {
                Storage::disk($disk)->delete($contract->signed_contract_file);
            }
            $path = $file->store($baseDir.'/signed', $disk);
            $contract->update(['signed_contract_file' => $path]);
        }

        if ($request->hasFile('supporting_documents')) {
            $paths = $contract->supporting_documents ?? [];
            foreach ($request->file('supporting_documents') as $file) {
                if (! $file->isValid()) {
                    continue;
                }
                $path = $file->store($baseDir.'/supporting', $disk);
                $paths[] = $path;
            }
            if (! empty($paths)) {
                $contract->update(['supporting_documents' => $paths]);
            }
        }
    }

    public function downloadFile(Request $request, Contract $contract, string $type, string $filename): StreamedResponse
    {
        $this->authorizeContract($request, $contract);

        if ($type === 'signed' && $contract->signed_contract_file) {
            $path = $contract->signed_contract_file;
            if (basename($path) !== $filename || ! Storage::disk('local')->exists($path)) {
                abort(404);
            }
            return Storage::disk('local')->download($path, $filename);
        }

        if ($type === 'supporting') {
            $supporting = $contract->supporting_documents ?? [];
            foreach ($supporting as $p) {
                if (basename($p) === $filename && Storage::disk('local')->exists($p)) {
                    return Storage::disk('local')->download($p, $filename);
                }
            }
        }

        abort(404);
    }
}
