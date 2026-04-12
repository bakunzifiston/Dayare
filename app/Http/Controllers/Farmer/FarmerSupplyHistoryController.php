<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Services\Farmer\FarmerSupplyHistoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerSupplyHistoryController extends Controller
{
    public function __construct(
        private FarmerSupplyHistoryService $farmerSupplyHistoryService
    ) {}

    public function __invoke(Request $request): View
    {
        $rows = $this->farmerSupplyHistoryService->history($request->user(), 200);

        return view('farmer.supply-history', ['history' => $rows]);
    }
}
