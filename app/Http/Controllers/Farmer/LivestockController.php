<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreLivestockRequest;
use App\Http\Requests\Farmer\UpdateFarmLivestockHealthSplitsRequest;
use App\Http\Requests\Farmer\UpdateLivestockDetailsRequest;
use App\Http\Requests\Farmer\UpdateLivestockRequest;
use App\Models\Farm;
use App\Models\Livestock;
use App\Support\FarmerAnimalType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LivestockController extends Controller
{
    public function index(Request $request, Farm $farm): View
    {
        $this->authorizeFarm($request, $farm);
        $livestock = $farm->livestock()
            ->with('detail')
            ->orderBy('type')
            ->orderBy('breed')
            ->get();

        $healthHeadcounts = Livestock::aggregateHealthQuantities($livestock);

        return view('farmer.livestock.index', compact('farm', 'livestock', 'healthHeadcounts'));
    }

    public function create(Request $request, Farm $farm): View
    {
        $this->authorizeFarm($request, $farm);
        $types = FarmerAnimalType::ALL;

        return view('farmer.livestock.create', compact('farm', 'types'));
    }

    public function store(StoreLivestockRequest $request, Farm $farm): RedirectResponse
    {
        $this->authorizeFarm($request, $farm);
        $validated = $request->validated();
        $detailKeys = ['age_range', 'weight_range', 'notes'];
        $livestock = $farm->livestock()->create(collect($validated)->except($detailKeys)->all());

        $detail = collect($validated)->only($detailKeys)->all();
        $hasDetail = collect($detail)->contains(fn ($v) => $v !== null && trim((string) $v) !== '');
        if ($hasDetail) {
            $livestock->detail()->create($detail);
        }

        $livestock->update([
            'healthy_quantity' => $livestock->total_quantity,
            'sick_quantity' => 0,
        ]);

        return redirect()->route('farmer.farms.livestock.index', $farm)
            ->with('status', __('Livestock saved.'));
    }

    public function show(Request $request, Farm $farm, Livestock $livestock): JsonResponse
    {
        $this->authorizeFarm($request, $farm);
        abort_unless($livestock->farm_id === $farm->id, 404);
        $livestock->load(['detail', 'latestHealthRecord']);

        $latest = $livestock->latestHealthRecord;

        return response()->json([
            'id' => $livestock->id,
            'core' => [
                'type' => $livestock->type,
                'breed' => $livestock->breed,
                'feeding_type' => $livestock->feeding_type,
                'total_quantity' => $livestock->total_quantity,
                'available_quantity' => $livestock->available_quantity,
                'base_price' => $livestock->base_price,
                'health_status' => $livestock->health_status,
                'healthy_quantity' => $livestock->healthy_quantity,
                'sick_quantity' => $livestock->sick_quantity,
                'herd_health_status' => $livestock->herd_health_status,
            ],
            'extended' => $livestock->detail
                ? [
                    'age_range' => $livestock->detail->age_range,
                    'weight_range' => $livestock->detail->weight_range,
                    'notes' => $livestock->detail->notes,
                ]
                : null,
            'quality' => $livestock->qualityScore(),
            'health_log' => [
                'note' => __('Visit logs do not change healthy/sick counts.'),
                'latest' => $latest
                    ? [
                        'condition' => $latest->condition,
                        'record_date' => $latest->record_date?->toDateString(),
                        'notes' => $latest->notes,
                    ]
                    : null,
            ],
        ]);
    }

    public function edit(Request $request, Farm $farm, Livestock $livestock): View
    {
        $this->authorizeFarm($request, $farm);
        abort_unless($livestock->farm_id === $farm->id, 404);
        $livestock->load('detail');
        $types = FarmerAnimalType::ALL;

        return view('farmer.livestock.edit', compact('farm', 'livestock', 'types'));
    }

    public function update(UpdateLivestockRequest $request, Farm $farm, Livestock $livestock): RedirectResponse
    {
        $this->authorizeFarm($request, $farm);
        abort_unless($livestock->farm_id === $farm->id, 404);
        $oldTotal = (int) $livestock->total_quantity;
        $validated = $request->validated();
        $livestock->update($validated);

        $status = __('Livestock updated.');
        if ((int) $validated['total_quantity'] !== $oldTotal) {
            $livestock->update([
                'healthy_quantity' => (int) $validated['total_quantity'],
                'sick_quantity' => 0,
            ]);
            $status = __('Livestock updated. Adjust healthy vs sick counts on the farm’s Health page if needed.');
        }

        return redirect()->route('farmer.farms.livestock.index', $farm)
            ->with('status', $status);
    }

    public function updateHealthSplits(UpdateFarmLivestockHealthSplitsRequest $request, Farm $farm): RedirectResponse
    {
        $this->authorizeFarm($request, $farm);

        foreach ($request->validated()['splits'] as $livestockId => $row) {
            Livestock::query()
                ->where('farm_id', $farm->id)
                ->whereKey((int) $livestockId)
                ->update([
                    'healthy_quantity' => (int) $row['healthy'],
                    'sick_quantity' => (int) $row['sick'],
                ]);
        }

        return redirect()->route('farmer.farms.health-records.index', $farm)
            ->with('status', __('Herd health counts saved.'));
    }

    public function updateDetails(UpdateLivestockDetailsRequest $request, Farm $farm, Livestock $livestock): RedirectResponse
    {
        $this->authorizeFarm($request, $farm);
        abort_unless($livestock->farm_id === $farm->id, 404);
        $livestock->detail()->updateOrCreate(
            ['livestock_id' => $livestock->id],
            $request->validated()
        );

        return redirect()
            ->back()
            ->with('status', __('Details saved.'));
    }

    public function destroy(Request $request, Farm $farm, Livestock $livestock): RedirectResponse
    {
        $this->authorizeFarm($request, $farm);
        abort_unless($livestock->farm_id === $farm->id, 404);
        $livestock->delete();

        return redirect()->route('farmer.farms.livestock.index', $farm)
            ->with('status', __('Livestock removed.'));
    }

    private function authorizeFarm(Request $request, Farm $farm): void
    {
        abort_unless(
            $request->user()->accessibleFarmerBusinessIds()->contains((int) $farm->business_id),
            403
        );
    }
}
