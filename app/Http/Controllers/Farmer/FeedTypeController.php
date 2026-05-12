<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleFarmerBusiness;
use App\Http\Requests\Farmer\StoreFeedTypeRequest;
use App\Http\Requests\Farmer\UpdateFeedTypeRequest;
use App\Models\FeedType;
use App\Services\Farmer\FeedCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedTypeController extends Controller
{
    use InteractsWithAccessibleFarmerBusiness;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FeedType::class);

        $query = $this->accessibleFeedTypesQuery($request)->latest();
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('feed_name', 'like', '%'.$search.'%')->orWhere('feed_code', 'like', '%'.$search.'%');
            });
        }
        if ($status = (string) $request->query('status', '')) {
            $query->where('status', $status);
        }

        $records = $query->paginate(20)->withQueryString();

        return view('farmer.feeding.feed-types.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FeedType::class);
        $businesses = $this->accessibleBusinessIds($request);

        return view('farmer.feeding.feed-types.create', compact('businesses'));
    }

    public function store(StoreFeedTypeRequest $request, FeedCodeService $codes): RedirectResponse
    {
        $this->authorize('create', FeedType::class);
        abort_unless($this->accessibleBusinessIds($request)->contains((int) $request->validated('business_id')), 403);

        $data = $request->validated();
        $data['feed_code'] = $codes->generateFeedTypeCode((int) $data['business_id']);
        $data['created_by'] = $request->user()->id;

        $record = FeedType::query()->create($data);

        return redirect()->route('farmer.feeding.feed-types.show', $record)->with('status', __('Feed type created.'));
    }

    public function show(FeedType $feed_type): View
    {
        $this->authorize('view', $feed_type);

        return view('farmer.feeding.feed-types.show', ['record' => $feed_type]);
    }

    public function edit(FeedType $feed_type): View
    {
        $this->authorize('update', $feed_type);

        return view('farmer.feeding.feed-types.edit', ['record' => $feed_type]);
    }

    public function update(UpdateFeedTypeRequest $request, FeedType $feed_type): RedirectResponse
    {
        $this->authorize('update', $feed_type);
        $feed_type->update($request->validated());

        return redirect()->route('farmer.feeding.feed-types.show', $feed_type)->with('status', __('Feed type updated.'));
    }

    public function destroy(FeedType $feed_type): RedirectResponse
    {
        $this->authorize('delete', $feed_type);
        $feed_type->delete();

        return redirect()->route('farmer.feeding.feed-types.index')->with('status', __('Feed type archived.'));
    }
}
