<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'business_name' => 'Test Processing Ltd',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_type' => 'processor',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_rejects_email_duplicates_case_insensitively(): void
    {
        User::factory()->create([
            'email' => 'existing@email.com',
            'email_normalized' => 'existing@email.com',
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Another User',
            'email' => '  EXISTING@EMAIL.COM  ',
            'business_name' => 'Unique Business',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_type' => 'farmer',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'email' => 'This email is already registered',
        ]);
    }

    public function test_registration_rejects_business_name_duplicates_after_normalization(): void
    {
        $user = User::factory()->create([
            'email_normalized' => 'owner@example.com',
        ]);

        Business::create([
            'user_id' => $user->id,
            'type' => Business::TYPE_PROCESSOR,
            'business_name' => 'My Farm',
            'business_name_normalized' => 'my farm',
            'registration_number' => 'PENDING-123',
            'contact_phone' => '0000000000',
            'email' => 'owner@example.com',
            'status' => Business::STATUS_ACTIVE,
            'owner_first_name' => 'Owner',
            'owner_last_name' => 'Name',
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'New Owner',
            'email' => 'new@example.com',
            'business_name' => '  my   FARM  ',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_type' => 'logistics',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'business_name' => 'This business name is already taken',
        ]);
    }
}
