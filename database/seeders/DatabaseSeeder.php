<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. All test data is Rwanda-related (locations, names, plates, etc.).
     * Run: php artisan db:seed  or  php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        /** Dev logins (must run before any seeder that uses firstOrFail on these users). */
        $this->call(TestLoginSeeder::class);

        $this->call(AdministrativeDivisionSeeder::class);
        $this->call(SpeciesSeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        /** @see ComprehensiveRwandaSeeder Multi-tenant Rwanda demo (~200+ rows per major module). */
        $this->call(ComprehensiveRwandaSeeder::class);
        /** Local test tenants: test@example.com / tester@dayare.me — password: password; REG-TEST-* businesses. */
        $this->call(TestDataSeeder::class);
        /** Full processor pipeline + CRM + cold chain + finance for test@ (REG-TEST-001/002), 2022-01-01 → 2026-05-03. */
        $this->call(TestProcessorWorkspaceComprehensiveSeeder::class);
    }
}
