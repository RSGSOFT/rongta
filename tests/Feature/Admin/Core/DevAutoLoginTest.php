<?php

namespace Tests\Feature\Admin\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Passwordless /admin on a local machine only (APP_ENV=local + ADMIN_AUTO_LOGIN=true). */
class DevAutoLoginTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    private function environment(string $env, bool $autoLogin): void
    {
        $this->app['env'] = $env;
        config(['admin.auto_login' => $autoLogin]);
    }

    public function test_local_with_the_flag_signs_in_the_first_active_admin(): void
    {
        $this->admin(['is_active' => false]);
        $admin = $this->admin();
        $this->admin();
        $this->environment('local', true);

        $this->get('/admin')->assertOk();
        $this->assertAuthenticatedAs($admin);

        $this->get('/admin/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_an_editor_is_never_chosen(): void
    {
        $this->editor();
        $this->environment('local', true);

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_production_ignores_the_flag(): void
    {
        $this->admin();
        $this->environment('production', true);

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_local_without_the_flag_still_needs_a_password(): void
    {
        $this->admin();
        $this->environment('local', false);

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->assertGuest();
    }
}
