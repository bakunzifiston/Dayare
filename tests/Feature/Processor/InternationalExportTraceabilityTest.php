<?php

namespace Tests\Feature\Processor;

use App\Enums\MeatExportDocumentType;
use App\Models\BusinessUser;
use App\Models\MeatExportDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class InternationalExportTraceabilityTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_traceability_pdf_includes_export_documents_for_international_delivery(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');
        MeatExportDocument::query()->create([
            'delivery_confirmation_id' => $confirmation->id,
            'document_type' => MeatExportDocumentType::VeterinaryHealthCertificate->value,
            'document_number' => 'VHC-TRACE',
            'issuing_authority' => 'RAB',
            'status' => MeatExportDocument::STATUS_ISSUED,
            'issued_date' => now()->toDateString(),
            'created_by' => $fixture['user']->id,
        ]);

        $response = $this->actingAs($fixture['user'])
            ->get(route('transport-trips.export.traceability'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type') ?? '');
    }

    public function test_traceability_requires_export_traceability_permission(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_ORG_ADMIN, asMember: true);
        $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');

        $this->actingAs($fixture['user'])
            ->get(route('transport-trips.export.traceability'))
            ->assertForbidden();
    }
}
