<?php

namespace App\Services\Farmer;

use App\Models\MovementPermit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MovementPermitAnalyticsService
{
    /** @param  Collection<int, int>  $farmerIds */
    public function metrics(Collection $farmerIds): array
    {
        $query = MovementPermit::query()->whereIn('farmer_id', $farmerIds);

        return [
            'total_permits' => (int) (clone $query)->count(),
            'active_permits' => (int) (clone $query)->whereIn('permit_status', MovementPermit::ACTIVE_STATUSES)->count(),
            'approved_permits' => (int) (clone $query)->where('permit_status', MovementPermit::STATUS_APPROVED)->count(),
            'animals_in_transit' => (int) DB::table('movement_permit_animals')
                ->join('movement_permits', 'movement_permits.id', '=', 'movement_permit_animals.movement_permit_id')
                ->whereIn('movement_permits.farmer_id', $farmerIds->all())
                ->where('movement_permits.movement_status', MovementPermit::MOVEMENT_IN_TRANSIT)
                ->whereNotNull('movement_permit_animals.animal_id')
                ->count(),
            'rejected_permits' => (int) (clone $query)->where('permit_status', MovementPermit::STATUS_REJECTED)->count(),
            'expired_permits' => (int) (clone $query)->where('permit_status', MovementPermit::STATUS_EXPIRED)->count(),
        ];
    }

    /** @param  Collection<int, int>  $farmerIds */
    public function charts(Collection $farmerIds): array
    {
        $start = now()->subMonths(5)->startOfMonth();

        $movementTrend = MovementPermit::query()
            ->whereIn('farmer_id', $farmerIds)
            ->where('departure_date', '>=', $start)
            ->selectRaw("DATE_FORMAT(COALESCE(departure_date, issue_date), '%Y-%m') as period, COUNT(*) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period');

        $approvalTrend = MovementPermit::query()
            ->whereIn('farmer_id', $farmerIds)
            ->where('permit_status', MovementPermit::STATUS_APPROVED)
            ->where('departure_date', '>=', $start)
            ->selectRaw("DATE_FORMAT(COALESCE(departure_date, issue_date), '%Y-%m') as period, COUNT(*) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period');

        $byType = MovementPermit::query()
            ->whereIn('farmer_id', $farmerIds)
            ->selectRaw('permit_type, COUNT(*) as total')
            ->groupBy('permit_type')
            ->pluck('total', 'permit_type');

        $vetStatuses = MovementPermit::query()
            ->whereIn('farmer_id', $farmerIds)
            ->selectRaw('veterinary_status, COUNT(*) as total')
            ->groupBy('veterinary_status')
            ->pluck('total', 'veterinary_status');

        return [
            'movement_trend' => $this->lineChart($movementTrend, __('Movements')),
            'approval_trend' => $this->lineChart($approvalTrend, __('Approvals')),
            'destination_analytics' => $this->barChart($byType, __('Permit types')),
            'veterinary_analytics' => $this->barChart($vetStatuses, __('Veterinary clearance')),
        ];
    }

    private function lineChart(Collection $rows, string $label): array
    {
        return [
            'type' => 'line',
            'labels' => $rows->keys()->all(),
            'datasets' => [[
                'label' => $label,
                'data' => $rows->values()->map(fn ($v) => (int) $v)->all(),
                'borderColor' => '#1d4ed8',
                'backgroundColor' => 'rgba(29, 78, 216, 0.12)',
                'fill' => true,
                'tension' => 0.35,
            ]],
        ];
    }

    private function barChart(Collection $rows, string $label): array
    {
        return [
            'type' => 'bar',
            'labels' => $rows->keys()->map(fn ($key) => ucwords(str_replace('_', ' ', (string) $key)))->all(),
            'datasets' => [[
                'label' => $label,
                'data' => $rows->values()->map(fn ($v) => (int) $v)->all(),
                'backgroundColor' => '#0f766e',
            ]],
        ];
    }
}
