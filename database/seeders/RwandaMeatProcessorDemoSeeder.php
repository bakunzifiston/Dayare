<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use Carbon\Carbon;
use Database\Seeders\Support\ProcessorWorkspaceSeedBuilder;
use Database\Seeders\Support\ProcessorWorkspaceSeedProfile;
use Illuminate\Database\Seeder;

/**
 * Fully populated processor workspace for Nyagatare Prime Meats Ltd (Rwanda).
 *
 * Historical operational data from January 2022 through the current date, including
 * animal intakes, slaughter, inspections, certificates, cold chain, transport,
 * finance, CRM, and submitted RICA monthly inspection reports.
 *
 * Prerequisites:
 *   php artisan db:seed --class=AdministrativeDivisionSeeder
 *   php artisan db:seed --class=SpeciesSeeder
 *   php artisan db:seed --class=UnitSeeder
 *   php artisan db:seed --class=ColdRoomStandardSeeder
 *
 * Usage:
 *   php artisan db:seed --class=RwandaMeatProcessorDemoSeeder
 *
 * Re-seed (removes existing Nyagatare Prime data first):
 *   php artisan tinker --execute="(new \Database\Seeders\Support\RwandaMeatProcessorPurge)->run();"
 *   php artisan db:seed --class=RwandaMeatProcessorDemoSeeder
 *
 * Login: jeanpierre.mukamana@nyagataprime.rw / password
 */
class RwandaMeatProcessorDemoSeeder extends Seeder
{
    public const REGISTRATION_NUMBER = 'RDB/NPM/2022/0894';

    private const RANGE_START = '2022-01-01';

    /** @var list<array<string, mixed>> */
    private const BUSINESS_CATALOG = [
        [
            'name' => 'Nyagatare Prime Meats Ltd',
            'province' => 'Eastern Province',
            'team_size' => 6,
            'registration_number' => self::REGISTRATION_NUMBER,
            'owner_name' => 'Jean Pierre Mukamana',
            'owner_email' => 'jeanpierre.mukamana@nyagataprime.rw',
            'business_email' => 'info@nyagataprime.rw',
            'tax_id' => '101459821',
            'intake_count' => 185,
            'supplier_count' => 28,
            'client_count' => 24,
            'employee_count' => 22,
            'inspector_count' => 4,
            'demand_count' => 48,
            'activity_count' => 36,
        ],
    ];

    public function run(): void
    {
        if (Business::query()->where('registration_number', self::REGISTRATION_NUMBER)->exists()) {
            $this->command?->info('Rwanda meat processor demo already present ('.self::REGISTRATION_NUMBER.'). Skipping.');

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

        $rangeEnd = now()->endOfDay();

        $builder = new ProcessorWorkspaceSeedBuilder(
            password: 'password',
            rangeStart: Carbon::parse(self::RANGE_START)->startOfDay(),
            rangeEnd: $rangeEnd,
            businessCatalog: self::BUSINESS_CATALOG,
            profile: ProcessorWorkspaceSeedProfile::rwandaMeatProcessor(),
        );

        $businesses = $builder->seedAll($country, $provinces);

        $this->command?->newLine();
        $this->command?->info('Rwanda meat processor demo seeded — '.count($businesses).' business.');
        $this->command?->info('Company:      Nyagatare Prime Meats Ltd');
        $this->command?->info('Registration: '.self::REGISTRATION_NUMBER);
        $this->command?->info('Owner login:  jeanpierre.mukamana@nyagataprime.rw');
        $this->command?->info('Password:     password');
        $this->command?->info('Date range:   '.self::RANGE_START.' → '.$rangeEnd->toDateString());
    }
}
