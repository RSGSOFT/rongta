<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_admin_from_config(): void
    {
        config(['admin.name' => 'Site Admin', 'admin.email' => 'boss@example.test', 'admin.password' => 'first-password']);

        $this->seed(AdminUserSeeder::class);

        $user = User::where('email', 'boss@example.test')->firstOrFail();
        $this->assertSame('Site Admin', $user->name);
        $this->assertSame('admin', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('first-password', $user->password));
    }

    public function test_running_again_updates_the_same_account(): void
    {
        config(['admin.email' => 'boss@example.test', 'admin.password' => 'first-password']);
        $this->seed(AdminUserSeeder::class);

        User::where('email', 'boss@example.test')->update(['role' => 'editor', 'is_active' => false]);
        config(['admin.password' => 'second-password']);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::count());
        $user = User::firstOrFail();
        $this->assertSame('admin', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('second-password', $user->password));
        $this->assertFalse(Hash::check('first-password', $user->password));
    }

    public function test_empty_password_stops_with_an_error_and_creates_nothing(): void
    {
        config(['admin.email' => 'boss@example.test', 'admin.password' => '']);

        try {
            $this->seed(AdminUserSeeder::class);
            $this->fail('The seeder should have refused an empty password.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('ADMIN_PASSWORD', $e->getMessage());
        }

        $this->assertSame(0, User::count());
    }

    public function test_empty_email_stops_with_an_error(): void
    {
        config(['admin.email' => null, 'admin.password' => 'some-password']);

        $this->expectException(RuntimeException::class);
        $this->seed(AdminUserSeeder::class);
    }
}
