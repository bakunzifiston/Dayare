<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleFarmerBusiness;
use App\Http\Requests\Farmer\StoreFeedSupplierRequest;
use App\Models\FeedSupplier;
use App\Services\Farmer\FeedCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedSupplierController extends Controller
{
    use InteractsWithAccessibleFarmerBusiness;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FeedSupplier::class);

        $records = FeedSupplier::query()
            ->whereIn('business_id', $this->accessibleBusinessIds($request))
            ->latest()
            ->paginate(20);

        return view('farmer.feeding.suppliers.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FeedSupplier::class);
        $businesses = $this->accessibleBusinessIds($request);
        $feedTypes = $this->accessibleFeedTypesQuery($request)->orderBy('feed_name')->get();

        return view('farmer.feeding.suppliers.create', compact('businesses', 'feedTypes'));
    }

    public function store(StoreFeedSupplierRequest $request, FeedCodeService $codes): RedirectResponse
    {
        $this->authorize('create', FeedSupplier::class);
        abort_unless($this->accessibleBusinessIds($request)->contains((int) $request->validated('business_id')), 403);

        $data = $request->validated();
        $data['supplier_code'] = $codes->generateSupplierCode((int) $data['business_id']);
        $data['created_by'] = $request->user()->id;

        $record = FeedSupplier::query()->create($data);

        return redirect()->route('farmer.feeding.suppliers.show', $record)->with('status', __('Supplier created.'));
    }

    public function show(FeedSupplier $supplier): View
    {
        $this->authorize('view', $supplier);
        $supplier->load(['inventories.feedType']);

        return view('farmer.feeding.suppliers.show', ['record' => $supplier]);
    }
}
