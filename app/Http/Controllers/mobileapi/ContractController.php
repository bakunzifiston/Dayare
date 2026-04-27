<?php

namespace App\Http\Controllers\mobileapi;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractRequest;
use App\Http\Responses\ApiJson;
use App\Models\Business;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    /**
     * List all contracts for a business.
     * GET /api/v1/mobileapi/businesses/{business}/contracts
     */
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->authorizeAccess($request, $business);

        $contracts = $business->contracts()
            ->with(['supplier', 'employee', 'facility', 'client'])
            ->latest('start_date')
            ->get();

        return ApiJson::success($contracts);
    }

    /**
     * Create a new contract for a business.
     * POST /api/v1/mobileapi/businesses/{business}/contracts
     */
    public function store(StoreContractRequest $request, Business $business): JsonResponse
    {
        $this->authorizeAccess($request, $business);

        // Ensure the business_id in the request matches the route parameter
        if ((int) $request->validated('business_id') !== (int) $business->id) {
            return ApiJson::failure(__('Business ID mismatch.'), [], 422);
        }

        $contract = $business->contracts()->create($request->validated());

        return ApiJson::success(
            $contract->load(['supplier', 'employee', 'facility', 'client']),
            __('Contract created successfully.'),
            201
        );
    }

    /**
     * Private helper to authorize business access.
     */
    private function authorizeAccess(Request $request, Business $business): void
    {
        $user = $request->user();

        // 1. Super Admins have access to everything
        if ($user->isSuperAdmin()) {
            return;
        }

        // 2. Check if user is the primary owner
        if ($business->user_id !== null && (int) $user->id === (int) $business->user_id) {
            return;
        }

        // 3. Check if user is a member through the business_user table
        $isMember = $user->memberBusinesses()->where('businesses.id', $business->id)->exists();
        if ($isMember) {
            return;
        }

        // 4. If none of the above, deny access
        abort(403, __('You do not have access to this business.'));
    }
}
