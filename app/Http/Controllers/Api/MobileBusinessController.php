<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessRequest;
use App\Http\Responses\ApiJson;
use App\Models\Business;
use App\Models\BusinessUser;
use Illuminate\Http\JsonResponse;

class MobileBusinessController extends Controller
{
    /**
     * Register a new business for the authenticated user (stateless JSON; mirrors web BusinessController::store).
     */
    public function store(StoreBusinessRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $members = $validated['members'] ?? [];
        unset($validated['members']);

        $validated['type'] = $validated['type'] ?? Business::TYPE_PROCESSOR;
        $validated['pathway_status'] = $validated['pathway_status'] ?? 'active';

        $business = $request->user()->businesses()->create($validated);

        $role = match ($business->type) {
            Business::TYPE_FARMER => BusinessUser::ROLE_FARMER,
            Business::TYPE_LOGISTICS => BusinessUser::ROLE_LOGISTICS_MANAGER,
            default => BusinessUser::ROLE_ORG_ADMIN,
        };

        BusinessUser::query()->updateOrCreate(
            ['business_id' => $business->id, 'user_id' => $request->user()->id],
            ['role' => $role]
        );

        foreach (array_values($members) as $i => $m) {
            $firstName = trim((string) ($m['first_name'] ?? ''));
            $lastName = trim((string) ($m['last_name'] ?? ''));
            if ($firstName !== '' || $lastName !== '') {
                $business->ownershipMembers()->create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $m['date_of_birth'] ?? null,
                    'gender' => $m['gender'] ?? null,
                    'pwd_status' => $m['pwd_status'] ?? null,
                    'phone' => $m['phone'] ?? null,
                    'email' => $m['email'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        return ApiJson::success(
            $business->load(['ownershipMembers']),
            __('Business registered successfully.'),
            201
        );
    }
}
