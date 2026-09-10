<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherPriceRuleRequest;
use App\Http\Requests\Butcher\StoreButcherProductRequest;
use App\Http\Requests\Butcher\UpdateButcherPriceRuleRequest;
use App\Http\Requests\Butcher\UpdateButcherProductRequest;
use App\Models\ButcherPriceRule;
use App\Models\ButcherProduct;
use App\Services\Butcher\ButcherCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ButcherCatalogController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherCatalogService $catalog,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $query = $business->butcherProducts()->with(['cutType', 'priceRules']);

        $status = (string) $request->query('status', 'all');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('meat_type', 'like', '%'.$search.'%');
            });
        }

        $products = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('butcher.catalog.index', [
            'business' => $business,
            'products' => $products,
            'status' => $status,
            'search' => $search,
            'canManage' => $request->user()?->canButcherPermission(
                \App\Models\BusinessUser::PERMISSION_MANAGE_BUTCHER_CATALOG,
                $business->id
            ),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        return view('butcher.catalog.products.create', [
            'business' => $business,
            'cutTypes' => $business->butcherCutTypes()->where('is_active', true)->orderBy('name')->get(),
            'units' => ButcherProduct::UNITS,
            'meatTypes' => ButcherProduct::MEAT_TYPES,
        ]);
    }

    public function store(StoreButcherProductRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null, 404);

        try {
            $product = $this->catalog->createProduct($business, $request->validated());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('butcher.catalog.products.show', $product)
            ->with('status', __('Product created. Add a retail price rule before activating for POS.'));
    }

    public function show(Request $request, ButcherProduct $product): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $product->business_id === (int) $business->id, 404);

        $product->load(['cutType', 'priceRules.outlet']);

        return view('butcher.catalog.products.show', [
            'business' => $business,
            'product' => $product,
            'avgCost' => (float) $product->avg_cost_per_kg,
            'hasRetailRule' => $this->catalog->hasActiveRetailPriceRule($product),
            'canManage' => $request->user()?->canButcherPermission(
                \App\Models\BusinessUser::PERMISSION_MANAGE_BUTCHER_CATALOG,
                $business->id
            ),
        ]);
    }

    public function edit(Request $request, ButcherProduct $product): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $product->business_id === (int) $business->id, 404);

        return view('butcher.catalog.products.edit', [
            'business' => $business,
            'product' => $product,
            'cutTypes' => $business->butcherCutTypes()->where('is_active', true)->orderBy('name')->get(),
            'units' => ButcherProduct::UNITS,
            'meatTypes' => ButcherProduct::MEAT_TYPES,
            'hasRetailRule' => $this->catalog->hasActiveRetailPriceRule($product),
        ]);
    }

    public function update(UpdateButcherProductRequest $request, ButcherProduct $product): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null && (int) $product->business_id === (int) $business->id, 404);

        try {
            $this->catalog->updateProduct($product, $request->validated());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('butcher.catalog.products.show', $product)
            ->with('status', __('Product updated.'));
    }

    public function priceRulesCreate(Request $request, ButcherProduct $product): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $product->business_id === (int) $business->id, 404);

        return view('butcher.catalog.price-rules.create', [
            'business' => $business,
            'product' => $product,
            'outlets' => $business->butcherOutlets()->orderBy('name')->get(),
            'tiers' => ButcherPriceRule::CUSTOMER_TIERS,
            'avgCost' => (float) $product->avg_cost_per_kg,
        ]);
    }

    public function priceRulesStore(StoreButcherPriceRuleRequest $request, ButcherProduct $product): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null && (int) $product->business_id === (int) $business->id, 404);

        $data = $request->validated();
        $data['product_id'] = $product->id;

        $this->catalog->setPriceRule($business, $data);

        return redirect()
            ->route('butcher.catalog.products.show', $product)
            ->with('status', __('Price rule added.'));
    }

    public function priceRulesEdit(Request $request, ButcherProduct $product, ButcherPriceRule $priceRule): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $product->business_id === (int) $business->id, 404);
        abort_unless((int) $priceRule->product_id === (int) $product->id, 404);
        abort_unless((int) $priceRule->business_id === (int) $business->id, 404);

        return view('butcher.catalog.price-rules.edit', [
            'business' => $business,
            'product' => $product,
            'priceRule' => $priceRule,
            'outlets' => $business->butcherOutlets()->orderBy('name')->get(),
            'tiers' => ButcherPriceRule::CUSTOMER_TIERS,
            'avgCost' => (float) $product->avg_cost_per_kg,
        ]);
    }

    public function priceRulesUpdate(
        UpdateButcherPriceRuleRequest $request,
        ButcherProduct $product,
        ButcherPriceRule $priceRule,
    ): RedirectResponse {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null && (int) $product->business_id === (int) $business->id, 404);
        abort_unless((int) $priceRule->product_id === (int) $product->id, 404);

        try {
            $this->catalog->updatePriceRule($priceRule, $request->validated());
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('butcher.catalog.products.show', $product)
            ->with('status', __('Price rule updated.'));
    }
}
