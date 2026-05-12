<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleAnimals;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleFarmerBusiness;
use App\Http\Requests\Farmer\StoreFeedingScheduleRequest;
use App\Models\FeedingSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedingScheduleController extends Controller
{
    use InteractsWithAccessibleAnimals;
    use InteractsWithAccessibleFarmerBusiness;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FeedingSchedule::class);

        $records = FeedingSchedule::query()
            ->whereIn('business_id', $this->accessibleBusinessIds($request))
            ->with(['animal', 'livestock', 'feedType'])
            ->latest()
            ->paginate(20);

        return view('farmer.feeding.schedules.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FeedingSchedule::class);

        $businesses = $this->accessibleBusinessIds($request);
        $animals = $this->accessibleAnimalsQuery($request)->orderBy('animal_code')->get();
        $livestock = $this->accessibleLivestockQuery($request)->orderBy('livestock_name')->get();
        $feedTypes = $this->accessibleFeedTypesQuery($request)->where('status', 'active')->orderBy('feed_name')->get();

        return view('farmer.feeding.schedules.create', compact('businesses', 'animals', 'livestock', 'feedTypes'));
    }

    public function store(StoreFeedingScheduleRequest $request): RedirectResponse
    {
        $this->authorize('create', FeedingSchedule::class);
        abort_unless($this->accessibleBusinessIds($request)->contains((int) $request->validated('business_id')), 403);

        $data = $request->validated();
        unset($data['target_type']);
        if ($request->validated('target_type') === 'animal') {
            $data['livestock_id'] = null;
        } else {
            $data['animal_id'] = null;
        }
        $data['created_by'] = $request->user()->id;

        $record = FeedingSchedule::query()->create($data);

        return redirect()->route('farmer.feeding.schedules.index')->with('status', __('Feeding schedule created.'));
    }
}
