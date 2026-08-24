<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\CompleteButcherStockCountRequest;
use App\Http\Requests\Butcher\StoreButcherStockCountRequest;
use App\Http\Requests\Butcher\UpdateButcherStockCountLinesRequest;
use App\Models\ButcherOutlet;
use App\Models\ButcherStockCount;
use App\Services\Butcher\ButcherOnboardingService;
use App\Services\Butcher\ButcherStockCountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherStockCountController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherStockCountService $stockCounts,
        private readonly ButcherOnboardingService $onboarding,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $counts = $business->butcherStockCounts()
            ->with(['outlet', 'countedByUser'])
            ->withCount('lines')
            ->latest('count_date')
            ->latest('id')
            ->paginate(15);

        return view('butcher.stock-counts.index', [
            'business' => $business,
            'counts' => $counts,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        return view('butcher.stock-counts.create', [
            'business' => $business,
            'outlets' => $business->butcherOutlets()
                ->where('status', ButcherOutlet::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreButcherStockCountRequest $request): RedirectResponse
    {
        $business = $this->onboarding->resolveButcherBusiness($request->user());
        $count = $this->stockCounts->startCount($business, $request->validated(), $request->user());

        return redirect()
            ->route('butcher.stock-counts.show', $count)
            ->with('status', __('Stock count :number started.', ['number' => $count->count_number]));
    }

    public function show(Request $request, ButcherStockCount $stockCount): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $stockCount->business_id === (int) $business->id, 404);

        $stockCount->load(['lines.batch.outlet', 'outlet', 'countedByUser']);

        return view('butcher.stock-counts.show', [
            'business' => $business,
            'count' => $stockCount,
        ]);
    }

    public function updateLines(UpdateButcherStockCountLinesRequest $request, ButcherStockCount $stockCount): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $stockCount->business_id === (int) $business->id, 404);

        $this->stockCounts->updateLines($stockCount, $request->validated('lines'));

        return redirect()
            ->route('butcher.stock-counts.show', $stockCount)
            ->with('status', __('Counted weights saved.'));
    }

    public function complete(CompleteButcherStockCountRequest $request, ButcherStockCount $stockCount): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }
        abort_unless((int) $stockCount->business_id === (int) $business->id, 404);

        $this->stockCounts->completeCount(
            $stockCount,
            $request->user(),
            (bool) $request->validated('apply_variances', false),
        );

        return redirect()
            ->route('butcher.stock-counts.show', $stockCount)
            ->with('status', __('Stock count completed.'));
    }
}
