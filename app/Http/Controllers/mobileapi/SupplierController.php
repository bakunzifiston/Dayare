<?php

namespace App\Http\Controllers\mobileapi;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiJson;
use App\Models\Business;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * List all suppliers for a business.
     * GET /api/v1/mobileapi/businesses/{business}/suppliers
     */
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->authorizeAccess($request, $business);

        $suppliers = $business->suppliers()
            ->latest()
            ->get();

        return ApiJson::success($suppliers);
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
