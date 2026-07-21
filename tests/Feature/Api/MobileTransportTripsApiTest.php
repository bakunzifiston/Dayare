<?php

namespace Tests\Feature\Api;

use App\Models\MobileApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class MobileTransportTripsApiTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function mobileAuthHeaders(int $userId): array
    {
        $plainToken = 'mobile-test-'.uniqid();
        MobileApiToken::create([
            'user_id' => $userId,
            'name' => 'test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return ['Authorization' => 'Bearer '.$plainToken];
    }

    public function test_mobile_transport_trips_index_and_show(): void
    {
        ['user' => $user, 'trip' => $trip] = $this->createProcessorTransportFixture();

        $this->withHeaders($this->mobileAuthHeaders($user->id))
            ->getJson('/api/v1/transport-trips')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.id', $trip->id);

        $this->withHeaders($this->mobileAuthHeaders($user->id))
            ->getJson('/api/v1/transport-trips/'.$trip->id)
            ->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.certificate_id', $trip->certificate_id);
    }
}
