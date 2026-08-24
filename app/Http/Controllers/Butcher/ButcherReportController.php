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

        return view('butcher.reports.index', array_merge(
            ['business' => $business],
            $this->reports->buildHub($business, $from, $to),
        ));
    }
}
