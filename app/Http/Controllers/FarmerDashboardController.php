<?php

namespace App\Http\Controllers;

use App\Services\Farmer\FarmerDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerDashboardController extends Controller
{
    public function __invoke(Request $request, FarmerDashboardService $dashboard): View
    {
        $user = $request->user();
        $payload = $dashboard->build($user);

        return view('farmer.dashboard', array_merge(['user' => $user], $payload));
    }
}
