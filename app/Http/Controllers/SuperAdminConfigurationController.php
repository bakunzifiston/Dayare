<?php

namespace App\Http\Controllers;

use App\Models\Species;
use App\Models\Unit;
use Illuminate\View\View;

class SuperAdminConfigurationController extends Controller
{
    public function index(): View
    {
        return view('super-admin.configurations.index', [
            'speciesCount' => Species::count(),
            'activeSpeciesCount' => Species::where('is_active', true)->count(),
            'unitCount' => Unit::count(),
            'activeUnitCount' => Unit::where('is_active', true)->count(),
        ]);
    }
}
