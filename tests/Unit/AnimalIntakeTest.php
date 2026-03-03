<?php

namespace Tests\Unit;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\Facility;
use App\Models\SlaughterPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimalIntakeTest extends TestCase
{
    use RefreshDatabase;

    private function createIntake(array $overrides = []): AnimalIntake
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
            'registration_number' => 'REG-INT',
            'contact_phone' => '+250788000004',
            'email' => 'intake@test.com',
            'status' => 'active',
        ]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Test Facility',
            'facility_type' => 'slaughterhouse',
            'status' => 'active',
        ]);

        return AnimalIntake::create(array_merge([
            'facility_id' => $facility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Supplier',
            'supplier_lastname' => 'Name',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 10,
            'status' => AnimalIntake::STATUS_APPROVED,
            'health_certificate_expiry_date' => now()->addMonth(),
        ], $overrides));
    }

    public function test_is_health_certificate_expired_returns_true_when_expiry_in_past(): void
    {
        $intake = $this->createIntake(['health_certificate_expiry_date' => now()->subDay()]);

        $this->assertTrue($intake->isHealthCertificateExpired());
    }

    public function test_is_health_certificate_expired_returns_false_when_expiry_in_future(): void
    {
        $intake = $this->createIntake(['health_certificate_expiry_date' => now()->addMonth()]);

        $this->assertFalse($intake->isHealthCertificateExpired());
    }

    public function test_is_health_certificate_expired_returns_false_when_expiry_null(): void
    {
        $intake = $this->createIntake(['health_certificate_expiry_date' => null]);

        $this->assertFalse($intake->isHealthCertificateExpired());
    }

    public function test_remaining_animals_available_equals_total_when_no_plans(): void
    {
        $intake = $this->createIntake(['number_of_animals' => 10]);

        $this->assertSame(10, $intake->remainingAnimalsAvailable());
    }

    public function test_remaining_animals_available_decreases_when_plan_schedules_animals(): void
    {
        $intake = $this->createIntake(['number_of_animals' => 10]);
        $inspector = \App\Models\Inspector::create([
            'facility_id' => $intake->facility_id,
            'first_name' => 'I',
            'last_name' => 'Name',
            'national_id' => '111222333',
            'phone_number' => '+250788222222',
            'email' => 'ins@test.com',
            'dob' => '1985-05-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Kigali',
            'sector' => 'Gasabo',
            'authorization_number' => 'AUTH-002',
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => 'active',
        ]);

        SlaughterPlan::create([
            'facility_id' => $intake->facility_id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'slaughter_date' => now()->addDay(),
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 4,
            'status' => 'planned',
        ]);

        $this->assertSame(6, $intake->remainingAnimalsAvailable());
    }
}
