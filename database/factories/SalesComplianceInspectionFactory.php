<?php

namespace Database\Factories;

use App\Models\SalesComplianceInspection;
use App\Models\SalesComplianceSite;
use App\Support\SalesComplianceCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesComplianceInspection>
 */
class SalesComplianceInspectionFactory extends Factory
{
    protected $model = SalesComplianceInspection::class;

    public function definition(): array
    {
        return [
            'site_id' => SalesComplianceSite::factory(),
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00:00',
            'status' => SalesComplianceCatalog::STATUS_PENDING,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (SalesComplianceInspection $inspection): void {
            $site = $inspection->site;
            if ($site) {
                $inspection->business_id = $site->business_id;
            } elseif ($inspection->site_id) {
                $inspection->business_id = SalesComplianceSite::query()->whereKey($inspection->site_id)->value('business_id');
            }
        });
    }
}
