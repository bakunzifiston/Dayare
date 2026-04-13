<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessWorkspaceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_registration_redirects_to_farmer_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Farmer User',
            'email' => 'farmer@example.com',
            'business_name' => 'Farmer Workspace',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_type' => 'farmer',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('farmer.dashboard', absolute: false));
    }

    public function test_farmer_cannot_access_processor_dashboard(): void
    {
        $this->post('/register', [
            'name' => 'Farmer User',
            'email' => 'farmer@example.com',
            'business_name' => 'Farmer Workspace',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_type' => 'farmer',
        ]);

        $this->get('/dashboard')->assertForbidden();
    }

    public function test_farmer_can_access_farmer_dashboard(): void
    {
        $this->post('/register', [
            'name' => 'Farmer User',
            'email' => 'farmer@example.com',
            'business_name' => 'Farmer Workspace',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_type' => 'farmer',
        ]);

        $this->get('/farmer/dashboard')->assertOk();
    }

    public function test_processor_user_can_access_main_dashboard(): void
    {
        $user = User::factory()->create();
        Business::create([
            'user_id' => $user->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Test Co',
            'business_name_normalized' => 'test co',
            'registration_number' => 'REG-TEST-001',
            'contact_phone' => '1234567890',
            'email' => 'biz@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_processor_user_cannot_access_farmer_dashboard(): void
    {
        $user = User::factory()->create();
        Business::create([
            'user_id' => $user->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'Test Co',
            'business_name_normalized' => 'test co',
            'registration_number' => 'REG-TEST-002',
            'contact_phone' => '1234567890',
            'email' => 'biz@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)->get('/farmer/dashboard')->assertForbidden();
    }
}
