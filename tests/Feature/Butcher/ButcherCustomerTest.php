<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherCustomer;
use App\Models\ButcherOutlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherCustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->business = Business::factory()->butcher()->create([
            'user_id' => $this->user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-CUS-001',
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

    public function test_customers_index_is_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('butcher.customers.index'))
            ->assertOk()
            ->assertSee(__('Customers'))
            ->assertSee(__('Add customer'));
    }

    public function test_can_create_customer(): void
    {
        $this->actingAs($this->user)
            ->post(route('butcher.customers.store'), [
                'name' => 'Hotel Rwanda',
                'phone' => '+250788444444',
                'tier' => ButcherCustomer::TIER_WHOLESALE,
                'credit_limit' => 500000,
            ])
            ->assertRedirect(route('butcher.customers.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('butcher_customers', [
            'business_id' => $this->business->id,
            'name' => 'Hotel Rwanda',
            'tier' => ButcherCustomer::TIER_WHOLESALE,
            'credit_limit' => 500000,
        ]);
    }

    public function test_customers_list_scoped_to_business(): void
    {
        ButcherCustomer::query()->create([
            'business_id' => $this->business->id,
            'name' => 'My Customer',
            'phone' => '+250788555555',
            'tier' => ButcherCustomer::TIER_RETAIL,
        ]);

        $otherBusiness = Business::factory()->butcher()->create([
            'user_id' => User::factory()->create()->id,
            'registration_number' => 'RDB-OTHER-002',
        ]);

        ButcherCustomer::query()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Customer',
            'phone' => '+250788666666',
            'tier' => ButcherCustomer::TIER_RETAIL,
        ]);

        $this->actingAs($this->user)
            ->get(route('butcher.customers.index'))
            ->assertOk()
            ->assertSee('My Customer')
            ->assertDontSee('Other Customer');
    }
}
