<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateQr;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_traceability_returns_404_for_invalid_slug(): void
    {
        $response = $this->get(route('traceability.show', ['slug' => 'nonexistent-slug-12345']));

        $response->assertNotFound();
    }

    public function test_traceability_returns_200_for_valid_slug(): void
    {
        $user = User::factory()->create();
        $business = \App\Models\Business::create([
            'user_id' => $user->id,
            'business_name' => 'Test Abattoir',
            'registration_number' => 'REG-TRACE',
            'contact_phone' => '+250788000003',
            'email' => 'trace@test.com',
            'status' => 'active',
        ]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Test Slaughterhouse',
            'facility_type' => 'slaughterhouse',
            'status' => 'active',
        ]);
        $inspector = Inspector::create([
            'facility_id' => $facility->id,
            'first_name' => 'Jean',
            'last_name' => 'Inspector',
            'national_id' => '119988777666',
            'phone_number' => '+250788111111',
            'email' => 'insp@test.com',
            'dob' => '1990-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-001',
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => 'active',
        ]);
        $certificate = Certificate::create([
            'inspector_id' => $inspector->id,
            'facility_id' => $facility->id,
            'certificate_number' => 'CERT-TEST-001',
            'issued_at' => now(),
            'status' => Certificate::STATUS_ACTIVE,
        ]);
        $slug = CertificateQr::generateSlug();
        CertificateQr::create([
            'certificate_id' => $certificate->id,
            'slug' => $slug,
        ]);

        $response = $this->get(route('traceability.show', ['slug' => $slug]));

        $response->assertOk();
        $response->assertSee('CERT-TEST-001', false);
    }
}
