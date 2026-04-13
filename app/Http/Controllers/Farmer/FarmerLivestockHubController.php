<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FarmerLivestockHubController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $farm = Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->orderBy('id')
            ->first();

        if ($farm === null) {
            return redirect()
                ->route('farmer.farms.index')
                ->with('status', __('Please create a farm first to manage livestock.'));
        }

        return redirect()->route('farmer.farms.livestock.index', $farm);
    }
}
