<?php

namespace App\Http\Controllers\Butcher;

use App\Http\Controllers\Butcher\Concerns\InteractsWithAccessibleButcherBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Butcher\UpdateButcherTeamMemberRoleRequest;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ButcherTeamController extends Controller
{
    use InteractsWithAccessibleButcherBusiness;

    public function index(Request $request): View|RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        if ($business === null) {
            return redirect()->route('butcher.dashboard');
        }

        $members = BusinessUser::query()
            ->where('business_id', $business->id)
            ->with('user:id,name,email')
            ->orderBy('id')
            ->get();

        $owner = User::query()->find($business->user_id);

        return view('butcher.team.index', [
            'business' => $business,
            'members' => $members,
            'owner' => $owner,
            'roles' => BusinessUser::BUTCHER_ROLE_LABELS,
        ]);
    }

    public function update(UpdateButcherTeamMemberRoleRequest $request): RedirectResponse
    {
        $business = $this->primaryBusiness($request);
        abort_unless($business !== null, 404);

        $validated = $request->validated();
        $userId = (int) $validated['user_id'];

        abort_if($userId === (int) $business->user_id, 422, __('The business owner role cannot be changed here.'));

        BusinessUser::query()
            ->where('business_id', $business->id)
            ->where('user_id', $userId)
            ->update(['role' => $validated['role']]);

        return redirect()
            ->route('butcher.team.index')
            ->with('status', __('Team member role updated.'));
    }
}
