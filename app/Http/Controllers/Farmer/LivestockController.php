<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreLivestockRequest;
use App\Http\Requests\Farmer\UpdateFarmLivestockHealthSplitsRequest;
use App\Http\Requests\Farmer\UpdateLivestockDetailsRequest;
use App\Http\Requests\Farmer\UpdateLivestockRequest;
use App\Models\Farm;
use App\Models\Livestock;
use App\Models\MovementPermit;
use App\Services\Farmer\LivestockCodeService;
use App\Support\FarmerAnimalType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LivestockController extends Controller
{
    public function index(Request $request, Farm $farm): View
    {
        $this->authorize('viewAny', [Livestock::class, $farm]);

        $query = $farm->livestock()->withCount('animals');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('livestock_name', 'like', '%'.$search.'%')
                    ->orWhere('livestock_code', 'like', '%'.$search.'%')
                    ->orWhere('livestock_type', 'like', '%'.$search.'%');
            });
        }

        foreach (['status', 'health_status', 'lifecycle_status', 'livestock_type'] as $filter) {
            $value = (string) $request->query($filter, '');
            if ($value !== '') {
                $query->where($filter, $value);
            }
        }

        $livestock = $query->orderBy('livestock_name')->get();
        $healthHeadcounts = Livestock::aggregateHealthQuantities($livestock);

        $stats = [
            'groups' => $livestock->count(),
            'headcount' => (int) $livestock->sum(fn (Livestock $row) => (int) ($row->total_count ?? $row->total_quantity)),
            'active_groups' => $livestock->where('status', Livestock::STATUS_ACTIVE)->count(),
            'quarantined_groups' => $livestock->where('lifecycle_status', Livestock::LIFECYCLE_QUARANTINED)->count(),
        ];

        $destinationFarms = Farm::query()
            ->whereIn('business_id', $request->user()->accessibleFarmerBusinessIds())
            ->whereKeyNot($farm->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $validPermits = MovementPermit::query()
            ->where('farmer_id', $farm->business_id)
            ->where('source_farm_id', $farm->id)
            ->whereDate('issue_date', '<=', Carbon::today())
            ->whereDate('expiry_date', '>=', Carbon::today())
            ->orderBy('expiry_date')
            ->get(['id', 'permit_number', 'expiry_date']);

        return view('farmer.livestock.index', compact(
            'farm',
            'livestock',
            'healthHeadcounts',
            'stats',
            'destinationFarms',
            'validPermits'
        ));
    }

    public function create(Request $request, Farm $farm): View
    {
        $this->authorize('create', [Livestock::class, $farm]);
        $types = FarmerAnimalType::ALL;

        return view('farmer.livestock.create', compact('farm', 'types'));
    }

    public function store(StoreLivestockRequest $request, Farm $farm, LivestockCodeService $codes): RedirectResponse
    {
        $this->authorize('create', [Livestock::class, $farm]);

        $data = $request->validated();
        $data['livestock_code'] = $codes->generateForFarm($farm);
        $data['created_by'] = $request->user()->id;

        $livestock = $farm->livestock()->create($data);
        $livestock->update([
            'healthy_quantity' => (int) $livestock->total_count,
            'sick_quantity' => 0,
        ]);

        return redirect()->route('farmer.farms.livestock.show', [$farm, $livestock])
            ->with('status', __('Livestock group created.'));
    }

    public function show(Request $request, Farm $farm, Livestock $livestock): View|JsonResponse
    {
        $this->authorize('view', $livestock);
        abort_unless($livestock->farm_id === $farm->id, 404);

        $livestock->load(['detail', 'latestHealthRecord'])->loadCount('animals');

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $livestock->id,
                'core' => [
                    'livestock_name' => $livestock->livestock_name,
                    'livestock_code' => $livestock->livestock_code,
                    'livestock_type' => $livestock->livestock_type,
                    'production_purpose' => $livestock->production_purpose,
                    'total_count' => $livestock->total_count,
                    'health_status' => $livestock->health_status,
                    'lifecycle_status' => $livestock->lifecycle_status,
                    'status' => $livestock->status,
                ],
                'quality' => $livestock->qualityScore(),
            ]);
        }

        return view('farmer.livestock.show', compact('farm', 'livestock'));
    }

    public function edit(Request $request, Farm $farm, Livestock $livestock): View
    {
        $this->authorize('update', $livestock);
        abort_unless($livestock->farm_id === $farm->id, 404);
        $types = FarmerAnimalType::ALL;

        return view('farmer.livestock.edit', compact('farm', 'livestock', 'types'));
    }

    public function update(UpdateLivestockRequest $request, Farm $farm, Livestock $livestock): RedirectResponse
    {
        $this->authorize('update', $livestock);
        abort_unless($livestock->farm_id === $farm->id, 404);

        $livestock->update($request->validated());

        return redirect()->route('farmer.farms.livestock.show', [$farm, $livestock])
            ->with('status', __('Livestock group updated.'));
    }

    public function updateHealthSplits(UpdateFarmLivestockHealthSplitsRequest $request, Farm $farm): RedirectResponse
    {
        $this->authorize('viewAny', [Livestock::class, $farm]);

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
        $this->authorize('update', $livestock);
        abort_unless($livestock->farm_id === $farm->id, 404);

        $livestock->detail()->updateOrCreate(
            ['livestock_id' => $livestock->id],
            $request->validated()
        );

        return redirect()->back()->with('status', __('Details saved.'));
    }

    public function destroy(Request $request, Farm $farm, Livestock $livestock): RedirectResponse
    {
        $this->authorize('delete', $livestock);
        abort_unless($livestock->farm_id === $farm->id, 404);
        $livestock->delete();

        return redirect()->route('farmer.farms.livestock.index', $farm)
            ->with('status', __('Livestock group removed.'));
    }
}
