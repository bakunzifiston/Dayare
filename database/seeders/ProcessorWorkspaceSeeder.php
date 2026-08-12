<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use Carbon\Carbon;
use Database\Seeders\Support\ProcessorWorkspaceBusinessCatalog;
use Database\Seeders\Support\ProcessorWorkspaceSeedBuilder;
use Database\Seeders\Support\ProcessorWorkspaceSeedProfile;
use Illuminate\Database\Seeder;

/**
 * Seeds 12 standalone processor businesses with Rwanda-based demo data across the full processor chain.
 *
 * Prerequisites (run before this seeder):
 *   php artisan db:seed --class=AdministrativeDivisionSeeder
 *   php artisan db:seed --class=SpeciesSeeder
 *   php artisan db:seed --class=UnitSeeder
 *   php artisan db:seed --class=ColdRoomStandardSeeder   (optional — cold rooms)
 *
 * Usage:
 *   php artisan db:seed --class=ProcessorWorkspaceSeeder
 *
 * Idempotent: skips when registration numbers PWS-RDB-* already exist.
 * Demo password for all accounts: password
 */
class ProcessorWorkspaceSeeder extends Seeder
{
    private const RANGE_START = '2023-01-01';

    private const RANGE_END = '2026-05-01';

    public function run(): void
    {
        if (Business::query()->where('registration_number', 'like', ProcessorWorkspaceSeedBuilder::REG_PREFIX.'%')->exists()) {
            $this->command?->info('Processor workspace data already present (PWS-RDB-*). Skipping.');

            return;
        }

        $country = AdministrativeDivision::ofType(AdministrativeDivision::TYPE_COUNTRY)->first();
        if (! $country) {
            $this->command?->error('Run AdministrativeDivisionSeeder first.');

            return;
        }

        $provinces = AdministrativeDivision::byParent($country->id)->get();
        if ($provinces->isEmpty()) {
            $this->command?->error('No Rwanda provinces found in administrative_divisions.');

            return;
        }

        $builder = new ProcessorWorkspaceSeedBuilder(
            password: 'password',
            rangeStart: Carbon::parse(self::RANGE_START)->startOfDay(),
            rangeEnd: Carbon::parse(self::RANGE_END)->endOfDay(),
            businessCatalog: ProcessorWorkspaceBusinessCatalog::ENTRIES,
            profile: ProcessorWorkspaceSeedProfile::processorWorkspaceWithMonthlyReports(),
        );

        $businesses = $builder->seedAll($country, $provinces);

        $this->command?->newLine();
        $this->command?->info('Processor workspace seed complete — '.count($businesses).' businesses (PWS-RDB-001 … PWS-RDB-012).');
        $this->command?->info('Owner logins: owner@{business-slug}.rw (e.g. '.ProcessorWorkspaceBusinessCatalog::ownerEmail('saban').')');
        $this->command?->info('Team logins:  team.{index}@{business-slug}.rw');
        $this->command?->info('Monthly reports: seeded for slaughter facilities with activity (Submitted to RICA tab)');
        $this->command?->info('Password:     password');
        $this->command?->info('Date range:   '.self::RANGE_START.' → '.self::RANGE_END);
    }
}
