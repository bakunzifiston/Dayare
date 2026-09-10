<?php

namespace App\Services\Butcher;

use App\Models\ButcherComplianceOverride;
use App\Models\ButcherCutOutput;
use App\Models\ButcherCuttingSession;
use App\Models\ButcherInventoryBatch;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Hard gates for temperature-breached or expired batches.
 * Hygiene / permits remain advisory and are not checked here.
 */
class BatchSafetyGate
{
    /**
     * Evaluate a locked (or freshly loaded) batch. Re-reads safety flags before deciding.
     *
     * @return ButcherComplianceOverride|null Override row when an authorized override was applied
     */
    public function assertBatchSafe(
        ButcherInventoryBatch $batch,
        ?User $actor = null,
        ?string $overrideReason = null,
        string $contextType = ButcherComplianceOverride::CONTEXT_CUTTING_SESSION,
        ?int $contextId = null,
        bool $lockAndRecheck = true,
    ): ?ButcherComplianceOverride {
        if ($lockAndRecheck) {
            $batch = ButcherInventoryBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();
        } else {
            $batch->refresh();
        }

        $issues = $this->issuesForBatch($batch);
        if ($issues === []) {
            return null;
        }

        $reason = trim((string) ($overrideReason ?? ''));
        if ($reason === '') {
            throw ValidationException::withMessages([
                'batch_id' => [$this->blockMessage($batch, $issues)],
                'safety_override_required' => [__('An authorized override with a reason is required to proceed.')],
            ]);
        }

        if ($actor === null || ! $this->canOverride($actor, (int) $batch->business_id)) {
            throw ValidationException::withMessages([
                'batch_id' => [$this->blockMessage($batch, $issues)],
                'safety_override_reason' => [__('Only a Manager or Owner can override batch safety gates.')],
            ]);
        }

        return ButcherComplianceOverride::query()->create([
            'business_id' => $batch->business_id,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'batch_id' => $batch->id,
            'cut_output_id' => null,
            'reason' => $reason,
            'issues' => $issues,
            'overridden_by' => $actor->id,
            'overridden_at' => now(),
        ]);
    }

    /**
     * Gate every source batch linked to a cut output's cutting session.
     *
     * @return list<ButcherComplianceOverride>
     */
    public function assertCutOutputSafe(
        ButcherCutOutput $output,
        ?User $actor = null,
        ?string $overrideReason = null,
        string $contextType = ButcherComplianceOverride::CONTEXT_SALE,
        ?int $contextId = null,
    ): array {
        $output->loadMissing(['session.sources.batch', 'session.batch']);
        $session = $output->session;
        if ($session === null) {
            return [];
        }

        $batches = $this->batchesForSession($session);
        $overrides = [];

        foreach ($batches as $batch) {
            $override = $this->assertBatchSafe(
                $batch,
                $actor,
                $overrideReason,
                $contextType,
                $contextId,
                true,
            );

            if ($override !== null) {
                $override->update(['cut_output_id' => $output->id]);
                $overrides[] = $override->fresh();
            }
        }

        return $overrides;
    }

    /**
     * @return list<string>
     */
    public function issuesForBatch(ButcherInventoryBatch $batch): array
    {
        $issues = [];

        if ($batch->isExpired() || $batch->status === ButcherInventoryBatch::STATUS_EXPIRED) {
            $issues[] = ButcherComplianceOverride::ISSUE_EXPIRED;
        }

        if ((bool) $batch->temperature_breach) {
            $issues[] = ButcherComplianceOverride::ISSUE_TEMPERATURE_BREACH;
        }

        return $issues;
    }

    public function canOverride(User $user, int $businessId): bool
    {
        return $user->canButcherPermission(
            BusinessUser::PERMISSION_OVERRIDE_BUTCHER_BATCH_SAFETY,
            $businessId
        );
    }

    /**
     * @return Collection<int, ButcherInventoryBatch>
     */
    public function batchesForSession(ButcherCuttingSession $session): Collection
    {
        $batches = collect();

        if ($session->relationLoaded('sources') && $session->sources->isNotEmpty()) {
            foreach ($session->sources as $source) {
                if ($source->batch) {
                    $batches->put($source->batch->id, $source->batch);
                }
            }
        }

        if ($session->batch) {
            $batches->put($session->batch->id, $session->batch);
        }

        if ($batches->isEmpty() && $session->batch_id) {
            $batch = ButcherInventoryBatch::query()->find($session->batch_id);
            if ($batch) {
                $batches->put($batch->id, $batch);
            }
        }

        return $batches->values();
    }

    /**
     * @param  list<string>  $issues
     */
    private function blockMessage(ButcherInventoryBatch $batch, array $issues): string
    {
        $parts = [];
        if (in_array(ButcherComplianceOverride::ISSUE_TEMPERATURE_BREACH, $issues, true)) {
            $parts[] = __('temperature breach');
        }
        if (in_array(ButcherComplianceOverride::ISSUE_EXPIRED, $issues, true)) {
            $parts[] = __('expiry');
        }

        return __('Batch :number is blocked due to :issues. Escalate to a Manager/Owner for a logged override.', [
            'number' => $batch->batch_number,
            'issues' => implode(' / ', $parts),
        ]);
    }
}
