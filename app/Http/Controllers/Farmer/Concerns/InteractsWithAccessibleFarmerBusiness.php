<?php

namespace App\Http\Controllers\Farmer\Concerns;

use App\Models\FeedType;
use App\Models\Livestock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait InteractsWithAccessibleFarmerBusiness
{
    /**
     * @return Collection<int, int>
     */
    protected function accessibleBusinessIds(Request $request): Collection
    {
        return $request->user()->accessibleFarmerBusinessIds();
    }

    /**
     * @return Builder<FeedType>
     */
    protected function accessibleFeedTypesQuery(Request $request): Builder
    {
        return FeedType::query()
            ->whereIn('business_id', $this->accessibleBusinessIds($request));
    }

    /**
     * @return Builder<Livestock>
     */
    protected function accessibleLivestockQuery(Request $request): Builder
    {
        return Livestock::query()
            ->whereHas('farm', fn (Builder $query) => $query->whereIn('business_id', $this->accessibleBusinessIds($request)))
            ->with('farm');
    }
}
