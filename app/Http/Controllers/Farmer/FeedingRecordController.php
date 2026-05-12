<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleFarmerBusiness;
use App\Http\Requests\Farmer\StoreFeedingRecordRequest;
use App\Models\FeedInventory;
use App\Models\FeedingRecord;
use App\Services\Farmer\FeedCodeService;
use App\Services\Farmer\FeedInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FeedingRecordController extends Controller
{
    use InteractsWithAccessibleAnimals;
    use InteractsWithAccessibleFarmerBusiness;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FeedingRecord::class);

        $feedTypeIds = $this->accessibleFeedTypesQuery($request)->pluck('id');
        $query = FeedingRecord::query()
            ->whereIn('feed_type_id', $feedTypeIds)
            ->with(['animal', 'livestock.farm', 'feedType', 'feedInventory'])
            ->latest('feeding_date');

        if ($animalId = (int) $request->query('animal_id', 0)) {
            $query->where('animal_id', $animalId);
        }
        if ($livestockId = (int) $request->query('livestock_id', 0)) {
            $query->where('livestock_id', $livestockId);
        }

        $records = $query->paginate(20)->withQueryString();

        return view('farmer.feeding.records.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FeedingRecord::class);

        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();
        $livestock = $this->accessibleLivestockQuery($request)->orderBy('livestock_name')->get();
        $feedTypes = $this->accessibleFeedTypesQuery($request)->where('status', 'active')->orderBy('feed_name')->get();
        $inventories = FeedInventory::query()
            ->whereIn('feed_type_id', $feedTypes->pluck('id'))
            ->where('status', '!=', FeedInventory::STATUS_EXPIRED)
            ->where('quantity_remaining', '>', 0)
            ->with('feedType')
            ->orderBy('purchase_date')
            ->get();

        return view('farmer.feeding.records.create', compact('animals', 'livestock', 'feedTypes', 'inventories'));
    }

    public function store(StoreFeedingRecordRequest $request, FeedCodeService $codes, FeedInventoryService $inventoryService): RedirectResponse
    {
        $this->authorize('create', FeedingRecord::class);

        $feedType = $this->accessibleFeedTypesQuery($request)->whereKey((int) $request->validated('feed_type_id'))->first();
        abort_unless($feedType, 404);

        $inventory = FeedInventory::query()->whereKey((int) $request->validated('feed_inventory_id'))->first();
        abort_unless($inventory && (int) $inventory->feed_type_id === (int) $feedType->id, 404);

        if ($request->validated('target_type') === 'animal') {
            abort_unless($this->findAccessibleAnimal($request, (int) $request->validated('animal_id')), 404);
        } else {
            abort_unless($this->accessibleLivestockQuery($request)->whereKey((int) $request->validated('livestock_id'))->exists(), 404);
        }

        $record = DB::transaction(function () use ($request, $codes, $inventoryService, $inventory): FeedingRecord {
            $data = $request->validated();
            unset($data['target_type']);
            if ($request->validated('target_type') === 'animal') {
                $data['livestock_id'] = null;
            } else {
                $data['animal_id'] = null;
            }

            $data['feeding_code'] = $codes->generateFeedingCode();
            $data['created_by'] = $request->user()->id;
            $data['water_provided'] = $request->boolean('water_provided');

            $feedingRecord = FeedingRecord::query()->create($data);
            $inventoryService->consumeForFeeding($inventory, $feedingRecord, (float) $data['quantity'], $request->user()->id);

            return $feedingRecord;
        });

        return redirect()->route('farmer.feeding.records.show', $record)->with('status', __('Feeding activity recorded.'));
    }

    public function show(FeedingRecord $record): View
    {
        $this->authorize('view', $record);
        $record->load(['animal', 'livestock.farm', 'feedType', 'feedInventory']);

        return view('farmer.feeding.records.show', compact('record'));
    }
}
