<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Facility;
use App\Models\MobileApiToken;
use App\Models\RicaMonthlyInspectionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileMonthlyInspectionReportsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $business = Business::create([
            'user_id'             => $this->user->id,
            'business_name'       => 'Monthly Report API Co',
            'registration_number' => 'REG-MIR-'.uniqid(),
            'contact_phone'       => '+250788000900',
            'email'               => 'mir-api-'.uniqid().'@test.com',
            'status'              => 'active',
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id'     => $this->user->id,
            'role'        => BusinessUser::ROLE_ORG_ADMIN,
        ]);
        $this->facility = Facility::create([
            'business_id'   => $business->id,
            'facility_name' => 'Monthly Report Slaughterhouse',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'district'      => 'Kigali',
            'sector'        => 'Gasabo',
            'status'        => 'active',
        ]);
    }

    /** @return array<string, string> */
    private function mobileAuthHeaders(): array
    {
        $plainToken = 'mobile-test-'.uniqid();
        MobileApiToken::create([
            'user_id'    => $this->user->id,
            'name'       => 'test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return ['Authorization' => 'Bearer '.$plainToken];
    }

    public function test_monthly_inspection_reports_index_returns_list(): void
    {
        RicaMonthlyInspectionReport::create([
            'facility_id'   => $this->facility->id,
            'period_year'   => 2026,
            'period_month'  => 6,
            'status'        => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at'  => now(),
        ]);

        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->getJson('/api/v1/monthly-inspection-reports');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'data'    => [['id', 'facility_id', 'period_year', 'period_month', 'status']],
                    'meta'    => ['current_page', 'last_page', 'per_page', 'total'],
                    'filters' => [],
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, $response->json('data.meta.total'));
    }

    public function test_monthly_inspection_reports_index_filters_by_facility(): void
    {
        $otherUser     = User::factory()->create();
        $otherBusiness = Business::create([
            'user_id'             => $otherUser->id,
            'business_name'       => 'Other Co MIR',
            'registration_number' => 'REG-OTH-MIR-'.uniqid(),
            'contact_phone'       => '+250788001000',
            'email'               => 'other-mir-'.uniqid().'@test.com',
            'status'              => 'active',
        ]);
        $otherFacility = Facility::create([
            'business_id'   => $otherBusiness->id,
            'facility_name' => 'Other Slaughterhouse MIR',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'district'      => 'Kigali',
            'sector'        => 'Gasabo',
            'status'        => 'active',
        ]);

        RicaMonthlyInspectionReport::create([
            'facility_id'  => $otherFacility->id,
            'period_year'  => 2026,
            'period_month' => 5,
            'status'       => RicaMonthlyInspectionReport::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->getJson('/api/v1/monthly-inspection-reports');

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.meta.total'));
    }

    public function test_monthly_inspection_reports_show_returns_report_data(): void
    {
        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->getJson('/api/v1/monthly-inspection-reports/'.$this->facility->id.'?year=2026&month=6');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.facility_id', $this->facility->id)
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.month', 6)
            ->assertJsonStructure([
                'data' => [
                    'facility_id',
                    'year',
                    'month',
                    'report' => [
                        'meta',
                        'received_animals',
                        'ante_mortem',
                        'post_mortem',
                        'meat_supply',
                        'closure',
                    ],
                ],
            ]);
    }

    public function test_monthly_inspection_reports_closure_saves_draft(): void
    {
        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->postJson('/api/v1/monthly-inspection-reports/'.$this->facility->id.'/closure', [
                'year' => 2026,
                'month' => 6,
                'challenges' => 'Test challenge',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', RicaMonthlyInspectionReport::STATUS_DRAFT)
            ->assertJsonPath('data.challenges', 'Test challenge');

        $this->assertDatabaseHas('rica_monthly_inspection_reports', [
            'facility_id' => $this->facility->id,
            'period_year' => 2026,
            'period_month' => 6,
            'status' => RicaMonthlyInspectionReport::STATUS_DRAFT,
        ]);
    }

    public function test_monthly_inspection_reports_show_out_of_scope_returns_404(): void
    {
        $otherUser     = User::factory()->create();
        $otherBusiness = Business::create([
            'user_id'             => $otherUser->id,
            'business_name'       => 'Out of Scope Co',
            'registration_number' => 'REG-OOS-'.uniqid(),
            'contact_phone'       => '+250788001100',
            'email'               => 'oos-'.uniqid().'@test.com',
            'status'              => 'active',
        ]);
        $outOfScopeFacility = Facility::create([
            'business_id'   => $otherBusiness->id,
            'facility_name' => 'Out of Scope Facility',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'district'      => 'Kigali',
            'sector'        => 'Gasabo',
            'status'        => 'active',
        ]);

        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->getJson('/api/v1/monthly-inspection-reports/'.$outOfScopeFacility->id);

        $response->assertStatus(404);
    }
}
