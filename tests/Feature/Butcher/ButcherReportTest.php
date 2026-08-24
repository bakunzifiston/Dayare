<?php

namespace Tests\Feature\Butcher;

use App\Models\Business;
use App\Models\ButcherOutlet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ButcherReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_is_accessible(): void
    {
        $user = User::factory()->create();
        $business = Business::factory()->butcher()->create([
            'user_id' => $user->id,
            'status' => Business::STATUS_ACTIVE,
            'registration_number' => 'RDB-RPT-001',
            'tax_id' => '1234567890',
            'contact_phone' => '+250788123456',
        ]);

        ButcherOutlet::query()->create([
            'business_id' => $business->id,
            'name' => 'Main',
            'district' => 'Kigali',
            'phone' => '+250788111111',
            'status' => ButcherOutlet::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->get(route('butcher.reports.index'))
            ->assertOk()
            ->assertSee(__('Reports'))
            ->assertSee(__('Receiving'))
            ->assertSee(__('Waste & adjustments'))
            ->assertSee(__('Stock counts'));
    }
}
