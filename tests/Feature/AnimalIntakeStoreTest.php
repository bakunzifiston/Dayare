<?php

namespace Tests\Feature;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Facility;
use App\Models\Species;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnimalIntakeStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_submits_client_intake(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'Intake Store Test Co',
            'registration_number' => 'REG-AIS-'.uniqid(),
            'contact_phone' => '+250788000501',
            'email' => 'ais@test.com',
            'status' => 'active',
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => BusinessUser::ROLE_ORG_ADMIN,
        ]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Test Slaughterhouse',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'status' => 'active',
        ]);
        $client = Client::create([
            'business_id' => $business->id,
            'name' => 'Test Client Ltd',
            'email' => 'client@test.com',
            'phone' => '+250788000502',
            'country' => 'Rwanda',
            'is_active' => true,
        ]);
        $goats = Species::query()->firstOrCreate(
            ['code' => 'goat'],
            ['name' => 'Goats', 'sort_order' => 2, 'is_active' => true],
        );
        $business->configuredSpecies()->syncWithoutDetaching([$goats->id]);

        $earTag = 'EAR-'.uniqid();
        $localNow = now('Africa/Kigali')->format('Y-m-d\TH:i');

        $response = $this->actingAs($user)->post(route('animal-intakes.store'), [
            'facility_id' => $facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
            'client_id' => $client->id,
            'intake_date' => $localNow,
            'is_draft' => '0',
            'animals' => [
                [
                    'ear_tag' => $earTag,
                    'species' => 'Goats',
                    'sex' => AnimalIntake::SEX_MALE,
                    'health_status' => 'healthy',
                    'body_condition_score' => 'good',
                ],
            ],
        ]);

        $response->assertRedirect(route('animal-intakes.hub'));
        $this->assertDatabaseHas('animal_intakes', [
            'facility_id' => $facility->id,
            'client_id' => $client->id,
            'is_draft' => false,
            'status' => AnimalIntake::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('animal_intake_items', ['ear_tag' => $earTag]);
    }

    public function test_store_persists_per_animal_service_fee(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'Intake Service Fee Co',
            'registration_number' => 'REG-AIS-'.uniqid(),
            'contact_phone' => '+250788000504',
            'email' => 'ais-fee@test.com',
            'status' => 'active',
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => BusinessUser::ROLE_ORG_ADMIN,
        ]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Service Fee Slaughterhouse',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'status' => 'active',
        ]);
        $client = Client::create([
            'business_id' => $business->id,
            'name' => 'Fee Client Ltd',
            'email' => 'fee-client@test.com',
            'phone' => '+250788000505',
            'country' => 'Rwanda',
            'is_active' => true,
        ]);
        $goats = Species::query()->firstOrCreate(
            ['code' => 'goat'],
            ['name' => 'Goats', 'sort_order' => 2, 'is_active' => true],
        );
        $business->configuredSpecies()->syncWithoutDetaching([$goats->id]);

        $earTag = 'EAR-FEE-'.uniqid();

        $this->actingAs($user)->post(route('animal-intakes.store'), [
            'facility_id' => $facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
            'client_id' => $client->id,
            'intake_date' => now('Africa/Kigali')->format('Y-m-d\TH:i'),
            'is_draft' => '0',
            'animals' => [
                [
                    'ear_tag' => $earTag,
                    'species' => 'Goats',
                    'sex' => AnimalIntake::SEX_MALE,
                    'health_status' => 'healthy',
                    'body_condition_score' => 'good',
                    'service_fee' => 2500,
                ],
            ],
        ])->assertRedirect(route('animal-intakes.hub'));

        $this->assertDatabaseHas('animal_intake_items', [
            'ear_tag' => $earTag,
            'service_fee' => 2500,
        ]);
    }

    public function test_store_rejects_supplier_source_type(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'Intake Reject Supplier Co',
            'registration_number' => 'REG-AIS-'.uniqid(),
            'contact_phone' => '+250788000503',
            'email' => 'ais-reject@test.com',
            'status' => 'active',
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => BusinessUser::ROLE_ORG_ADMIN,
        ]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Reject Supplier Slaughterhouse',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'status' => 'active',
        ]);
        $goats = Species::query()->firstOrCreate(
            ['code' => 'goat'],
            ['name' => 'Goats', 'sort_order' => 2, 'is_active' => true],
        );
        $business->configuredSpecies()->syncWithoutDetaching([$goats->id]);

        $response = $this->actingAs($user)->post(route('animal-intakes.store'), [
            'facility_id' => $facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER,
            'intake_date' => now('Africa/Kigali')->format('Y-m-d\TH:i'),
            'is_draft' => '0',
            'animals' => [
                [
                    'ear_tag' => 'EAR-'.uniqid(),
                    'species' => 'Goats',
                    'sex' => AnimalIntake::SEX_MALE,
                    'health_status' => 'healthy',
                    'body_condition_score' => 'good',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('client_id');
    }

    public function test_create_form_renders_after_validation_redirect_with_old_animals(): void
    {
        [$user, $facility] = $this->makeIntakeContext('REG-AIS-OLD-'.uniqid());

        $this->actingAs($user)
            ->from(route('animal-intakes.create'))
            ->followingRedirects()
            ->post(route('animal-intakes.store'), [
                'facility_id' => $facility->id,
                'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
                'intake_date' => now('Africa/Kigali')->format('Y-m-d\TH:i'),
                'is_draft' => '0',
                'animals' => [
                    [
                        'ear_tag' => 'EAR-OLD-'.uniqid(),
                        'species' => 'Goats',
                        'sex' => AnimalIntake::SEX_MALE,
                        'health_status' => 'healthy',
                        'body_condition_score' => 'good',
                    ],
                ],
            ])
            ->assertOk();
    }

    public function test_store_allows_intake_without_supporting_documents(): void
    {
        [$user, $facility, $client] = $this->makeIntakeContext('REG-AIS-DOCS-'.uniqid());

        $earTag = 'EAR-NODOC-'.uniqid();

        $this->actingAs($user)->post(route('animal-intakes.store'), $this->intakePayload($facility->id, $client->id, $earTag))
            ->assertRedirect(route('animal-intakes.hub'));

        $intake = AnimalIntake::query()->where('facility_id', $facility->id)->first();
        $this->assertNotNull($intake);
        $this->assertNull($intake->movement_permit_document_path);
        $this->assertNull($intake->receipt_document_path);
    }

    public function test_store_saves_optional_movement_permit_or_receipt(): void
    {
        Storage::fake('public');
        [$user, $facility, $client] = $this->makeIntakeContext('REG-AIS-FILE-'.uniqid());

        $earTag = 'EAR-FILE-'.uniqid();
        $permit = UploadedFile::fake()->create('permit.pdf', 120, 'application/pdf');
        $receipt = UploadedFile::fake()->image('receipt.jpg');

        $this->actingAs($user)->post(route('animal-intakes.store'), array_merge(
            $this->intakePayload($facility->id, $client->id, $earTag),
            [
                'movement_permit_document' => $permit,
                'receipt_document' => $receipt,
            ],
        ))->assertRedirect(route('animal-intakes.hub'));

        $intake = AnimalIntake::query()->where('facility_id', $facility->id)->first();
        $this->assertNotNull($intake?->movement_permit_document_path);
        $this->assertNotNull($intake?->receipt_document_path);
        Storage::disk('public')->assertExists($intake->movement_permit_document_path);
        Storage::disk('public')->assertExists($intake->receipt_document_path);
    }

    public function test_destroy_blocks_intake_with_animals_assigned_to_plan(): void
    {
        [$user, $facility, $client] = $this->makeIntakeContext('REG-AIS-DEL-'.uniqid());

        $intake = AnimalIntake::create([
            'facility_id' => $facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
            'client_id' => $client->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Docs',
            'supplier_lastname' => 'Client',
            'species' => 'Goats',
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $inspector = \App\Models\Inspector::create([
            'facility_id' => $facility->id,
            'first_name' => 'Insp',
            'last_name' => 'Delete',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'insp-del-'.uniqid().'@test.com',
            'dob' => '1988-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-'.uniqid(),
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Goats',
            'status' => 'active',
        ]);

        $plan = \App\Models\SlaughterPlan::create([
            'facility_id' => $facility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'slaughter_date' => now()->addDay(),
            'species' => 'Goats',
            'number_of_animals_scheduled' => 1,
            'status' => 'planned',
        ]);

        $intake->items()->create([
            'ear_tag' => 'DEL-'.uniqid(),
            'species' => 'Goats',
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 0,
            'service_fee' => 0,
            'health_status' => 'healthy',
            'slaughter_plan_id' => $plan->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('animal-intakes.show', $intake))
            ->delete(route('animal-intakes.destroy', $intake));

        $response->assertRedirect(route('animal-intakes.show', $intake));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('animal_intakes', ['id' => $intake->id]);
    }

    public function test_destroy_deletes_unassigned_intake(): void
    {
        [$user, $facility, $client] = $this->makeIntakeContext('REG-AIS-DELOK-'.uniqid());

        $intake = AnimalIntake::create([
            'facility_id' => $facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
            'client_id' => $client->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Docs',
            'supplier_lastname' => 'Client',
            'species' => 'Goats',
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
            'submitted_at' => now(),
        ]);

        $intake->items()->create([
            'ear_tag' => 'DEL-OK-'.uniqid(),
            'species' => 'Goats',
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 0,
            'service_fee' => 0,
            'health_status' => 'healthy',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('animal-intakes.destroy', $intake));

        $response->assertRedirect(route('animal-intakes.hub'));
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('animal_intakes', ['id' => $intake->id]);
    }

    /**
     * @return array{0: User, 1: Facility, 2: Client}
     */
    private function makeIntakeContext(string $registrationNumber): array
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'Intake Docs Co',
            'registration_number' => $registrationNumber,
            'contact_phone' => '+250788000506',
            'email' => 'ais-docs-'.uniqid().'@test.com',
            'status' => 'active',
        ]);
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => BusinessUser::ROLE_ORG_ADMIN,
        ]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Docs Slaughterhouse',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'status' => 'active',
        ]);
        $client = Client::create([
            'business_id' => $business->id,
            'name' => 'Docs Client Ltd',
            'email' => 'docs-client-'.uniqid().'@test.com',
            'phone' => '+250788000507',
            'country' => 'Rwanda',
            'is_active' => true,
        ]);
        $goats = Species::query()->firstOrCreate(
            ['code' => 'goat'],
            ['name' => 'Goats', 'sort_order' => 2, 'is_active' => true],
        );
        $business->configuredSpecies()->syncWithoutDetaching([$goats->id]);

        return [$user, $facility, $client];
    }

    /**
     * @return array<string, mixed>
     */
    private function intakePayload(int $facilityId, int $clientId, string $earTag): array
    {
        return [
            'facility_id' => $facilityId,
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
            'client_id' => $clientId,
            'intake_date' => now('Africa/Kigali')->format('Y-m-d\TH:i'),
            'is_draft' => '0',
            'animals' => [
                [
                    'ear_tag' => $earTag,
                    'species' => 'Goats',
                    'sex' => AnimalIntake::SEX_MALE,
                    'health_status' => 'healthy',
                    'body_condition_score' => 'good',
                ],
            ],
        ];
    }
}
