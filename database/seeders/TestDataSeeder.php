<?php

namespace Database\Seeders;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\BusinessOwnershipMember;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Seed test data for development: users, businesses, facilities, inspectors.
     * Run after AdministrativeDivisionSeeder (e.g. php artisan db:seed).
     *
     * Test login: test@example.com / password
     * Or: tester@dayare.me / password
     */
    public function run(): void
    {
        $country = AdministrativeDivision::ofType(AdministrativeDivision::TYPE_COUNTRY)->first();
        if (! $country) {
            $this->command?->warn('Run AdministrativeDivisionSeeder first. Skipping test data.');
            return;
        }

        $provinces = AdministrativeDivision::byParent($country->id)->get();
        $district = null;
        $sector = null;
        if ($provinces->isNotEmpty()) {
            $district = AdministrativeDivision::byParent($provinces->first()->id)->first();
            if ($district) {
                $sector = AdministrativeDivision::byParent($district->id)->first();
            }
        }

        $password = Hash::make('password');

        // --- Test users ---
        $user1 = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => $password, 'email_verified_at' => now()]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'tester@dayare.me'],
            ['name' => 'Tester One', 'password' => $password, 'email_verified_at' => now()]
        );

        $this->command?->info('Test users ready (test@example.com / tester@dayare.me — password: password).');

        // --- Businesses for user1 ---
        $b1 = $this->createBusiness($user1, [
            'business_name' => 'Dayare Meat Co.',
            'registration_number' => 'REG-TEST-001',
            'tax_id' => 'TIN-100001',
            'contact_phone' => '+250788111001',
            'email' => 'contact@dayaremeat.test',
            'status' => Business::STATUS_ACTIVE,
            'owner_first_name' => 'Jean',
            'owner_last_name' => 'Niyonzima',
            'owner_dob' => '1985-06-15',
            'owner_phone' => '+250788111002',
            'owner_email' => 'jean@dayaremeat.test',
            'ownership_type' => 'sole_proprietor',
            'country_id' => $country->id,
            'province_id' => $provinces->first()?->id,
            'district_id' => $district?->id,
            'sector_id' => $sector?->id,
        ]);
        $this->createFacility($b1, 'Kigali Slaughterhouse', Facility::TYPE_SLAUGHTERHOUSE, 'Kicukiro', 'Gikondo');
        $this->createFacility($b1, 'Downtown Butchery', Facility::TYPE_BUTCHERY, 'Gasabo', 'Remera');

        $b2 = $this->createBusiness($user1, [
            'business_name' => 'Rwanda Fresh Meats Ltd',
            'registration_number' => 'REG-TEST-002',
            'tax_id' => 'TIN-100002',
            'contact_phone' => '+250788222001',
            'email' => 'info@rwandafresh.test',
            'status' => Business::STATUS_ACTIVE,
            'owner_first_name' => 'Marie',
            'owner_last_name' => 'Uwera',
            'owner_dob' => '1990-03-20',
            'owner_phone' => '+250788222002',
            'owner_email' => 'marie@rwandafresh.test',
            'ownership_type' => 'company',
            'country_id' => $country->id,
            'province_id' => $provinces->first()?->id,
            'district_id' => $district?->id,
            'sector_id' => $sector?->id,
        ]);
        $this->createOwnershipMember($b2, 'Patrick', 'Habimana', '1988-11-10');
        $this->createOwnershipMember($b2, 'Grace', 'Mukiza', '1992-07-05');
        $f2 = $this->createFacility($b2, 'Eastern Slaughterhouse', Facility::TYPE_SLAUGHTERHOUSE, 'Nyagatare', 'Gatunda');
        $this->createInspector($f2, 'Inspector', 'One');
        $this->createInspector($f2, 'Inspector', 'Two');

        // --- Business for user2 ---
        $b3 = $this->createBusiness($user2, [
            'business_name' => 'Hilltop Butchery',
            'registration_number' => 'REG-TEST-003',
            'contact_phone' => '+250788333001',
            'email' => 'hilltop@test.me',
            'status' => Business::STATUS_ACTIVE,
            'owner_first_name' => 'David',
            'owner_last_name' => 'Mugisha',
            'owner_dob' => '1982-09-12',
            'ownership_type' => 'sole_proprietor',
            'country_id' => $country->id,
            'province_id' => $provinces->first()?->id,
            'district_id' => $district?->id,
        ]);
        $f3 = $this->createFacility($b3, 'Hilltop Main', Facility::TYPE_BUTCHERY, 'Musanze', 'Muhoza');
        $this->createInspector($f3, 'Jean Pierre', 'Ndayisaba');

        $this->command?->info('Test data seeded: businesses, facilities, inspectors.');
    }

    private function createBusiness(User $user, array $attrs): Business
    {
        $defaults = [
            'user_id' => $user->id,
            'status' => Business::STATUS_ACTIVE,
        ];
        return Business::firstOrCreate(
            [
                'user_id' => $user->id,
                'registration_number' => $attrs['registration_number'],
            ],
            array_merge($defaults, $attrs)
        );
    }

    private function createOwnershipMember(Business $business, string $firstName, string $lastName, string $dob): void
    {
        $sort = $business->ownershipMembers()->count();
        BusinessOwnershipMember::firstOrCreate(
            [
                'business_id' => $business->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ],
            [
                'date_of_birth' => $dob,
                'sort_order' => $sort,
            ]
        );
    }

    private function createFacility(Business $business, string $name, string $type, string $district, string $sector): Facility
    {
        return Facility::firstOrCreate(
            [
                'business_id' => $business->id,
                'facility_name' => $name,
            ],
            [
                'facility_type' => $type,
                'district' => $district,
                'sector' => $sector,
                'license_number' => 'LIC-' . strtoupper(substr(uniqid(), -6)),
                'license_issue_date' => now()->subMonths(6),
                'license_expiry_date' => now()->addYear(),
                'daily_capacity' => $type === Facility::TYPE_SLAUGHTERHOUSE ? 50 : 200,
                'status' => Facility::STATUS_ACTIVE,
            ]
        );
    }

    private function createInspector(Facility $facility, string $firstName, string $lastName): Inspector
    {
        $nationalId = 'TEST-NI-' . $facility->id . '-' . substr(md5($firstName . $lastName), 0, 6);
        return Inspector::firstOrCreate(
            [
                'facility_id' => $facility->id,
                'national_id' => $nationalId,
            ],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone_number' => '+250788' . random_int(100000, 999999),
                'email' => strtolower($firstName . '.' . $lastName) . '@inspector.test',
                'dob' => now()->subYears(30)->format('Y-m-d'),
                'nationality' => 'Rwandan',
                'country' => 'Rwanda',
                'district' => $facility->district,
                'sector' => $facility->sector,
                'authorization_number' => 'AUTH-' . strtoupper(substr(uniqid(), -6)),
                'authorization_issue_date' => now()->subMonths(12),
                'authorization_expiry_date' => now()->addYear(),
                'species_allowed' => 'Cattle, Goat, Sheep',
                'daily_capacity' => 100,
                'stamp_serial_number' => 'STAMP-' . random_int(1000, 9999),
                'status' => Inspector::STATUS_ACTIVE,
            ]
        );
    }
}
