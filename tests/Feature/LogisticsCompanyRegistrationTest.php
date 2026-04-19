<?php

namespace Tests\Feature;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\LogisticsCompany;
use App\Models\LogisticsCompanyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsCompanyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_logistics_user_can_register_individual_company_for_accessible_business(): void
    {
        $user = User::factory()->create();
        $business = $this->createLogisticsBusiness($user, 'REG-BIZ-001');
        $loc = $this->createDivisionChain();

        $response = $this->actingAs($user)->post(route('logistics.company.store'), [
            'business_id' => $business->id,
            'company_type' => LogisticsCompany::TYPE_INDIVIDUAL,
            'name' => 'Alpha Logistics',
            'registration_number' => 'LOG-COMP-001',
            'tax_id' => 'TAX-001',
            'license_type' => 'National carrier',
            'license_expiry_date' => now()->addYear()->toDateString(),
            'contact_person' => 'Jane Doe',
            'country_id' => $loc->country_id,
            'province_id' => $loc->province_id,
            'district_id' => $loc->district_id,
            'sector_id' => $loc->sector_id,
            'cell_id' => $loc->cell_id,
            'village_id' => $loc->village_id,
        ]);

        $response->assertRedirect(route('logistics.company.index', ['company_id' => LogisticsCompany::query()->firstOrFail()->id], absolute: false));
        $this->assertDatabaseHas('logistics_companies', [
            'business_id' => $business->id,
            'name' => 'Alpha Logistics',
            'registration_number' => 'LOG-COMP-001',
            'company_type' => LogisticsCompany::TYPE_INDIVIDUAL,
            'country_id' => $loc->country_id,
            'village_id' => $loc->village_id,
        ]);
        $this->assertSame(0, LogisticsCompanyMember::query()->count());
    }

    public function test_logistics_user_can_register_shared_company_with_members(): void
    {
        $user = User::factory()->create();
        $business = $this->createLogisticsBusiness($user, 'REG-BIZ-SHARED');
        $loc = $this->createDivisionChain();

        $response = $this->actingAs($user)->post(route('logistics.company.store'), [
            'business_id' => $business->id,
            'company_type' => LogisticsCompany::TYPE_SHARED_COMPANY,
            'name' => 'Shared Logistics Co',
            'registration_number' => 'LOG-SHARED-001',
            'tax_id' => 'TAX-S',
            'license_type' => 'National carrier',
            'license_expiry_date' => now()->addYear()->toDateString(),
            'contact_person' => 'Lead Contact',
            'country_id' => $loc->country_id,
            'province_id' => $loc->province_id,
            'district_id' => $loc->district_id,
            'sector_id' => $loc->sector_id,
            'cell_id' => $loc->cell_id,
            'village_id' => $loc->village_id,
            'members' => [
                [
                    'first_name' => 'Ann',
                    'last_name' => 'One',
                    'phone' => '+250700000011',
                    'email' => 'ann.one@example.com',
                ],
                [
                    'first_name' => 'Bob',
                    'last_name' => 'Two',
                    'phone' => '+250700000022',
                    'email' => 'bob.two@example.com',
                ],
            ],
        ]);

        $response->assertRedirect();
        $company = LogisticsCompany::query()->where('registration_number', 'LOG-SHARED-001')->firstOrFail();
        $this->assertSame(LogisticsCompany::TYPE_SHARED_COMPANY, $company->company_type);
        $this->assertSame(2, $company->members()->count());
        $this->assertDatabaseHas('logistics_company_members', [
            'logistics_company_id' => $company->id,
            'email' => 'ann.one@example.com',
        ]);
    }

    public function test_logistics_user_can_register_multiple_companies_for_same_business(): void
    {
        $user = User::factory()->create();
        $business = $this->createLogisticsBusiness($user, 'REG-BIZ-002');
        $loc = $this->createDivisionChain();

        LogisticsCompany::query()->create([
            'business_id' => $business->id,
            'name' => 'Existing Logistics',
            'registration_number' => 'LOG-COMP-EXISTING',
            'tax_id' => 'TAX-EXISTING',
            'license_type' => 'National carrier',
            'license_expiry_date' => now()->addYear()->toDateString(),
            'contact_person' => 'Existing Contact',
            'country_id' => $loc->country_id,
            'province_id' => $loc->province_id,
            'district_id' => $loc->district_id,
            'sector_id' => $loc->sector_id,
            'cell_id' => $loc->cell_id,
            'village_id' => $loc->village_id,
        ]);

        $response = $this->actingAs($user)->post(route('logistics.company.store'), [
            'business_id' => $business->id,
            'company_type' => LogisticsCompany::TYPE_INDIVIDUAL,
            'name' => 'Duplicate Logistics',
            'registration_number' => 'LOG-COMP-002',
            'tax_id' => 'TAX-002',
            'license_type' => 'Regional carrier',
            'license_expiry_date' => now()->addYear()->toDateString(),
            'contact_person' => 'John Doe',
            'country_id' => $loc->country_id,
            'province_id' => $loc->province_id,
            'district_id' => $loc->district_id,
            'sector_id' => $loc->sector_id,
            'cell_id' => $loc->cell_id,
            'village_id' => $loc->village_id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('logistics_companies', [
            'registration_number' => 'LOG-COMP-002',
        ]);
        $this->assertSame(2, LogisticsCompany::query()->where('business_id', $business->id)->count());
    }

    private function createLogisticsBusiness(User $user, string $registrationNumber): Business
    {
        return Business::query()->create([
            'user_id' => $user->id,
            'type' => Business::TYPE_LOGISTICS,
            'business_name' => 'Logistics Workspace '.$registrationNumber,
            'registration_number' => $registrationNumber,
            'contact_phone' => '1234567890',
            'email' => strtolower($registrationNumber).'@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);
    }

    private function createDivisionChain(): object
    {
        $country = AdministrativeDivision::query()->create([
            'parent_id' => null,
            'name' => 'Test Country',
            'type' => AdministrativeDivision::TYPE_COUNTRY,
            'code' => 'TC',
        ]);
        $province = AdministrativeDivision::query()->create([
            'parent_id' => $country->id,
            'name' => 'Test Province',
            'type' => AdministrativeDivision::TYPE_PROVINCE,
            'code' => 'TP',
        ]);
        $district = AdministrativeDivision::query()->create([
            'parent_id' => $province->id,
            'name' => 'Test District',
            'type' => AdministrativeDivision::TYPE_DISTRICT,
            'code' => 'TD',
        ]);
        $sector = AdministrativeDivision::query()->create([
            'parent_id' => $district->id,
            'name' => 'Test Sector',
            'type' => AdministrativeDivision::TYPE_SECTOR,
            'code' => 'TS',
        ]);
        $cell = AdministrativeDivision::query()->create([
            'parent_id' => $sector->id,
            'name' => 'Test Cell',
            'type' => AdministrativeDivision::TYPE_CELL,
            'code' => 'TCL',
        ]);
        $village = AdministrativeDivision::query()->create([
            'parent_id' => $cell->id,
            'name' => 'Test Village',
            'type' => AdministrativeDivision::TYPE_VILLAGE,
            'code' => 'TV',
        ]);

        return (object) [
            'country_id' => $country->id,
            'province_id' => $province->id,
            'district_id' => $district->id,
            'sector_id' => $sector->id,
            'cell_id' => $cell->id,
            'village_id' => $village->id,
        ];
    }
}
