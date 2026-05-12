<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Requests\Farmer\StoreTreatmentRequest;
use App\Http\Requests\Farmer\UpdateTreatmentRequest;
use App\Models\Animal;
use App\Models\Treatment;
use App\Services\Farmer\HealthRecordCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Treatment::class);

        $animalIds = $this->accessibleAnimalIds($request);
        $query = Treatment::query()
            ->whereIn('animal_id', $animalIds)
            ->with(['animal.livestock.farm'])
            ->latest('treatment_start_date');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('treatment_code', 'like', '%'.$search.'%')
                    ->orWhere('disease_name', 'like', '%'.$search.'%')
                    ->orWhere('medicine_name', 'like', '%'.$search.'%');
            });
        }

        if ($status = (string) $request->query('status', '')) {
            $query->where('status', $status);
        }

        if ($animalId = (int) $request->query('animal_id', 0)) {
            $query->where('animal_id', $animalId);
        }

        $records = $query->paginate(20)->withQueryString();
        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();

        return view('farmer.health.treatments.index', compact('records', 'animals'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Treatment::class);

        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();
        $selectedAnimalId = (int) $request->query('animal_id', 0);

        return view('farmer.health.treatments.create', compact('animals', 'selectedAnimalId'));
    }

    public function store(StoreTreatmentRequest $request, HealthRecordCodeService $codes): RedirectResponse
    {
        $this->authorize('create', Treatment::class);

        $animal = $this->findAccessibleAnimal($request, (int) $request->validated('animal_id'));
        abort_unless($animal, 404);

        $data = $request->validated();
        unset($data['attachment']);
        $data['treatment_code'] = $codes->generateTreatmentCode();
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('farm-health', 'public');
        }

        $record = Treatment::query()->create($data);

        if ($record->status === Treatment::STATUS_ONGOING) {
            $animal->update(['health_status' => Animal::HEALTH_UNDER_TREATMENT]);
        }

        return redirect()->route('farmer.health.treatments.show', $record)
            ->with('status', __('Treatment recorded.'));
    }

    public function show(Treatment $treatment): View
    {
        $this->authorize('view', $treatment);
        $treatment->load(['animal.livestock.farm', 'creator']);

        return view('farmer.health.treatments.show', ['record' => $treatment]);
    }

    public function edit(Treatment $treatment): View
    {
        $this->authorize('update', $treatment);
        $treatment->load('animal.livestock.farm');

        return view('farmer.health.treatments.edit', ['record' => $treatment]);
    }

    public function update(UpdateTreatmentRequest $request, Treatment $treatment): RedirectResponse
    {
        $this->authorize('update', $treatment);

        $data = $request->validated();
        unset($data['attachment']);

        if ($request->hasFile('attachment')) {
            if ($treatment->attachment_path) {
                Storage::disk('public')->delete($treatment->attachment_path);
            }
            $data['attachment_path'] = $request->file('attachment')->store('farm-health', 'public');
        }

        $treatment->update($data);

        return redirect()->route('farmer.health.treatments.show', $treatment)
            ->with('status', __('Treatment updated.'));
    }

    public function destroy(Treatment $treatment): RedirectResponse
    {
        $this->authorize('delete', $treatment);
        $treatment->delete();

        return redirect()->route('farmer.health.treatments.index')
            ->with('status', __('Treatment archived.'));
    }
}
