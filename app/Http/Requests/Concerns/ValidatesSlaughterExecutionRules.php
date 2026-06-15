<?php

namespace App\Http\Requests\Concerns;

use App\Models\AnteMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesSlaughterExecutionRules
{
    protected function prepareSlaughterExecutionValidation(): void
    {
        $items = $this->input('item_slaughters');
        if (! is_array($items)) {
            return;
        }

        $filtered = collect($items)
            ->filter(function ($item) {
                if (! is_array($item)) {
                    return false;
                }

                $quantity = $item['meat_quantity_kg'] ?? null;

                return $quantity !== null
                    && $quantity !== ''
                    && (float) $quantity > 0;
            })
            ->values()
            ->all();

        $merge = ['item_slaughters' => $filtered];
        if ($filtered !== []) {
            $merge['actual_animals_slaughtered'] = count($filtered);
        }

        $this->merge($merge);
    }

    /**
     * @return list<int>
     */
    protected function slaughteredItemIdsForPlan(int $planId, ?int $ignoreExecutionId = null): array
    {
        return SlaughterExecutionItem::query()
            ->whereHas('execution', function ($query) use ($planId, $ignoreExecutionId) {
                $query->where('slaughter_plan_id', $planId);
                if ($ignoreExecutionId !== null) {
                    $query->where('id', '!=', $ignoreExecutionId);
                }
            })
            ->pluck('animal_intake_item_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
    protected function resolveSlaughterPlanIdForValidation(): ?int
    {
        $planId = $this->input('slaughter_plan_id');
        if ($planId !== null && $planId !== '') {
            return (int) $planId;
        }

        $execution = $this->route('slaughter_execution');
        if ($execution instanceof SlaughterExecution) {
            return (int) $execution->slaughter_plan_id;
        }

        return null;
    }

    protected function validateSlaughterExecutionBusinessRules(Validator $validator): void
    {
        $planId = $this->resolveSlaughterPlanIdForValidation();
        if ($planId === null) {
            return;
        }

        $plan = SlaughterPlan::query()->find($planId);
        $latestAM = $plan?->anteMortemInspections()->latest('inspection_date')->first();

        if ($latestAM === null) {
            $validator->errors()->add(
                'slaughter_plan_id',
                __('This plan has no ante-mortem inspection recorded. Ante-mortem must be completed before slaughter.'),
            );

            return;
        }

        $slaughterTime = Carbon::parse($this->input('slaughter_time'));

        if ($slaughterTime->toDateString() < $latestAM->inspection_date->toDateString()) {
            $validator->errors()->add(
                'slaughter_time',
                __('Slaughter time cannot be before the ante-mortem inspection date.'),
            );
        }
        // 24-hour window is advisory only — shown on the execution report, not enforced on submit.

        if ($plan && $plan->assignedItems()->exists()) {
            $approvedCount = AnteMortemInspectionItem::query()
                ->whereHas('inspection', fn ($query) => $query->where('slaughter_plan_id', $plan->id))
                ->approved()
                ->count();
            $slaughtered = (int) $this->input('actual_animals_slaughtered');
            if ($slaughtered > $approvedCount) {
                $validator->errors()->add(
                    'actual_animals_slaughtered',
                    __('Only :count animals were approved at ante-mortem for this plan — cannot slaughter more than :count.', [
                        'count' => $approvedCount,
                    ]),
                );
            }
        } elseif ($plan) {
            $scheduled = (int) $plan->number_of_animals_scheduled;
            $slaughtered = (int) $this->input('actual_animals_slaughtered');
            if ($slaughtered > $scheduled) {
                $validator->errors()->add(
                    'actual_animals_slaughtered',
                    __('Cannot slaughter more animals (:slaughtered) than scheduled (:scheduled).', [
                        'slaughtered' => $slaughtered,
                        'scheduled' => $scheduled,
                    ]),
                );
            }
        }

        $execution = $this->route('slaughter_execution');
        $ignoreExecutionId = $execution instanceof SlaughterExecution ? (int) $execution->id : null;

        $itemSlaughters = $this->input('item_slaughters');
        if (is_array($itemSlaughters) && $itemSlaughters !== [] && $plan !== null) {
            $approvedItemIds = AnteMortemInspectionItem::query()
                ->whereHas('inspection', fn ($query) => $query->where('slaughter_plan_id', $plan->id))
                ->approved()
                ->pluck('animal_intake_item_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $alreadySlaughteredIds = $this->slaughteredItemIdsForPlan((int) $plan->id, $ignoreExecutionId);

            $seenItemIds = [];
            foreach ($itemSlaughters as $index => $item) {
                $itemId = (int) ($item['animal_intake_item_id'] ?? 0);

                if (! in_array($itemId, $approvedItemIds, true)) {
                    $validator->errors()->add(
                        "item_slaughters.{$index}.animal_intake_item_id",
                        __('This animal was not approved at ante-mortem for the selected plan.'),
                    );
                }

                if (in_array($itemId, $alreadySlaughteredIds, true)) {
                    $validator->errors()->add(
                        "item_slaughters.{$index}.animal_intake_item_id",
                        __('This animal has already been recorded as slaughtered on this session.'),
                    );
                }

                if (in_array($itemId, $seenItemIds, true)) {
                    $validator->errors()->add(
                        "item_slaughters.{$index}.animal_intake_item_id",
                        __('Each animal may only appear once in the slaughter record.'),
                    );
                }

                $seenItemIds[] = $itemId;
            }

            $approvedCount = count($approvedItemIds);
            $totalAfterSave = count($alreadySlaughteredIds) + count($seenItemIds);
            if ($totalAfterSave > $approvedCount) {
                $validator->errors()->add(
                    'item_slaughters',
                    __('Only :count animals were approved at ante-mortem — :remaining still pending slaughter.', [
                        'count' => $approvedCount,
                        'remaining' => max(0, $approvedCount - count($alreadySlaughteredIds)),
                    ]),
                );
            }
        }

        // Species field not on SlaughterExecution yet — plan species match deferred to a future section.
    }
}
