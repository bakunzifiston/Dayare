<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Requests\Farmer\StoreDiseaseRecordRequest;
use App\Http\Requests\Farmer\UpdateDiseaseRecordRequest;
use App\Models\Animal;
use App\Models\DiseaseRecord;
use App\Services\Farmer\HealthRecordCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DiseaseRecordController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DiseaseRecord::class);

        $animalIds = $this->accessibleAnimalIds($request);
        $query = DiseaseRecord::query()
            ->whereIn('animal_id', $animalIds)
            ->with(['animal.livestock.farm'])
            ->latest('diagnosis_date');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('disease_name', 'like', '%'.$search.'%');
        }

        foreach (['severity_level', 'recovery_status'] as $filter) {
            $value = (string) $request->query($filter, '');
            if ($value !== '') {
                $query->where($filter, $value);
            }
        }

        if ($animalId = (int) $request->query('animal_id', 0)) {
            $query->where('animal_id', $animalId);
        }

        $records = $query->paginate(20)->withQueryString();
        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();

        return view('farmer.health.diseases.index', compact('records', 'animals'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', DiseaseRecord::class);

        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();
        $selectedAnimalId = (int) $request->query('animal_id', 0);

        return view('farmer.health.diseases.create', compact('animals', 'selectedAnimalId'));
    }

    public function store(StoreDiseaseRecordRequest $request, HealthRecordCodeService $codes): RedirectResponse
    {
        $this->authorize('create', DiseaseRecord::class);

        $animal = $this->findAccessibleAnimal($request, (int) $request->validated('animal_id'));
        abort_unless($animal, 404);

        $data = $request->validated();
        unset($data['attachment']);
        $data['disease_code'] = $codes->generateDiseaseCode();
        $data['created_by'] = $request->user()->id;
        $data['quarantine_required'] = $request->boolean('quarantine_required');

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('farm-health', 'public');
        }

        $record = DiseaseRecord::query()->create($data);

        if ($record->quarantine_required) {
            $animal->update(['health_status' => Animal::HEALTH_QUARANTINED]);
        } elseif ($record->recovery_status === DiseaseRecord::RECOVERY_RECOVERING) {
            $animal->update(['health_status' => Animal::HEALTH_SICK]);
        }

        return redirect()->route('farmer.health.diseases.show', $record)
            ->with('status', __('Disease record created.'));
    }

    public function show(DiseaseRecord $disease): View
    {
        $this->authorize('view', $disease);
        $disease->load(['animal.livestock.farm', 'creator']);

        return view('farmer.health.diseases.show', ['record' => $disease]);
    }

    public function edit(DiseaseRecord $disease): View
    {
        $this->authorize('update', $disease);
        $disease->load('animal.livestock.farm');

        return view('farmer.health.diseases.edit', ['record' => $disease]);
    }

    public function update(UpdateDiseaseRecordRequest $request, DiseaseRecord $disease): RedirectResponse
    {
        $this->authorize('update', $disease);

        $data = $request->validated();
        unset($data['attachment']);
        $data['quarantine_required'] = $request->boolean('quarantine_required');

        if ($request->hasFile('attachment')) {
            if ($disease->attachment_path) {
                Storage::disk('public')->delete($disease->attachment_path);
            }
            $data['attachment_path'] = $request->file('attachment')->store('farm-health', 'public');
        }

        $disease->update($data);

        return redirect()->route('farmer.health.diseases.show', $disease)
            ->with('status', __('Disease record updated.'));
    }

    public function destroy(DiseaseRecord $disease): RedirectResponse
    {
        $this->authorize('delete', $disease);
        $disease->delete();

        return redirect()->route('farmer.health.diseases.index')
            ->with('status', __('Disease record archived.'));
    }
}
