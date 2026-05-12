<?php

namespace App\Http\Controllers\Farmer\Concerns;

use App\Models\Animal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait InteractsWithAccessibleAnimals
{
    /**
     * @return Builder<Animal>
     */
    protected function accessibleAnimalsQuery(Request $request): Builder
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();

        return Animal::query()
            ->whereHas('livestock.farm', fn (Builder $query) => $query->whereIn('business_id', $farmerIds))
            ->with(['livestock.farm'])
            ->orderedForSelection();
    }

    /**
     * @return Collection<int, int>
     */
    protected function accessibleAnimalIds(Request $request): Collection
    {
        return $this->accessibleAnimalsQuery($request)->pluck('id');
    }

    protected function findAccessibleAnimal(Request $request, int $animalId): ?Animal
    {
        return $this->accessibleAnimalsQuery($request)->whereKey($animalId)->first();
    }
}
