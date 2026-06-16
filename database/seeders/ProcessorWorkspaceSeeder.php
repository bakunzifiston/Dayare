<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use Carbon\Carbon;
use Database\Seeders\Support\ProcessorWorkspaceSeedBuilder;
use Illuminate\Database\Seeder;

/**
 * Seeds 10 standalone processor businesses with Rwanda-based demo data across the full processor chain.
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

    /** @var list<array{name: string, province: string, team_size: int}> */
    private const BUSINESS_CATALOG = [
        ['name' => 'Nyagatare Prime Meats Ltd', 'province' => 'Eastern Province', 'team_size' => 3],
        ['name' => 'Musanze Highland Beef Cooperative', 'province' => 'Northern Province', 'team_size' => 4],
        ['name' => 'Gikondo Urban Slaughterhouse', 'province' => 'City of Kigali', 'team_size' => 5],
        ['name' => 'Rusizi Lakeside Meats', 'province' => 'Western Province', 'team_size' => 3],
        ['name' => 'Huye Southern Protein Co.', 'province' => 'Southern Province', 'team_size' => 4],
        ['name' => 'Kayonza Eastern Livestock Processors', 'province' => 'Eastern Province', 'team_size' => 5],
        ['name' => 'Rubavu Border Meat Exporters', 'province' => 'Western Province', 'team_size' => 3],
        ['name' => 'Muhanga Central Abattoir', 'province' => 'Southern Province', 'team_size' => 4],
        ['name' => 'Rwamagana Agri-Meat Cooperative', 'province' => 'Eastern Province', 'team_size' => 5],
        ['name' => 'Kicukiro Industrial Cold Meats', 'province' => 'City of Kigali', 'team_size' => 4],
    ];

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
            businessCatalog: self::BUSINESS_CATALOG,
        );

        $businesses = $builder->seedAll($country, $provinces);

        $this->command?->newLine();
        $this->command?->info('Processor workspace seed complete — '.count($businesses).' businesses (PWS-RDB-001 … PWS-RDB-010).');
        $this->command?->info('Owner logins: owner.pws.1@processor.rw … owner.pws.10@processor.rw');
        $this->command?->info('Team logins:  team.pws.{business}.{index}@processor.rw');
        $this->command?->info('Password:     password');
        $this->command?->info('Date range:   '.self::RANGE_START.' → '.self::RANGE_END);
    }
}
