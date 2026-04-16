<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\LogisticsCompany;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogisticsCompanyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_logistics_user_can_register_company_for_accessible_business(): void
    {
        $user = User::factory()->create();
        $business = $this->createLogisticsBusiness($user, 'REG-BIZ-001');

        $response = $this->actingAs($user)->post(route('logistics.company.store'), [
            'business_id' => $business->id,
            'name' => 'Alpha Logistics',
            'registration_number' => 'LOG-COMP-001',
            'tax_id' => 'TAX-001',
            'license_type' => 'National carrier',
            'license_expiry_date' => now()->addYear()->toDateString(),
            'contact_person' => 'Jane Doe',
        ]);

        $response->assertRedirect(route('logistics.company.index', ['company_id' => LogisticsCompany::query()->firstOrFail()->id], absolute: false));
        $this->assertDatabaseHas('logistics_companies', [
            'business_id' => $business->id,
            'name' => 'Alpha Logistics',
            'registration_number' => 'LOG-COMP-001',
        ]);
    }

    public function test_logistics_user_cannot_register_second_company_for_same_business(): void
    {
        $user = User::factory()->create();
        $business = $this->createLogisticsBusiness($user, 'REG-BIZ-002');

        LogisticsCompany::query()->create([
            'business_id' => $business->id,
            'name' => 'Existing Logistics',
            'registration_number' => 'LOG-COMP-EXISTING',
            'tax_id' => 'TAX-EXISTING',
            'license_type' => 'National carrier',
            'license_expiry_date' => now()->addYear()->toDateString(),
            'contact_person' => 'Existing Contact',
        ]);

        $response = $this->from(route('logistics.company.index'))->actingAs($user)->post(route('logistics.company.store'), [
            'business_id' => $business->id,
            'name' => 'Duplicate Logistics',
            'registration_number' => 'LOG-COMP-002',
            'tax_id' => 'TAX-002',
            'license_type' => 'Regional carrier',
            'license_expiry_date' => now()->addYear()->toDateString(),
            'contact_person' => 'John Doe',
        ]);

        $response->assertRedirect(route('logistics.company.index', absolute: false));
        $response->assertSessionHasErrors('business_id');
        $this->assertDatabaseMissing('logistics_companies', [
            'registration_number' => 'LOG-COMP-002',
        ]);
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
}
