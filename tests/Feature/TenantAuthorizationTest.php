<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_users_business(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $business = Business::create([
            'user_id' => $owner->id,
            'business_name' => 'Owner Business',
            'registration_number' => 'REG-001',
            'contact_phone' => '+250788000001',
            'email' => 'owner@test.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($otherUser)->get(route('businesses.show', $business));

        $response->assertNotFound();
    }

    public function test_user_can_view_own_business(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'My Business',
            'registration_number' => 'REG-002',
            'contact_phone' => '+250788000002',
            'email' => 'user@test.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('businesses.show', $business));

        $response->assertOk();
        $response->assertSee('My Business');
    }
}
