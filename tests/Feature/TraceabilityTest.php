<?php

namespace Tests\Feature;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Certificate;
use App\Models\CertificateQr;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
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

    public function test_traceability_shows_individual_animals_for_batch_with_items(): void
    {
        $user = User::factory()->create();
        $business = \App\Models\Business::create([
            'user_id' => $user->id,
            'business_name' => 'Test Abattoir',
            'registration_number' => 'REG-TRACE-2',
            'contact_phone' => '+250788000004',
            'email' => 'trace2@test.com',
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
            'national_id' => '119988777667',
            'phone_number' => '+250788111112',
            'email' => 'insp2@test.com',
            'dob' => '1990-01-01',
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

        $intake = AnimalIntake::create([
            'facility_id' => $facility->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'intake_date' => today(),
            'status' => AnimalIntake::STATUS_RECEIVED,
            'supplier_firstname' => 'Paul',
            'supplier_lastname' => 'Farmer',
        ]);

        $intakeItem = AnimalIntakeItem::create([
            'animal_intake_id' => $intake->id,
            'ear_tag' => 'RW-TRACE-001',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 100000,
            'live_weight_kg' => 250,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $plan = SlaughterPlan::create([
            'facility_id' => $facility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 1,
            'slaughter_date' => today(),
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $amInspection = \App\Models\AnteMortemInspection::create([
            'slaughter_plan_id' => $plan->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_examined' => 1,
            'number_approved' => 1,
            'number_rejected' => 0,
            'inspection_date' => today(),
            'examined_count_source' => \App\Models\AnteMortemInspection::SOURCE_ITEMS,
        ]);

        \App\Models\AnteMortemInspectionItem::create([
            'ante_mortem_inspection_id' => $amInspection->id,
            'animal_intake_item_id' => $intakeItem->id,
            'outcome' => \App\Models\AnteMortemInspectionItem::OUTCOME_APPROVED,
            'outcome_notes' => 'Fit for slaughter',
        ]);

        \App\Models\AnteMortemObservation::create([
            'ante_mortem_inspection_id' => $amInspection->id,
            'animal_intake_item_id' => $intakeItem->id,
            'item' => 'locomotion',
            'value' => 'normal',
        ]);

        $execution = SlaughterExecution::create([
            'slaughter_plan_id' => $plan->id,
            'actual_animals_slaughtered' => 1,
            'slaughter_time' => now(),
            'status' => SlaughterExecution::STATUS_COMPLETED,
            'slaughter_count_source' => SlaughterExecution::SOURCE_ITEMS,
        ]);

        $executionItem = SlaughterExecutionItem::create([
            'slaughter_execution_id' => $execution->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 120,
        ]);

        $batch = Batch::create([
            'slaughter_execution_id' => $execution->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 120,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-TRACE-001',
            'status' => Batch::STATUS_APPROVED,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ]);

        $batchItem = BatchItem::create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $executionItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 120,
        ]);

        $pm = \App\Models\PostMortemInspection::create([
            'batch_id' => $batch->id,
            'inspector_id' => $inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => 1,
            'approved_quantity' => 1,
            'condemned_quantity' => 0,
            'inspection_date' => today(),
            'result' => \App\Models\PostMortemInspection::RESULT_APPROVED,
        ]);

        PostMortemInspectionItem::create([
            'post_mortem_inspection_id' => $pm->id,
            'batch_item_id' => $batchItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'outcome' => PostMortemInspectionItem::OUTCOME_APPROVED,
            'carcass_weight_kg' => 118,
            'outcome_notes' => 'Carcass approved',
        ]);

        \App\Models\PostMortemObservation::create([
            'post_mortem_inspection_id' => $pm->id,
            'animal_intake_item_id' => $intakeItem->id,
            'category' => 'organ',
            'item' => 'liver',
            'value' => 'normal',
        ]);

        $certificate = Certificate::create([
            'batch_id' => $batch->id,
            'inspector_id' => $inspector->id,
            'facility_id' => $facility->id,
            'certificate_number' => 'CERT-TRACE-ANIMALS',
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
        $response->assertSee('Individual animals', false);
        $response->assertSee('RW-TRACE-001', false);
        $response->assertSee('250.00 kg', false);
        $response->assertSee('Approved', false);
        $response->assertSee('Tap to view inspection details', false);
        $response->assertSee('Ante-mortem inspection', false);
        $response->assertSee('Post-mortem inspection', false);
        $response->assertSee('Locomotion', false);
        $response->assertSee('Liver', false);
        $response->assertSee('Fit for slaughter', false);
        $response->assertSee('Carcass approved', false);
    }
}
