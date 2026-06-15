<?php

namespace Tests\Feature\Certificate;

use App\Models\AdministrativeDivision;
use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Business;
use App\Models\Certificate;
use App\Models\CertificateQr;
use App\Models\Client;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\PostMortemInspection;
use App\Models\PostMortemInspectionItem;
use App\Models\SlaughterExecution;
use App\Models\SlaughterExecutionItem;
use App\Models\SlaughterPlan;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\WarehouseStorage;
use App\Services\Processor\CertificatePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificatePdfTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Facility $slaughterFacility;

    private Facility $storageFacility;

    private Inspector $inspector;

    private Batch $batch;

    private Certificate $certificate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::create([
            'user_id' => $this->user->id,
            'business_name' => 'Nyagatare Meats Ltd',
            'registration_number' => 'REG-NMS-001',
            'contact_phone' => '+250788123456',
            'email' => 'nyagatare-'.uniqid().'@test.com',
            'status' => 'active',
        ]);

        $country = AdministrativeDivision::create(['name' => 'Rwanda', 'type' => AdministrativeDivision::TYPE_COUNTRY]);
        $province = AdministrativeDivision::create([
            'parent_id' => $country->id,
            'name' => 'Eastern Province',
            'type' => AdministrativeDivision::TYPE_PROVINCE,
        ]);
        $district = AdministrativeDivision::create([
            'parent_id' => $province->id,
            'name' => 'Nyagatare',
            'type' => AdministrativeDivision::TYPE_DISTRICT,
        ]);
        $sector = AdministrativeDivision::create([
            'parent_id' => $district->id,
            'name' => 'Rwimiyaga',
            'type' => AdministrativeDivision::TYPE_SECTOR,
        ]);
        $cell = AdministrativeDivision::create([
            'parent_id' => $sector->id,
            'name' => 'Nyagatare Cell',
            'type' => AdministrativeDivision::TYPE_CELL,
        ]);

        $this->slaughterFacility = Facility::create([
            'business_id' => $this->business->id,
            'facility_name' => CertificatePdfService::NYAGATARE_FACILITY_NAME,
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'country_id' => $country->id,
            'province_id' => $province->id,
            'district_id' => $district->id,
            'sector_id' => $sector->id,
            'cell_id' => $cell->id,
            'status' => Facility::STATUS_ACTIVE,
        ]);

        $this->storageFacility = Facility::create([
            'business_id' => $this->business->id,
            'facility_name' => 'Nyagatare Cold Storage',
            'facility_type' => Facility::TYPE_STORAGE,
            'status' => Facility::STATUS_ACTIVE,
        ]);

        $this->inspector = Inspector::create([
            'facility_id' => $this->slaughterFacility->id,
            'first_name' => 'Alice',
            'last_name' => 'Vet',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'vet-'.uniqid().'@test.com',
            'dob' => '1985-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'authorization_number' => 'AUTH-'.uniqid(),
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => 'active',
        ]);

        [$this->batch, $this->certificate] = $this->createCertificateFixture();
    }

    public function test_certificate_cannot_be_issued_without_released_cold_room_storage(): void
    {
        $batch = Batch::create([
            'slaughter_execution_id' => $this->batch->slaughter_execution_id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 60,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-NMS-NOREL-'.strtoupper(substr(uniqid(), -6)),
            'status' => Batch::STATUS_APPROVED,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ]);

        PostMortemInspection::create([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => 60,
            'approved_quantity' => 60,
            'condemned_quantity' => 0,
            'inspection_date' => today(),
            'result' => PostMortemInspection::RESULT_APPROVED,
        ]);

        $this->assertFalse($batch->fresh()->canIssueCertificate());

        $response = $this->actingAs($this->user)->post(route('certificates.store'), [
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'facility_id' => $this->slaughterFacility->id,
            'slaughterhouse_display_name' => CertificatePdfService::NYAGATARE_FACILITY_NAME,
            'issued_at' => now()->toDateString(),
            'status' => Certificate::STATUS_ACTIVE,
        ]);

        $response->assertSessionHasErrors('batch_id');
        $this->assertDatabaseMissing('certificates', [
            'batch_id' => $batch->id,
        ]);
    }

    public function test_certificate_can_be_issued_after_cold_room_storage_is_released(): void
    {
        $batch = Batch::create([
            'slaughter_execution_id' => $this->batch->slaughter_execution_id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 80,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-NMS-RELEASE-'.strtoupper(substr(uniqid(), -6)),
            'status' => Batch::STATUS_APPROVED,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ]);

        PostMortemInspection::create([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => 80,
            'approved_quantity' => 80,
            'condemned_quantity' => 0,
            'inspection_date' => today(),
            'result' => PostMortemInspection::RESULT_APPROVED,
        ]);

        $this->createReleasedStorageForBatch($batch, 'RW-TAG-RELEASE');

        $this->assertTrue($batch->fresh()->canIssueCertificate());

        $response = $this->actingAs($this->user)->post(route('certificates.store'), [
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'facility_id' => $this->slaughterFacility->id,
            'slaughterhouse_display_name' => CertificatePdfService::NYAGATARE_FACILITY_NAME,
            'issued_at' => now()->toDateString(),
            'status' => Certificate::STATUS_ACTIVE,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('certificates', [
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
        ]);
    }

    public function test_certificate_can_be_issued_with_released_storage_before_all_animals_have_pm(): void
    {
        $batch = Batch::create([
            'slaughter_execution_id' => $this->batch->slaughter_execution_id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 150,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-NMS-PARTIAL-'.strtoupper(substr(uniqid(), -6)),
            'status' => Batch::STATUS_APPROVED,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ]);

        $intakeItemOne = AnimalIntakeItem::create([
            'animal_intake_id' => $this->batch->slaughterExecution->slaughterPlan->animal_intake_id,
            'ear_tag' => 'RW-TAG-PARTIAL-1',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 100000,
            'live_weight_kg' => 220,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $executionItemOne = SlaughterExecutionItem::create([
            'slaughter_execution_id' => $batch->slaughter_execution_id,
            'animal_intake_item_id' => $intakeItemOne->id,
            'meat_quantity_kg' => 60,
        ]);

        $firstBatchItem = BatchItem::create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $executionItemOne->id,
            'animal_intake_item_id' => $intakeItemOne->id,
            'meat_quantity_kg' => 60,
        ]);

        $intakeItemTwo = AnimalIntakeItem::create([
            'animal_intake_id' => $this->batch->slaughterExecution->slaughterPlan->animal_intake_id,
            'ear_tag' => 'RW-TAG-PARTIAL-2',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_FEMALE,
            'unit_price' => 100000,
            'live_weight_kg' => 200,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $executionItemTwo = SlaughterExecutionItem::create([
            'slaughter_execution_id' => $batch->slaughter_execution_id,
            'animal_intake_item_id' => $intakeItemTwo->id,
            'meat_quantity_kg' => 90,
        ]);

        BatchItem::create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $executionItemTwo->id,
            'animal_intake_item_id' => $intakeItemTwo->id,
            'meat_quantity_kg' => 90,
        ]);

        $pm = PostMortemInspection::create([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => 60,
            'approved_quantity' => 60,
            'condemned_quantity' => 0,
            'inspection_date' => today(),
            'result' => PostMortemInspection::RESULT_APPROVED,
        ]);

        PostMortemInspectionItem::create([
            'post_mortem_inspection_id' => $pm->id,
            'batch_item_id' => $firstBatchItem->id,
            'animal_intake_item_id' => $intakeItemOne->id,
            'outcome' => PostMortemInspectionItem::OUTCOME_APPROVED,
            'carcass_weight_kg' => 60,
        ]);

        WarehouseStorage::create([
            'warehouse_facility_id' => $this->storageFacility->id,
            'batch_id' => $batch->id,
            'animal_intake_item_id' => $firstBatchItem->animal_intake_item_id,
            'entry_date' => now()->toDateString(),
            'temperature_at_entry' => -2.5,
            'quantity_stored' => 55.5,
            'quantity_unit' => 'kg',
            'status' => WarehouseStorage::STATUS_RELEASED,
            'released_date' => now()->toDateString(),
        ]);

        $batch = $batch->fresh(['postMortemInspection.inspectionItems', 'items']);

        $this->assertFalse($batch->isPostMortemComplete());
        $this->assertTrue($batch->canIssueCertificate());
    }

    public function test_warehouse_storage_release_without_released_date_enables_certificate(): void
    {
        $batch = Batch::create([
            'slaughter_execution_id' => $this->batch->slaughter_execution_id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 65,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-NMS-UNREL-'.strtoupper(substr(uniqid(), -6)),
            'status' => Batch::STATUS_APPROVED,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ]);

        PostMortemInspection::create([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => 65,
            'approved_quantity' => 65,
            'condemned_quantity' => 0,
            'inspection_date' => today(),
            'result' => PostMortemInspection::RESULT_APPROVED,
        ]);

        $storage = $this->createReleasedStorageForBatch($batch, 'RW-TAG-UNREL');
        $storage->update([
            'status' => WarehouseStorage::STATUS_IN_STORAGE,
            'released_date' => null,
        ]);

        $this->actingAs($this->user)->put(route('warehouse-storages.update', $storage), [
            'warehouse_facility_id' => $storage->warehouse_facility_id,
            'quantity_stored' => $storage->quantity_stored,
            'quantity_unit' => $storage->quantity_unit,
            'status' => WarehouseStorage::STATUS_RELEASED,
            'released_date' => '',
        ])->assertRedirect(route('warehouse-storages.show', $storage));

        $storage->refresh();
        $this->assertSame(WarehouseStorage::STATUS_RELEASED, $storage->status);
        $this->assertNotNull($storage->released_date);

        $response = $this->actingAs($this->user)->get(route('certificates.create'));
        $response->assertOk();
        $response->assertSee($batch->batch_code, false);
    }

    public function test_certificate_can_be_issued_when_released_storage_links_via_post_mortem_item_only(): void
    {
        $batch = Batch::create([
            'slaughter_execution_id' => $this->batch->slaughter_execution_id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 70,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-NMS-PMITEM-'.strtoupper(substr(uniqid(), -6)),
            'status' => Batch::STATUS_APPROVED,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ]);

        $intakeItem = AnimalIntakeItem::create([
            'animal_intake_id' => $this->batch->slaughterExecution->slaughterPlan->animal_intake_id,
            'ear_tag' => 'RW-TAG-PMITEM',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 100000,
            'live_weight_kg' => 220,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $executionItem = SlaughterExecutionItem::create([
            'slaughter_execution_id' => $batch->slaughter_execution_id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 70,
        ]);

        $batchItem = BatchItem::create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $executionItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 70,
        ]);

        $pm = PostMortemInspection::create([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => 70,
            'approved_quantity' => 0,
            'condemned_quantity' => 0,
            'inspection_date' => today(),
            'result' => PostMortemInspection::RESULT_APPROVED,
        ]);

        $pmItem = PostMortemInspectionItem::create([
            'post_mortem_inspection_id' => $pm->id,
            'batch_item_id' => $batchItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'outcome' => PostMortemInspectionItem::OUTCOME_APPROVED,
            'carcass_weight_kg' => 70,
        ]);

        WarehouseStorage::create([
            'warehouse_facility_id' => $this->storageFacility->id,
            'batch_id' => $batch->id,
            'post_mortem_inspection_item_id' => $pmItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'entry_date' => now()->toDateString(),
            'temperature_at_entry' => -2.5,
            'quantity_stored' => 70,
            'quantity_unit' => 'kg',
            'status' => WarehouseStorage::STATUS_RELEASED,
            'released_date' => now()->toDateString(),
        ]);

        $batch = $batch->fresh(['postMortemInspection.inspectionItems', 'items']);

        $this->assertTrue($batch->hasReleasedColdRoomStorage());
        $this->assertTrue($batch->hasReleasedStorageWithPostMortemItem());
        $this->assertTrue($batch->canIssueCertificate());
        $this->assertContains($batch->id, WarehouseStorage::releasedBatchIdsFor(collect([$batch->id]))->all());

        $response = $this->actingAs($this->user)->get(route('certificates.create'));
        $response->assertOk();
        $response->assertSee($batch->batch_code, false);

        $storeResponse = $this->actingAs($this->user)->post(route('certificates.store'), [
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'facility_id' => $this->slaughterFacility->id,
            'slaughterhouse_display_name' => CertificatePdfService::NYAGATARE_FACILITY_NAME,
            'issued_at' => now()->toDateString(),
            'status' => Certificate::STATUS_ACTIVE,
        ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('certificates', [
            'batch_id' => $batch->id,
        ]);
    }

    public function test_certificate_pdf_download_succeeds_for_valid_nyagatare_certificate(): void
    {
        $this->createReleasedStorage('RW-TAG-001');

        $response = $this->actingAs($this->user)
            ->get(route('certificates.export-single', $this->certificate));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'certificate_'.$this->batch->batch_code.'_',
            (string) $response->headers->get('content-disposition'),
        );
    }

    public function test_certificate_pdf_rejects_non_nyagatare_facility(): void
    {
        $this->slaughterFacility->update(['facility_name' => 'Other Slaughterhouse']);
        $this->certificate->update(['facility_id' => $this->slaughterFacility->id]);
        $this->createReleasedStorage('RW-TAG-002');

        $response = $this->actingAs($this->user)
            ->from(route('certificates.show', $this->certificate))
            ->get(route('certificates.export-single', $this->certificate));

        $response->assertRedirect(route('certificates.show', $this->certificate));
        $response->assertSessionHasErrors('certificate_pdf');
    }

    public function test_certificate_pdf_requires_complete_facility_location(): void
    {
        $this->slaughterFacility->update(['cell_id' => null]);
        $this->createReleasedStorage('RW-TAG-003');

        $service = app(CertificatePdfService::class);

        $this->expectExceptionMessage('Slaughterhouse location (District, Sector, Cell) must be complete before issuing a certificate.');

        $service->validate($this->certificate->fresh(['facility', 'inspector', 'batch']));
    }

    public function test_certificate_pdf_requires_released_storage(): void
    {
        $service = app(CertificatePdfService::class);

        $this->expectExceptionMessage('At least one released cold room storage record is required for this batch before generating the certificate.');

        $service->validate($this->certificate->fresh(['facility', 'inspector', 'batch']));
    }

    public function test_certificate_pdf_requires_inspector_from_same_facility(): void
    {
        $otherFacility = Facility::create([
            'business_id' => $this->business->id,
            'facility_name' => CertificatePdfService::NYAGATARE_FACILITY_NAME.' Annex',
            'facility_type' => Facility::TYPE_SLAUGHTERHOUSE,
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'status' => Facility::STATUS_ACTIVE,
        ]);

        $outsideInspector = Inspector::create([
            'facility_id' => $otherFacility->id,
            'first_name' => 'Outside',
            'last_name' => 'Inspector',
            'national_id' => (string) random_int(100000000000, 999999999999),
            'phone_number' => '+250788'.random_int(100000, 999999),
            'email' => 'outside-'.uniqid().'@test.com',
            'dob' => '1985-01-01',
            'nationality' => 'Rwandan',
            'country' => 'Rwanda',
            'district' => 'Nyagatare',
            'sector' => 'Rwimiyaga',
            'authorization_number' => 'AUTH-'.uniqid(),
            'authorization_issue_date' => now()->subYear(),
            'authorization_expiry_date' => now()->addYear(),
            'species_allowed' => 'Cattle',
            'status' => 'active',
        ]);

        $this->certificate->update(['inspector_id' => $outsideInspector->id]);
        $this->createReleasedStorage('RW-TAG-004');

        $service = app(CertificatePdfService::class);

        $this->expectExceptionMessage('The inspector must be assigned to this slaughterhouse facility.');

        $service->validate($this->certificate->fresh(['facility', 'inspector', 'batch']));
    }

    public function test_certificate_pdf_view_data_includes_owner_and_transport_details(): void
    {
        $client = Client::create([
            'business_id' => $this->business->id,
            'name' => 'Nyagatare Prime Butchery',
            'contact_person' => 'Jean Butcher',
            'phone' => '+250788999888',
            'country' => 'Rwanda',
            'is_active' => true,
        ]);

        $intake = $this->batch->slaughterExecution->slaughterPlan->animalIntake;
        $intake->update([
            'client_id' => $client->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
        ]);

        $this->createReleasedStorage('RW-TAG-005');

        TransportTrip::create([
            'certificate_id' => $this->certificate->id,
            'batch_id' => $this->batch->id,
            'origin_facility_id' => $this->slaughterFacility->id,
            'destination_name' => 'Kigali Market',
            'vehicle_plate_number' => 'RAB 482 K',
            'driver_name' => 'Paul Driver',
            'driver_phone' => '+250788111222',
            'departure_date' => now()->toDateString(),
            'status' => TransportTrip::STATUS_PENDING,
        ]);

        $data = app(CertificatePdfService::class)->buildViewData($this->certificate->fresh());

        $this->assertSame('Jean Butcher', $data['owner']->name);
        $this->assertSame('Nyagatare Prime Butchery', $data['owner']->business_name);
        $this->assertSame(['RW-TAG-005'], $data['releasedEarTags']);
        $this->assertSame('Paul Driver', $data['transportTrip']->driver_name);
        $this->assertSame(CertificatePdfService::NYAGATARE_FACILITY_NAME, $data['slaughterhouseDisplayName']);
        $this->assertNotEmpty($data['qrImage']);
    }

    public function test_certificate_pdf_uses_manual_slaughterhouse_display_name(): void
    {
        $this->createReleasedStorage('RW-TAG-CUSTOM');

        $customName = 'NYAGATARE MODERN SLAUGHTER HOUSE';
        $this->certificate->update(['slaughterhouse_display_name' => $customName]);

        $data = app(CertificatePdfService::class)->buildViewData($this->certificate->fresh());

        $this->assertSame($customName, $data['slaughterhouseDisplayName']);
    }

    /**
     * @return array{0: Batch, 1: Certificate}
     */
    private function createCertificateFixture(): array
    {
        $intake = AnimalIntake::create([
            'facility_id' => $this->slaughterFacility->id,
            'intake_date' => now(),
            'supplier_firstname' => 'Fallback',
            'supplier_lastname' => 'Supplier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 1,
            'status' => AnimalIntake::STATUS_APPROVED,
            'is_draft' => false,
        ]);

        $intakeItem = AnimalIntakeItem::create([
            'animal_intake_id' => $intake->id,
            'ear_tag' => 'RW-TAG-BASE',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 100000,
            'live_weight_kg' => 250,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        $plan = SlaughterPlan::create([
            'slaughter_date' => now()->toDateString(),
            'facility_id' => $this->slaughterFacility->id,
            'animal_intake_id' => $intake->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals_scheduled' => 1,
            'status' => SlaughterPlan::STATUS_APPROVED,
        ]);

        $execution = SlaughterExecution::create([
            'slaughter_plan_id' => $plan->id,
            'actual_animals_slaughtered' => 1,
            'slaughter_time' => now(),
            'status' => SlaughterExecution::STATUS_COMPLETED,
        ]);

        $executionItem = SlaughterExecutionItem::create([
            'slaughter_execution_id' => $execution->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 120,
        ]);

        $batch = Batch::create([
            'slaughter_execution_id' => $execution->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'quantity' => 120,
            'quantity_unit' => 'kg',
            'batch_code' => 'BAT-NMS-'.strtoupper(substr(uniqid(), -6)),
            'status' => Batch::STATUS_APPROVED,
            'cold_chain_status' => Batch::COLD_CHAIN_OK,
        ]);

        $batchItem = BatchItem::create([
            'batch_id' => $batch->id,
            'slaughter_execution_item_id' => $executionItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'meat_quantity_kg' => 120,
        ]);

        $pm = PostMortemInspection::create([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'total_examined' => 120,
            'approved_quantity' => 120,
            'condemned_quantity' => 0,
            'inspection_date' => today(),
            'result' => PostMortemInspection::RESULT_APPROVED,
        ]);

        PostMortemInspectionItem::create([
            'post_mortem_inspection_id' => $pm->id,
            'batch_item_id' => $batchItem->id,
            'animal_intake_item_id' => $intakeItem->id,
            'outcome' => PostMortemInspectionItem::OUTCOME_APPROVED,
            'carcass_weight_kg' => 120,
        ]);

        $certificate = Certificate::create([
            'batch_id' => $batch->id,
            'inspector_id' => $this->inspector->id,
            'facility_id' => $this->slaughterFacility->id,
            'slaughterhouse_display_name' => CertificatePdfService::NYAGATARE_FACILITY_NAME,
            'certificate_number' => 'CERT-NMS-'.uniqid(),
            'issued_at' => now(),
            'expiry_date' => now()->addMonth(),
            'status' => Certificate::STATUS_ACTIVE,
        ]);

        CertificateQr::create([
            'certificate_id' => $certificate->id,
            'slug' => CertificateQr::generateSlug(),
        ]);

        return [$batch, $certificate];
    }

    private function createReleasedStorage(string $earTag): WarehouseStorage
    {
        return $this->createReleasedStorageForBatch($this->batch, $earTag);
    }

    private function createReleasedStorageForBatch(Batch $batch, string $earTag): WarehouseStorage
    {
        $intakeItem = AnimalIntakeItem::create([
            'animal_intake_id' => $batch->slaughterExecution->slaughterPlan->animal_intake_id,
            'ear_tag' => $earTag,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'sex' => AnimalIntake::SEX_MALE,
            'unit_price' => 100000,
            'live_weight_kg' => 250,
            'health_status' => AnimalIntakeItem::HEALTH_HEALTHY,
        ]);

        return WarehouseStorage::create([
            'warehouse_facility_id' => $this->storageFacility->id,
            'batch_id' => $batch->id,
            'certificate_id' => $batch->id === $this->batch->id ? $this->certificate->id : null,
            'animal_intake_item_id' => $intakeItem->id,
            'entry_date' => now()->toDateString(),
            'temperature_at_entry' => -2.5,
            'quantity_stored' => 55.5,
            'quantity_unit' => 'kg',
            'status' => WarehouseStorage::STATUS_RELEASED,
            'released_date' => now()->toDateString(),
        ]);
    }
}
