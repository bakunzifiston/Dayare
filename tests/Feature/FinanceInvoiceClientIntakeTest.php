<?php

namespace Tests\Feature;

use App\Models\AnimalIntake;
use App\Models\Business;
use App\Models\Client;
use App\Models\Facility;
use App\Models\FinanceInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceInvoiceClientIntakeTest extends TestCase
{
    use RefreshDatabase;

    private function processorWithClientIntake(): array
    {
        $user = User::factory()->create();
        $business = Business::factory()->for($user)->create([
            'type' => Business::TYPE_PROCESSOR,
        ]);
        $facility = Facility::factory()->create([
            'business_id' => $business->id,
        ]);
        $client = Client::factory()->create([
            'business_id' => $business->id,
        ]);
        $intake = AnimalIntake::factory()->create([
            'facility_id' => $facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_CLIENT,
            'client_id' => $client->id,
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => 12,
        ]);

        return [$user, $business, $intake];
    }

    private function validInvoicePayload(int $animalIntakeId, string $invoiceNumber): array
    {
        return [
            'invoice_number' => $invoiceNumber,
            'status' => 'draft',
            'currency' => 'RWF',
            'link_contract' => 'no',
            'animal_intake_id' => $animalIntakeId,
            'issued_at' => now()->format('Y-m-d\TH:i'),
            'due_date' => '',
            'paid_at' => '',
            'tax_amount' => 0,
            'discount_amount' => 0,
            'amount_paid' => 0,
            'notes' => '',
            'line_description' => 'Test line',
            'quantity' => 2,
            'unit_price' => 100,
            'batch_id' => '',
            'certificate_id' => '',
            'quantity_unit' => 'kg',
        ];
    }

    public function test_create_page_lists_client_source_intakes(): void
    {
        [$user, $business, $intake] = $this->processorWithClientIntake();

        $response = $this->actingAs($user)->get(route('finance.invoices.create'));

        $response->assertOk();
        $response->assertSee('name="animal_intake_id"', false);
        $response->assertSee((string) $intake->id, false);
        $response->assertSee($intake->labelForFinanceInvoice(), false);
    }

    public function test_store_persists_animal_intake_and_resolves_client_id(): void
    {
        [$user, $business, $intake] = $this->processorWithClientIntake();

        $payload = $this->validInvoicePayload($intake->id, 'AR-TEST-'.uniqid());

        $response = $this->actingAs($user)->post(route('finance.invoices.store'), $payload);

        $response->assertRedirect();
        $invoice = FinanceInvoice::query()->where('invoice_number', $payload['invoice_number'])->first();
        $this->assertNotNull($invoice);
        $this->assertSame((int) $intake->id, (int) $invoice->animal_intake_id);
        $this->assertSame((int) $intake->client_id, (int) $invoice->client_id);
        $this->assertSame((int) $business->id, (int) $invoice->business_id);
    }

    public function test_store_rejects_intake_from_other_processor_business(): void
    {
        [$userA] = $this->processorWithClientIntake();
        [, , $intakeB] = $this->processorWithClientIntake();

        $payload = $this->validInvoicePayload($intakeB->id, 'AR-TEST-'.uniqid());

        $response = $this->actingAs($userA)->post(route('finance.invoices.store'), $payload);

        $response->assertStatus(422);
    }

    public function test_store_rejects_supplier_source_intake(): void
    {
        [$user, $business, $clientIntake] = $this->processorWithClientIntake();
        $facility = Facility::factory()->create(['business_id' => $business->id]);
        $supplierIntake = AnimalIntake::factory()->create([
            'facility_id' => $facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER,
        ]);

        $payload = $this->validInvoicePayload($supplierIntake->id, 'AR-TEST-'.uniqid());

        $response = $this->actingAs($user)->post(route('finance.invoices.store'), $payload);

        $response->assertStatus(422);
    }
}
