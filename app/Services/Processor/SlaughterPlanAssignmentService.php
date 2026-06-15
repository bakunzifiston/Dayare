<?php

namespace App\Services\Processor;

use App\Exceptions\InsufficientAnimalsException;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\SlaughterPlan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Assigns and releases individual {@see AnimalIntakeItem} rows for slaughter plans.
 */
class SlaughterPlanAssignmentService
{
    /**
     * @return Collection<int, AnimalIntakeItem>
     */
    public function assignItemsToPlan(SlaughterPlan $plan, int $count, ?string $species = null): Collection
    {
        if ($count < 1) {
            return new Collection;
        }

        $plan->loadMissing('animalIntake');
        $intake = $plan->animalIntake;
        $species = $species ?? (string) $plan->species;

        if (! $intake) {
            throw new InsufficientAnimalsException(0, $count, $species);
        }

        /** @var Collection<int, AnimalIntakeItem> $items */
        $items = $intake->items()
            ->available()
            ->bySpecies($species)
            ->orderBy('id')
            ->limit($count)
            ->lockForUpdate()
            ->get();

        if ($items->count() < $count) {
            throw new InsufficientAnimalsException($items->count(), $count, $species);
        }

        foreach ($items as $item) {
            $item->update(['slaughter_plan_id' => $plan->id]);
        }

        return $items;
    }

    public function releaseItemsFromPlan(SlaughterPlan $plan): void
    {
        AnimalIntakeItem::query()
            ->where('slaughter_plan_id', $plan->id)
            ->update(['slaughter_plan_id' => null]);
    }

    public function rebalancePlan(SlaughterPlan $plan, int $newCount, string $newSpecies): void
    {
        DB::transaction(function () use ($plan, $newCount, $newSpecies): void {
            $this->releaseItemsFromPlan($plan);
            $this->assignItemsToPlan($plan, $newCount, $newSpecies);
        });
    }

    public function availableCountForSpecies(AnimalIntake $intake, string $species): int
    {
        return (int) $intake->items()
            ->available()
            ->bySpecies($species)
            ->count();
    }

    /**
     * Assign animals to slaughter plans that predate per-animal item rows.
     *
     * @return array{plans_assigned: int, items_assigned: int, plans_skipped: int}
     */
    public function assignOrphanedPlans(): array
    {
        $plansAssigned = 0;
        $itemsAssigned = 0;
        $plansSkipped = 0;

        SlaughterPlan::query()
            ->withAssignmentGap()
            ->whereHas('animalIntake', fn ($query) => $query->whereHas('items'))
            ->with('animalIntake')
            ->orderBy('id')
            ->chunkById(50, function ($plans) use (&$plansAssigned, &$itemsAssigned, &$plansSkipped): void {
                foreach ($plans as $plan) {
                    $count = (int) $plan->number_of_animals_scheduled;
                    if ($count < 1) {
                        $plansSkipped++;

                        continue;
                    }

                    try {
                        $assigned = DB::transaction(
                            fn () => $this->assignItemsToPlan($plan, $count, (string) $plan->species),
                        );
                        $plansAssigned++;
                        $itemsAssigned += $assigned->count();
                    } catch (InsufficientAnimalsException) {
                        $plansSkipped++;
                    }
                }
            });

        return [
            'plans_assigned' => $plansAssigned,
            'items_assigned' => $itemsAssigned,
            'plans_skipped' => $plansSkipped,
        ];
    }
}
