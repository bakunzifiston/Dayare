<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreMovementPermitRequest;
use App\Models\Farm;
use App\Models\Livestock;
use App\Models\MovementPermit;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MovementPermitController extends Controller
{
    public function index(Request $request): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $permits = MovementPermit::query()
            ->whereIn('farmer_id', $farmerIds)
            ->with(['sourceFarm', 'animals.livestock'])
            ->latest('issue_date')
            ->paginate(20);

        return view('farmer.movement-permits.index', compact('permits'));
    }

    public function create(Request $request): View
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $farms = Farm::query()
            ->whereIn('business_id', $farmerIds)
            ->with(['livestock' => fn ($q) => $q->orderBy('type')])
            ->orderBy('name')
            ->get();

        return view('farmer.movement-permits.create', compact('farms'));
    }

    public function store(StoreMovementPermitRequest $request): RedirectResponse
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $sourceFarm = Farm::query()
            ->whereKey((int) $request->validated('source_farm_id'))
            ->whereIn('business_id', $farmerIds)
            ->firstOrFail();

        $data = $request->validated();
        $filePath = $request->file('file')->store('movement-permits', 'public');

        $permit = DB::transaction(function () use ($data, $sourceFarm, $filePath) {
            $permit = MovementPermit::query()->create([
                'permit_number' => $data['permit_number'],
                'farmer_id' => $sourceFarm->business_id,
                'source_farm_id' => $sourceFarm->id,
                'destination_district_id' => $data['destination_district_id'],
                'destination_sector_id' => $data['destination_sector_id'],
                'destination_cell_id' => $data['destination_cell_id'],
                'destination_village_id' => $data['destination_village_id'],
                'transport_mode' => $data['transport_mode'] ?? null,
                'vehicle_plate' => $data['vehicle_plate'] ?? null,
                'issue_date' => $data['issue_date'],
                'expiry_date' => $data['expiry_date'],
                'issued_by' => $data['issued_by'],
                'file_path' => $filePath,
            ]);

            foreach ($data['animals'] as $row) {
                $livestockId = isset($row['livestock_id']) ? (int) $row['livestock_id'] : null;
                if ($livestockId !== null) {
                    $livestockBelongs = Livestock::query()
                        ->where('farm_id', $sourceFarm->id)
                        ->whereKey($livestockId)
                        ->exists();
                    if (! $livestockBelongs) {
                        throw ValidationException::withMessages([
                            'animals' => [__('Selected livestock does not belong to source farm.')],
                        ]);
                    }
                }

                $permit->animals()->create([
                    'livestock_id' => $livestockId,
                    'animal_identifier' => $row['animal_identifier'] ?? null,
                    'quantity' => $row['quantity'] ?? 1,
                ]);
            }

            return $permit;
        });

        return redirect()
            ->route('farmer.movement-permits.show', $permit)
            ->with('status', __('Movement permit uploaded successfully.'));
    }

    public function show(Request $request, MovementPermit $movementPermit): View
    {
        $this->authorizePermit($request, $movementPermit);
        $movementPermit->load([
            'sourceFarm',
            'destinationDistrict',
            'destinationSector',
            'destinationCell',
            'destinationVillage',
            'animals.livestock',
            'livestockEvents',
        ]);

        $isValid = $movementPermit->isValidOn(Carbon::today());

        return view('farmer.movement-permits.show', compact('movementPermit', 'isValid'));
    }

    public function download(Request $request, MovementPermit $movementPermit)
    {
        $this->authorizePermit($request, $movementPermit);
        abort_unless(Storage::disk('public')->exists($movementPermit->file_path), 404);

        return Storage::disk('public')->download($movementPermit->file_path);
    }

    private function authorizePermit(Request $request, MovementPermit $permit): void
    {
        abort_unless(
            $request->user()->accessibleFarmerBusinessIds()->contains((int) $permit->farmer_id),
            403
        );
    }
}

