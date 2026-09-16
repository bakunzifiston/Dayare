<?php

namespace App\Http\Controllers;

use App\Services\Butcher\ButcherDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherDashboardController extends Controller
{
    private const TIMEZONE = 'Africa/Kigali';

    public function __invoke(Request $request, ButcherDashboardService $dashboard): View
    {
        $user = $request->user();
        $filters = $this->resolveDateFilters($request);
        $payload = $dashboard->build($user, $filters['from'], $filters['to'], $filters);

        return view('butcher.dashboard', array_merge(['user' => $user], $payload));
    }

    /**
     * @return array{
     *     period: string,
     *     from: ?Carbon,
     *     to: ?Carbon,
     *     from_input: string,
     *     to_input: string,
     *     range_label: string,
     *     all_time: bool
     * }
     */
    private function resolveDateFilters(Request $request): array
    {
        $today = now(self::TIMEZONE)->startOfDay();
        $period = trim((string) $request->query('period', 'all'));
        $rawFrom = trim((string) $request->query('from', ''));
        $rawTo = trim((string) $request->query('to', ''));

        $hasCustomDates = $rawFrom !== '' || $rawTo !== '';

        if ($period === 'all' && ! $hasCustomDates) {
            return [
                'period' => 'all',
                'from' => null,
                'to' => null,
                'from_input' => '',
                'to_input' => '',
                'range_label' => __('All time'),
                'all_time' => true,
            ];
        }

        if ($period === '7d' && ! $hasCustomDates) {
            $from = $today->copy()->subDays(6);
            $to = $today->copy();
        } elseif ($period === '30d' && ! $hasCustomDates) {
            $from = $today->copy()->subDays(29);
            $to = $today->copy();
        } elseif ($period === 'month' && ! $hasCustomDates) {
            $from = $today->copy()->startOfMonth();
            $to = $today->copy();
        } elseif ($period === 'today' && ! $hasCustomDates) {
            $from = $today->copy();
            $to = $today->copy();
        } elseif ($hasCustomDates) {
            $from = $this->parseDate($rawFrom, $today->copy()->startOfMonth());
            $to = $this->parseDate($rawTo, $today->copy());
            $period = 'custom';
        } else {
            // Unknown/empty period without dates → all time
            return [
                'period' => 'all',
                'from' => null,
                'to' => null,
                'from_input' => '',
                'to_input' => '',
                'range_label' => __('All time'),
                'all_time' => true,
            ];
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        // Cap absurd custom ranges (max 366 days).
        if ($from->diffInDays($to) > 365) {
            $from = $to->copy()->subDays(365);
        }

        return [
            'period' => $period,
            'from' => $from->copy()->startOfDay(),
            'to' => $to->copy()->startOfDay(),
            'from_input' => $from->toDateString(),
            'to_input' => $to->toDateString(),
            'range_label' => $from->equalTo($to)
                ? $from->isoFormat('D MMM YYYY')
                : $from->isoFormat('D MMM YYYY').' – '.$to->isoFormat('D MMM YYYY'),
            'all_time' => false,
        ];
    }

    private function parseDate(string $value, Carbon $fallback): Carbon
    {
        if ($value === '') {
            return $fallback;
        }

        try {
            return Carbon::parse($value, self::TIMEZONE)->startOfDay();
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
