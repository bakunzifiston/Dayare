<?php

namespace Tests\Unit\SuperAdmin;

use App\Services\SuperAdmin\SuperAdminSlaughterDashboardService;
use Illuminate\Http\Request;
use Tests\TestCase;

class SuperAdminSlaughterDashboardServiceTest extends TestCase
{
    private SuperAdminSlaughterDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SuperAdminSlaughterDashboardService::class);
    }

    public function test_hub_filters_default_to_all_time(): void
    {
        $filters = $this->service->resolveHubFilters(Request::create('/rica', 'GET'));

        $this->assertSame('all', $filters['period']);
        $this->assertFalse($filters['is_filtered']);
        $this->assertSame('All time', $filters['range_label']);
    }

    public function test_hub_filters_daily_preset_ignores_stale_date_fields(): void
    {
        $filters = $this->service->resolveHubFilters(Request::create('/rica', 'GET', [
            'period' => 'day',
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
        ]));

        $this->assertSame('day', $filters['period']);
        $this->assertTrue($filters['is_filtered']);
        $this->assertSame(now()->toDateString(), $filters['date_from']);
        $this->assertSame(now()->toDateString(), $filters['date_to']);
    }

    public function test_hub_filters_custom_range_when_period_is_all(): void
    {
        $filters = $this->service->resolveHubFilters(Request::create('/rica', 'GET', [
            'period' => 'all',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]));

        $this->assertSame('all', $filters['period']);
        $this->assertTrue($filters['is_filtered']);
        $this->assertTrue($filters['has_custom_range']);
        $this->assertSame('2026-01-01', $filters['date_from']);
        $this->assertSame('2026-01-31', $filters['date_to']);
    }
}
