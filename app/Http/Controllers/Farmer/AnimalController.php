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
    public function index(Request $request, Farm $farm, Livestock $livestock): View
    {
        $this->authorize('viewAny', [Animal::class, $livestock]);
        abort_unless($livestock->farm_id === $farm->id, 404);

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

    public function create(Request $request, Farm $farm, Livestock $livestock): View
    {
        $this->authorize('create', [Animal::class, $livestock]);
        abort_unless($livestock->farm_id === $farm->id, 404);

        return view('farmer.animals.create', compact('farm', 'livestock'));
    }

    public function store(StoreAnimalRequest $request, Farm $farm, Livestock $livestock, AnimalCodeService $codes): RedirectResponse
    {
        $this->authorize('create', [Animal::class, $livestock]);
        abort_unless($livestock->farm_id === $farm->id, 404);

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

    public function show(Request $request, Farm $farm, Livestock $livestock, Animal $animal, AnimalHealthTimelineService $timeline): View
    {
        $this->authorize('view', $animal);
        abort_unless($livestock->farm_id === $farm->id && $animal->livestock_id === $livestock->id, 404);

        $healthTimeline = $timeline->forAnimal($animal);

        return view('farmer.animals.show', compact('farm', 'livestock', 'animal', 'healthTimeline'));
    }

    public function edit(Request $request, Farm $farm, Livestock $livestock, Animal $animal): View
    {
        $this->authorize('update', $animal);
        abort_unless($livestock->farm_id === $farm->id && $animal->livestock_id === $livestock->id, 404);

        return view('farmer.animals.edit', compact('farm', 'livestock', 'animal'));
    }

    public function update(UpdateAnimalRequest $request, Farm $farm, Livestock $livestock, Animal $animal): RedirectResponse
    {
        $this->authorize('update', $animal);
        abort_unless($livestock->farm_id === $farm->id && $animal->livestock_id === $livestock->id, 404);

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
        abort_unless($livestock->farm_id === $farm->id && $animal->livestock_id === $livestock->id, 404);

        if ($animal->photo_path) {
            Storage::disk('public')->delete($animal->photo_path);
        }

        $animal->delete();

        return redirect()->route('farmer.farms.livestock.animals.index', [$farm, $livestock])
            ->with('status', __('Animal record removed.'));
    }
}
