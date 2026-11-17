<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates or updates the staff admin from ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD
 * (read through config/admin.php). Same behaviour in every environment.
 * Run alone: php artisan db:seed --class=AdminUserSeeder --force
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) config('admin.email'));
        $password = (string) config('admin.password');
        $name = trim((string) config('admin.name')) ?: 'Administrator';

        if ($email === '' || $password === '') {
            throw new RuntimeException(
                'Admin account not seeded: set ADMIN_EMAIL and ADMIN_PASSWORD in .env '
                .'(then run "php artisan config:clear" if the config is cached) and seed again.'
            );
        }

        // The "hashed" cast re-hashes the password from the current value on every run.
        User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'role' => 'admin', 'is_active' => true],
        );
    }
}
