<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleFarmerBusiness;
use App\Http\Requests\Farmer\StoreBuyerRequest;
use App\Http\Requests\Farmer\UpdateBuyerRequest;
use App\Models\Buyer;
use App\Services\Farmer\BuyerCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuyerController extends Controller
{
    use InteractsWithAccessibleFarmerBusiness;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Buyer::class);

        $query = Buyer::query()
            ->whereIn('business_id', $this->accessibleBusinessIds($request))
            ->latest();

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('buyer_name', 'like', '%'.$search.'%')
                    ->orWhere('buyer_code', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        foreach (['buyer_type', 'status', 'trust_level'] as $filter) {
            if ($value = (string) $request->query($filter, '')) {
                $query->where($filter, $value);
            }
        }

        $records = $query->paginate(20)->withQueryString();

        return view('farmer.sales.buyers.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Buyer::class);
        $businesses = $request->user()
            ->accessibleBusinesses()
            ->where('type', \App\Models\Business::TYPE_FARMER)
            ->orderBy('business_name')
            ->get();

        return view('farmer.sales.buyers.create', compact('businesses'));
    }

    public function store(StoreBuyerRequest $request, BuyerCodeService $codes): RedirectResponse
    {
        $this->authorize('create', Buyer::class);
        abort_unless($this->accessibleBusinessIds($request)->contains((int) $request->validated('business_id')), 403);

        $data = $request->validated();
        $data['buyer_code'] = $codes->generate((int) $data['business_id']);
        $data['created_by'] = $request->user()->id;

        $buyer = Buyer::query()->create($data);

        return redirect()->route('farmer.sales.buyers.show', $buyer)->with('status', __('Buyer created.'));
    }

    public function show(Buyer $buyer): View
    {
        $this->authorize('view', $buyer);
        $buyer->load(['sales' => fn ($q) => $q->latest('sale_date')->limit(10), 'business']);

        return view('farmer.sales.buyers.show', compact('buyer'));
    }

    public function edit(Buyer $buyer): View
    {
        $this->authorize('update', $buyer);

        return view('farmer.sales.buyers.edit', compact('buyer'));
    }

    public function update(UpdateBuyerRequest $request, Buyer $buyer): RedirectResponse
    {
        $this->authorize('update', $buyer);
        $buyer->update($request->validated());

        return redirect()->route('farmer.sales.buyers.show', $buyer)->with('status', __('Buyer updated.'));
    }

    public function destroy(Buyer $buyer): RedirectResponse
    {
        $this->authorize('delete', $buyer);
        $buyer->delete();

        return redirect()->route('farmer.sales.buyers.index')->with('status', __('Buyer archived.'));
    }
}
