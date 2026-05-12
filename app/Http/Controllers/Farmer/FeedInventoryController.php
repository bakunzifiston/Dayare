<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleFarmerBusiness;
use App\Http\Requests\Farmer\StoreFeedInventoryRequest;
use App\Models\FeedInventory;
use App\Services\Farmer\FeedCodeService;
use App\Services\Farmer\FeedInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedInventoryController extends Controller
{
    use InteractsWithAccessibleFarmerBusiness;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FeedInventory::class);

        $feedTypeIds = $this->accessibleFeedTypesQuery($request)->pluck('id');
        $query = FeedInventory::query()->whereIn('feed_type_id', $feedTypeIds)->with(['feedType', 'supplier'])->latest('purchase_date');

        if ($status = (string) $request->query('status', '')) {
            $query->where('status', $status);
        }

        $records = $query->paginate(20)->withQueryString();
        $feedTypes = $this->accessibleFeedTypesQuery($request)->where('status', 'active')->orderBy('feed_name')->get();

        return view('farmer.feeding.inventory.index', compact('records', 'feedTypes'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FeedInventory::class);

        $feedTypes = $this->accessibleFeedTypesQuery($request)->where('status', 'active')->orderBy('feed_name')->get();
        $suppliers = \App\Models\FeedSupplier::query()->whereIn('business_id', $this->accessibleBusinessIds($request))->where('status', 'active')->orderBy('supplier_name')->get();

        return view('farmer.feeding.inventory.create', compact('feedTypes', 'suppliers'));
    }

    public function store(StoreFeedInventoryRequest $request, FeedCodeService $codes, FeedInventoryService $inventoryService): RedirectResponse
    {
        $this->authorize('create', FeedInventory::class);

        $feedType = $this->accessibleFeedTypesQuery($request)->whereKey((int) $request->validated('feed_type_id'))->first();
        abort_unless($feedType, 404);

        $data = $request->validated();
        $quantity = (float) $data['quantity_received'];
        $unitCost = isset($data['unit_cost']) ? (float) $data['unit_cost'] : null;

        $data['inventory_code'] = $codes->generateInventoryCode();
        $data['quantity_remaining'] = $quantity;
        $data['total_cost'] = $unitCost !== null ? round($quantity * $unitCost, 2) : null;
        $data['created_by'] = $request->user()->id;

        $record = FeedInventory::query()->create($data);
        $record->syncStatus();
        $inventoryService->receiveStock($record, $request->user()->id);

        return redirect()->route('farmer.feeding.inventory.show', $record)->with('status', __('Inventory batch recorded.'));
    }

    public function show(FeedInventory $inventory): View
    {
        $this->authorize('view', $inventory);
        $inventory->load(['feedType', 'supplier', 'movements' => fn ($query) => $query->latest()->limit(20)]);

        return view('farmer.feeding.inventory.show', ['record' => $inventory]);
    }
}
