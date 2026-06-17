<?php

namespace App\Services\Butcher;

use App\Models\Business;
use App\Models\ButcherCutOutput;
use App\Models\ButcherCuttingSession;
use App\Models\ButcherCutType;
use App\Models\ButcherInventoryBatch;
use App\Support\DomPdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ButcherCuttingService
{
    public function openSession(Business $business, array $data): ButcherCuttingSession
    {
        return DB::transaction(function () use ($business, $data) {
            /** @var ButcherInventoryBatch $batch */
            $batch = ButcherInventoryBatch::query()
                ->where('business_id', $business->id)
                ->lockForUpdate()
                ->findOrFail((int) $data['batch_id']);

            if (! in_array($batch->status, ButcherInventoryBatch::ACTIVE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'batch_id' => [__('This batch cannot be used for cutting (status: :status).', ['status' => $batch->status])],
                ]);
            }

            if ($batch->isExpired()) {
                throw ValidationException::withMessages([
                    'batch_id' => [__('This batch has expired and cannot be used for cutting.')],
                ]);
            }

            $sourceWeight = (float) $data['source_weight_kg'];
            $remaining = (float) $batch->remaining_weight_kg;

            if ($sourceWeight <= 0 || $sourceWeight > $remaining) {
                throw ValidationException::withMessages([
                    'source_weight_kg' => [__('Source weight must be between 0.1 and :max kg.', ['max' => number_format($remaining, 3)])],
                ]);
            }

            $batch->remaining_weight_kg = round($remaining - $sourceWeight, 3);
            $batch->status = $batch->remaining_weight_kg <= 0
                ? ButcherInventoryBatch::STATUS_FULLY_USED
                : ButcherInventoryBatch::STATUS_PARTIALLY_USED;
            $batch->save();

            $sessionDate = isset($data['session_date'])
                ? Carbon::parse($data['session_date'])->toDateString()
                : now()->toDateString();

            return ButcherCuttingSession::query()->create([
                'business_id' => $business->id,
                'outlet_id' => (int) $data['outlet_id'],
                'batch_id' => $batch->id,
                'session_number' => $this->generateSessionNumber($business->id),
                'source_weight_kg' => $sourceWeight,
                'session_date' => $sessionDate,
                'status' => ButcherCuttingSession::STATUS_OPEN,
            ]);
        });
    }

    public function addCutOutput(ButcherCuttingSession $session, array $data): ButcherCutOutput
    {
        if (! $session->isOpen()) {
            throw ValidationException::withMessages([
                'session' => [__('Cannot add cuts to a closed session.')],
            ]);
        }

        $weight = (float) $data['weight_kg'];
        if ($weight <= 0) {
            throw ValidationException::withMessages([
                'weight_kg' => [__('Cut weight must be greater than zero.')],
            ]);
        }

        $cutType = ButcherCutType::query()
            ->where('business_id', $session->business_id)
            ->where('is_active', true)
            ->findOrFail((int) $data['cut_type_id']);

        $session->loadMissing('batch');
        $batchCost = (float) ($session->batch?->unit_cost_per_kg ?? 0);
        $yieldRatio = max((float) $cutType->expected_yield_pct / 100, 0.01);
        $unitCost = round($batchCost / $yieldRatio, 2);

        return DB::transaction(function () use ($session, $cutType, $weight, $unitCost) {
            $output = ButcherCutOutput::query()->create([
                'business_id' => $session->business_id,
                'session_id' => $session->id,
                'cut_type_id' => $cutType->id,
                'weight_kg' => $weight,
                'remaining_weight_kg' => $weight,
                'unit_cost_per_kg' => $unitCost,
            ]);

            $session->update([
                'total_cuts_weight_kg' => round((float) $session->cutOutputs()->sum('weight_kg'), 3),
            ]);

            return $output->load('cutType');
        });
    }

    public function closeSession(ButcherCuttingSession $session): void
    {
        if (! $session->isOpen()) {
            throw ValidationException::withMessages([
                'session' => [__('This session is already closed.')],
            ]);
        }

        $outputCount = $session->cutOutputs()->count();
        if ($outputCount === 0) {
            throw ValidationException::withMessages([
                'session' => [__('Record at least one cut output before closing the session.')],
            ]);
        }

        $wastage = $this->calculateWastage($session);

        $session->update([
            'total_cuts_weight_kg' => $wastage['total_cuts_weight_kg'],
            'wastage_kg' => $wastage['wastage_kg'],
            'wastage_pct' => $wastage['wastage_pct'],
            'status' => ButcherCuttingSession::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        app(ButcherCatalogService::class)->recalculateProductsAfterSessionClose($session->fresh());
    }

    /**
     * @return array{source_weight_kg: float, total_cuts_weight_kg: float, wastage_kg: float, wastage_pct: float}
     */
    public function calculateWastage(ButcherCuttingSession $session): array
    {
        $sourceWeight = (float) $session->source_weight_kg;
        $totalCuts = round((float) $session->cutOutputs()->sum('weight_kg'), 3);
        $wastageKg = round(max($sourceWeight - $totalCuts, 0), 3);
        $wastagePct = $sourceWeight > 0
            ? round(($wastageKg / $sourceWeight) * 100, 2)
            : 0.0;

        return [
            'source_weight_kg' => $sourceWeight,
            'total_cuts_weight_kg' => $totalCuts,
            'wastage_kg' => $wastageKg,
            'wastage_pct' => $wastagePct,
        ];
    }

    public function generateLabel(ButcherCutOutput $output): string
    {
        $output->loadMissing(['session.batch', 'session.outlet', 'cutType', 'business']);

        $filename = sprintf(
            'butcher-labels/%d/%s-cut-%d.pdf',
            $output->business_id,
            $output->session->session_number,
            $output->id
        );

        $pdf = DomPdf::loadView('butcher.cutting.labels.shelf', [
            'output' => $output,
            'session' => $output->session,
            'batch' => $output->session->batch,
            'cutType' => $output->cutType,
            'business' => $output->business,
        ])->setPaper([0, 0, 226.77, 113.39], 'portrait');

        Storage::disk('public')->put($filename, $pdf->output());

        $output->update([
            'label_printed' => true,
            'label_path' => $filename,
        ]);

        return $filename;
    }

    /**
     * @return array<string, mixed>
     */
    public function getYieldReport(Business $business, string $period = '30d'): array
    {
        $from = $this->periodStart($period);

        $sessions = $business->butcherCuttingSessions()
            ->where('status', ButcherCuttingSession::STATUS_CLOSED)
            ->where('session_date', '>=', $from->toDateString())
            ->get();

        $totalSource = (float) $sessions->sum('source_weight_kg');
        $totalCuts = (float) $sessions->sum('total_cuts_weight_kg');
        $totalWastage = (float) $sessions->sum('wastage_kg');
        $avgWastagePct = $sessions->isNotEmpty()
            ? round((float) $sessions->avg('wastage_pct'), 2)
            : 0.0;

        return [
            'period' => $period,
            'sessions_closed' => $sessions->count(),
            'total_source_kg' => $totalSource,
            'total_yield_kg' => $totalCuts,
            'total_wastage_kg' => $totalWastage,
            'avg_wastage_pct' => $avgWastagePct,
            'yield_pct' => $totalSource > 0 ? round(($totalCuts / $totalSource) * 100, 2) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCuttingSummary(Business $business): array
    {
        $today = now()->toDateString();
        $report = $this->getYieldReport($business, '30d');

        $sessionsToday = $business->butcherCuttingSessions()
            ->whereDate('session_date', $today)
            ->count();

        $yieldToday = (float) ButcherCutOutput::query()
            ->where('business_id', $business->id)
            ->whereHas('session', fn ($q) => $q->whereDate('session_date', $today))
            ->sum('weight_kg');

        $closedToday = $business->butcherCuttingSessions()
            ->where('status', ButcherCuttingSession::STATUS_CLOSED)
            ->whereDate('session_date', $today)
            ->get();

        $avgWastageToday = $closedToday->isNotEmpty()
            ? round((float) $closedToday->avg('wastage_pct'), 2)
            : null;

        $openSessions = $business->butcherCuttingSessions()
            ->with(['batch', 'outlet'])
            ->where('status', ButcherCuttingSession::STATUS_OPEN)
            ->latest('id')
            ->limit(5)
            ->get();

        $recentClosed = $business->butcherCuttingSessions()
            ->with(['batch', 'outlet'])
            ->where('status', ButcherCuttingSession::STATUS_CLOSED)
            ->latest('closed_at')
            ->limit(5)
            ->get();

        return [
            'sessions_today' => $sessionsToday,
            'yield_today_kg' => $yieldToday,
            'avg_wastage_pct_today' => $avgWastageToday,
            'avg_wastage_pct' => $report['avg_wastage_pct'],
            'total_yield_kg' => $report['total_yield_kg'],
            'open_sessions' => $openSessions,
            'recent_closed_sessions' => $recentClosed,
            'yield_report' => $report,
        ];
    }

    private function generateSessionNumber(int $businessId): string
    {
        $sequence = ButcherCuttingSession::query()->where('business_id', $businessId)->count() + 1;

        return sprintf('CUT-%d-%04d', $businessId, $sequence);
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(7)->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->subDays(30)->startOfDay(),
        };
    }
}
