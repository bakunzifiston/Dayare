<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Models\Treatment;
use App\Models\Vaccination;
use App\Services\Farmer\HealthDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerHealthHubController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request, HealthDashboardService $dashboard): View
    {
        $animalIds = $this->accessibleAnimalIds($request);
        $metrics = $dashboard->metrics($animalIds);
        $charts = $dashboard->charts($animalIds);

        $today = Carbon::today();
        $upcomingVaccinations = Vaccination::query()
            ->whereIn('animal_id', $animalIds)
            ->whereIn('status', [Vaccination::STATUS_SCHEDULED, Vaccination::STATUS_COMPLETED])
            ->whereDate('next_due_date', '>=', $today)
            ->whereDate('next_due_date', '<=', $today->copy()->addDays(14))
            ->with(['animal.livestock.farm'])
            ->orderBy('next_due_date')
            ->limit(10)
            ->get();

        $overdueVaccinations = Vaccination::query()
            ->whereIn('animal_id', $animalIds)
            ->whereIn('status', [Vaccination::STATUS_SCHEDULED, Vaccination::STATUS_MISSED])
            ->whereDate('next_due_date', '<', $today)
            ->with(['animal.livestock.farm'])
            ->orderBy('next_due_date')
            ->limit(10)
            ->get();

        $followUpTreatments = Treatment::query()
            ->whereIn('animal_id', $animalIds)
            ->whereDate('follow_up_date', '>=', $today)
            ->whereDate('follow_up_date', '<=', $today->copy()->addDays(14))
            ->with(['animal.livestock.farm'])
            ->orderBy('follow_up_date')
            ->limit(10)
            ->get();

        return view('farmer.health.hub', compact(
            'metrics',
            'charts',
            'upcomingVaccinations',
            'overdueVaccinations',
            'followUpTreatments',
        ));
    }
}
