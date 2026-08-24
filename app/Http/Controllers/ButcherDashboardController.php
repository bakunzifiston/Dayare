<?php

namespace App\Http\Controllers;

use App\Services\Butcher\ButcherDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherDashboardController extends Controller
{
    public function __invoke(Request $request, ButcherDashboardService $dashboard): View
    {
        $user = $request->user();
        $payload = $dashboard->build($user);

        return view('butcher.dashboard', array_merge(['user' => $user], $payload));
    }
}
