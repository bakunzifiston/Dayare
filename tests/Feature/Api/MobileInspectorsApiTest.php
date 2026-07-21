<?php

namespace Tests\Feature\Api;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\MobileApiToken;
use App\Models\Species;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileInspectorsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Facility $facility;

    private Business $business;

    private function ensureConfiguredSpecies(): void
    {
        foreach ([
            ['name' => AnimalIntake::SPECIES_CATTLE, 'code' => 'cattle', 'sort_order' => 1],
            ['name' => AnimalIntake::SPECIES_GOAT,   'code' => 'goat',   'sort_order' => 2],
        ] as $row) {
            Species::updateOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'sort_order' => $row['sort_order'], 'is_active' => true],
            );
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureConfiguredSpecies();

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'user_id'             => $this->user->id,
            'business_name'       => 'Inspector API Co',
            'registration_number' => 'REG-INS-'.uniqid(),
            'contact_phone'       => '+250788000700',
            'email'               => 'inspector-api-'.uniqid().'@test.com',
            'status'              => 'active',
        ]);
        BusinessUser::query()->create([
            'business_id' => $this->business->id,
            'user_id'     => $this->user->id,
            'role'        => BusinessUser::ROLE_ORG_ADMIN,
        ]);
        $this->facility = Facility::create([
            'business_id'   => $this->business->id,
            'facility_name' => 'Inspector Test Facility',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
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

    private function inspectorPayload(array $overrides = []): array
    {
        return array_merge([
            'facility_id'               => $this->facility->id,
            'first_name'                => 'Jane',
            'last_name'                 => 'Doe',
            'national_id'               => 'NID-'.uniqid(),
            'phone_number'              => '+250788000001',
            'email'                     => 'jane-'.uniqid().'@test.com',
            'dob'                       => '1985-06-15',
            'nationality'               => 'Rwandan',
            'country'                   => 'Rwanda',
            'district'                  => 'Kigali',
            'sector'                    => 'Gasabo',
            'authorization_number'      => 'AUTH-'.uniqid(),
            'authorization_issue_date'  => '2023-01-01',
            'authorization_expiry_date' => '2026-01-01',
            'species_allowed'           => [AnimalIntake::SPECIES_CATTLE],
            'status'                    => Inspector::STATUS_ACTIVE,
        ], $overrides);
    }

    private function createInspector(array $overrides = []): Inspector
    {
        $data = $this->inspectorPayload($overrides);
        if (is_array($data['species_allowed'])) {
            $data['species_allowed'] = implode(', ', $data['species_allowed']);
        }

        return Inspector::create($data);
    }

    public function test_inspectors_index_returns_paginated_list(): void
    {
        $this->createInspector();
        $this->createInspector();

        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->getJson('/api/v1/inspectors');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'data'    => [['id', 'first_name', 'last_name', 'facility_id', 'status']],
                    'meta'    => ['current_page', 'last_page', 'per_page', 'total'],
                    'filters' => [],
                ],
            ]);

        $this->assertGreaterThanOrEqual(2, $response->json('data.meta.total'));
    }

    public function test_inspectors_show_returns_inspector(): void
    {
        $inspector = $this->createInspector();

        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->getJson('/api/v1/inspectors/'.$inspector->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $inspector->id)
            ->assertJsonPath('data.first_name', 'Jane');
    }

    public function test_inspectors_store_creates_inspector(): void
    {
        $payload = $this->inspectorPayload();

        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->postJson('/api/v1/inspectors', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Jane');

        $this->assertDatabaseHas('inspectors', ['first_name' => 'Jane']);
    }

    public function test_inspectors_update_updates_inspector(): void
    {
        $inspector = $this->createInspector();
        $payload   = $this->inspectorPayload(['first_name' => 'Janet', 'national_id' => $inspector->national_id]);

        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->putJson('/api/v1/inspectors/'.$inspector->id, $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Janet');

        $this->assertDatabaseHas('inspectors', ['id' => $inspector->id, 'first_name' => 'Janet']);
    }

    public function test_inspectors_destroy_deletes_inspector(): void
    {
        $inspector = $this->createInspector();

        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->deleteJson('/api/v1/inspectors/'.$inspector->id);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('inspectors', ['id' => $inspector->id, 'deleted_at' => null]);
    }

    public function test_inspectors_out_of_scope_returns_404(): void
    {
        $otherUser     = User::factory()->create();
        $otherBusiness = Business::create([
            'user_id'             => $otherUser->id,
            'business_name'       => 'Other Co',
            'registration_number' => 'REG-OTH-'.uniqid(),
            'contact_phone'       => '+250788000800',
            'email'               => 'other-'.uniqid().'@test.com',
            'status'              => 'active',
        ]);
        $otherFacility = Facility::create([
            'business_id'   => $otherBusiness->id,
            'facility_name' => 'Other Facility',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'status'        => 'active',
        ]);
        $otherInspector = Inspector::create(array_merge(
            $this->inspectorPayload(),
            ['facility_id' => $otherFacility->id, 'national_id' => 'NID-OTHER-'.uniqid(), 'species_allowed' => 'Cattle']
        ));

        $response = $this->withHeaders($this->mobileAuthHeaders())
            ->getJson('/api/v1/inspectors/'.$otherInspector->id);

        $response->assertStatus(404);
    }
}
