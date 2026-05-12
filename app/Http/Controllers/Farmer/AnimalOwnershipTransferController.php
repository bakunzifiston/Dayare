<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Models\AnimalOwnershipTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnimalOwnershipTransferController extends Controller
{
    use InteractsWithAccessibleAnimals;

    public function index(Request $request): View
    {
        $animalIds = $this->accessibleAnimalIds($request);
        $records = AnimalOwnershipTransfer::query()
            ->whereIn('animal_id', $animalIds)
            ->with('animal')
            ->latest('transfer_date')
            ->paginate(20);

        return view('farmer.animal-certificates.transfers.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();

        return view('farmer.animal-certificates.transfers.create', compact('animals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'animal_id' => ['required', 'integer', 'exists:animals,id'],
            'previous_owner' => ['required', 'string', 'max:255'],
            'new_owner' => ['required', 'string', 'max:255'],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'transfer_reason' => ['nullable', 'string', 'max:255'],
            'approved_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        abort_unless($this->findAccessibleAnimal($request, (int) $data['animal_id']), 404);
        $data['created_by'] = $request->user()->id;

        AnimalOwnershipTransfer::query()->create($data);

        return redirect()->route('farmer.certificates.ownership-transfers.index')
            ->with('status', __('Ownership transfer recorded.'));
    }
}
