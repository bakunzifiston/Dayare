<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\Livestock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmerLivestockHubController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $farmerIds = $request->user()->accessibleFarmerBusinessIds();
        $farmIds = Farm::query()
            ->whereIn('business_id', $farmerIds)
            ->pluck('id');

        if ($farmIds->isEmpty()) {
            return redirect()
                ->route('farmer.farms.index')
                ->with('status', __('Please create a farm first to manage livestock.'));
        }

        $query = Livestock::query()
            ->whereIn('farm_id', $farmIds)
            ->with('farm')
            ->withCount('animals');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('livestock_name', 'like', '%'.$search.'%')
                    ->orWhere('livestock_code', 'like', '%'.$search.'%')
                    ->orWhere('livestock_type', 'like', '%'.$search.'%');
            });
        }

        foreach (['status', 'health_status', 'lifecycle_status'] as $filter) {
            $value = (string) $request->query($filter, '');
            if ($value !== '') {
                $query->where($filter, $value);
            }
        }

        $rows = $query
            ->orderBy('livestock_name')
            ->paginate(12)
            ->withQueryString();

        $allGroups = Livestock::query()
            ->whereIn('farm_id', $farmIds)
            ->get();

        $healthHeadcounts = Livestock::aggregateHealthQuantities($allGroups);

        $stats = [
            'groups' => $allGroups->count(),
            'headcount' => (int) $allGroups->sum(fn (Livestock $row) => (int) ($row->total_count ?? $row->total_quantity)),
            'active_groups' => $allGroups->where('status', Livestock::STATUS_ACTIVE)->count(),
            'quarantined_groups' => $allGroups->where('lifecycle_status', Livestock::LIFECYCLE_QUARANTINED)->count(),
        ];

        return view('farmer.livestock.hub', compact('rows', 'healthHeadcounts', 'stats'));
    }
}
