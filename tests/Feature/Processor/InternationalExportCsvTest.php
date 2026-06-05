<?php

namespace Tests\Feature\Processor;

use App\Enums\MeatExportDocumentType;
use App\Models\BusinessUser;
use App\Models\MeatExportDocument;
use App\Models\TransportTrip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class InternationalExportCsvTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_international_csv_includes_export_columns(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');
        MeatExportDocument::query()->create([
            'delivery_confirmation_id' => $confirmation->id,
            'document_type' => MeatExportDocumentType::VeterinaryHealthCertificate->value,
            'document_number' => 'VET-99',
            'status' => MeatExportDocument::STATUS_ISSUED,
            'created_by' => $fixture['user']->id,
        ]);

        $response = $this->actingAs($fixture['user'])
            ->get(route('delivery-confirmations.export', ['format' => 'csv']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Is international', $content);
        $this->assertStringContainsString('Vet. health cert. number', $content);
        $this->assertStringContainsString('VET-99', $content);
        $this->assertStringContainsString('Yes', $content);
    }

    public function test_domestic_csv_has_blank_international_fields(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER, asMember: true);
        $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'RW');

        $response = $this->actingAs($fixture['user'])
            ->get(route('delivery-confirmations.export', ['format' => 'csv']));

        $response->assertOk();
        $lines = explode("\n", trim($response->streamedContent()));
        $this->assertGreaterThanOrEqual(2, count($lines));
        $dataRow = str_getcsv($lines[1]);
        $this->assertSame('No', $dataRow[array_search('Is international', str_getcsv($lines[0]), true)]);
    }

    public function test_export_eager_loads_export_documents_without_n_plus_one(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER, asMember: true);
        $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');
        $this->createDeliveryForTrip(
            TransportTrip::query()->create([
                'certificate_id' => $fixture['certificate']->id,
                'origin_facility_id' => $fixture['origin']->id,
                'destination_facility_id' => $fixture['destination']->id,
                'vehicle_plate_number' => 'RAB 999Z',
                'driver_name' => 'Driver 2',
                'departure_date' => now()->toDateString(),
                'status' => 'in_transit',
            ]),
            $fixture['origin'],
            receiverCountry: 'UG'
        );

        DB::enableQueryLog();
        $this->actingAs($fixture['user'])
            ->get(route('delivery-confirmations.export', ['format' => 'csv']))
            ->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(45, $queryCount);
    }
}
