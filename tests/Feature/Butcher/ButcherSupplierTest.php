<?php

namespace Tests\Feature\Butcher;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\ButcherOutlet;
use App\Models\ButcherSupplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherSupplierTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedDistrict('Kigali');

        $this->user = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-SUP-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

        ButcherOutlet::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Main',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);
    }

    public function test_suppliers_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.suppliers.index'))
            ->assertOk()
            ->assertSee(__('Suppliers'))
            ->assertSee(__('Add supplier'));
    }

    public function test_can_create_supplier(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.suppliers.store'), [
                'name' => 'Kigali Abattoir',
                'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
                'contact_person' => 'Jean',
                'phone' => '+250788222222',
                'district' => 'Kigali',
                'is_active' => '1',
            ])
            ->assertRedirect(route('butcher.suppliers.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('butcher_suppliers', [
            'business_id' => $this->business->id,
            'name' => 'Kigali Abattoir',
            'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
        ]);
    }

    public function test_can_update_supplier(): void
    {
        $supplier = ButcherSupplier::query()->create([
            'business_id' => $this->business->id,
            'name' => 'Old Name',
            'supplier_type' => ButcherSupplier::TYPE_FARM,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->put(route('butcher.suppliers.update', $supplier), [
                'name' => 'Updated Farm',
                'supplier_type' => ButcherSupplier::TYPE_FARM,
                'phone' => '+250788333333',
                'is_active' => '1',
            ])
            ->assertRedirect(route('butcher.suppliers.index'));

        $this->assertDatabaseHas('butcher_suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Farm',
            'phone' => '+250788333333',
        ]);
    }

    public function test_can_delete_supplier(): void
    {
        $supplier = ButcherSupplier::query()->create([
            'business_id' => $this->business->id,
            'name' => 'To Remove',
            'supplier_type' => ButcherSupplier::TYPE_MARKET,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->delete(route('butcher.suppliers.destroy', $supplier))
            ->assertRedirect(route('butcher.suppliers.index'));

        $this->assertDatabaseMissing('butcher_suppliers', ['id' => $supplier->id]);
    }

    public function test_cannot_manage_other_business_supplier(): void
    {
        $otherBusiness = Business::factory()->butcher()->create([
            'user_id' => User::factory()->create()->id,
            'registration_number' => 'RDB-OTHER-001',
        ]);

        $supplier = ButcherSupplier::query()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Supplier',
            'supplier_type' => ButcherSupplier::TYPE_OTHER,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->put(route('butcher.suppliers.update', $supplier), [
                'name' => 'Hacked',
                'supplier_type' => ButcherSupplier::TYPE_OTHER,
            ])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->delete(route('butcher.suppliers.destroy', $supplier))
            ->assertNotFound();
    }

    private function seedDistrict(string $name): void
    {
        AdministrativeDivision::query()->create([
            'parent_id' => null,
            'name' => $name,
            'type' => AdministrativeDivision::TYPE_DISTRICT,
        ]);
    }
}
