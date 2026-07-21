<?php

namespace Tests\Feature\Api;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Facility;
use App\Models\MobileApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileLookupsAndIntakeSubmitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Facility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $business = Business::create([
            'user_id' => $this->user->id,
            'business_name' => 'Mobile API Co',
            'registration_number' => 'REG-MOB-'.uniqid(),
            'contact_phone' => '+250788000600',
            'email' => 'mobile-api-'.uniqid().'@test.com',
            'status' => 'active',
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $this->user->id,
            'role' => BusinessUser::ROLE_ORG_ADMIN,
        ]);
        $this->facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Mobile Test Slaughterhouse',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'status' => 'active',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function mobileAuthHeaders(): array
    {
        $plainToken = 'mobile-test-'.uniqid();
        MobileApiToken::create([
            'user_id' => $this->user->id,
            'name' => 'test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return ['Authorization' => 'Bearer '.$plainToken];
    }

    public function test_lookups_include_inspection_checklists_and_batch_statuses(): void
    {
        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->getJson('/api/v1/lookups');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'ante_mortem_checklists',
                    'ante_mortem_checklist_meta' => ['species_aliases', 'value_options'],
                    'post_mortem_checklists',
                    'post_mortem_checklist_meta' => ['species_aliases', 'value_options'],
                    'statuses' => ['batch'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.ante_mortem_checklists'));
        $this->assertNotEmpty($response->json('data.post_mortem_checklists'));
        $this->assertNotEmpty($response->json('data.ante_mortem_checklist_meta.value_options'));
        $this->assertNotEmpty($response->json('data.post_mortem_checklist_meta.value_options'));
    }

    public function test_mobile_submit_draft_intake(): void
    {
        $intake = AnimalIntake::create([
            'facility_id' => $this->facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
            'intake_date' => now(),
            'supplier_firstname' => 'Draft',
            'supplier_lastname' => 'Supplier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_RECEIVED,
            'is_draft' => true,
        ]);

        AnimalIntakeItem::create([
            'animal_intake_id' => $intake->id,
            'ear_tag' => 'TAG-'.uniqid(),
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
            'unit_price' => 100000,
        ]);

        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->postJson('/api/v1/animal-intakes/'.$intake->id.'/submit');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_draft', false)
            ->assertJsonPath('data.status', AnimalIntake::STATUS_APPROVED);

        $this->assertNotNull($intake->fresh()->submitted_at);
    }

    public function test_mobile_submit_rejects_non_draft_intake(): void
    {
        $intake = AnimalIntake::create([
            'facility_id' => $this->facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
            'intake_date' => now(),
            'supplier_firstname' => 'Live',
            'supplier_lastname' => 'Supplier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $this->withHeaders($this->mobileAuthHeaders())
            ->postJson('/api/v1/animal-intakes/'.$intake->id.'/submit')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
