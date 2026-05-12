<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);

        $farmIds = \App\Models\Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->pluck('id');

        $records = SaleLog::query()
            ->whereHas('sale', fn ($q) => $q->whereIn('farm_id', $farmIds))
            ->with(['sale', 'actor'])
            ->latest('action_date')
            ->paginate(30)
            ->withQueryString();

        return view('farmer.sales.logs.index', compact('records'));
    }
}
