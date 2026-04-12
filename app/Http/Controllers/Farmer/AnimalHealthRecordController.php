<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreAnimalHealthRecordRequest;
use App\Models\AnimalHealthRecord;
use App\Models\Farm;
use App\Models\Livestock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnimalHealthRecordController extends Controller
{
    public function index(Request $request, Farm $farm): View
    {
        $this->authorizeFarm($request, $farm);
        $farm->load(['livestock' => fn ($q) => $q->orderBy('type')->orderBy('breed')]);
        $records = $farm->healthRecords()
            ->with('livestock')
            ->latest('record_date')
            ->paginate(20);

        $healthHeadcounts = Livestock::aggregateHealthQuantities($farm->livestock);

        return view('farmer.health.index', compact('farm', 'records', 'healthHeadcounts'));
    }

    public function store(StoreAnimalHealthRecordRequest $request, Farm $farm): RedirectResponse
    {
        $this->authorizeFarm($request, $farm);
        $data = $request->validated();
        if (! empty($data['livestock_id'])) {
            $belongs = $farm->livestock()->whereKey($data['livestock_id'])->exists();
            if (! $belongs) {
                return redirect()->back()->withErrors(['livestock_id' => __('Livestock must belong to this farm.')]);
            }
        }

        $farm->healthRecords()->create($data);

        return redirect()->route('farmer.farms.health-records.index', $farm)
            ->with('status', __('Health record added.'));
    }

    public function destroy(Request $request, Farm $farm, AnimalHealthRecord $health_record): RedirectResponse
    {
        $this->authorizeFarm($request, $farm);
        abort_unless($health_record->farm_id === $farm->id, 404);
        $health_record->delete();

        return redirect()->route('farmer.farms.health-records.index', $farm)
            ->with('status', __('Health record removed.'));
    }

    private function authorizeFarm(Request $request, Farm $farm): void
    {
        abort_unless(
            $request->user()->accessibleFarmerBusinessIds()->contains((int) $farm->business_id),
            403
        );
    }
}
