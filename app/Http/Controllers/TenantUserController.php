<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantUserController extends Controller
{
    public static function roleOptions(): array
    {
        return [
            BusinessUser::ROLE_ORG_ADMIN => __('Org Admin'),
            BusinessUser::ROLE_OPERATIONS_MANAGER => __('Operations Manager'),
            BusinessUser::ROLE_COMPLIANCE_OFFICER => __('Compliance Officer'),
            BusinessUser::ROLE_INSPECTOR => __('Inspector'),
            BusinessUser::ROLE_TRANSPORT_MANAGER => __('Transport Manager'),
        ];
    }

    public static function roleGuidance(): array
    {
        return [
            BusinessUser::ROLE_ORG_ADMIN => [
                'description' => __('Full workspace governance and cross-module visibility.'),
                'permissions' => [
                    __('View all modules'),
                    __('Manage business users'),
                    __('Assign business roles'),
                    __('Monitor compliance metrics'),
                    __('Track delivery status'),
                ],
            ],
            BusinessUser::ROLE_OPERATIONS_MANAGER => [
                'description' => __('Runs day-to-day processing operations and planning.'),
                'permissions' => [
                    __('Create animal intake records'),
                    __('Schedule slaughter plans'),
                    __('Create processing batches'),
                    __('Assign batches to inspectors'),
                    __('View inspections and certificates'),
                ],
            ],
            BusinessUser::ROLE_COMPLIANCE_OFFICER => [
                'description' => __('Oversees compliance controls, evidence, and monitoring.'),
                'permissions' => [
                    __('Submit compliance checklists'),
                    __('Log non-compliance issues'),
                    __('Upload compliance evidence'),
                    __('Monitor compliance metrics'),
                    __('Monitor temperature logs'),
                ],
            ],
            BusinessUser::ROLE_INSPECTOR => [
                'description' => __('Handles ante/post-mortem inspections and certification steps.'),
                'permissions' => [
                    __('Record ante-mortem inspections'),
                    __('Record post-mortem inspections'),
                    __('Issue certificates'),
                    __('View assigned batches'),
                    __('View inspections and certificates'),
                ],
            ],
            BusinessUser::ROLE_TRANSPORT_MANAGER => [
                'description' => __('Manages trip dispatch, delivery confirmation, and transport tracking.'),
                'permissions' => [
                    __('Create transport trips'),
                    __('Assign vehicle and driver'),
                    __('Dispatch deliveries'),
                    __('Confirm deliveries'),
                    __('Track delivery status'),
                ],
            ],
        ];
    }

    public function index(Request $request): View|RedirectResponse
    {
        $manageableBusinesses = $this->manageableBusinesses($request);
        $this->authorizeOwnerOrFail($request, $manageableBusinesses);
        $manageableBusinessIds = $manageableBusinesses->pluck('id')->map(fn ($id) => (int) $id)->all();

        $users = User::query()
            ->whereIn('id', BusinessUser::query()->whereIn('business_id', $manageableBusinessIds)->pluck('user_id'))
            ->orderBy('name')
            ->get();

        $userBusinessRoles = [];
        foreach ($users as $u) {
            $pivots = BusinessUser::query()
                ->where('user_id', (int) $u->id)
                ->whereIn('business_id', $manageableBusinessIds)
                ->with('business:id,business_name')
                ->orderBy('business_id')
                ->get();
            $userBusinessRoles[$u->id] = $pivots
                ->map(fn (BusinessUser $pivot) => [
                    'business_name' => $pivot->business?->business_name ?? '—',
                    'role' => $pivot->role,
                ])
                ->all();
        }

        $validRoles = BusinessUser::ROLES;
        $usersWithoutAssignedRoles = BusinessUser::query()
            ->whereIn('business_id', $manageableBusinessIds)
            ->where(function ($query) use ($validRoles): void {
                $query->whereNull('role')
                    ->orWhereNotIn('role', $validRoles);
            })
            ->count();
        $pendingInvitations = $users->filter(fn (User $u) => $u->email_verified_at === null)->count();
        $roleConflicts = BusinessUser::query()
            ->whereIn('business_id', $manageableBusinessIds)
            ->whereNotIn('role', $validRoles)
            ->count();
        $roleSummary = BusinessUser::query()
            ->whereIn('business_id', $manageableBusinessIds)
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role')
            ->mapWithKeys(fn ($total, $role) => [$role => (int) $total])
            ->all();

        $kpis = [
            'total_users' => $users->count(),
            'active_users' => $users->filter(fn (User $u) => $u->email_verified_at !== null)->count(),
            'users_by_role' => $roleSummary,
            'recently_added_users' => $users->filter(fn (User $u) => optional($u->created_at)?->gte(now()->subDays(7)))->count(),
        ];
        $alerts = [
            'users_without_roles' => $usersWithoutAssignedRoles,
            'pending_invitations' => $pendingInvitations,
            'role_conflicts' => $roleConflicts,
        ];

        return view('tenant-users.index', compact('users', 'userBusinessRoles', 'kpis', 'alerts'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $manageableBusinesses = $this->manageableBusinesses($request);
        $this->authorizeOwnerOrFail($request, $manageableBusinesses);

        $roleOptions = self::roleOptions();
        $roleGuidance = self::roleGuidance();
        $assignableBusinesses = $manageableBusinesses->map(fn ($business) => [
            'id' => (int) $business->id,
            'name' => (string) $business->business_name,
        ])->all();

        return view('tenant-users.create', compact('roleOptions', 'roleGuidance', 'assignableBusinesses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $manageableBusinesses = $this->manageableBusinesses($request);
        $this->authorizeOwnerOrFail($request, $manageableBusinesses);
        $manageableBusinessIds = $manageableBusinesses->pluck('id')->map(fn ($id) => (int) $id)->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(BusinessUser::ROLES)],
            'business_ids' => ['required', 'array', 'min:1'],
            'business_ids.*' => ['required', 'integer', Rule::in($manageableBusinessIds)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $businessIds = collect($validated['business_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        foreach ($businessIds as $businessId) {
            BusinessUser::query()->updateOrCreate(
                ['business_id' => $businessId, 'user_id' => $user->id],
                ['role' => $validated['role']]
            );
        }

        return redirect()->route('tenant-users.index')->with('status', __('User created successfully.'));
    }

    public function edit(Request $request, User $tenant_user): View|RedirectResponse
    {
        $manageableBusinesses = $this->manageableBusinesses($request);
        $this->authorizeOwnerOrFail($request, $manageableBusinesses);
        $manageableBusinessIds = $manageableBusinesses->pluck('id')->map(fn ($id) => (int) $id)->all();

        $me = $request->user();
        $user = $tenant_user;
        $userId = (int) $user->id;
        if ($userId === (int) $me->id) {
            return redirect()->route('tenant-users.index')->with('info', __('You cannot modify your own role.'));
        }

        $assignments = BusinessUser::query()
            ->where('user_id', $userId)
            ->whereIn('business_id', $manageableBusinessIds)
            ->get(['business_id', 'role']);
        if ($assignments->isEmpty()) {
            abort(404, __('User not found in your team.'));
        }

        $currentRole = $assignments->first()?->role ?? BusinessUser::ROLE_OPERATIONS_MANAGER;
        $selectedBusinessIds = $assignments->pluck('business_id')->map(fn ($id) => (int) $id)->all();
        $roleOptions = self::roleOptions();
        $assignableBusinesses = $manageableBusinesses->map(fn ($business) => [
            'id' => (int) $business->id,
            'name' => (string) $business->business_name,
        ])->all();

        return view('tenant-users.edit', compact('user', 'currentRole', 'selectedBusinessIds', 'roleOptions', 'assignableBusinesses'));
    }

    public function update(Request $request, User $tenant_user): RedirectResponse
    {
        $manageableBusinesses = $this->manageableBusinesses($request);
        $this->authorizeOwnerOrFail($request, $manageableBusinesses);
        $manageableBusinessIds = $manageableBusinesses->pluck('id')->map(fn ($id) => (int) $id)->all();

        $user = $tenant_user;
        $me = $request->user();
        $userId = (int) $user->id;
        if ($userId === (int) $me->id) {
            return redirect()->route('tenant-users.index')->with('error', __('You cannot modify your own role.'));
        }

        $existingAssignments = BusinessUser::query()
            ->where('user_id', $userId)
            ->whereIn('business_id', $manageableBusinessIds)
            ->get(['business_id', 'role']);
        if ($existingAssignments->isEmpty()) {
            abort(404, __('User not found in your team.'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(BusinessUser::ROLES)],
            'business_ids' => ['required', 'array', 'min:1'],
            'business_ids.*' => ['required', 'integer', Rule::in($manageableBusinessIds)],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);
        if (! empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $selectedBusinessIds = collect($validated['business_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $currentAdminBusinessIds = $existingAssignments
            ->filter(fn (BusinessUser $assignment) => $assignment->role === BusinessUser::ROLE_ORG_ADMIN)
            ->pluck('business_id')
            ->map(fn ($id) => (int) $id);
        foreach ($currentAdminBusinessIds as $businessId) {
            $remainsAdmin = $validated['role'] === BusinessUser::ROLE_ORG_ADMIN
                && $selectedBusinessIds->contains($businessId);
            if (! $remainsAdmin && $this->isLastAdmin((int) $businessId, $userId)) {
                return redirect()->route('tenant-users.index')->with('error', __('Cannot remove the last org admin from one of your businesses.'));
            }
        }

        BusinessUser::query()
            ->where('user_id', $user->id)
            ->whereIn('business_id', $manageableBusinessIds)
            ->delete();
        foreach ($selectedBusinessIds as $businessId) {
            BusinessUser::query()->updateOrCreate(
                ['business_id' => $businessId, 'user_id' => $user->id],
                ['role' => $validated['role']]
            );
        }

        return redirect()->route('tenant-users.index')->with('status', __('User updated successfully.'));
    }

    public function destroy(Request $request, User $tenant_user): RedirectResponse
    {
        $manageableBusinesses = $this->manageableBusinesses($request);
        $this->authorizeOwnerOrFail($request, $manageableBusinesses);
        $manageableBusinessIds = $manageableBusinesses->pluck('id')->map(fn ($id) => (int) $id)->all();

        $user = $tenant_user;
        $me = $request->user();
        $userId = (int) $user->id;
        if ($userId === (int) $me->id) {
            return redirect()->route('tenant-users.index')->with('error', __('You cannot remove yourself.'));
        }
        $existingAssignments = BusinessUser::query()
            ->where('user_id', $userId)
            ->whereIn('business_id', $manageableBusinessIds)
            ->get(['business_id', 'role']);
        if ($existingAssignments->isEmpty()) {
            abort(404, __('User not found in your team.'));
        }

        foreach ($existingAssignments as $assignment) {
            if ($assignment->role === BusinessUser::ROLE_ORG_ADMIN
                && $this->isLastAdmin((int) $assignment->business_id, $userId)) {
                return redirect()->route('tenant-users.index')->with('error', __('Cannot remove the last org admin from one of your businesses.'));
            }
        }

        BusinessUser::query()
            ->where('user_id', $userId)
            ->whereIn('business_id', $manageableBusinessIds)
            ->delete();

        return redirect()->route('tenant-users.index')->with('status', __('User removed from your team.'));
    }

    private function manageableBusinesses(Request $request): EloquentCollection
    {
        $owned = $request->user()
            ->businesses()
            ->where('type', Business::TYPE_PROCESSOR)
            ->orderBy('business_name')
            ->get(['id', 'business_name']);
        if ($owned->isNotEmpty()) {
            return $owned;
        }

        // Backward-compatible fallback for legacy accounts where ownership may only exist via membership.
        $accessibleProcessorIds = $request->user()->accessibleProcessorBusinessIds()->all();
        if ($accessibleProcessorIds === []) {
            return collect();
        }

        return Business::query()
            ->where('type', Business::TYPE_PROCESSOR)
            ->whereIn('id', $accessibleProcessorIds)
            ->orderBy('business_name')
            ->get(['id', 'business_name']);
    }

    private function authorizeOwnerOrFail(Request $request, EloquentCollection $manageableBusinesses): void
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($manageableBusinesses->isEmpty()) {
            abort(403, __('Only account owners can manage users.'));
        }
    }

    private function isLastAdmin(int $businessId, int $targetUserId): bool
    {
        $targetRole = BusinessUser::query()
            ->where('business_id', $businessId)
            ->where('user_id', $targetUserId)
            ->value('role');
        if ($targetRole !== BusinessUser::ROLE_ORG_ADMIN) {
            return false;
        }

        $adminCount = BusinessUser::query()
            ->where('business_id', $businessId)
            ->where('role', BusinessUser::ROLE_ORG_ADMIN)
            ->count();

        return $adminCount <= 1;
    }
}
