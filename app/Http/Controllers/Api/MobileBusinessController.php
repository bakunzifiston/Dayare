<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessRequest;
use App\Http\Responses\ApiJson;
use App\Models\Business;
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

        $business = $request->user()->businesses()->create($validated);

        foreach (array_values($members) as $i => $m) {
            $firstName = trim((string) ($m['first_name'] ?? ''));
            $lastName = trim((string) ($m['last_name'] ?? ''));
            if ($firstName !== '' || $lastName !== '') {
                $business->ownershipMembers()->create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $m['date_of_birth'] ?? null,
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
