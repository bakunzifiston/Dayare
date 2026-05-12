<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Requests\Farmer\StoreMortalityRecordRequest;
use App\Http\Requests\Farmer\UpdateMortalityRecordRequest;
use App\Models\Animal;
use App\Models\MortalityRecord;
use App\Services\Farmer\HealthRecordCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MortalityRecordController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MortalityRecord::class);

        $animalIds = $this->accessibleAnimalIds($request);
        $query = MortalityRecord::query()
            ->whereIn('animal_id', $animalIds)
            ->with(['animal.livestock.farm'])
            ->latest('death_date');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('mortality_code', 'like', '%'.$search.'%')
                    ->orWhere('cause_of_death', 'like', '%'.$search.'%');
            });
        }

        if ($animalId = (int) $request->query('animal_id', 0)) {
            $query->where('animal_id', $animalId);
        }

        $records = $query->paginate(20)->withQueryString();
        $animals = $this->accessibleAnimalsQuery($request)
            ->where('lifecycle_status', '!=', Animal::LIFECYCLE_DEAD)
            ->orderBy('animal_code')
            ->get();

        return view('farmer.health.mortalities.index', compact('records', 'animals'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', MortalityRecord::class);

        $animals = $this->accessibleAnimalsQuery($request)
            ->where('lifecycle_status', '!=', Animal::LIFECYCLE_DEAD)
            ->orderBy('animal_code')
            ->get();
        $selectedAnimalId = (int) $request->query('animal_id', 0);

        return view('farmer.health.mortalities.create', compact('animals', 'selectedAnimalId'));
    }

    public function store(StoreMortalityRecordRequest $request, HealthRecordCodeService $codes): RedirectResponse
    {
        $this->authorize('create', MortalityRecord::class);

        $animal = $this->findAccessibleAnimal($request, (int) $request->validated('animal_id'));
        abort_unless($animal, 404);

        $data = $request->validated();
        unset($data['attachment']);
        $data['mortality_code'] = $codes->generateMortalityCode();
        $data['created_by'] = $request->user()->id;
        $data['postmortem_done'] = $request->boolean('postmortem_done');

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('farm-health', 'public');
        }

        $record = MortalityRecord::query()->create($data);

        $animal->update([
            'lifecycle_status' => Animal::LIFECYCLE_DEAD,
            'health_status' => Animal::HEALTH_SICK,
        ]);

        return redirect()->route('farmer.health.mortalities.show', $record)
            ->with('status', __('Mortality record created. Animal lifecycle updated.'));
    }

    public function show(MortalityRecord $mortality): View
    {
        $this->authorize('view', $mortality);
        $mortality->load(['animal.livestock.farm', 'creator']);

        return view('farmer.health.mortalities.show', ['record' => $mortality]);
    }

    public function edit(MortalityRecord $mortality): View
    {
        $this->authorize('update', $mortality);
        $mortality->load('animal.livestock.farm');

        return view('farmer.health.mortalities.edit', ['record' => $mortality]);
    }

    public function update(UpdateMortalityRecordRequest $request, MortalityRecord $mortality): RedirectResponse
    {
        $this->authorize('update', $mortality);

        $data = $request->validated();
        unset($data['attachment']);
        $data['postmortem_done'] = $request->boolean('postmortem_done');

        if ($request->hasFile('attachment')) {
            if ($mortality->attachment_path) {
                Storage::disk('public')->delete($mortality->attachment_path);
            }
            $data['attachment_path'] = $request->file('attachment')->store('farm-health', 'public');
        }

        $mortality->update($data);

        return redirect()->route('farmer.health.mortalities.show', $mortality)
            ->with('status', __('Mortality record updated.'));
    }

    public function destroy(MortalityRecord $mortality): RedirectResponse
    {
        $this->authorize('delete', $mortality);
        $mortality->delete();

        return redirect()->route('farmer.health.mortalities.index')
            ->with('status', __('Mortality record archived.'));
    }
}
