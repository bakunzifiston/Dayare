<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessorBusinessRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_processor_can_register_business_when_name_already_exists_elsewhere(): void
    {
        $existingOwner = User::factory()->create();
        Business::create([
            'user_id' => $existingOwner->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Kigali Meat Processors',
            'business_name_normalized' => 'kigali meat processors',
            'registration_number' => 'RDB-EXISTING-001',
            'contact_phone' => '0780000001',
            'email' => 'existing@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $processorUser = User::factory()->create();

        $response = $this->actingAs($processorUser)->post(route('businesses.store'), [
            'business_name' => 'Kigali Meat Processors',
            'registration_number' => 'RDB-NEW-002',
            'contact_phone' => '0780000002',
            'email' => 'processor@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('businesses.hub'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('businesses', [
            'user_id' => $processorUser->id,
            'business_name' => 'Kigali Meat Processors',
            'registration_number' => 'RDB-NEW-002',
        ]);
    }

    public function test_processor_registration_still_rejects_duplicate_registration_number(): void
    {
        $existingOwner = User::factory()->create();
        Business::create([
            'user_id' => $existingOwner->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Existing Co',
            'business_name_normalized' => 'existing co',
            'registration_number' => 'RDB-SHARED-001',
            'contact_phone' => '0780000001',
            'email' => 'existing@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $processorUser = User::factory()->create();

        $response = $this->actingAs($processorUser)
            ->from(route('businesses.create'))
            ->post(route('businesses.store'), [
                'business_name' => 'Another Co',
                'registration_number' => 'RDB-SHARED-001',
                'contact_phone' => '0780000002',
                'email' => 'processor@example.com',
                'status' => Business::STATUS_ACTIVE,
            ]);

        $response->assertRedirect(route('businesses.create'));
        $response->assertSessionHasErrors(['registration_number']);
    }
}
