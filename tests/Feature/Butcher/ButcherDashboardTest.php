<?php

namespace Tests\Feature\Butcher;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\ButcherOutlet;
use App\Models\ButcherPermit;
use App\Models\ButcherSale;
use App\Models\ButcherSupplier;
use App\Models\User;
use App\Services\Butcher\ButcherOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_kpi_sections_for_completed_onboarding(): void
    {
        [$user, $business] = $this->seedButcherWithCompletedOnboarding('RDB-DASH-001', 'Demo Butchery');

        $this->actingAs($user)
            ->get(route('butcher.dashboard'))
            ->assertOk()
            ->assertSee(__('Today at a glance'))
            ->assertSee(__('Stock & inventory'))
            ->assertSee(__('Recent sales'))
            ->assertSee('Demo Butchery');
    }

    public function test_dashboard_service_scopes_data_to_business(): void
    {
        [$user, $business] = $this->seedButcherWithCompletedOnboarding('RDB-DASH-002', 'My Butchery');

        $other = Business::factory()->butcher()->create([
            'user_id' => User::factory()->create()->id,
            'registration_number' => 'RDB-OTHER-001',
        ]);

        ButcherSale::query()->create([
            'business_id' => $other->id,
            'outlet_id' => ButcherOutlet::query()->create([
                'business_id' => $other->id,
                'name' => 'Other',
                'district' => 'Kigali',
                'phone' => '+250788999999',
                'status' => ButcherOutlet::STATUS_ACTIVE,
            ])->id,
            'sale_number' => 'OTHER-001',
            'sale_date' => now()->toDateString(),
            'subtotal' => 50000,
            'total_amount' => 50000,
            'payment_method' => ButcherSale::PAYMENT_CASH,
            'amount_paid' => 50000,
            'status' => ButcherSale::STATUS_COMPLETED,
            'sold_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('butcher.dashboard'))
            ->assertOk()
            ->assertSee('My Butchery')
            ->assertDontSee('OTHER-001');
    }

    /**
     * @return array{0: User, 1: Business}
     */
    private function seedButcherWithCompletedOnboarding(string $reg, string $name): array
    {
        $this->seedRwandaDistrict('Kigali');

        $user = User::factory()->create();
        $business = Business::factory()->butcher()->create([
            'user_id' => $user->id,
            'business_name' => $name,
            'status' => Business::STATUS_PENDING,
            'registration_number' => 'PENDING-'.$reg,
            'tax_id' => null,
            'contact_phone' => '0000000000',
        ]);

        $onboarding = app(ButcherOnboardingService::class);

        $onboarding->createBusinessProfile([
            'business_name' => $name,
            'butchery_type' => Business::BUTCHERY_TYPE_RETAIL,
            'rdb_registration_number' => $reg,
            'tin_number' => '1234567890',
            'phone' => '+250788123456',
            'district' => 'Kigali',
        ], $user);

        $onboarding->addOutlet($business->fresh(), [
            'name' => 'Main',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'is_primary' => true,
        ]);

        $onboarding->uploadPermit($business->fresh(), [
            'permit_type' => ButcherPermit::TYPE_RICA,
            'permit_number' => 'RICA-'.$reg,
            'issued_by' => 'RICA',
            'issue_date' => now()->subMonths(2)->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
        ], null);

        $onboarding->createSupplier($business->fresh(), [
            'name' => 'Supplier',
            'supplier_type' => ButcherSupplier::TYPE_ABATTOIR,
        ]);

        return [$user, $business->fresh()];
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
