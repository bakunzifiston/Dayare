<?php

namespace App\Http\Controllers\mobileapi;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Http\Responses\ApiJson;
use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    /**
     * List all facilities for a business.
     * GET /api/v1/mobileapi/businesses/{business}/facilities
     */
    public function index(Request $request, Business $business): JsonResponse
    {
        $this->authorizeAccess($request, $business);

        $facilities = $business->facilities()
            ->latest()
            ->get();

        return ApiJson::success($facilities);
    }

    /**
     * Create a new facility for a business.
     * POST /api/v1/mobileapi/businesses/{business}/facilities
     */
    public function store(StoreFacilityRequest $request, Business $business): JsonResponse
    {
        $this->authorizeAccess($request, $business);

        $data = $this->resolveLocationIds($request->validated());
        $facility = $business->facilities()->create($data);

        return ApiJson::success(
            $facility,
            __('Facility created successfully.'),
            201
        );
    }

    /**
     * Show a specific facility.
     * GET /api/v1/mobileapi/businesses/{business}/facilities/{facility}
     */
    public function show(Request $request, Business $business, Facility $facility): JsonResponse
    {
        $this->authorizeAccess($request, $business);
        $this->ensureBelongs($business, $facility);

        return ApiJson::success($facility);
    }

    /**
     * Update a specific facility.
     * PUT/PATCH /api/v1/mobileapi/businesses/{business}/facilities/{facility}
     */
    public function update(UpdateFacilityRequest $request, Business $business, Facility $facility): JsonResponse
    {
        $this->authorizeAccess($request, $business);
        $this->ensureBelongs($business, $facility);

        $data = $this->resolveLocationIds($request->validated());
        $facility->update($data);

        return ApiJson::success(
            $facility->fresh(),
            __('Facility updated successfully.')
        );
    }

    /**
     * Remove a specific facility.
     * DELETE /api/v1/mobileapi/businesses/{business}/facilities/{facility}
     */
    public function destroy(Request $request, Business $business, Facility $facility): JsonResponse
    {
        $this->authorizeAccess($request, $business);
        $this->ensureBelongs($business, $facility);

        $facility->delete();

        return ApiJson::success(null, __('Facility deleted successfully.'));
    }

    /**
     * Resolve location names to IDs.
     */
    private function resolveLocationIds(array $data): array
    {
        $mappings = [
            'country' => ['id' => 'country_id', 'type' => AdministrativeDivision::TYPE_COUNTRY],
            'province' => ['id' => 'province_id', 'type' => AdministrativeDivision::TYPE_PROVINCE],
            'district' => ['id' => 'district_id', 'type' => AdministrativeDivision::TYPE_DISTRICT],
            'sector' => ['id' => 'sector_id', 'type' => AdministrativeDivision::TYPE_SECTOR],
            'cell' => ['id' => 'cell_id', 'type' => AdministrativeDivision::TYPE_CELL],
            'village' => ['id' => 'village_id', 'type' => AdministrativeDivision::TYPE_VILLAGE],
        ];

        foreach ($mappings as $nameKey => $map) {
            $nameValue = isset($data[$nameKey]) ? trim((string) $data[$nameKey]) : null;
            if (empty($nameValue)) {
                continue;
            }

            $idKey = $map['id'];

            // If ID is already provided, skip lookup
            if (!empty($data[$idKey])) {
                continue;
            }

            // Look up the ID
            $query = AdministrativeDivision::where('type', $map['type']);

            if ($map['type'] === AdministrativeDivision::TYPE_PROVINCE) {
                $query->where(function ($q) use ($nameValue) {
                    $q->where('name', 'like', $nameValue)
                        ->orWhere('name', 'like', $nameValue . ' Province')
                        ->orWhere('name', 'like', 'City of ' . $nameValue);
                });
            } else {
                $query->where('name', 'like', $nameValue);
            }

            $division = $query->first();
            if ($division) {
                $data[$idKey] = $division->id;
            }
        }

        return $data;
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

    /**
     * Private helper to ensure the facility belongs to the business.
     */
    private function ensureBelongs(Business $business, Facility $facility): void
    {
        if ((int) $facility->business_id !== (int) $business->id) {
            abort(404, __('Facility does not belong to this business.'));
        }
    }
}
