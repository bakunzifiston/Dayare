<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreLivestockMovementRequest;
use App\Models\Farm;
use App\Models\Livestock;
use App\Models\LivestockEvent;
use App\Models\MovementPermit;
use App\Services\Farmer\MovementPermitValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LivestockMovementController extends Controller
{
    public function __construct(
        private MovementPermitValidationService $permitValidationService
    ) {}

    public function store(StoreLivestockMovementRequest $request, Farm $farm): RedirectResponse
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        abort_unless($farmerIds->contains((int) $farm->business_id), 403);

        $data = $request->validated();
        $sourceFarmId = (int) $data['source_farm_id'];
        abort_unless($sourceFarmId === (int) $farm->id, 403);

        $destinationFarm = Farm::query()
            ->whereKey((int) $data['destination_farm_id'])
            ->whereIn('business_id', $farmerIds)
            ->firstOrFail();

        $permit = MovementPermit::query()
            ->whereKey((int) $data['movement_permit_id'])
            ->firstOrFail();

        DB::transaction(function () use ($farm, $destinationFarm, $permit, $data) {
            /** @var Livestock $sourceLivestock */
            $sourceLivestock = Livestock::query()
                ->whereKey((int) $data['livestock_id'])
                ->where('farm_id', $farm->id)
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int) $data['quantity'];
            if ($sourceLivestock->available_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => [__('Insufficient available animals for movement.')],
                ]);
            }

            $this->permitValidationService->assertValidForMovement(
                $permit,
                (int) $farm->business_id,
                (int) $farm->id,
                $quantity,
                $sourceLivestock
            );

            /** @var Livestock $destinationLivestock */
            $destinationLivestock = Livestock::query()
                ->where('farm_id', $destinationFarm->id)
                ->where('type', $sourceLivestock->type)
                ->where('breed', $sourceLivestock->breed)
                ->lockForUpdate()
                ->first();

            if ($destinationLivestock === null) {
                $destinationLivestock = Livestock::query()->create([
                    'farm_id' => $destinationFarm->id,
                    'type' => $sourceLivestock->type,
                    'breed' => $sourceLivestock->breed,
                    'feeding_type' => $sourceLivestock->feeding_type,
                    'total_quantity' => 0,
                    'available_quantity' => 0,
                    'base_price' => $sourceLivestock->base_price,
                    'health_status' => $sourceLivestock->health_status,
                    'healthy_quantity' => 0,
                    'sick_quantity' => 0,
                ]);
            }

            $sourceLivestock->decrement('available_quantity', $quantity);
            $sourceLivestock->decrement('healthy_quantity', $quantity);
            $sourceLivestock->decrement('total_quantity', $quantity);

            $destinationLivestock->increment('available_quantity', $quantity);
            $destinationLivestock->increment('healthy_quantity', $quantity);
            $destinationLivestock->increment('total_quantity', $quantity);

            LivestockEvent::query()->create([
                'farm_id' => $farm->id,
                'livestock_id' => $sourceLivestock->id,
                'movement_permit_id' => $permit->id,
                'event_type' => LivestockEvent::TYPE_MOVEMENT,
                'quantity' => $quantity,
                'event_date' => $data['movement_date'],
                'notes' => __('Livestock moved from :source to :destination. Reason: :reason', [
                    'source' => $farm->name,
                    'destination' => $destinationFarm->name,
                    'reason' => $data['reason'],
                ]),
                'metadata' => [
                    'destination_farm_id' => $destinationFarm->id,
                    'reason' => $data['reason'],
                    'vehicle_plate' => $permit->vehicle_plate,
                ],
            ]);
        });

        return redirect()
            ->route('farmer.farms.livestock.index', $farm)
            ->with('status', __('Livestock movement recorded with permit.'));
    }
}

