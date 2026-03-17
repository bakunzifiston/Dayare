<?php

namespace Database\Seeders;

use App\Models\AdministrativeDivision;
use App\Models\Business;
use App\Models\BusinessOwnershipMember;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\Inspector;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Seed test data for development: users, businesses, facilities, inspectors.
     * All data is Rwanda-related. Run after AdministrativeDivisionSeeder.
     *
     * Test login: test@example.com / password  or  tester@dayare.me / password
     */
    public function run(): void
    {
        $country = AdministrativeDivision::ofType(AdministrativeDivision::TYPE_COUNTRY)->first();
        if (! $country) {
            $this->command?->warn('Run AdministrativeDivisionSeeder first. Skipping test data.');
            return;
        }

        $provinces = AdministrativeDivision::byParent($country->id)->orderBy('name')->get();
        $provinceKigali = $provinces->firstWhere('name', 'City of Kigali') ?? $provinces->first();
        $provinceEast = $provinces->firstWhere('name', 'Eastern Province') ?? $provinces->get(1) ?? $provinces->first();
        $provinceNorth = $provinces->firstWhere('name', 'Northern Province') ?? $provinces->first();

        $districtKigali = $provinceKigali ? AdministrativeDivision::byParent($provinceKigali->id)->orderBy('name')->first() : null;
        $districtEast = $provinceEast ? AdministrativeDivision::byParent($provinceEast->id)->orderBy('name')->first() : null;
        $districtNorth = $provinceNorth ? AdministrativeDivision::byParent($provinceNorth->id)->orderBy('name')->first() : null;

        $sectorKigali = $districtKigali ? AdministrativeDivision::byParent($districtKigali->id)->orderBy('name')->first() : null;
        $sectorEast = $districtEast ? AdministrativeDivision::byParent($districtEast->id)->orderBy('name')->first() : null;
        $sectorNorth = $districtNorth ? AdministrativeDivision::byParent($districtNorth->id)->orderBy('name')->first() : null;

        $cellKigali = $sectorKigali ? AdministrativeDivision::byParent($sectorKigali->id)->orderBy('name')->first() : null;
        $villageKigali = $cellKigali ? AdministrativeDivision::byParent($cellKigali->id)->orderBy('name')->first() : null;

        $password = Hash::make('password');
        $superAdminPassword = Hash::make('superadmin');

        // Tenant users (test@example.com and tester@dayare.me) — password: password
        $user1 = User::updateOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => $password, 'email_verified_at' => now(), 'is_super_admin' => false]
        );
        $user2 = User::updateOrCreate(
            ['email' => 'tester@dayare.me'],
            ['name' => 'Tester One', 'password' => $password, 'email_verified_at' => now(), 'is_super_admin' => false]
        );
        // Assign tenant role (owner) so permission checks apply; Super Admin bypass is in Gate::before
        foreach ([$user1, $user2] as $u) {
            if (! $u->hasRole('owner')) {
                $u->assignRole('owner');
            }
        }

        // Super Admin (platform owner) — separate credentials to access /super-admin dashboard (no role; is_super_admin + Gate::before)
        User::updateOrCreate(
            ['email' => 'superadmin@dayare.me'],
            ['name' => 'Super Admin', 'password' => $superAdminPassword, 'email_verified_at' => now(), 'is_super_admin' => true]
        );

        $this->command?->info('Tenant logins: test@example.com / tester@dayare.me — password: password');
        $this->command?->info('Super Admin login: superadmin@dayare.me — password: superadmin');

        // --- Dayare Meat Co. (Kigali) ---
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
            'province_id' => $provinceKigali?->id,
            'district_id' => $districtKigali?->id,
            'sector_id' => $sectorKigali?->id,
        ]);
        $f1Slaughter = $this->createFacility($b1, 'Kigali Slaughterhouse', Facility::TYPE_SLAUGHTERHOUSE, $provinceKigali, $districtKigali, $sectorKigali, $cellKigali, $villageKigali);
        $f1Butchery = $this->createFacility($b1, 'Downtown Butchery Kigali', Facility::TYPE_BUTCHERY, $provinceKigali, $districtKigali, $sectorKigali, null, null);
        $storageFacility = $this->createFacility($b1, 'Kigali Cold Storage', Facility::TYPE_STORAGE, $provinceKigali, $districtKigali, $sectorKigali, null, null, 500);
        $this->createInspector($f1Slaughter, 'André', 'Nkurunziza');

        // --- Rwanda Fresh Meats Ltd (Eastern Province) ---
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
            'province_id' => $provinceEast?->id,
            'district_id' => $districtEast?->id,
            'sector_id' => $sectorEast?->id,
        ]);
        $this->createOwnershipMember($b2, 'Patrick', 'Habimana', '1988-11-10');
        $this->createOwnershipMember($b2, 'Grace', 'Mukiza', '1992-07-05');
        $f2 = $this->createFacility($b2, 'Nyagatare Slaughterhouse', Facility::TYPE_SLAUGHTERHOUSE, $provinceEast, $districtEast, $sectorEast, null, null);
        $this->createInspector($f2, 'Eric', 'Nkusi');
        $this->createInspector($f2, 'Claudine', 'Uwineza');

        // --- Hilltop Butchery (Northern Province, user2) ---
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
            'province_id' => $provinceNorth?->id,
            'district_id' => $districtNorth?->id,
            'sector_id' => $sectorNorth?->id,
        ]);
        $f3 = $this->createFacility($b3, 'Musanze Butchery', Facility::TYPE_BUTCHERY, $provinceNorth, $districtNorth, $sectorNorth, null, null);
        $this->createInspector($f3, 'Jean Pierre', 'Ndayisaba');

        // Employees (Rwanda names, +250 phone)
        $emp1 = $this->createEmployee($b1, $f1Slaughter, 'Fabrice', 'Murekezi', '+250788111010', 'facility_manager');
        $emp2 = $this->createEmployee($b1, $storageFacility, 'Chantal', 'Umutoni', '+250788111011', 'warehouse_manager');
        $emp3 = $this->createEmployee($b2, $f2, 'Innocent', 'Nsengimana', '+250788222010', 'slaughter_operator');
        $emp4 = $this->createEmployee($b2, null, 'Vestine', 'Mukandori', '+250788222011', 'general_manager');

        // Suppliers (Rwanda, RWF context)
        $sup1 = $this->createSupplier($b1, 'Jean Baptiste', 'Habyarimana', $country, $provinceKigali, $districtKigali, $sectorKigali);
        $sup2 = $this->createSupplier($b1, 'Marie Claire', 'Murekatete', $country, $provinceEast, $districtEast, $sectorEast);
        $sup3 = $this->createSupplier($b2, 'Patrick', 'Niyonsenga', $country, $provinceEast, $districtEast, $sectorEast);

        // Clients (Rwanda, butchery/restaurant, preferred_facility)
        $cl1 = $this->createClient($b1, 'Umuriro Butchery', $f1Butchery, $country, $provinceKigali, $districtKigali);
        $cl2 = $this->createClient($b1, 'Kigali Heights Restaurant', null, $country, $provinceKigali, $districtKigali);
        $cl3 = $this->createClient($b1, 'Nyabugogo Market Stall', $f1Butchery, $country, $provinceKigali, $districtKigali);
        $cl4 = $this->createClient($b2, 'Kayonza Distributor Ltd', null, $country, $provinceEast, $districtEast);

        // Contracts (employee, supplier, customer — RWF amounts)
        $this->createEmployeeContract($b1, $emp1, 850_000);
        $this->createEmployeeContract($b1, $emp2, 720_000);
        $this->createEmployeeContract($b2, $emp3, 650_000);
        $this->createEmployeeContract($b2, $emp4, 1_200_000);
        $this->createContract($b1, Contract::CATEGORY_SUPPLIER, ['supplier_id' => $sup1->id], 12_000_000);
        $this->createContract($b1, Contract::CATEGORY_SUPPLIER, ['supplier_id' => $sup2->id], 8_500_000);
        $this->createContract($b1, Contract::CATEGORY_CUSTOMER, ['client_id' => $cl1->id], 5_000_000);
        $this->createContract($b2, Contract::CATEGORY_SUPPLIER, ['supplier_id' => $sup3->id], 15_000_000);

        $this->command?->info('Test data seeded: businesses, facilities, inspectors, employees, suppliers, clients, contracts (employee + supplier + customer) — Rwanda.');
    }

    private function createBusiness(User $user, array $attrs): Business
    {
        $defaults = ['user_id' => $user->id, 'status' => Business::STATUS_ACTIVE];
        return Business::firstOrCreate(
            ['user_id' => $user->id, 'registration_number' => $attrs['registration_number']],
            array_merge($defaults, $attrs)
        );
    }

    private function createOwnershipMember(Business $business, string $firstName, string $lastName, string $dob): void
    {
        $sort = $business->ownershipMembers()->count();
        BusinessOwnershipMember::firstOrCreate(
            ['business_id' => $business->id, 'first_name' => $firstName, 'last_name' => $lastName],
            ['date_of_birth' => $dob, 'sort_order' => $sort]
        );
    }

    private function createFacility(
        Business $business,
        string $name,
        string $type,
        ?AdministrativeDivision $province,
        ?AdministrativeDivision $district,
        ?AdministrativeDivision $sector = null,
        ?AdministrativeDivision $cell = null,
        ?AdministrativeDivision $village = null,
        ?int $dailyCapacity = null
    ): Facility {
        $districtName = $district?->name ?? '';
        $sectorName = $sector?->name ?? '';
        $capacity = $dailyCapacity ?? ($type === Facility::TYPE_SLAUGHTERHOUSE ? 50 : ($type === Facility::TYPE_STORAGE ? 200 : 200));
        return Facility::firstOrCreate(
            ['business_id' => $business->id, 'facility_name' => $name],
            [
                'facility_type' => $type,
                'district' => $districtName,
                'sector' => $sectorName,
                'country_id' => $province?->parent_id ? AdministrativeDivision::find($province->parent_id)?->id : null,
                'province_id' => $province?->id,
                'district_id' => $district?->id,
                'sector_id' => $sector?->id,
                'cell_id' => $cell?->id,
                'village_id' => $village?->id,
                'license_number' => 'LIC-RW-' . strtoupper(substr(uniqid(), -6)),
                'license_issue_date' => now()->subMonths(6),
                'license_expiry_date' => now()->addYear(),
                'daily_capacity' => $capacity,
                'status' => Facility::STATUS_ACTIVE,
            ]
        );
    }

    private function createInspector(Facility $facility, string $firstName, string $lastName): Inspector
    {
        $nationalId = 'NI-RW-' . $facility->id . '-' . substr(md5($firstName . $lastName), 0, 6);
        $districtName = $facility->district ?: $facility->districtDivision?->name ?? 'Kigali';
        $sectorName = $facility->sector ?: $facility->sectorDivision?->name ?? '';
        return Inspector::firstOrCreate(
            ['facility_id' => $facility->id, 'national_id' => $nationalId],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone_number' => '+250788' . random_int(100000, 999999),
                'email' => strtolower(str_replace(' ', '.', $firstName . '.' . $lastName)) . '@inspector.rw',
                'dob' => now()->subYears(30)->format('Y-m-d'),
                'nationality' => 'Rwandan',
                'country' => 'Rwanda',
                'district' => $districtName,
                'sector' => $sectorName,
                'authorization_number' => 'AUTH-RW-' . strtoupper(substr(uniqid(), -6)),
                'authorization_issue_date' => now()->subMonths(12),
                'authorization_expiry_date' => now()->addYear(),
                'species_allowed' => 'Cattle, Goat, Sheep',
                'daily_capacity' => 100,
                'stamp_serial_number' => 'STAMP-' . random_int(1000, 9999),
                'status' => Inspector::STATUS_ACTIVE,
            ]
        );
    }

    private function createEmployee(Business $business, ?Facility $facility, string $firstName, string $lastName, string $phone, string $jobTitle): Employee
    {
        return Employee::firstOrCreate(
            [
                'business_id' => $business->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ],
            [
                'facility_id' => $facility?->id,
                'national_id' => 'NI-RW-' . substr(md5($firstName . $lastName . $business->id), 0, 8),
                'date_of_birth' => now()->subYears(28)->format('Y-m-d'),
                'nationality' => 'Rwandan',
                'work_email' => strtolower(str_replace(' ', '.', $firstName . '.' . $lastName)) . '@' . str_replace(' ', '', $business->business_name) . '.rw',
                'phone' => $phone,
                'job_title' => $jobTitle,
                'employment_type' => Employee::JOB_TITLES[$jobTitle] ? 'full_time' : 'full_time',
                'hire_date' => now()->subMonths(rand(6, 24)),
                'status' => 'active',
            ]
        );
    }

    private function createSupplier(
        Business $business,
        string $firstName,
        string $lastName,
        ?AdministrativeDivision $country,
        ?AdministrativeDivision $province,
        ?AdministrativeDivision $district,
        ?AdministrativeDivision $sector
    ): Supplier {
        return Supplier::firstOrCreate(
            [
                'business_id' => $business->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ],
            [
                'date_of_birth' => now()->subYears(35)->format('Y-m-d'),
                'nationality' => 'Rwandan',
                'registration_number' => 'SUP-RW-' . strtoupper(substr(uniqid(), -6)),
                'type' => 'livestock_supply',
                'phone' => '+250788' . random_int(100000, 999999),
                'email' => strtolower($firstName . '.' . $lastName) . '@supplier.rw',
                'address_line_1' => 'Farm / Co-op, ' . ($district?->name ?? 'Rwanda'),
                'country_id' => $country?->id,
                'province_id' => $province?->id,
                'district_id' => $district?->id,
                'sector_id' => $sector?->id,
                'is_active' => true,
                'supplier_status' => Supplier::STATUS_APPROVED,
            ]
        );
    }

    private function createClient(
        Business $business,
        string $name,
        ?Facility $preferredFacility,
        ?AdministrativeDivision $country,
        ?AdministrativeDivision $province,
        ?AdministrativeDivision $district
    ): Client {
        return Client::firstOrCreate(
            [
                'business_id' => $business->id,
                'name' => $name,
            ],
            [
                'contact_person' => 'Contact ' . explode(' ', $name)[0],
                'email' => strtolower(str_replace(' ', '.', $name)) . '@rwanda.rw',
                'phone' => '+250788' . random_int(200000, 999999),
                'country' => 'Rwanda',
                'country_id' => $country?->id,
                'province_id' => $province?->id,
                'district_id' => $district?->id,
                'business_type' => in_array($name, ['Kigali Heights Restaurant'], true) ? Client::BUSINESS_TYPE_RESTAURANT : Client::BUSINESS_TYPE_BUTCHERY,
                'address_line_1' => ($district?->name ?? 'Kigali') . ', Rwanda',
                'preferred_facility_id' => $preferredFacility?->id,
                'preferred_species' => 'Cattle',
                'is_active' => true,
            ]
        );
    }

    private function createEmployeeContract(Business $business, Employee $employee, int $amountRwf): Contract
    {
        $contractNumber = 'EMP-CONTRACT-' . strtoupper(substr(uniqid(), -6));
        return Contract::firstOrCreate(
            [
                'business_id' => $business->id,
                'contract_category' => Contract::CATEGORY_EMPLOYEE,
                'employee_id' => $employee->id,
            ],
            [
                'contract_number' => $contractNumber,
                'title' => 'Employment — ' . $employee->first_name . ' ' . $employee->last_name,
                'type' => Contract::TYPE_EMPLOYMENT,
                'employment_type' => Contract::EMPLOYMENT_FULL_TIME,
                'start_date' => now()->subMonths(3),
                'end_date' => now()->addYear(),
                'status' => Contract::STATUS_ACTIVE,
                'amount' => $amountRwf,
                'notes' => 'RWF ' . number_format($amountRwf) . ' — Rwanda.',
            ]
        );
    }

    private function createContract(Business $business, string $category, array $foreignKeys, int $amountRwf): Contract
    {
        $contractNumber = 'CONTRACT-' . strtoupper(substr(uniqid(), -6));
        $attrs = array_merge([
            'business_id' => $business->id,
            'contract_category' => $category,
            'contract_number' => $contractNumber,
            'title' => ($category === Contract::CATEGORY_SUPPLIER ? 'Livestock supply' : 'Meat supply') . ' — ' . $contractNumber,
            'type' => $category === Contract::CATEGORY_SUPPLIER ? Contract::TYPE_LIVESTOCK_SUPPLY : Contract::TYPE_SALE_AGREEMENT,
            'start_date' => now()->subMonths(3),
            'end_date' => now()->addYear(),
            'status' => Contract::STATUS_ACTIVE,
            'amount' => $amountRwf,
            'notes' => 'RWF ' . number_format($amountRwf) . ' — Rwanda.',
        ], $foreignKeys);

        return Contract::firstOrCreate(
            ['business_id' => $business->id, 'contract_number' => $contractNumber],
            $attrs
        );
    }
}
