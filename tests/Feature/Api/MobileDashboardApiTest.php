<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\MobileApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function mobileAuthHeaders(User $user): array
    {
        $plainToken = 'mobile-test-'.uniqid();
        MobileApiToken::create([
            'user_id' => $user->id,
            'name' => 'test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return ['Authorization' => 'Bearer '.$plainToken];
    }

    public function test_mobile_dashboard_returns_operations_manager_kpis(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $business = Business::create([
            'user_id' => $owner->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Ops Dashboard Co',
            'registration_number' => 'REG-OPS-'.uniqid(),
            'contact_phone' => '+250788001000',
            'email' => 'ops-dashboard-'.uniqid().'@test.com',
            'status' => 'active',
        ]);

        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $member->id,
            'role' => BusinessUser::ROLE_OPERATIONS_MANAGER,
        ]);

        Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Ops Facility',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'status' => 'active',
        ]);

        $member->setActiveProcessorBusinessId($business->id);

        $response = $this->withHeaders($this->mobileAuthHeaders($member))
            ->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', BusinessUser::ROLE_OPERATIONS_MANAGER)
            ->assertJsonPath('data.showPeriodFilter', true)
            ->assertJsonStructure([
                'data' => [
                    'business_id',
                    'role',
                    'headerBadge',
                    'kpiCards' => [
                        ['label', 'value', 'change', 'deltaTone'],
                    ],
                ],
            ]);
    }

    public function test_mobile_dashboard_returns_inspector_kpis(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create([
            'email' => 'inspector-dashboard-'.uniqid().'@test.com',
        ]);

        $business = Business::create([
            'user_id' => $owner->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Inspector Dashboard Co',
            'registration_number' => 'REG-INSP-'.uniqid(),
            'contact_phone' => '+250788001001',
            'email' => 'insp-dashboard-'.uniqid().'@test.com',
            'status' => 'active',
        ]);

        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $member->id,
            'role' => BusinessUser::ROLE_INSPECTOR,
        ]);

        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Inspector Facility',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'status' => 'active',
        ]);

        Inspector::create([
            'facility_id' => $facility->id,
            'first_name' => 'Mobile',
            'last_name' => 'Inspector',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => $member->email,
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-INSP-'.uniqid(),
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => Inspector::STATUS_ACTIVE,
        ]);

        $member->setActiveProcessorBusinessId($business->id);

        $response = $this->withHeaders($this->mobileAuthHeaders($member))
            ->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', BusinessUser::ROLE_INSPECTOR)
            ->assertJsonPath('data.showPeriodFilter', true)
            ->assertJsonStructure([
                'data' => [
                    'business_id',
                    'role',
                    'headerBadge',
                    'kpiCards' => [
                        ['label', 'value', 'change', 'deltaTone'],
                    ],
                ],
            ]);
    }
}
