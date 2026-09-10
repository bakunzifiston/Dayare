<?php

namespace App\Services\Butcher;

use App\Exceptions\Butcher\InsufficientButcherStockException;
use App\Models\Business;
use App\Models\ButcherCutOutput;
use App\Models\ButcherCuttingSession;
use App\Models\ButcherCuttingSessionSource;
use App\Models\ButcherCutType;
use App\Models\ButcherInventoryBatch;
use App\Models\ButcherInventoryMovement;
use App\Models\User;
use App\Support\DomPdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ButcherCuttingService
{
    public function __construct(
        private readonly InventoryConsumptionService $consumption,
        private readonly BatchSafetyGate $safetyGate,
    ) {}

    public function openSession(Business $business, array $data, ?User $actor = null): ButcherCuttingSession
    {
        return DB::transaction(function () use ($business, $data, $actor) {
            $batch = $this->assertActiveBatch($business, (int) $data['batch_id']);
            $sourceWeight = (float) $data['source_weight_kg'];
            $this->assertSourceWeightFits($batch, $sourceWeight);

            $sessionDate = isset($data['session_date'])
                ? Carbon::parse($data['session_date'])->toDateString()
                : now()->toDateString();

            // Intent only — inventory is not mutated until close.
            $session = ButcherCuttingSession::query()->create([
                'business_id' => $business->id,
                'outlet_id' => (int) $data['outlet_id'],
                'batch_id' => $batch->id,
                'session_number' => $this->generateSessionNumber($business->id),
                'source_weight_kg' => $sourceWeight,
                'session_date' => $sessionDate,
                'status' => ButcherCuttingSession::STATUS_OPEN,
            ]);

            ButcherCuttingSessionSource::query()->create([
                'session_id' => $session->id,
                'batch_id' => $batch->id,
                'source_weight_kg' => $sourceWeight,
            ]);

            $this->safetyGate->assertBatchSafe(
                $batch,
                $actor,
                isset($data['safety_override_reason']) ? (string) $data['safety_override_reason'] : null,
                \App\Models\ButcherComplianceOverride::CONTEXT_CUTTING_SESSION,
                (int) $session->id,
                true,
            );

            return $session->fresh(['batch', 'outlet', 'sources.batch']);
        });
    }

    public function addSource(ButcherCuttingSession $session, array $data, ?User $actor = null): ButcherCuttingSessionSource
    {
        if (! $session->isOpen()) {
            throw ValidationException::withMessages([
                'session' => [__('Cannot add sources to a closed session.')],
            ]);
        }

        return DB::transaction(function () use ($session, $data, $actor) {
            $business = $session->business ?? Business::query()->findOrFail($session->business_id);
            $batch = $this->assertActiveBatch($business, (int) $data['batch_id']);
            $sourceWeight = (float) $data['source_weight_kg'];
            $this->assertSourceWeightFits($batch, $sourceWeight);

            $existing = $session->sources()->where('batch_id', $batch->id)->first();
            if ($existing !== null) {
                $existing->update([
                    'source_weight_kg' => round((float) $existing->source_weight_kg + $sourceWeight, 3),
                ]);
                $source = $existing->fresh(['batch']);
            } else {
                $source = ButcherCuttingSessionSource::query()->create([
                    'session_id' => $session->id,
                    'batch_id' => $batch->id,
                    'source_weight_kg' => $sourceWeight,
                ]);
            }

            $this->safetyGate->assertBatchSafe(
                $batch,
                $actor,
                isset($data['safety_override_reason']) ? (string) $data['safety_override_reason'] : null,
                \App\Models\ButcherComplianceOverride::CONTEXT_CUTTING_SOURCE,
                (int) $session->id,
                true,
            );

            $this->syncSessionSourceTotal($session);

            return $source->load('batch');
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

        $avgSourceCost = $this->weightedAverageSourceCost($session);
        $yieldRatio = max((float) $cutType->expected_yield_pct / 100, 0.01);
        $unitCost = round($avgSourceCost / $yieldRatio, 2);

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

    public function closeSession(ButcherCuttingSession $session, ?User $actor = null, ?string $safetyOverrideReason = null): void
    {
        if (! $session->isOpen()) {
            throw ValidationException::withMessages([
                'session' => [__('This session is already closed.')],
            ]);
        }

        $session->load(['sources.batch', 'cutOutputs.cutType']);

        if ($session->cutOutputs->isEmpty()) {
            throw ValidationException::withMessages([
                'session' => [__('Record at least one cut output before closing the session.')],
            ]);
        }

        if ($session->sources->isEmpty()) {
            throw ValidationException::withMessages([
                'session' => [__('Add at least one source batch before closing the session.')],
            ]);
        }

        try {
            DB::transaction(function () use ($session, $actor, $safetyOverrideReason) {
                $batchIds = $session->sources->pluck('batch_id')->map(fn ($id) => (int) $id)->sort()->values();

                // Lock all source batches in ascending ID order to avoid deadlocks.
                $locked = ButcherInventoryBatch::query()
                    ->whereIn('id', $batchIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($session->sources as $source) {
                    $batch = $locked->get((int) $source->batch_id);
                    if ($batch === null) {
                        throw ValidationException::withMessages([
                            'sources' => [__('A source batch is missing and the session cannot be closed.')],
                        ]);
                    }

                    if (! in_array($batch->status, ButcherInventoryBatch::ACTIVE_STATUSES, true)) {
                        throw ValidationException::withMessages([
                            'sources' => [__('Batch :batch is no longer available for cutting.', [
                                'batch' => $batch->batch_number,
                            ])],
                        ]);
                    }

                    // Pre-commit re-check: expiry + temperature breach under the same locks.
                    $hasPriorOverride = \App\Models\ButcherComplianceOverride::query()
                        ->where('business_id', $session->business_id)
                        ->where('batch_id', $batch->id)
                        ->where('context_id', $session->id)
                        ->whereIn('context_type', [
                            \App\Models\ButcherComplianceOverride::CONTEXT_CUTTING_SESSION,
                            \App\Models\ButcherComplianceOverride::CONTEXT_CUTTING_SOURCE,
                        ])
                        ->exists();

                    $this->safetyGate->assertBatchSafe(
                        $batch,
                        $actor,
                        $hasPriorOverride ? ($safetyOverrideReason ?: __('Prior override on session open')) : $safetyOverrideReason,
                        \App\Models\ButcherComplianceOverride::CONTEXT_CUTTING_SESSION,
                        (int) $session->id,
                        false,
                    );

                    $needed = (float) $source->source_weight_kg;
                    $available = (float) $batch->remaining_weight_kg;
                    if ($needed > $available + 0.0005) {
                        throw ValidationException::withMessages([
                            'sources' => [__('Batch :batch only has :available kg available, but this session needs :needed kg. Adjust the source weight and retry.', [
                                'batch' => $batch->batch_number,
                                'available' => number_format($available, 3),
                                'needed' => number_format($needed, 3),
                            ])],
                        ]);
                    }
                }

                $closedAt = now();

                foreach ($session->sources->sortBy('batch_id') as $source) {
                    $this->consumption->consumeFromBatch(
                        (int) $session->business_id,
                        (int) $source->batch_id,
                        (float) $source->source_weight_kg,
                        $actor,
                        ButcherCuttingSession::class,
                        (int) $session->id,
                        ButcherInventoryMovement::TYPE_CUTTING_CONSUMPTION,
                        $closedAt,
                    );
                }

                $avgSourceCost = $this->weightedAverageSourceCost($session);

                foreach ($session->cutOutputs as $output) {
                    $yieldRatio = max((float) ($output->cutType?->expected_yield_pct ?? 100) / 100, 0.01);
                    $unitCost = round($avgSourceCost / $yieldRatio, 2);
                    $weight = (float) $output->weight_kg;

                    $output->update(['unit_cost_per_kg' => $unitCost]);

                    ButcherInventoryMovement::record([
                        'business_id' => $session->business_id,
                        'outlet_id' => $session->outlet_id,
                        'batch_id' => null,
                        'cut_output_id' => $output->id,
                        'type' => ButcherInventoryMovement::TYPE_CUTTING_OUTPUT_IN,
                        'quantity_kg' => $weight,
                        'before_qty' => 0,
                        'after_qty' => $weight,
                        'reference_type' => ButcherCuttingSession::class,
                        'reference_id' => $session->id,
                        'actor_id' => $actor?->id,
                        'occurred_at' => $closedAt,
                    ]);
                }

                $wastage = $this->calculateWastage($session->fresh(['cutOutputs', 'sources']));

                $session->update([
                    'source_weight_kg' => $wastage['source_weight_kg'],
                    'total_cuts_weight_kg' => $wastage['total_cuts_weight_kg'],
                    'wastage_kg' => $wastage['wastage_kg'],
                    'wastage_pct' => $wastage['wastage_pct'],
                    'status' => ButcherCuttingSession::STATUS_CLOSED,
                    'closed_at' => $closedAt,
                ]);

                app(ButcherCatalogService::class)->recalculateProductsAfterSessionClose($session->fresh());
            });
        } catch (InsufficientButcherStockException $e) {
            throw ValidationException::withMessages([
                'sources' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * @return array{source_weight_kg: float, total_cuts_weight_kg: float, wastage_kg: float, wastage_pct: float}
     */
    public function calculateWastage(ButcherCuttingSession $session): array
    {
        $session->loadMissing('sources');

        $sourceWeight = $session->sources->isNotEmpty()
            ? round((float) $session->sources->sum('source_weight_kg'), 3)
            : (float) $session->source_weight_kg;

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
        $output->loadMissing(['session.batch', 'session.sources.batch', 'session.outlet', 'cutType', 'business']);

        $filename = sprintf(
            'butcher-labels/%d/%s-cut-%d.pdf',
            $output->business_id,
            $output->session->session_number,
            $output->id
        );

        $batch = $output->session->batch
            ?? $output->session->sources->first()?->batch;

        $pdf = DomPdf::loadView('butcher.processing.labels.shelf', [
            'output' => $output,
            'session' => $output->session,
            'batch' => $batch,
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
            ->with(['batch', 'outlet', 'sources'])
            ->where('status', ButcherCuttingSession::STATUS_OPEN)
            ->latest('id')
            ->limit(5)
            ->get();

        $recentClosed = $business->butcherCuttingSessions()
            ->with(['batch', 'outlet', 'sources'])
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

    private function assertActiveBatch(Business $business, int $batchId): ButcherInventoryBatch
    {
        /** @var ButcherInventoryBatch $batch */
        $batch = ButcherInventoryBatch::query()
            ->where('business_id', $business->id)
            ->findOrFail($batchId);

        if (! in_array($batch->status, ButcherInventoryBatch::ACTIVE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'batch_id' => [__('This batch cannot be used for cutting (status: :status).', ['status' => $batch->status])],
            ]);
        }

        return $batch;
    }

    private function assertSourceWeightFits(ButcherInventoryBatch $batch, float $sourceWeight): void
    {
        $remaining = (float) $batch->remaining_weight_kg;

        if ($sourceWeight <= 0 || $sourceWeight > $remaining + 0.0005) {
            throw ValidationException::withMessages([
                'source_weight_kg' => [__('Source weight must be between 0.1 and :max kg.', ['max' => number_format($remaining, 3)])],
            ]);
        }
    }

    private function syncSessionSourceTotal(ButcherCuttingSession $session): void
    {
        $total = round((float) $session->sources()->sum('source_weight_kg'), 3);
        $primaryBatchId = $session->sources()->orderBy('id')->value('batch_id') ?? $session->batch_id;

        $session->update([
            'source_weight_kg' => $total,
            'batch_id' => $primaryBatchId,
        ]);
    }

    private function weightedAverageSourceCost(ButcherCuttingSession $session): float
    {
        $session->loadMissing('sources.batch');

        if ($session->sources->isEmpty()) {
            $session->loadMissing('batch');

            return (float) ($session->batch?->unit_cost_per_kg ?? 0);
        }

        $weightTotal = 0.0;
        $costTotal = 0.0;

        foreach ($session->sources as $source) {
            $w = (float) $source->source_weight_kg;
            $c = (float) ($source->batch?->unit_cost_per_kg ?? 0);
            $weightTotal += $w;
            $costTotal += $w * $c;
        }

        return $weightTotal > 0 ? round($costTotal / $weightTotal, 4) : 0.0;
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
