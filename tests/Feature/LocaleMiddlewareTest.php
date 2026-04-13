<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_locale_can_be_switched_and_saved_in_session(): void
    {
        $response = $this->post('/locale', [
            'locale' => 'rw',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'rw');
    }

    public function test_logged_in_user_locale_is_persisted_to_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/locale', [
            'locale' => 'rw',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('settings', [
            'user_id' => $user->id,
            'key' => 'default_language',
            'value' => 'rw',
        ]);
    }

    public function test_logged_in_user_setting_overrides_session_locale(): void
    {
        $user = User::factory()->create();

        Setting::query()->create([
            'user_id' => $user->id,
            'key' => 'default_language',
            'value' => 'rw',
        ]);

        $this->withSession(['locale' => 'en'])
            ->actingAs($user)
            ->get('/contact-us')
            ->assertOk();

        $this->assertSame('rw', app()->getLocale());
    }
}
