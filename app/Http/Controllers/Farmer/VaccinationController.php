<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Requests\Farmer\StoreVaccinationRequest;
use App\Http\Requests\Farmer\UpdateVaccinationRequest;
use App\Models\Vaccination;
use App\Services\Farmer\HealthRecordCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VaccinationController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View|StreamedResponse
    {
        $this->authorize('viewAny', Vaccination::class);

        $animalIds = $this->accessibleAnimalIds($request);
        $query = Vaccination::query()
            ->whereIn('animal_id', $animalIds)
            ->with(['animal.livestock.farm'])
            ->latest('vaccination_date');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('vaccination_code', 'like', '%'.$search.'%')
                    ->orWhere('vaccine_name', 'like', '%'.$search.'%')
                    ->orWhere('veterinarian_name', 'like', '%'.$search.'%');
            });
        }

        foreach (['status'] as $filter) {
            $value = (string) $request->query($filter, '');
            if ($value !== '') {
                $query->where($filter, $value);
            }
        }

        if ($animalId = (int) $request->query('animal_id', 0)) {
            $query->where('animal_id', $animalId);
        }

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($query->get(), [
                'code' => 'vaccination_code',
                'animal' => fn (Vaccination $record) => $record->animal?->animal_code,
                'vaccine' => 'vaccine_name',
                'date' => fn (Vaccination $record) => $record->vaccination_date?->toDateString(),
                'status' => 'status',
            ], 'vaccinations.csv');
        }

        $records = $query->paginate(20)->withQueryString();
        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();

        return view('farmer.health.vaccinations.index', compact('records', 'animals'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Vaccination::class);

        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();
        $selectedAnimalId = (int) $request->query('animal_id', 0);

        return view('farmer.health.vaccinations.create', compact('animals', 'selectedAnimalId'));
    }

    public function store(StoreVaccinationRequest $request, HealthRecordCodeService $codes): RedirectResponse
    {
        $this->authorize('create', Vaccination::class);

        $animal = $this->findAccessibleAnimal($request, (int) $request->validated('animal_id'));
        abort_unless($animal, 404);

        $data = $request->validated();
        unset($data['attachment']);
        $data['vaccination_code'] = $codes->generateVaccinationCode();
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('farm-health', 'public');
        }

        $record = Vaccination::query()->create($data);

        return redirect()->route('farmer.health.vaccinations.show', $record)
            ->with('status', __('Vaccination recorded.'));
    }

    public function show(Vaccination $vaccination): View
    {
        $this->authorize('view', $vaccination);
        $vaccination->load(['animal.livestock.farm', 'creator']);

        return view('farmer.health.vaccinations.show', ['record' => $vaccination]);
    }

    public function edit(Vaccination $vaccination): View
    {
        $this->authorize('update', $vaccination);
        $vaccination->load('animal.livestock.farm');

        return view('farmer.health.vaccinations.edit', ['record' => $vaccination]);
    }

    public function update(UpdateVaccinationRequest $request, Vaccination $vaccination): RedirectResponse
    {
        $this->authorize('update', $vaccination);

        $data = $request->validated();
        unset($data['attachment']);

        if ($request->hasFile('attachment')) {
            if ($vaccination->attachment_path) {
                Storage::disk('public')->delete($vaccination->attachment_path);
            }
            $data['attachment_path'] = $request->file('attachment')->store('farm-health', 'public');
        }

        $vaccination->update($data);

        return redirect()->route('farmer.health.vaccinations.show', $vaccination)
            ->with('status', __('Vaccination updated.'));
    }

    public function destroy(Vaccination $vaccination): RedirectResponse
    {
        $this->authorize('delete', $vaccination);
        $vaccination->delete();

        return redirect()->route('farmer.health.vaccinations.index')
            ->with('status', __('Vaccination archived.'));
    }

    /**
     * @param  iterable<int, Vaccination>  $records
     * @param  array<string, string|callable>  $columns
     */
    private function exportCsv(iterable $records, array $columns, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($records, $columns): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_keys($columns));

            foreach ($records as $record) {
                $row = [];
                foreach ($columns as $key => $column) {
                    $row[] = is_callable($column) ? $column($record) : data_get($record, $column);
                }
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
