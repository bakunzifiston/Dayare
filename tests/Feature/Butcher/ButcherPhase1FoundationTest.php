<?php

namespace Tests\Feature\Butcher;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\ButcherInventoryMovement;
use App\Models\ButcherOutlet;
use App\Models\ButcherPermit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherPhase1FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_all_phase_one_modules(): void
    {
        [$owner] = $this->createButcherOwnerBusiness();

        $this->actingAs($owner)
            ->get(route('butcher.dashboard'))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('butcher.business.edit'))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('butcher.finance.index'))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('butcher.team.index'))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('butcher.compliance.health.index'))
            ->assertOk();
    }

    public function test_cashier_is_blocked_from_finance_staff_health_and_administration(): void
    {
        [$owner, $business] = $this->createButcherOwnerBusiness();
        $cashier = $this->attachMember($business, BusinessUser::ROLE_BUTCHER_CASHIER);

        $this->actingAs($cashier)
            ->get(route('butcher.dashboard'))
            ->assertOk();

        $this->actingAs($cashier)
            ->get(route('butcher.sales.index'))
            ->assertOk();

        $this->actingAs($cashier)
            ->get(route('butcher.finance.index'))
            ->assertForbidden();

        $this->actingAs($cashier)
            ->get(route('butcher.compliance.health.index'))
            ->assertForbidden();

        $this->actingAs($cashier)
            ->get(route('butcher.business.edit'))
            ->assertForbidden();

        $this->actingAs($cashier)
            ->get(route('butcher.team.index'))
            ->assertForbidden();
    }

    public function test_member_without_butcher_role_is_blocked(): void
    {
        [$owner, $business] = $this->createButcherOwnerBusiness();
        $member = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $member->id,
            'role' => BusinessUser::ROLE_INSPECTOR,
        ]);

        $this->actingAs($member)
            ->get(route('butcher.dashboard'))
            ->assertForbidden();
    }

    public function test_business_profile_update_persists_new_fields(): void
    {
        $this->seedDistrict('Gasabo');
        [$owner, $business] = $this->createButcherOwnerBusiness();

        $this->actingAs($owner)
            ->put(route('butcher.business.update'), [
                'business_name' => 'Updated Butchery',
                'butchery_type' => Business::BUTCHERY_TYPE_RETAIL,
                'registration_number' => 'RDB-UPD-001',
                'tax_id' => '1234567890',
                'contact_phone' => '+250788111222',
                'email' => 'shop@example.com',
                'address_line_1' => 'KG 1 Ave',
                'city' => 'Kigali',
                'rfa_permit_number' => 'RFA-1',
                'rfa_permit_expiry' => now()->addYear()->toDateString(),
                'butcher_district' => 'Gasabo',
                'butcher_sector' => 'Remera',
                'butcher_cell' => 'Rukiri',
                'gps_lat' => -1.94,
                'gps_lng' => 30.06,
                'butcher_fresh_max_temp_c' => 3.5,
                'butcher_frozen_max_temp_c' => -18,
                'butcher_batch_shelf_life_days' => 5,
            ])
            ->assertRedirect(route('butcher.business.edit'));

        $business->refresh();
        $this->assertSame('Updated Butchery', $business->business_name);
        $this->assertSame('1234567890', $business->tax_id);
        $this->assertSame('Gasabo', $business->butcher_district);
        $this->assertSame(5, (int) $business->butcher_batch_shelf_life_days);
        $this->assertEquals(3.5, (float) $business->butcher_fresh_max_temp_c);
    }

    public function test_outlet_crud_and_tenant_isolation(): void
    {
        $this->seedDistrict('Kigali');
        [$owner, $business] = $this->createButcherOwnerBusiness();
        [$otherOwner, $otherBusiness] = $this->createButcherOwnerBusiness('Other Shop', 'REG-OTHER-OUT');

        $otherOutlet = ButcherOutlet::query()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Outlet',
            'district' => 'Kigali',
            'phone' => '+250788000111',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);

        $this->actingAs($owner)
            ->post(route('butcher.outlets.store'), [
                'name' => 'Main Counter',
                'district' => 'Kigali',
                'sector' => 'Nyarugenge',
                'phone' => '+250788333444',
                'is_primary' => true,
            ])
            ->assertRedirect(route('butcher.outlets.index'));

        $this->assertDatabaseHas('butcher_outlets', [
            'business_id' => $business->id,
            'name' => 'Main Counter',
            'phone' => '+250788333444',
        ]);

        $this->actingAs($owner)
            ->put(route('butcher.outlets.update', $otherOutlet), [
                'name' => 'Hacked',
                'district' => 'Kigali',
                'phone' => '+250788333444',
            ])
            ->assertNotFound();
    }

    public function test_permit_crud_and_tenant_isolation(): void
    {
        [$owner, $business] = $this->createButcherOwnerBusiness();
        [$otherOwner, $otherBusiness] = $this->createButcherOwnerBusiness('Permit Other', 'REG-OTHER-PERM');

        $otherPermit = ButcherPermit::query()->create([
            'business_id' => $otherBusiness->id,
            'permit_type' => ButcherPermit::TYPE_RICA,
            'permit_number' => 'OTH-1',
            'issued_by' => 'RICA',
            'issue_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => ButcherPermit::STATUS_VALID,
        ]);

        $this->actingAs($owner)
            ->post(route('butcher.permits.store'), [
                'permit_type' => ButcherPermit::TYPE_OPERATING_LICENSE,
                'permit_number' => 'OP-100',
                'issued_by' => 'City of Kigali',
                'issue_date' => now()->subDay()->toDateString(),
                'expiry_date' => now()->addYear()->toDateString(),
            ])
            ->assertRedirect(route('butcher.permits.index'));

        $this->assertDatabaseHas('butcher_permits', [
            'business_id' => $business->id,
            'permit_number' => 'OP-100',
        ]);

        $this->actingAs($owner)
            ->put(route('butcher.permits.update', $otherPermit), [
                'permit_type' => ButcherPermit::TYPE_RICA,
                'permit_number' => 'HACKED',
                'issued_by' => 'RICA',
                'issue_date' => now()->subMonth()->toDateString(),
                'expiry_date' => now()->addYear()->toDateString(),
            ])
            ->assertNotFound();
    }

    public function test_owner_can_assign_team_roles(): void
    {
        [$owner, $business] = $this->createButcherOwnerBusiness();
        $member = $this->attachMember($business, BusinessUser::ROLE_BUTCHER_CASHIER);

        $this->actingAs($owner)
            ->put(route('butcher.team.update'), [
                'user_id' => $member->id,
                'role' => BusinessUser::ROLE_BUTCHER_STOREKEEPER,
            ])
            ->assertRedirect(route('butcher.team.index'));

        $this->assertDatabaseHas('business_user', [
            'business_id' => $business->id,
            'user_id' => $member->id,
            'role' => BusinessUser::ROLE_BUTCHER_STOREKEEPER,
        ]);
    }

    public function test_inventory_movement_record_helper_writes_row(): void
    {
        [$owner, $business] = $this->createButcherOwnerBusiness();
        $outlet = ButcherOutlet::query()->create([
            'business_id' => $business->id,
            'name' => 'Ledger Outlet',
            'district' => 'Kigali',
            'phone' => '+250788555666',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);

        $movement = ButcherInventoryMovement::record([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'type' => ButcherInventoryMovement::TYPE_ADJUSTMENT,
            'quantity_kg' => -1.250,
            'before_qty' => 10,
            'after_qty' => 8.75,
            'actor_id' => $owner->id,
        ]);

        $this->assertDatabaseHas('butcher_inventory_movements', [
            'id' => $movement->id,
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'type' => ButcherInventoryMovement::TYPE_ADJUSTMENT,
            'actor_id' => $owner->id,
        ]);
    }

    /**
     * @return array{0: User, 1: Business}
     */
    private function createButcherOwnerBusiness(string $name = 'Phase1 Butchery', string $reg = 'REG-P1-001'): array
    {
        $owner = User::factory()->create();
        $business = Business::factory()->butcher()->create([
            'user_id' => $owner->id,
            'business_name' => $name,
            'registration_number' => $reg,
            'status' => Business::STATUS_ACTIVE,
        ]);

        BusinessUser::query()->updateOrCreate(
            ['business_id' => $business->id, 'user_id' => $owner->id],
            ['role' => BusinessUser::ROLE_BUTCHER_OWNER]
        );

        return [$owner, $business];
    }

    private function attachMember(Business $business, string $role): User
    {
        $member = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $member->id,
            'role' => $role,
        ]);

        return $member;
    }

    private function seedDistrict(string $name): void
    {
        AdministrativeDivision::query()->firstOrCreate(
            ['name' => 'Rwanda', 'type' => AdministrativeDivision::TYPE_COUNTRY],
            ['parent_id' => null]
        );

        AdministrativeDivision::query()->firstOrCreate(
            ['name' => $name, 'type' => AdministrativeDivision::TYPE_DISTRICT],
            ['parent_id' => null]
        );
    }
}
