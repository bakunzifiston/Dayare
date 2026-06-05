<?php

namespace Tests\Feature\Processor;

use App\Enums\MeatExportDocumentType;
use App\Models\BusinessUser;
use App\Models\MeatExportDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class MeatExportDocumentTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_can_create_document_on_international_confirmation(): void
    {
        Storage::fake('local');
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');

        $response = $this->actingAs($fixture['user'])->post(
            route('export-documents.store', $confirmation),
            [
                'document_type' => MeatExportDocumentType::VeterinaryHealthCertificate->value,
                'document_number' => 'VHC-001',
                'status' => MeatExportDocument::STATUS_ISSUED,
                'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
            ]
        );

        $response->assertRedirect(route('delivery-confirmations.show', $confirmation));
        $doc = MeatExportDocument::query()->first();
        $this->assertNotNull($doc);
        $this->assertSame('VHC-001', $doc->document_number);
        Storage::disk('local')->assertExists($doc->file_path);
    }

    public function test_store_rejected_for_domestic_confirmation(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'RW');

        $response = $this->actingAs($fixture['user'])->post(
            route('export-documents.store', $confirmation),
            [
                'document_type' => MeatExportDocumentType::VeterinaryHealthCertificate->value,
                'status' => MeatExportDocument::STATUS_PENDING,
            ]
        );

        $response->assertSessionHasErrors('document_type');
        $this->assertDatabaseCount('meat_export_documents', 0);
    }

    public function test_all_export_document_types_accepted(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'UG');

        foreach (MeatExportDocumentType::cases() as $type) {
            $this->actingAs($fixture['user'])->post(
                route('export-documents.store', $confirmation),
                [
                    'document_type' => $type->value,
                    'document_number' => 'NUM-'.$type->value,
                    'status' => MeatExportDocument::STATUS_ISSUED,
                ]
            )->assertRedirect();
        }

        $this->assertDatabaseCount('meat_export_documents', 4);
    }

    public function test_all_export_documents_issued_when_four_issued(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');
        $confirmation->load('exportDocuments');

        $this->assertFalse($confirmation->allExportDocumentsIssued());

        foreach (MeatExportDocumentType::REQUIRED_TYPES as $type) {
            MeatExportDocument::query()->create([
                'delivery_confirmation_id' => $confirmation->id,
                'document_type' => $type,
                'status' => MeatExportDocument::STATUS_ISSUED,
                'created_by' => $fixture['user']->id,
            ]);
        }

        $confirmation->refresh()->load('exportDocuments');
        $this->assertTrue($confirmation->allExportDocumentsIssued());
    }

    public function test_expired_document_is_not_complete(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');

        $doc = MeatExportDocument::query()->create([
            'delivery_confirmation_id' => $confirmation->id,
            'document_type' => MeatExportDocumentType::ColdChainLog->value,
            'status' => MeatExportDocument::STATUS_ISSUED,
            'expiry_date' => now()->subDay()->toDateString(),
            'created_by' => $fixture['user']->id,
        ]);

        $this->assertTrue($doc->isExpired());
        $this->assertFalse($doc->isComplete());
    }
}
