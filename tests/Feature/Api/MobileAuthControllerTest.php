<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('Invalid credentials.'));
    }

    public function test_login_response_includes_user_role_and_business_type(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret-123'),
        ]);

        Business::create([
            'user_id' => $user->id,
            'type' => Business::TYPE_FARMER,
            'business_name' => 'Farmer One Ltd',
            'registration_number' => 'REG-1001',
            'tax_id' => 'TIN-1001',
            'contact_phone' => '+250700000001',
            'email' => 'farmer-one@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-123',
            'device_name' => 'android-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.userRole', 'owner')
            ->assertJsonPath('data.user.business_type', Business::TYPE_FARMER);
    }

    public function test_auth_me_includes_member_user_role_and_business_type(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create([
            'password' => Hash::make('secret-123'),
        ]);

        $business = Business::create([
            'user_id' => $owner->id,
            'type' => Business::TYPE_LOGISTICS,
            'business_name' => 'Logistics One Ltd',
            'registration_number' => 'REG-2001',
            'tax_id' => 'TIN-2001',
            'contact_phone' => '+250700000002',
            'email' => 'logistics-one@example.com',
            'status' => Business::STATUS_ACTIVE,
        ]);

        $business->memberUsers()->attach($member->id, ['role' => BusinessUser::ROLE_MANAGER]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $member->email,
            'password' => 'secret-123',
            'device_name' => 'ios-test',
        ])->assertOk();

        $token = (string) $loginResponse->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.userRole', 'manager')
            ->assertJsonPath('data.business_type', Business::TYPE_LOGISTICS);
    }
}
