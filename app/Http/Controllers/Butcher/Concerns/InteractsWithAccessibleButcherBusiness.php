<?php

namespace App\Http\Controllers\Butcher\Concerns;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait InteractsWithAccessibleButcherBusiness
{
    /**
     * @return Collection<int, int>
     */
    protected function accessibleBusinessIds(Request $request): Collection
    {
        return $request->user()->accessibleButcherBusinessIds();
    }

    protected function primaryBusiness(Request $request): ?Business
    {
        return Business::query()
            ->whereIn('id', $this->accessibleBusinessIds($request))
            ->orderBy('id')
            ->first();
    }
}
