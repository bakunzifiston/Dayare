<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Idempotent dev / QA logins. Called from other seeders so test accounts always exist
 * when you run the comprehensive demo, test fixtures, or a full `db:seed`.
 *
 * Passwords are stored as plain strings here so {@see User}'s `password` => `hashed` cast
 * performs a single bcrypt hash (avoids double-hashing if a pre-hashed value were mis-detected).
 */
class TestLoginSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]
        );
        User::query()->updateOrCreate(
            ['email' => 'tester@dayare.me'],
            [
                'name' => 'Tester One',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_super_admin' => false,
            ]
        );
        User::query()->updateOrCreate(
            ['email' => 'superadmin@dayare.me'],
            [
                'name' => 'Super Admin',
                'password' => 'superadmin',
                'email_verified_at' => now(),
                'is_super_admin' => true,
            ]
        );

        $this->command?->info('Test logins: test@example.com, tester@dayare.me — password: password; superadmin@dayare.me — password: superadmin');
    }
}
