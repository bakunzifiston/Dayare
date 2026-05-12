<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Requests\Farmer\StoreVeterinaryVisitRequest;
use App\Http\Requests\Farmer\UpdateVeterinaryVisitRequest;
use App\Models\VeterinaryVisit;
use App\Services\Farmer\HealthRecordCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VeterinaryVisitController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', VeterinaryVisit::class);

        $animalIds = $this->accessibleAnimalIds($request);
        $query = VeterinaryVisit::query()
            ->whereIn('animal_id', $animalIds)
            ->with(['animal.livestock.farm'])
            ->latest('visit_date');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('visit_code', 'like', '%'.$search.'%')
                    ->orWhere('veterinarian_name', 'like', '%'.$search.'%')
                    ->orWhere('clinic_name', 'like', '%'.$search.'%');
            });
        }

        if ($animalId = (int) $request->query('animal_id', 0)) {
            $query->where('animal_id', $animalId);
        }

        $records = $query->paginate(20)->withQueryString();
        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();

        return view('farmer.health.vet-visits.index', compact('records', 'animals'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', VeterinaryVisit::class);

        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();
        $selectedAnimalId = (int) $request->query('animal_id', 0);

        return view('farmer.health.vet-visits.create', compact('animals', 'selectedAnimalId'));
    }

    public function store(StoreVeterinaryVisitRequest $request, HealthRecordCodeService $codes): RedirectResponse
    {
        $this->authorize('create', VeterinaryVisit::class);

        $animal = $this->findAccessibleAnimal($request, (int) $request->validated('animal_id'));
        abort_unless($animal, 404);

        $data = $request->validated();
        unset($data['attachment']);
        $data['visit_code'] = $codes->generateVisitCode();
        $data['created_by'] = $request->user()->id;
        $data['follow_up_required'] = $request->boolean('follow_up_required');

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('farm-health', 'public');
        }

        $record = VeterinaryVisit::query()->create($data);

        return redirect()->route('farmer.health.vet-visits.show', $record)
            ->with('status', __('Veterinary visit recorded.'));
    }

    public function show(VeterinaryVisit $vet_visit): View
    {
        $this->authorize('view', $vet_visit);
        $vet_visit->load(['animal.livestock.farm', 'creator']);

        return view('farmer.health.vet-visits.show', ['record' => $vet_visit]);
    }

    public function edit(VeterinaryVisit $vet_visit): View
    {
        $this->authorize('update', $vet_visit);
        $vet_visit->load('animal.livestock.farm');

        return view('farmer.health.vet-visits.edit', ['record' => $vet_visit]);
    }

    public function update(UpdateVeterinaryVisitRequest $request, VeterinaryVisit $vet_visit): RedirectResponse
    {
        $this->authorize('update', $vet_visit);

        $data = $request->validated();
        unset($data['attachment']);
        $data['follow_up_required'] = $request->boolean('follow_up_required');

        if ($request->hasFile('attachment')) {
            if ($vet_visit->attachment_path) {
                Storage::disk('public')->delete($vet_visit->attachment_path);
            }
            $data['attachment_path'] = $request->file('attachment')->store('farm-health', 'public');
        }

        $vet_visit->update($data);

        return redirect()->route('farmer.health.vet-visits.show', $vet_visit)
            ->with('status', __('Veterinary visit updated.'));
    }

    public function destroy(VeterinaryVisit $vet_visit): RedirectResponse
    {
        $this->authorize('delete', $vet_visit);
        $vet_visit->delete();

        return redirect()->route('farmer.health.vet-visits.index')
            ->with('status', __('Veterinary visit archived.'));
    }
}
