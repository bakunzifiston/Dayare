<?php

namespace Tests\Feature\Processor;

use App\Enums\MeatExportDocumentType;
use App\Models\BusinessUser;
use App\Models\MeatExportDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Processor\Concerns\BuildsProcessorTransportData;
use Tests\TestCase;

class MeatExportPermissionTest extends TestCase
{
    use BuildsProcessorTransportData;
    use RefreshDatabase;

    public function test_transport_manager_can_create_export_document(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_TRANSPORT_MANAGER, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');

        $this->actingAs($fixture['user'])
            ->get(route('export-documents.create', $confirmation))
            ->assertOk();

        $this->actingAs($fixture['user'])
            ->post(route('export-documents.store', $confirmation), [
                'document_type' => MeatExportDocumentType::CommercialInvoice->value,
                'status' => MeatExportDocument::STATUS_PENDING,
            ])
            ->assertRedirect();
    }

    public function test_org_admin_can_view_but_not_create(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_ORG_ADMIN, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');
        $doc = MeatExportDocument::query()->create([
            'delivery_confirmation_id' => $confirmation->id,
            'document_type' => MeatExportDocumentType::CustomsExportPermit->value,
            'status' => MeatExportDocument::STATUS_PENDING,
            'created_by' => $fixture['user']->id,
        ]);

        $this->actingAs($fixture['user'])
            ->get(route('export-documents.show', [$confirmation, $doc]))
            ->assertOk();

        $this->actingAs($fixture['user'])
            ->get(route('export-documents.create', $confirmation))
            ->assertForbidden();

        $this->actingAs($fixture['user'])
            ->post(route('export-documents.store', $confirmation), [
                'document_type' => MeatExportDocumentType::ColdChainLog->value,
                'status' => MeatExportDocument::STATUS_PENDING,
            ])
            ->assertForbidden();
    }

    public function test_compliance_officer_can_view_only(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_COMPLIANCE_OFFICER, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');
        $doc = MeatExportDocument::query()->create([
            'delivery_confirmation_id' => $confirmation->id,
            'document_type' => MeatExportDocumentType::VeterinaryHealthCertificate->value,
            'status' => MeatExportDocument::STATUS_ISSUED,
            'created_by' => $fixture['user']->id,
        ]);

        $this->actingAs($fixture['user'])
            ->get(route('export-documents.show', [$confirmation, $doc]))
            ->assertOk();

        $this->actingAs($fixture['user'])
            ->post(route('export-documents.store', $confirmation), [
                'document_type' => MeatExportDocumentType::ColdChainLog->value,
                'status' => MeatExportDocument::STATUS_PENDING,
            ])
            ->assertForbidden();
    }

    public function test_inspector_cannot_access_export_documents(): void
    {
        $fixture = $this->createProcessorTransportFixture(BusinessUser::ROLE_INSPECTOR, asMember: true);
        $confirmation = $this->createDeliveryForTrip($fixture['trip'], $fixture['origin'], receiverCountry: 'KE');

        $this->actingAs($fixture['user'])
            ->get(route('export-documents.create', $confirmation))
            ->assertForbidden();
    }
}
