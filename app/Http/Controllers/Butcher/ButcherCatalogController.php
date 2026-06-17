<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\StoreButcherPriceRuleRequest;
use App\Http\Requests\Butcher\StoreButcherProductRequest;
use App\Http\Requests\Butcher\UpdateButcherProductRequest;
use App\Models\ButcherPriceRule;
use App\Models\ButcherProduct;
use App\Services\Butcher\ButcherCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            return redirect()->route('butcher.onboarding.index');
        }

        return view('butcher.catalog.index', [
            'business' => $business,
            'summary' => $this->catalog->getCatalogSummary($business),
        ]);
    }

    public function productsIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $products = $business->butcherProducts()
            ->with('cutType')
            ->orderBy('name')
            ->paginate(20);

        return view('butcher.catalog.products.index', [
            'business' => $business,
            'products' => $products,
        ]);
    }

    public function productsCreate(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        return view('butcher.catalog.products.create', [
            'business' => $business,
            'cutTypes' => $business->butcherCutTypes()->where('is_active', true)->orderBy('name')->get(),
            'meatTypes' => ButcherProduct::MEAT_TYPES,
            'units' => ButcherProduct::UNITS,
        ]);
    }

    public function productsStore(StoreButcherProductRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $product = $this->catalog->createProduct($business, $request->validated());

        return redirect()
            ->route('butcher.catalog.products.edit', $product)
            ->with('status', __('Product created.'));
    }

    public function productsEdit(Request $request, ButcherProduct $product): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $product->business_id === (int) $business->id, 404);

        $product->load('cutType');

        return view('butcher.catalog.products.edit', [
            'business' => $business,
            'product' => $product,
            'cutTypes' => $business->butcherCutTypes()->where('is_active', true)->orderBy('name')->get(),
            'meatTypes' => ButcherProduct::MEAT_TYPES,
            'units' => ButcherProduct::UNITS,
        ]);
    }

    public function productsUpdate(UpdateButcherProductRequest $request, ButcherProduct $product): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $product->business_id === (int) $business->id, 404);

        $this->catalog->updateProduct($product, $request->validated());

        return redirect()
            ->route('butcher.catalog.products.edit', $product)
            ->with('status', __('Product updated.'));
    }

    public function pricingIndex(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $priceRules = $business->butcherPriceRules()
            ->with(['product', 'outlet'])
            ->latest('id')
            ->paginate(20);

        return view('butcher.catalog.pricing.index', [
            'business' => $business,
            'priceRules' => $priceRules,
            'products' => $business->butcherProducts()->where('is_active', true)->orderBy('name')->get(),
            'outlets' => $business->butcherOutlets()->orderBy('name')->get(),
            'tiers' => ButcherPriceRule::CUSTOMER_TIERS,
        ]);
    }

    public function pricingStore(StoreButcherPriceRuleRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }

        $this->catalog->setPriceRule($business, $request->validated());

        return redirect()
            ->route('butcher.catalog.pricing.index')
            ->with('status', __('Price rule saved.'));
    }

    public function pricingDestroy(Request $request, ButcherPriceRule $priceRule): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.onboarding.index');
        }
        abort_unless((int) $priceRule->business_id === (int) $business->id, 404);

        $priceRule->delete();

        return redirect()
            ->route('butcher.catalog.pricing.index')
            ->with('status', __('Price rule removed.'));
    }
}
