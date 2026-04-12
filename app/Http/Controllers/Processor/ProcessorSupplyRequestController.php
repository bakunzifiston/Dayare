<?php

namespace App\Http\Controllers\Processor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Processor\StoreProcessorSupplyRequestRequest;
use App\Models\Business;
use App\Models\Facility;
use App\Models\SupplyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProcessorSupplyRequestController extends Controller
{
    public function index(Request $request): View
    {
        $processorIds = $request->user()->accessibleProcessorBusinessIds();
        $requests = SupplyRequest::query()
            ->with(['farmer', 'destinationFacility'])
            ->whereIn('processor_id', $processorIds)
            ->latest()
            ->paginate(15);

        return view('processor.supply-requests.index', compact('requests'));
    }

    public function create(Request $request): View
    {
        $processorIds = $request->user()->accessibleProcessorBusinessIds();
        $processorBusinesses = Business::query()
            ->whereIn('id', $processorIds)
            ->orderBy('business_name')
            ->get();

        $farmers = Business::query()
            ->where('type', Business::TYPE_FARMER)
            ->where('status', Business::STATUS_ACTIVE)
            ->orderBy('business_name')
            ->limit(500)
            ->get(['id', 'business_name', 'registration_number']);

        $facilities = Facility::query()
            ->whereIn('business_id', $processorIds)
            ->orderBy('facility_name')
            ->get(['id', 'facility_name', 'business_id']);

        if ($facilities->isEmpty()) {
            return redirect()->route('businesses.hub')
                ->with('error', __('Add at least one facility to your processor business before requesting supply from farmers.'));
        }

        return view('processor.supply-requests.create', compact('processorBusinesses', 'farmers', 'facilities'));
    }

    public function store(StoreProcessorSupplyRequestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        SupplyRequest::create([
            'processor_id' => $data['processor_business_id'],
            'farmer_id' => $data['farmer_id'],
            'destination_facility_id' => $data['destination_facility_id'],
            'animal_type' => $data['animal_type'],
            'quantity_requested' => $data['quantity_requested'],
            'preferred_date' => $data['preferred_date'] ?? null,
            'status' => SupplyRequest::STATUS_PENDING,
        ]);

        return redirect()->route('processor.supply-requests.index')
            ->with('status', __('Supply request sent to the farmer.'));
    }
}
