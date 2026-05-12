<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Requests\Farmer\StoreMovementPermitRequest;
use App\Http\Requests\Farmer\UpdateMovementPermitRequest;
use App\Models\Farm;
use App\Models\MovementPermit;
use App\Services\Farmer\MovementHistoryService;
use App\Services\Farmer\MovementPermitPdfService;
use App\Services\Farmer\MovementPermitService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MovementPermitController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MovementPermit::class);
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $query = MovementPermit::query()
            ->whereIn('farmer_id', $farmerIds)
            ->with(['sourceFarm', 'animals'])
            ->latest('departure_date');

        foreach (['permit_type', 'permit_status', 'movement_status'] as $filter) {
            if ($value = (string) $request->query($filter, '')) {
                $query->where($filter, $value);
            }
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('permit_number', 'like', '%'.$search.'%');
        }

        $records = $query->paginate(20)->withQueryString();

        return view('farmer.movement.permits.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', MovementPermit::class);

        return view('farmer.movement.permits.create', [
            'farms' => $this->accessibleFarms($request),
            'animals' => $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get(),
        ]);
    }

    public function store(StoreMovementPermitRequest $request, MovementPermitService $service, MovementHistoryService $history): RedirectResponse
    {
        $this->authorize('create', MovementPermit::class);
        $data = $this->payload($request);
        $permit = $service->create($data, $request->user()->id, $history->requestIp($request));

        return redirect()->route('farmer.movement.permits.show', $permit)->with('status', __('Movement permit created.'));
    }

    public function show(Request $request, MovementPermit $movementPermit): View
    {
        $this->authorize('view', $movementPermit);
        $movementPermit->load(['sourceFarm', 'animals.animal', 'animals.livestock', 'transport', 'veterinaryApproval', 'logs.actor', 'approver']);
        $isValid = $movementPermit->isValidOn(Carbon::today());

        return view('farmer.movement.permits.show', ['permit' => $movementPermit, 'isValid' => $isValid]);
    }

    public function edit(Request $request, MovementPermit $movementPermit): View
    {
        $this->authorize('update', $movementPermit);
        abort_unless($movementPermit->isEditable(), 403);
        $movementPermit->load(['animals', 'transport', 'veterinaryApproval']);

        return view('farmer.movement.permits.edit', [
            'permit' => $movementPermit,
            'farms' => $this->accessibleFarms($request),
            'animals' => $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get(),
        ]);
    }

    public function update(UpdateMovementPermitRequest $request, MovementPermit $movementPermit, MovementPermitService $service, MovementHistoryService $history): RedirectResponse
    {
        $this->authorize('update', $movementPermit);
        $service->update($movementPermit, $this->payload($request), $request->user()->id, $history->requestIp($request));

        return redirect()->route('farmer.movement.permits.show', $movementPermit)->with('status', __('Movement permit updated.'));
    }

    public function destroy(MovementPermit $movementPermit): RedirectResponse
    {
        $this->authorize('delete', $movementPermit);
        $movementPermit->delete();

        return redirect()->route('farmer.movement.permits.index')->with('status', __('Movement permit archived.'));
    }

    public function submit(Request $request, MovementPermit $movementPermit, MovementPermitService $service, MovementHistoryService $history): RedirectResponse
    {
        $this->authorize('update', $movementPermit);
        $service->submitForApproval($movementPermit, $request->user()->id, $history->requestIp($request));

        return back()->with('status', __('Permit submitted for approval.'));
    }

    public function approve(Request $request, MovementPermit $movementPermit, MovementPermitService $service, MovementHistoryService $history): RedirectResponse
    {
        $this->authorize('approve', $movementPermit);
        $service->approve($movementPermit, $request->user()->id, $history->requestIp($request));

        return back()->with('status', __('Permit approved.'));
    }

    public function reject(Request $request, MovementPermit $movementPermit, MovementPermitService $service, MovementHistoryService $history): RedirectResponse
    {
        $this->authorize('approve', $movementPermit);
        $service->reject($movementPermit, $request->user()->id, $history->requestIp($request), $request->input('notes'));

        return back()->with('status', __('Permit rejected.'));
    }

    public function startTransit(Request $request, MovementPermit $movementPermit, MovementPermitService $service, MovementHistoryService $history): RedirectResponse
    {
        $this->authorize('update', $movementPermit);
        $service->startTransit($movementPermit, $request->user()->id, $history->requestIp($request));

        return back()->with('status', __('Movement started.'));
    }

    public function confirmArrival(Request $request, MovementPermit $movementPermit, MovementPermitService $service, MovementHistoryService $history): RedirectResponse
    {
        $this->authorize('update', $movementPermit);
        $service->confirmArrival($movementPermit, $request->user()->id, $history->requestIp($request));

        return back()->with('status', __('Arrival confirmed.'));
    }

    public function cancel(Request $request, MovementPermit $movementPermit, MovementPermitService $service, MovementHistoryService $history): RedirectResponse
    {
        $this->authorize('update', $movementPermit);
        $service->cancel($movementPermit, $request->user()->id, $history->requestIp($request), $request->input('notes'));

        return back()->with('status', __('Permit cancelled.'));
    }

    public function download(Request $request, MovementPermit $movementPermit, MovementPermitPdfService $pdfService)
    {
        $this->authorize('view', $movementPermit);
        $path = $movementPermit->pdf_path ?: $movementPermit->file_path;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            $path = $pdfService->generate($movementPermit);
        }

        return Storage::disk('public')->download($path, $movementPermit->permit_number.'.pdf');
    }

    private function accessibleFarms(Request $request)
    {
        return Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->orderBy('name')
            ->get();
    }

    /** @return array<string, mixed> */
    private function payload(StoreMovementPermitRequest $request): array
    {
        $data = $request->validated();
        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('movement-permits/attachments', 'public');
        }

        return $data;
    }
}
