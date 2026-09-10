<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Services\Butcher\ButcherReportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherReportController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function __construct(
        private readonly ButcherReportService $reports,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();
        $outletId = $this->requestedOutletId($request, $business);

        return view('butcher.reports.index', array_merge(
            [
                'business' => $business,
                'outlets' => $business->butcherOutlets()->orderBy('name')->get(),
                'filterOutletId' => $outletId,
            ],
            $this->reports->buildHub($business, $from, $to, $outletId),
        ));
    }

    public function traceability(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $result = $this->reports->resolveTraceability(
            $business,
            $request->query('q')
        );

        return view('butcher.reports.traceability', [
            'business' => $business,
            'result' => $result,
        ]);
    }

    public function complianceOverrides(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : null;
        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : null;

        return view('butcher.reports.compliance-overrides', [
            'business' => $business,
            'report' => $this->reports->complianceOverridesReport($business, $from, $to),
            'from' => $from?->toDateString() ?? '',
            'to' => $to?->toDateString() ?? '',
        ]);
    }
}
