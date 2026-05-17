<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Requests\Farmer\StorePermitRequestRequest;
use App\Http\Requests\Farmer\UpdatePermitRequestRequest;
use App\Models\Farm;
use App\Models\PermitRequest;
use App\Services\Farmer\PermitRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermitRequestController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PermitRequest::class);

        $query = PermitRequest::query()
            ->whereIn('farmer_id', $request->user()->accessibleFarmerBusinessIds())
            ->with(['farm', 'applicant'])->withCount('animals')
            ->latest('request_date');

        if ($status = (string) $request->query('status', '')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('request_number', 'like', '%'.$search.'%');
        }

        $records = $query->paginate(20)->withQueryString();

        return view('farmer.movement.requests.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PermitRequest::class);

        return view('farmer.movement.requests.create', [
            'farms' => $this->accessibleFarms($request),
            'animals' => $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get(),
        ]);
    }

    public function store(StorePermitRequestRequest $request, PermitRequestService $service): RedirectResponse
    {
        $this->authorize('create', PermitRequest::class);

        $record = $service->create($request->validated(), $request->user()->id);

        if ($request->input('submit') === '1') {
            $service->submit($record);
        }

        return redirect()->route('farmer.movement.requests.show', $record)
            ->with('status', $request->input('submit') === '1' ? __('Permit request submitted.') : __('Permit request saved.'));
    }

    public function show(PermitRequest $permitRequest): View
    {
        $this->authorize('view', $permitRequest);
        $permitRequest->load(['farm', 'applicant', 'animals.animal.livestock', 'reviewer', 'permit']);

        return view('farmer.movement.requests.show', ['request' => $permitRequest]);
    }

    public function edit(Request $httpRequest, PermitRequest $permitRequest): View
    {
        $this->authorize('update', $permitRequest);
        abort_unless($permitRequest->isEditable(), 403);

        return view('farmer.movement.requests.edit', [
            'request' => $permitRequest->load(['animals']),
            'farms' => $this->accessibleFarms($httpRequest),
            'animals' => $this->accessibleAnimalsQuery($httpRequest)->orderBy('animal_code')->get(),
        ]);
    }

    public function update(UpdatePermitRequestRequest $httpRequest, PermitRequest $permitRequest, PermitRequestService $service): RedirectResponse
    {
        $this->authorize('update', $permitRequest);
        $record = $service->update($permitRequest, $httpRequest->validated());

        if ($httpRequest->boolean('submit')) {
            $service->submit($record);
        }

        return redirect()->route('farmer.movement.requests.show', $record)
            ->with('status', __('Permit request updated.'));
    }

    public function destroy(PermitRequest $permitRequest): RedirectResponse
    {
        $this->authorize('delete', $permitRequest);
        $permitRequest->delete();

        return redirect()->route('farmer.movement.requests.index')
            ->with('status', __('Permit request deleted.'));
    }

    public function submit(PermitRequest $permitRequest, PermitRequestService $service): RedirectResponse
    {
        $this->authorize('update', $permitRequest);
        $service->submit($permitRequest);

        return back()->with('status', __('Request submitted for review.'));
    }

    public function approve(Request $httpRequest, PermitRequest $permitRequest, PermitRequestService $service): RedirectResponse
    {
        $this->authorize('review', $permitRequest);
        $service->approve($permitRequest, $httpRequest->user()->id);

        return back()->with('status', __('Request approved. You can now issue or upload the official permit.'));
    }

    public function reject(Request $httpRequest, PermitRequest $permitRequest, PermitRequestService $service): RedirectResponse
    {
        $this->authorize('review', $permitRequest);
        $httpRequest->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        $service->reject($permitRequest, $httpRequest->user()->id, $httpRequest->input('rejection_reason'));

        return back()->with('status', __('Request rejected.'));
    }

    public function issuePermit(PermitRequest $permitRequest): RedirectResponse
    {
        $this->authorize('view', $permitRequest);
        abort_unless($permitRequest->canIssuePermit(), 403);

        return redirect()->route('farmer.movement.permits.create', [
            'permit_request_id' => $permitRequest->id,
            'source_farm_id' => $permitRequest->farm_id,
        ]);
    }

    private function accessibleFarms(Request $request)
    {
        return Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->orderBy('name')
            ->get();
    }
}
