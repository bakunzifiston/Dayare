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
}
