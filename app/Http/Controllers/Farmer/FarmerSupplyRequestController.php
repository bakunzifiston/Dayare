<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\AcceptSupplyRequestRequest;
use App\Models\Farm;
use App\Models\MovementPermit;
use App\Models\SupplyRequest;
use App\Services\Farmer\SupplyRequestService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerSupplyRequestController extends Controller
{
    public function __construct(
        private SupplyRequestService $supplyRequestService
    ) {}

    public function index(Request $request): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $requests = SupplyRequest::query()
            ->with(['processor', 'destinationFacility'])
            ->whereIn('farmer_id', $farmerIds)
            ->latest()
            ->paginate(15);

        return view('farmer.supply-requests.index', compact('requests'));
    }

    public function show(Request $request, SupplyRequest $supplyRequest): View
    {
        $this->authorizeRequest($request, $supplyRequest);
        $supplyRequest->load(['processor', 'destinationFacility', 'sourceFarm']);

        $farmOptions = Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->where('business_id', $supplyRequest->farmer_id)
            ->orderBy('name')
            ->get();

        $permits = MovementPermit::query()
            ->where('farmer_id', $supplyRequest->farmer_id)
            ->whereDate('issue_date', '<=', Carbon::today())
            ->whereDate('expiry_date', '>=', Carbon::today())
            ->orderBy('expiry_date')
            ->get();

        return view('farmer.supply-requests.show', compact('supplyRequest', 'farmOptions', 'permits'));
    }

    public function accept(AcceptSupplyRequestRequest $request, SupplyRequest $supplyRequest): RedirectResponse
    {
        $this->authorizeRequest($request, $supplyRequest);

        try {
            $this->supplyRequestService->accept(
                $supplyRequest,
                $request->user(),
                (int) $request->validated('farm_id'),
                (int) $request->validated('movement_permit_id')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()->route('farmer.supply-requests.show', $supplyRequest)
            ->with('status', __('Request accepted. Animal intake was created at the processor facility.'));
    }

    public function reject(Request $request, SupplyRequest $supplyRequest): RedirectResponse
    {
        $this->authorizeRequest($request, $supplyRequest);

        try {
            $this->supplyRequestService->reject($supplyRequest, $request->user());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }

        return redirect()->route('farmer.supply-requests.index')
            ->with('status', __('Request rejected.'));
    }

    private function authorizeRequest(Request $request, SupplyRequest $supplyRequest): void
    {
        abort_unless(
            $request->user()->accessibleFarmerBusinessIds()->contains((int) $supplyRequest->farmer_id),
            403
        );
    }
}
