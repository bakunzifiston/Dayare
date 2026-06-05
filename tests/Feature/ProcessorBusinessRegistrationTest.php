<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProcessorBusinessRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_processor_account_can_register_when_legacy_unique_index_still_exists(): void
    {
        $existingOwner = User::factory()->create();
        Business::create([
            'user_id' => $existingOwner->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Dayare Meat Co.',
            'business_name_normalized' => 'dayare meat co.',
            'registration_number' => 'REG-TEST-001',
            'contact_phone' => '0780000001',
            'email' => 'existing@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        Schema::table('businesses', function ($table): void {
            $table->unique('business_name_normalized', 'legacy_test_name_unique');
        });

        $processorUser = User::factory()->create();

        $response = $this->actingAs($processorUser)->post(route('businesses.store'), [
            'business_name' => 'Dayare Meat Co.',
            'registration_number' => 'RDB-BRAND-NEW-001',
            'contact_phone' => '0780000002',
            'email' => 'newprocessor@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('businesses.hub'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('businesses', [
            'user_id' => $processorUser->id,
            'business_name' => 'Dayare Meat Co.',
            'registration_number' => 'RDB-BRAND-NEW-001',
        ]);
    }

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

    public function test_processor_can_resubmit_onboarding_for_owned_business_name(): void
    {
        $processorUser = User::factory()->create();
        Business::create([
            'user_id' => $processorUser->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Dayare Meat Co',
            'business_name_normalized' => 'dayare meat co',
            'registration_number' => 'RDB-EXISTING-OWNED',
            'contact_phone' => '0780000001',
            'email' => 'processor@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($processorUser)->post(route('businesses.store'), [
            'business_name' => 'Dayare Meat Co',
            'registration_number' => 'RDB-UPDATED-OWNED',
            'contact_phone' => '0780000099',
            'email' => 'processor@example.com',
            'status' => Business::STATUS_ACTIVE,
            'owner_first_name' => 'Sandy',
            'owner_last_name' => 'Owner',
        ]);

        $response->assertRedirect(route('businesses.hub'));
        $response->assertSessionHas('status');
        $this->assertDatabaseCount('businesses', 1);
        $this->assertDatabaseHas('businesses', [
            'user_id' => $processorUser->id,
            'business_name' => 'Dayare Meat Co',
            'registration_number' => 'RDB-UPDATED-OWNED',
            'contact_phone' => '0780000099',
            'owner_first_name' => 'Sandy',
        ]);
    }

    public function test_processor_can_resubmit_when_owned_business_has_null_normalized_name(): void
    {
        $processorUser = User::factory()->create();
        $now = now();

        DB::table('businesses')->insert([
            'user_id' => $processorUser->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Dayare Meat Co',
            'business_name_normalized' => null,
            'registration_number' => 'RDB-LEGACY-001',
            'contact_phone' => '0780000001',
            'email' => 'processor@example.com',
            'status' => Business::STATUS_ACTIVE,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->actingAs($processorUser)->post(route('businesses.store'), [
            'business_name' => '  dayare   meat   co  ',
            'registration_number' => 'RDB-LEGACY-001',
            'contact_phone' => '0780000099',
            'email' => 'processor@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('businesses.hub'));
        $response->assertSessionHas('status');
        $this->assertDatabaseCount('businesses', 1);
        $this->assertDatabaseHas('businesses', [
            'user_id' => $processorUser->id,
            'business_name' => 'dayare meat co',
            'contact_phone' => '0780000099',
        ]);
    }

    public function test_create_wizard_redirects_to_edit_when_processor_business_already_exists(): void
    {
        $processorUser = User::factory()->create();
        $business = Business::create([
            'user_id' => $processorUser->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Existing Processor',
            'business_name_normalized' => 'existing processor',
            'registration_number' => 'RDB-REDIRECT-001',
            'contact_phone' => '0780000001',
            'email' => 'processor@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($processorUser)->get(route('businesses.create'));

        $response->assertRedirect(route('businesses.edit', $business));
        $response->assertSessionHas('status');
    }

    public function test_new_processor_registration_uses_account_suffix_when_unique_index_cannot_be_cleared(): void
    {
        $existingOwner = User::factory()->create();
        Business::create([
            'user_id' => $existingOwner->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Shared Processor Name',
            'business_name_normalized' => 'shared processor name',
            'registration_number' => 'REG-SHARED-001',
            'contact_phone' => '0780000001',
            'email' => 'existing@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        Schema::table('businesses', function ($table): void {
            $table->unique('business_name', 'legacy_exact_business_name_unique');
        });

        $processorUser = User::factory()->create();

        $response = $this->actingAs($processorUser)->post(route('businesses.store'), [
            'business_name' => 'Shared Processor Name',
            'registration_number' => 'RDB-UNIQUE-999',
            'contact_phone' => '0780000002',
            'email' => 'newprocessor@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('businesses.hub'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('businesses', [
            'user_id' => $processorUser->id,
            'registration_number' => 'RDB-UNIQUE-999',
        ]);

        $storedName = (string) Business::query()
            ->where('user_id', $processorUser->id)
            ->value('business_name');

        $this->assertTrue(
            $storedName === 'Shared Processor Name'
            || str_contains($storedName, ' #'.$processorUser->id),
            'Expected either the requested name or an account-scoped fallback name.'
        );
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
