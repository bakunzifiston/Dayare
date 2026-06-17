<?php

namespace Tests\Feature\Butcher;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\ButcherOutlet;
use App\Models\ButcherPermit;
use App\Models\ButcherSupplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ButcherOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRwandaDistrict('Kigali');

        $this->user = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->user->id,
            'status' => Business::STATUS_PENDING,
            'registration_number' => 'PENDING-test',
            'tax_id' => null,
            'contact_phone' => '0000000000',
        ]);
    }

    public function test_onboarding_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.onboarding.index'))
            ->assertOk()
            ->assertSee(__('Butcher onboarding'));
    }

    public function test_profile_store_updates_business_and_maps_identifiers(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.onboarding.profile.store'), [
                'business_name' => 'Kigali Prime Butchery',
                'butchery_type' => Business::BUTCHERY_TYPE_RETAIL,
                'rdb_registration_number' => 'RDB-BUTCHER-001',
                'tin_number' => '1234567890',
                'phone' => '+250788123456',
                'district' => 'Kigali',
                'sector' => 'Gasabo',
                'cell' => 'Kacyiru',
            ])
            ->assertRedirect(route('butcher.onboarding.outlets'));

        $this->business->refresh();

        $this->assertSame('Kigali Prime Butchery', $this->business->business_name);
        $this->assertSame('RDB-BUTCHER-001', $this->business->registration_number);
        $this->assertSame('1234567890', $this->business->tax_id);
        $this->assertSame('+250788123456', $this->business->contact_phone);
        $this->assertSame(Business::STATUS_ACTIVE, $this->business->status);
        $this->assertSame('Kigali', $this->business->butcher_district);
    }

    public function test_profile_validation_rejects_invalid_phone_tin_and_district(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.onboarding.profile.store'), [
                'business_name' => 'Test Butchery',
                'butchery_type' => Business::BUTCHERY_TYPE_MIXED,
                'rdb_registration_number' => 'RDB-002',
                'tin_number' => '123',
                'phone' => '0788123456',
                'district' => 'Not A District',
            ])
            ->assertSessionHasErrors(['tin_number', 'phone', 'district']);
    }

    public function test_outlet_store_creates_primary_outlet(): void
    {
        $this->completeProfile();

        $this->actingAs($this->user)
            ->post(route('butcher.onboarding.outlets.store'), [
                'name' => 'Kigali Main Branch',
                'district' => 'Kigali',
                'sector' => 'Gasabo',
                'phone' => '+250788222222',
                'is_primary' => '1',
            ])
            ->assertRedirect(route('butcher.onboarding.outlets'));

        $outlet = ButcherOutlet::query()->first();
        $this->assertNotNull($outlet);
        $this->assertTrue($outlet->is_primary);
        $this->assertSame('Kigali Main Branch', $outlet->name);
    }

    public function test_permit_store_uploads_document_and_requires_future_expiry(): void
    {
        Storage::fake('public');
        $this->completeProfile();

        $this->actingAs($this->user)
            ->post(route('butcher.onboarding.permits.store'), [
                'permit_type' => ButcherPermit::TYPE_RFA_PERMIT,
                'permit_number' => 'RFA-123',
                'issued_by' => 'RFA',
                'issue_date' => now()->subMonth()->toDateString(),
                'expiry_date' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['expiry_date']);

        $file = UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf');

        $this->actingAs($this->user)
            ->post(route('butcher.onboarding.permits.store'), [
                'permit_type' => ButcherPermit::TYPE_OPERATING_LICENSE,
                'permit_number' => 'OP-456',
                'issued_by' => 'City of Kigali',
                'issue_date' => now()->subMonth()->toDateString(),
                'expiry_date' => now()->addYear()->toDateString(),
                'document' => $file,
            ])
            ->assertRedirect(route('butcher.onboarding.permits'));

        $permit = ButcherPermit::query()->first();
        $this->assertNotNull($permit);
        $this->assertNotNull($permit->document_path);
        Storage::disk('public')->assertExists($permit->document_path);
    }

    public function test_supplier_crud_is_scoped_to_butcher_business(): void
    {
        $this->completeProfile();

        $this->actingAs($this->user)
            ->post(route('butcher.onboarding.suppliers.store'), [
                'name' => 'Nyagatare Abattoir',
                'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
                'phone' => '+250788333333',
                'district' => 'Kigali',
            ])
            ->assertRedirect(route('butcher.onboarding.suppliers'));

        $supplier = ButcherSupplier::query()->first();
        $this->assertNotNull($supplier);

        $this->actingAs($this->user)
            ->put(route('butcher.onboarding.suppliers.update', $supplier), [
                'name' => 'Updated Supplier',
                'supplier_type' => ButcherSupplier::TYPE_MARKET,
                'phone' => '+250788444444',
            ])
            ->assertRedirect(route('butcher.onboarding.suppliers'));

        $supplier->refresh();
        $this->assertSame('Updated Supplier', $supplier->name);

        $this->actingAs($this->user)
            ->delete(route('butcher.onboarding.suppliers.destroy', $supplier))
            ->assertRedirect(route('butcher.onboarding.suppliers'));

        $this->assertDatabaseMissing('butcher_suppliers', ['id' => $supplier->id]);
    }

    public function test_onboarding_progress_reaches_one_hundred_percent(): void
    {
        $service = app(\App\Services\Butcher\ButcherOnboardingService::class);

        $this->completeProfile();

        $service->addOutlet($this->business, [
            'name' => 'Branch',
            'district' => 'Kigali',
            'phone' => '+250788555555',
        ]);

        $service->uploadPermit($this->business, [
            'permit_type' => ButcherPermit::TYPE_RICA,
            'permit_number' => 'RICA-1',
            'issued_by' => 'RICA',
            'issue_date' => now()->subMonths(2)->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
        ], null);

        $service->createSupplier($this->business, [
            'name' => 'Farm Supplier',
            'supplier_type' => ButcherSupplier::TYPE_FARM,
        ]);

        $progress = $service->getOnboardingProgress($this->business->fresh());
        $this->assertSame(100, $progress['percent']);
    }

    public function test_registration_redirects_butcher_to_onboarding(): void
    {
        $this->seedRwandaDistrict('Kigali');

        $response = $this->post('/register', [
            'name' => 'New Butcher',
            'email' => 'newbutcher@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'business_type' => 'butcher',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('butcher.onboarding.index', absolute: false));

        $business = Business::query()->where('user_id', auth()->id())->first();
        $this->assertSame(Business::STATUS_PENDING, $business->status);
    }

    private function completeProfile(): void
    {
        app(\App\Services\Butcher\ButcherOnboardingService::class)->createBusinessProfile([
            'business_name' => 'Test Butchery',
            'butchery_type' => Business::BUTCHERY_TYPE_RETAIL,
            'rdb_registration_number' => 'RDB-PROFILE-001',
            'tin_number' => '1098765432',
            'phone' => '+250788111111',
            'district' => 'Kigali',
        ], $this->user);

        $this->business->refresh();
    }

    private function seedRwandaDistrict(string $name): void
    {
        AdministrativeDivision::query()->create([
            'parent_id' => null,
            'name' => 'Rwanda',
            'type' => AdministrativeDivision::TYPE_COUNTRY,
        ]);

        AdministrativeDivision::query()->create([
            'parent_id' => null,
            'name' => $name,
            'type' => AdministrativeDivision::TYPE_DISTRICT,
        ]);
    }
}
