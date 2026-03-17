<?php

namespace App\Http\Controllers;

use App\Models\DeliveryConfirmation;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipientController extends Controller
{
    /**
     * List facilities (of the user's businesses) that have received at least one delivery.
     * Read-only view: no new table; aggregates from delivery_confirmations.
     */
    public function index(Request $request): View
    {
        $facilityIds = Facility::whereIn('business_id', $request->user()->accessibleBusinessIds())
            ->pluck('id');

        $aggregates = DeliveryConfirmation::query()
            ->whereNotNull('receiving_facility_id')
            ->whereIn('receiving_facility_id', $facilityIds)
            ->selectRaw('receiving_facility_id as facility_id, max(received_date) as last_delivery_date, count(*) as delivery_count')
            ->groupBy('receiving_facility_id')
            ->orderByDesc('last_delivery_date')
            ->get();

        $facilityIdsWithDeliveries = $aggregates->pluck('facility_id')->unique()->values();
        $facilities = Facility::with('business')
            ->whereIn('id', $facilityIdsWithDeliveries)
            ->get()
            ->keyBy('id');

        $recipients = $aggregates->map(function ($row) use ($facilities) {
            $facility = $facilities->get($row->facility_id);
            return (object) [
                'facility_id' => $row->facility_id,
                'facility' => $facility,
                'last_delivery_date' => $row->last_delivery_date,
                'delivery_count' => (int) $row->delivery_count,
            ];
        });

        return view('recipients.index', compact('recipients'));
    }
}
