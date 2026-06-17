<?php

namespace App\Services\Butcher;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\ButcherOutlet;
use App\Models\ButcherPermit;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Support\ButcherPermitDocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ButcherOnboardingService
{
    public function createBusinessProfile(array $data, User $user): Business
    {
        $business = $this->resolveButcherBusiness($user);
        $businessName = trim((string) ($data['business_name'] ?? $business->business_name));

        $business->fill([
            'business_name' => $businessName,
            'business_name_normalized' => Business::normalizeDisplayName($businessName),
            'registration_number' => (string) $data['rdb_registration_number'],
            'tax_id' => (string) $data['tin_number'],
            'contact_phone' => (string) $data['phone'],
            'butchery_type' => (string) $data['butchery_type'],
            'rfa_permit_number' => $data['rfa_permit_number'] ?? null,
            'rfa_permit_expiry' => $data['rfa_permit_expiry'] ?? null,
            'butcher_district' => (string) $data['district'],
            'butcher_sector' => $data['sector'] ?? null,
            'butcher_cell' => $data['cell'] ?? null,
            'gps_lat' => $data['gps_lat'] ?? null,
            'gps_lng' => $data['gps_lng'] ?? null,
            'status' => Business::STATUS_ACTIVE,
        ]);

        $business->save();

        return $business->fresh();
    }

    public function addOutlet(Business $business, array $data): ButcherOutlet
    {
        return DB::transaction(function () use ($business, $data) {
            $isPrimary = (bool) ($data['is_primary'] ?? false);

            if ($isPrimary) {
                $business->butcherOutlets()->update(['is_primary' => false]);
            }

            return $business->butcherOutlets()->create([
                'name' => (string) $data['name'],
                'district' => (string) $data['district'],
                'sector' => $data['sector'] ?? null,
                'phone' => (string) $data['phone'],
                'gps_lat' => $data['gps_lat'] ?? null,
                'gps_lng' => $data['gps_lng'] ?? null,
                'is_primary' => $isPrimary,
                'status' => ButcherOutlet::STATUS_ACTIVE,
            ]);
        });
    }

    public function uploadPermit(Business $business, array $data, ?UploadedFile $file): ButcherPermit
    {
        $documentPath = null;
        if ($file instanceof UploadedFile) {
            $documentPath = ButcherPermitDocumentStorage::store($file, (int) $business->id);
        }

        return $business->butcherPermits()->create([
            'permit_type' => (string) $data['permit_type'],
            'permit_number' => (string) $data['permit_number'],
            'issued_by' => (string) $data['issued_by'],
            'issue_date' => $data['issue_date'],
            'expiry_date' => $data['expiry_date'],
            'document_path' => $documentPath,
            'status' => ButcherPermit::STATUS_VALID,
        ]);
    }

    public function createSupplier(Business $business, array $data): ButcherSupplier
    {
        return $business->butcherSuppliers()->create([
            'name' => (string) $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'supplier_type' => (string) $data['supplier_type'],
            'district' => $data['district'] ?? null,
            'sector' => $data['sector'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function updateSupplier(ButcherSupplier $supplier, array $data): void
    {
        $supplier->update([
            'name' => (string) $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'supplier_type' => (string) $data['supplier_type'],
            'district' => $data['district'] ?? null,
            'sector' => $data['sector'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @return array{
     *   percent: int,
     *   completed_steps: int,
     *   total_steps: int,
     *   steps: list<array{key: string, label: string, complete: bool, route: string}>
     * }
     */
    public function getOnboardingProgress(Business $business): array
    {
        $steps = [
            [
                'key' => 'profile',
                'label' => __('Business profile'),
                'complete' => $this->isProfileComplete($business),
                'route' => 'butcher.onboarding.profile',
            ],
            [
                'key' => 'outlets',
                'label' => __('Outlets'),
                'complete' => $business->butcherOutlets()->exists(),
                'route' => 'butcher.onboarding.outlets',
            ],
            [
                'key' => 'permits',
                'label' => __('Permits'),
                'complete' => $business->butcherPermits()->exists(),
                'route' => 'butcher.onboarding.permits',
            ],
            [
                'key' => 'suppliers',
                'label' => __('Suppliers'),
                'complete' => $business->butcherSuppliers()->exists(),
                'route' => 'butcher.onboarding.suppliers',
            ],
        ];

        $completed = collect($steps)->where('complete', true)->count();

        return [
            'percent' => (int) round(($completed / count($steps)) * 100),
            'completed_steps' => $completed,
            'total_steps' => count($steps),
            'steps' => $steps,
        ];
    }

    public function isProfileComplete(Business $business): bool
    {
        return $business->isButcher()
            && ! $business->hasPlaceholderRegistration()
            && filled($business->tax_id)
            && filled($business->contact_phone)
            && $business->contact_phone !== '0000000000'
            && filled($business->butchery_type)
            && filled($business->butcher_district)
            && preg_match('/^\+2507[0-9]{8}$/', (string) $business->contact_phone) === 1
            && preg_match('/^\d{10}$/', (string) $business->tax_id) === 1;
    }

    public function resolveButcherBusiness(User $user): Business
    {
        $business = Business::query()
            ->where('type', Business::TYPE_BUTCHER)
            ->whereIn('id', $user->accessibleButcherBusinessIds())
            ->orderBy('id')
            ->first();

        abort_unless($business instanceof Business, 404);

        return $business;
    }

    /**
     * @return list<string>
     */
    public function rwandaDistrictNames(): array
    {
        return AdministrativeDivision::query()
            ->where('type', AdministrativeDivision::TYPE_DISTRICT)
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->all();
    }
}
