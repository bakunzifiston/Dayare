<?php

namespace Tests\Feature;

use App\Models\AnimalIntake;
use App\Models\AnimalIntakeItem;
use App\Models\Business;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Support\LegacyAnimalIntakeBackfill;
use Tests\TestCase;

class AnimalIntakeLegacyBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_creates_legacy_items_and_is_idempotent(): void
    {
        $intake = $this->createLegacyIntake(3);

        $this->runBackfillMigration();
        $intake->refresh()->load('items');

        $this->assertSame(3, $intake->items->count());
        $this->assertSame(
            [
                'LEGACY-'.$intake->id.'-1',
                'LEGACY-'.$intake->id.'-2',
                'LEGACY-'.$intake->id.'-3',
            ],
            $intake->items->pluck('ear_tag')->all(),
        );
        $this->assertSame(AnimalIntakeItem::HEALTH_HEALTHY, $intake->items->first()->health_status);
        $this->assertNotNull($intake->reference);

        $countAfterFirst = AnimalIntakeItem::count();

        $this->runBackfillMigration();

        $this->assertSame($countAfterFirst, AnimalIntakeItem::count());
    }

    private function createLegacyIntake(int $headCount): AnimalIntake
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'business_name' => 'Backfill Test Co',
            'registration_number' => 'REG-BF-001',
            'contact_phone' => '+250788000099',
            'email' => 'backfill@test.com',
            'status' => 'active',
        ]);
        $facility = Facility::create([
            'business_id' => $business->id,
            'facility_name' => 'Test Slaughterhouse',
            'facility_type' => 'Slaughterhouse',
            'status' => 'active',
        ]);

        return AnimalIntake::create([
            'facility_id' => $facility->id,
            'source_type' => AnimalIntake::SOURCE_TYPE_SUPPLIER,
            'intake_date' => now()->toDateString(),
            'supplier_firstname' => 'Jean',
            'supplier_lastname' => 'Supplier',
            'species' => AnimalIntake::SPECIES_CATTLE,
            'number_of_animals' => $headCount,
            'unit_price' => 100000,
            'total_price' => 100000 * $headCount,
            'status' => AnimalIntake::STATUS_APPROVED,
        ]);
    }

    private function runBackfillMigration(): void
    {
        (new LegacyAnimalIntakeBackfill)->run();
    }
}
