<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreAnimalRequest;
use App\Http\Requests\Farmer\UpdateAnimalRequest;
use App\Models\Animal;
use App\Models\Farm;
use App\Models\Livestock;
use App\Services\Farmer\AnimalCodeService;
use App\Services\Farmer\AnimalHealthTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AnimalController extends Controller
{
    public function index(Request $request, Farm $farm, Livestock $livestock): View|RedirectResponse
    {
        if ($redirect = $this->redirectToCanonicalNestedAnimalUrl($farm, $livestock, null, 'farmer.farms.livestock.animals.index')) {
            return $redirect;
        }

        $this->authorize('viewAny', [Animal::class, $livestock]);

        $query = $livestock->animals();

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('animal_code', 'like', '%'.$search.'%')
                    ->orWhere('tag_number', 'like', '%'.$search.'%')
                    ->orWhere('animal_name', 'like', '%'.$search.'%');
            });
        }

        foreach (['health_status', 'production_status', 'lifecycle_status', 'gender'] as $filter) {
            $value = (string) $request->query($filter, '');
            if ($value !== '') {
                $query->where($filter, $value);
            }
        }

        $animals = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => (int) $livestock->animals()->count(),
            'active' => (int) $livestock->animals()->where('lifecycle_status', Animal::LIFECYCLE_ACTIVE)->count(),
            'sick' => (int) $livestock->animals()->where('health_status', Animal::HEALTH_SICK)->count(),
            'ready_for_sale' => (int) $livestock->animals()->where('production_status', Animal::PRODUCTION_READY_FOR_SALE)->count(),
        ];

        return view('farmer.animals.index', compact('farm', 'livestock', 'animals', 'stats'));
    }

    public function create(Request $request, Farm $farm, Livestock $livestock): View|RedirectResponse
    {
        if ($redirect = $this->redirectToCanonicalNestedAnimalUrl($farm, $livestock, null, 'farmer.farms.livestock.animals.create')) {
            return $redirect;
        }

        $this->authorize('create', [Animal::class, $livestock]);

        return view('farmer.animals.create', compact('farm', 'livestock'));
    }

    public function store(StoreAnimalRequest $request, Farm $farm, Livestock $livestock, AnimalCodeService $codes): RedirectResponse
    {
        $this->authorize('create', [Animal::class, $livestock]);
        if ((int) $livestock->farm_id !== (int) $farm->id) {
            $canonicalFarm = $livestock->farm ?? Farm::query()->findOrFail((int) $livestock->farm_id);

            return redirect()->route('farmer.farms.livestock.animals.create', [$canonicalFarm, $livestock])
                ->withInput()
                ->with('status', __('The farm in the URL did not match this livestock group. Please submit again.'));
        }

        $data = $request->validated();
        unset($data['photo']);

        $data['animal_code'] = $codes->generateForLivestock($livestock);
        $data['qr_code'] = $codes->generateQrPayload($data['animal_code']);
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('farm-animals', 'public');
        }

        $animal = $livestock->animals()->create($data);

        return redirect()->route('farmer.farms.livestock.animals.show', [$farm, $livestock, $animal])
            ->with('status', __('Animal record created.'));
    }

    public function show(Request $request, Farm $farm, Livestock $livestock, Animal $animal, AnimalHealthTimelineService $timeline): View|RedirectResponse
    {
        if ($redirect = $this->redirectToCanonicalNestedAnimalUrl($farm, $livestock, $animal, 'farmer.farms.livestock.animals.show')) {
            return $redirect;
        }

        $this->authorize('view', $animal);

        $healthTimeline = $timeline->forAnimal($animal);

        return view('farmer.animals.show', compact('farm', 'livestock', 'animal', 'healthTimeline'));
    }

    public function edit(Request $request, Farm $farm, Livestock $livestock, Animal $animal): View|RedirectResponse
    {
        if ($redirect = $this->redirectToCanonicalNestedAnimalUrl($farm, $livestock, $animal, 'farmer.farms.livestock.animals.edit')) {
            return $redirect;
        }

        $this->authorize('update', $animal);

        return view('farmer.animals.edit', compact('farm', 'livestock', 'animal'));
    }

    public function update(UpdateAnimalRequest $request, Farm $farm, Livestock $livestock, Animal $animal): RedirectResponse
    {
        $this->authorize('update', $animal);
        if ($redirect = $this->redirectToCanonicalNestedAnimalUrl($farm, $livestock, $animal, 'farmer.farms.livestock.animals.edit')) {
            return $redirect->withInput()->with('status', __('The farm or group in the URL did not match this animal. Please save again.'));
        }

        $data = $request->validated();
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            if ($animal->photo_path) {
                Storage::disk('public')->delete($animal->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('farm-animals', 'public');
        }

        $animal->update($data);

        return redirect()->route('farmer.farms.livestock.animals.show', [$farm, $livestock, $animal])
            ->with('status', __('Animal record updated.'));
    }

    public function destroy(Request $request, Farm $farm, Livestock $livestock, Animal $animal): RedirectResponse
    {
        $this->authorize('delete', $animal);
        if ($redirect = $this->redirectToCanonicalNestedAnimalUrl($farm, $livestock, $animal, 'farmer.farms.livestock.animals.show')) {
            return $redirect->with('status', __('We could not delete from that link. Use delete from the page below if you still want to remove this animal.'));
        }

        if ($animal->photo_path) {
            Storage::disk('public')->delete($animal->photo_path);
        }

        $animal->delete();

        return redirect()->route('farmer.farms.livestock.animals.index', [$farm, $livestock])
            ->with('status', __('Animal record removed.'));
    }

    /**
     * Nested URLs use /farms/{farm}/livestock/{livestock}/… If {farm} is stale (e.g. after a move) or {livestock}
     * does not match {animal}, send the user to the canonical URL instead of a silent 404.
     */
    private function redirectToCanonicalNestedAnimalUrl(Farm $farm, Livestock $livestock, ?Animal $animal, string $routeName): ?RedirectResponse
    {
        if ($animal !== null && (int) $animal->livestock_id !== (int) $livestock->id) {
            $this->authorize('view', $animal);
            $resolvedLivestock = $animal->livestock ?? $animal->livestock()->firstOrFail();
            $resolvedFarm = $resolvedLivestock->farm ?? Farm::query()->findOrFail((int) $resolvedLivestock->farm_id);

            return redirect()->route($routeName, [$resolvedFarm, $resolvedLivestock, $animal]);
        }

        if ((int) $livestock->farm_id !== (int) $farm->id) {
            $this->authorize('view', $livestock);
            $resolvedFarm = $livestock->farm ?? Farm::query()->findOrFail((int) $livestock->farm_id);
            $params = [$resolvedFarm, $livestock];
            if ($animal !== null) {
                $params[] = $animal;
            }

            return redirect()->route($routeName, $params);
        }

        return null;
    }
}
