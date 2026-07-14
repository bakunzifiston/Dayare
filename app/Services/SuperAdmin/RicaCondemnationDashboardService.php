<?php

namespace App\Services\SuperAdmin;

use App\Models\AdministrativeDivision;
use App\Models\Facility;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\RicaSetting;
use App\Support\RicaDiseaseLabelResolver;
use App\Support\TenantEnvironmentScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RicaCondemnationDashboardService
{
  public function __construct(
    private readonly SuperAdminSlaughterDashboardService $slaughterDashboard,
  ) {}

  /**
   * @return array<string, mixed>
   */
  public function build(Request $request): array
  {
    $filters = $this->slaughterDashboard->resolveHubFilters($request);
    $selectedDistrictId = $this->normalizeDistrictId($request->query('district_id'));
    $rows = $this->condemnationRows($filters, $selectedDistrictId);
    $approvedKg = $this->approvedKg($filters, $selectedDistrictId);
    $previousRows = $this->condemnationRows($this->previousPeriodFilters($filters), $selectedDistrictId);
    $previousApprovedKg = $this->approvedKg($this->previousPeriodFilters($filters), $selectedDistrictId);

    return [
      'filters' => $filters,
      'districtOptions' => $this->districtOptions(),
      'selectedDistrictId' => $selectedDistrictId,
      'kpis' => $this->kpis($rows, $previousRows, $approvedKg, $previousApprovedKg),
      'chartSpecs' => $this->chartSpecs($rows, $filters),
      'slaughterhouseRows' => $this->slaughterhouseRows($rows, $approvedKg, $filters, $selectedDistrictId),
      'economicLossRows' => $this->economicLossRows($rows),
    ];
  }

  /**
   * @return list<array{
   *     kg: float,
   *     species: string,
   *     seized_part: string,
   *     reason: string,
   *     facility_id: int|null,
   *     facility_name: string,
   *     inspection_date: Carbon,
   * }>
   */
  private function condemnationRows(array $filters, ?int $districtId): array
  {
    $itemRows = PostMortemInspectionItem::query()
      ->condemned()
      ->with([
        'intakeItem',
        'inspection.batch.slaughterExecution.slaughterPlan.facility',
      ])
      ->whereHas('inspection', fn (Builder $query) => $this->applyInspectionScope($query, $filters, $districtId))
      ->get()
      ->map(function (PostMortemInspectionItem $item): array {
        $facility = $item->inspection?->batch?->slaughterExecution?->slaughterPlan?->facility;

        return [
          'kg' => round((float) ($item->condemned_weight_kg ?? $item->carcass_weight_kg ?? 0), 2),
          'species' => $this->normalizeSpecies($item->intakeItem?->species ?? $item->inspection?->species),
          'seized_part' => $this->normalizeOrgan($item->seized_part),
          'reason' => $this->normalizeReason($item->reason),
          'facility_id' => $facility?->id,
          'facility_name' => $facility?->facility_name ?? __('Unknown slaughterhouse'),
          'inspection_date' => $item->inspection?->inspection_date ?? now(),
        ];
      });

    $legacyRows = PostMortemInspection::query()
      ->with(['batch.slaughterExecution.slaughterPlan.facility', 'observations'])
      ->where('condemned_quantity', '>', 0)
      ->whereDoesntHave('inspectionItems')
      ->where(function (Builder $query) use ($filters, $districtId): void {
        $this->applyInspectionScope($query, $filters, $districtId);
      })
      ->get()
      ->map(function (PostMortemInspection $inspection): array {
        $facility = $inspection->batch?->slaughterExecution?->slaughterPlan?->facility;
        $observations = $inspection->observations->whereNull('animal_intake_item_id');

        return [
          'kg' => round((float) $inspection->condemned_quantity, 2),
          'species' => $this->normalizeSpecies($inspection->species),
          'seized_part' => $this->normalizeOrgan(
            $observations->pluck('item')->filter()->first() ?: __('Whole carcass'),
          ),
          'reason' => $this->normalizeReason(
            $observations->pluck('notes')->filter()->first() ?: $inspection->notes,
          ),
          'facility_id' => $facility?->id,
          'facility_name' => $facility?->facility_name ?? __('Unknown slaughterhouse'),
          'inspection_date' => $inspection->inspection_date ?? now(),
        ];
      });

    return $itemRows
      ->merge($legacyRows)
      ->filter(fn (array $row): bool => $row['kg'] > 0)
      ->values()
      ->all();
  }

  private function approvedKg(array $filters, ?int $districtId): float
  {
    $itemKg = (float) PostMortemInspectionItem::query()
      ->approved()
      ->with(['batchItem'])
      ->whereHas('inspection', fn (Builder $query) => $this->applyInspectionScope($query, $filters, $districtId))
      ->get()
      ->sum(fn (PostMortemInspectionItem $item): float => $this->approvedMeatKgForItem($item));

    $legacyKg = (float) PostMortemInspection::query()
      ->where('approved_quantity', '>', 0)
      ->whereDoesntHave('inspectionItems')
      ->where(function (Builder $query) use ($filters, $districtId): void {
        $this->applyInspectionScope($query, $filters, $districtId);
      })
      ->sum('approved_quantity');

    return round($itemKg + $legacyKg, 2);
  }

  private function approvedMeatKgForItem(PostMortemInspectionItem $item): float
  {
    $beforeKg = (float) ($item->batchItem?->meat_quantity_kg ?? 0);
    $carcassKg = (float) ($item->carcass_weight_kg ?? 0);
    $carcassPart = $carcassKg > 0 ? $carcassKg : $beforeKg;
    $otherPart = $carcassKg > 0 && $beforeKg > $carcassKg ? $beforeKg - $carcassKg : 0.0;

    return $carcassPart + $otherPart;
  }

  private function lossPerKgRwf(): float
  {
    return RicaSetting::condemnationLossPerKgRwf();
  }

  /**
   * @param  Builder<PostMortemInspection>  $query
   */
  private function applyInspectionScope(Builder $query, array $filters, ?int $districtId): void
  {
    $query->whereHas(
      'batch.slaughterExecution.slaughterPlan.facility',
      function (Builder $facilityQuery) use ($districtId): void {
        TenantEnvironmentScope::applyToFacilities($facilityQuery);

        if ($districtId !== null) {
          $facilityQuery->where('district_id', $districtId);
        }
      },
    );

    if ($filters['is_filtered'] && $filters['start'] !== null && $filters['end'] !== null) {
      $query->whereBetween('inspection_date', [
        $filters['start']->copy()->startOfDay()->toDateString(),
        $filters['end']->copy()->endOfDay()->toDateString(),
      ]);
    }
  }

  /**
   * @param  list<array<string, mixed>>  $rows
   * @param  list<array<string, mixed>>  $previousRows
   * @return array<string, mixed>
   */
  private function kpis(array $rows, array $previousRows, float $approvedKg, float $previousApprovedKg): array
  {
    $rejectedKg = round(collect($rows)->sum('kg'), 2);
    $previousRejectedKg = round(collect($previousRows)->sum('kg'), 2);
    $cases = count($rows);
    $previousCases = count($previousRows);

    $rejectionRate = $this->rejectionRate($rejectedKg, $approvedKg);
    $previousRejectionRate = $this->rejectionRate($previousRejectedKg, $previousApprovedKg);

    $economicLoss = $rejectedKg * $this->lossPerKgRwf();
    $previousEconomicLoss = $previousRejectedKg * $this->lossPerKgRwf();

    return [
      'rejected_meat_kg' => [
        'value' => $rejectedKg,
        'trend' => $this->trendDelta($rejectedKg, $previousRejectedKg),
      ],
      'rejection_rate' => [
        'value' => $rejectionRate,
        'trend' => $this->trendDeltaPoints($rejectionRate, $previousRejectionRate),
      ],
      'economic_loss' => [
        'value' => $economicLoss,
        'formatted' => $this->formatLoss($economicLoss),
        'trend' => $this->trendDelta($economicLoss, $previousEconomicLoss),
      ],
      'rejection_cases' => [
        'value' => $cases,
        'trend' => $this->trendDelta($cases, $previousCases),
      ],
    ];
  }

  /**
   * @param  list<array<string, mixed>>  $rows
   * @return list<array<string, mixed>>
   */
  private function chartSpecs(array $rows, array $filters): array
  {
    $colors = config('bucha.chart.series', ['#A11D1E', '#7A1516', '#3C3C3B', '#718096', '#D69E2E', '#38A169']);

    $organTotals = $this->groupTotals($rows, 'seized_part', 6);
    $reasonTotals = $this->groupTotals($rows, 'reason', 6);
    $speciesTotals = $this->groupTotals($rows, 'species', 5);
    $trend = $this->rejectionTrend($rows, $filters);

    $organColors = $this->chartColors(count($organTotals['labels']), $colors);
    $reasonColors = $this->chartColors(count($reasonTotals['labels']), $colors);
    $speciesColors = $this->chartColors(count($speciesTotals['labels']), $colors);

    return [
      [
        'id' => 'rica-cond-organ-donut',
        'title' => __('Rejected meat by organ'),
        'type' => 'donut',
        'height' => 220,
        'labels' => $organTotals['labels'],
        'data' => $organTotals['data'],
        'colors' => $organColors,
        'centerLabel' => __('kg'),
        'legend' => $this->donutLegend($organTotals['labels'], $organColors),
        'emptyMessage' => __('No condemned meat for this period.'),
      ],
      [
        'id' => 'rica-cond-reasons-bar',
        'title' => __('Reasons for rejection'),
        'type' => 'bar',
        'indexAxis' => 'y',
        'height' => $this->horizontalBarHeight(count($reasonTotals['labels'])),
        'labels' => $reasonTotals['labels'],
        'datasets' => [[
          'label' => __('Rejected (kg)'),
          'data' => $reasonTotals['data'],
          'backgroundColor' => $reasonColors,
        ]],
        'emptyMessage' => __('No rejection reasons for this period.'),
      ],
      [
        'id' => 'rica-cond-species-bar',
        'title' => __('Top rejection by species'),
        'type' => 'bar',
        'indexAxis' => 'y',
        'height' => $this->horizontalBarHeight(count($speciesTotals['labels'])),
        'labels' => $speciesTotals['labels'],
        'datasets' => [[
          'label' => __('Rejected (kg)'),
          'data' => $speciesTotals['data'],
          'backgroundColor' => $speciesColors,
        ]],
        'emptyMessage' => __('No species breakdown for this period.'),
      ],
      array_merge($trend, [
        'id' => 'rica-cond-trend-line',
        'title' => __('Rejection trend'),
        'type' => 'area-line',
        'height' => 240,
        'fullWidth' => true,
        'emptyMessage' => __('No rejection trend for this period.'),
      ]),
    ];
  }

  /**
   * @param  list<string>  $labels
   * @param  list<string>  $colors
   * @return list<array{color: string, label: string}>
   */
  private function donutLegend(array $labels, array $colors): array
  {
    return collect($labels)
      ->values()
      ->map(fn (string $label, int $index) => [
        'color' => $colors[$index] ?? '#718096',
        'label' => $label,
      ])
      ->all();
  }

  private function horizontalBarHeight(int $rowCount): int
  {
    if ($rowCount <= 0) {
      return 200;
    }

    return max(180, min(320, 48 + ($rowCount * 28)));
  }

  /**
   * @param  list<array<string, mixed>>  $rows
   * @return list<array{facility_name: string, rejected_kg: float, rejection_rate: float}>
   */
  private function slaughterhouseRows(array $rows, float $approvedKg, array $filters, ?int $districtId): array
  {
    $facilityApproved = PostMortemInspectionItem::query()
      ->approved()
      ->with(['inspection.batch.slaughterExecution.slaughterPlan.facility'])
      ->whereHas('inspection', fn (Builder $query) => $this->applyInspectionScope($query, $filters, $districtId))
      ->get()
      ->groupBy(fn (PostMortemInspectionItem $item) => $item->inspection?->batch?->slaughterExecution?->slaughterPlan?->facility?->facility_name ?? __('Unknown slaughterhouse'))
      ->map(fn (Collection $group) => round($group->sum(fn (PostMortemInspectionItem $item) => $this->approvedMeatKgForItem($item)), 2));

    return collect($rows)
      ->groupBy('facility_name')
      ->map(function (Collection $group, string $facilityName) use ($facilityApproved): array {
        $rejectedKg = round($group->sum('kg'), 2);
        $approved = (float) ($facilityApproved[$facilityName] ?? 0);

        return [
          'facility_name' => $facilityName,
          'rejected_kg' => $rejectedKg,
          'rejection_rate' => $this->rejectionRate($rejectedKg, $approved),
        ];
      })
      ->sortByDesc('rejected_kg')
      ->take(5)
      ->values()
      ->all();
  }

  /**
   * @param  list<array<string, mixed>>  $rows
   * @return list<array{reason: string, loss: float, formatted_loss: string, share: float}>
   */
  private function economicLossRows(array $rows): array
  {
    $totalLoss = collect($rows)->sum(fn (array $row) => $row['kg'] * $this->lossPerKgRwf());

    return collect($rows)
      ->groupBy('reason')
      ->map(fn (Collection $group, string $reason) => round($group->sum('kg') * $this->lossPerKgRwf(), 2))
      ->sortDesc()
      ->take(5)
      ->map(function (float $loss, string $reason) use ($totalLoss): array {
        return [
          'reason' => $reason,
          'loss' => $loss,
          'formatted_loss' => $this->formatLoss($loss),
          'share' => $totalLoss > 0 ? round($loss / $totalLoss * 100, 1) : 0.0,
        ];
      })
      ->values()
      ->all();
  }

  /**
   * @param  list<array<string, mixed>>  $rows
   * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
   */
  private function rejectionTrend(array $rows, array $filters): array
  {
    if ($rows === []) {
      return ['labels' => [], 'datasets' => []];
    }

    $start = $filters['start'] ?? collect($rows)->min('inspection_date');
    $end = $filters['end'] ?? collect($rows)->max('inspection_date');
    $start = $start instanceof Carbon ? $start->copy()->startOfDay() : Carbon::parse($start)->startOfDay();
    $end = $end instanceof Carbon ? $end->copy()->endOfDay() : Carbon::parse($end)->endOfDay();

    $days = max(1, $start->diffInDays($end) + 1);
    $bucketCount = min(12, max(4, (int) ceil($days / 3)));
    $bucketSize = (int) ceil($days / $bucketCount);

    $labels = [];
    $data = [];
    $cursor = $start->copy();

    for ($bucket = 0; $bucket < $bucketCount; $bucket++) {
      $bucketStart = $cursor->copy();
      $bucketEnd = $cursor->copy()->addDays($bucketSize - 1)->min($end);
      $labels[] = $bucketStart->format('M j');
      $data[] = round(collect($rows)
        ->filter(fn (array $row) => Carbon::parse($row['inspection_date'])->between($bucketStart, $bucketEnd))
        ->sum('kg'), 2);
      $cursor = $bucketEnd->copy()->addDay();
      if ($cursor->gt($end)) {
        break;
      }
    }

    $primary = config('bucha.chart.series.0', '#A11D1E');

    return [
      'labels' => $labels,
      'datasets' => [[
        'label' => __('Rejected (kg)'),
        'data' => $data,
        'borderColor' => $primary,
        'backgroundColor' => 'rgba(161, 29, 30, 0.12)',
        'fill' => true,
      ]],
    ];
  }

  /**
   * @param  list<array<string, mixed>>  $rows
   * @return array{labels: list<string>, data: list<float>}
   */
  private function groupTotals(array $rows, string $key, int $limit): array
  {
    $all = collect($rows)
      ->groupBy($key)
      ->map(fn (Collection $group) => round($group->sum('kg'), 2))
      ->sortDesc();

    $top = $all->take($limit);
    $otherKg = round((float) $all->slice($limit)->sum(), 2);

    $labels = $top->keys()->values()->all();
    $data = $top->values()->all();

    if ($otherKg > 0) {
      $labels[] = __('Others');
      $data[] = $otherKg;
    }

    return [
      'labels' => $labels,
      'data' => $data,
    ];
  }

  /**
   * @return array<int, string>
   */
  private function districtOptions(): array
  {
    return AdministrativeDivision::query()
      ->where('type', AdministrativeDivision::TYPE_DISTRICT)
      ->orderBy('name')
      ->pluck('name', 'id')
      ->all();
  }

  private function rejectionRate(float $rejectedKg, float $approvedKg): float
  {
    $total = $rejectedKg + $approvedKg;

    return $total > 0 ? round($rejectedKg / $total * 100, 2) : 0.0;
  }

  private function formatLoss(float $amount): string
  {
    if ($amount >= 1_000_000) {
      return 'RWF '.number_format($amount / 1_000_000, 1).'M';
    }

    if ($amount >= 1_000) {
      return 'RWF '.number_format($amount / 1_000, 1).'K';
    }

    return 'RWF '.number_format($amount, 0);
  }

  private function normalizeSpecies(?string $species): string
  {
    $normalized = Str::lower(trim((string) $species));

    return match (true) {
      $normalized === '' => __('Unknown'),
      str_contains($normalized, 'cattle'), str_contains($normalized, 'cow'), str_contains($normalized, 'bull') => __('Cattle'),
      str_contains($normalized, 'goat') => __('Goats'),
      str_contains($normalized, 'sheep') => __('Sheep'),
      str_contains($normalized, 'pig'), str_contains($normalized, 'swine') => __('Pigs'),
      default => __('Other'),
    };
  }

  private function normalizeOrgan(?string $value): string
  {
    $value = trim((string) $value);
    if ($value === '' || $value === '—') {
      return __('Unspecified organ');
    }

    $normalized = Str::lower(str_replace(['_', '-'], ' ', $value));

    return match (true) {
      str_contains($normalized, 'whole carcass'),
      $normalized === 'carcass',
      str_contains($normalized, 'entire carcass') => __('Whole carcass'),
      str_contains($normalized, 'liver') => __('Liver'),
      str_contains($normalized, 'spleen') => __('Spleen'),
      str_contains($normalized, 'kidney') => __('Kidneys'),
      str_contains($normalized, 'heart') => __('Heart'),
      str_contains($normalized, 'lung') => __('Lungs'),
      str_contains($normalized, 'intestine') => __('Intestines'),
      str_contains($normalized, 'stomach'), str_contains($normalized, 'rumen') => __('Stomach'),
      str_contains($normalized, 'lymph') => __('Lymph nodes'),
      str_contains($normalized, 'head') => __('Head'),
      str_contains($normalized, 'tongue') => __('Tongue'),
      default => Str::title(Str::limit($value, 40)),
    };
  }

  private function normalizeReason(?string $value): string
  {
    $value = trim((string) $value);
    if ($value === '' || $value === '—') {
      return __('Unspecified reason');
    }

    return RicaDiseaseLabelResolver::fromText($value);
  }

  /**
   * @param  array<int, string>  $palette
   * @return array<int, string>
   */
  private function chartColors(int $count, array $palette): array
  {
    return collect(range(0, max(0, $count - 1)))
      ->map(fn (int $index) => $palette[$index % count($palette)])
      ->all();
  }

  /**
   * @param  array{is_filtered: bool, start: ?Carbon, end: ?Carbon, period: string}  $filters
   * @return array{is_filtered: bool, start: ?Carbon, end: ?Carbon, period: string}
   */
  private function previousPeriodFilters(array $filters): array
  {
    if (! $filters['is_filtered'] || $filters['start'] === null || $filters['end'] === null) {
      $end = now()->subMonth()->endOfMonth();
      $start = now()->subMonth()->startOfMonth();

      return [
        'is_filtered' => true,
        'start' => $start,
        'end' => $end,
        'period' => 'month',
      ];
    }

    $days = max(1, $filters['start']->diffInDays($filters['end']) + 1);
    $previousEnd = $filters['start']->copy()->subDay()->endOfDay();
    $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

    return [
      'is_filtered' => true,
      'start' => $previousStart,
      'end' => $previousEnd,
      'period' => $filters['period'],
    ];
  }

  /**
   * @return array{direction: string, percent: float, label: string}
   */
  private function trendDelta(float|int $current, float|int $previous): array
  {
    if ($previous <= 0) {
      return [
        'direction' => $current > 0 ? 'up' : 'neutral',
        'percent' => 0.0,
        'label' => __('vs last month'),
      ];
    }

    $delta = (($current - $previous) / $previous) * 100;

    return [
      'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
      'percent' => round(abs($delta), 1),
      'label' => __('vs last month'),
    ];
  }

  /**
   * @return array{direction: string, percent: float, label: string}
   */
  private function trendDeltaPoints(float $current, float $previous): array
  {
    $delta = $current - $previous;

    return [
      'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
      'percent' => round(abs($delta), 2),
      'label' => __('pp vs last month'),
    ];
  }

  private function normalizeDistrictId(mixed $districtId): ?int
  {
    if ($districtId === null || $districtId === '' || $districtId === 'all') {
      return null;
    }

    return is_numeric($districtId) ? (int) $districtId : null;
  }
}
