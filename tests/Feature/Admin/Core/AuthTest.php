<?php

namespace Tests\Feature\Admin\Core;

use App\Livewire\Admin\Auth\Login;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    public function test_guest_is_sent_to_the_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/models')->assertRedirect('/admin/login');
        $this->get('/admin/staff')->assertRedirect('/admin/login');
    }

    public function test_login_page_renders_in_russian_without_the_sidebar(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Панель управления')
            ->assertSee('Войти')
            ->assertDontSee('Модели весов');
    }

    public function test_active_staff_can_log_in_and_lands_on_the_dashboard(): void
    {
        $user = $this->editor(['email' => 'editor@example.test', 'password' => 'secret-pass-1']);

        Livewire::test(Login::class)
            ->set('email', 'editor@example.test')
            ->set('password', 'secret-pass-1')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['action' => 'login', 'user_id' => $user->id]);
    }

    public function test_remember_me_sets_a_remember_token(): void
    {
        $user = $this->editor(['email' => 'editor@example.test', 'password' => 'secret-pass-1', 'remember_token' => null]);

        Livewire::test(Login::class)
            ->set('email', 'editor@example.test')
            ->set('password', 'secret-pass-1')
            ->set('remember', true)
            ->call('login')
            ->assertHasNoErrors();

        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->editor(['email' => 'editor@example.test', 'password' => 'secret-pass-1']);

        Livewire::test(Login::class)
            ->set('email', 'editor@example.test')
            ->set('password', 'wrong')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSet('password', '')
            ->assertNoRedirect();

        $this->assertGuest();
        $this->assertSame(0, ActivityLog::count());
    }

    public function test_inactive_staff_cannot_log_in(): void
    {
        $this->editor(['email' => 'off@example.test', 'password' => 'secret-pass-1', 'is_active' => false]);

        Livewire::test(Login::class)
            ->set('email', 'off@example.test')
            ->set('password', 'secret-pass-1')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_and_password_are_required(): void
    {
        Livewire::test(Login::class)
            ->call('login')
            ->assertHasErrors(['email' => 'required', 'password' => 'required']);
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $this->editor(['email' => 'editor@example.test', 'password' => 'secret-pass-1']);

        $page = Livewire::test(Login::class)->set('email', 'editor@example.test');
        for ($i = 0; $i < Login::MAX_ATTEMPTS; $i++) {
            $page->set('password', 'wrong')->call('login')->assertHasErrors('email');
        }

        // Even the right password is refused while the limit is active.
        $page->set('password', 'secret-pass-1')->call('login')->assertHasErrors('email');
        $this->assertStringContainsString('Слишком много попыток', $page->errors()->first('email'));
        $this->assertGuest();
    }

    public function test_logged_in_staff_skips_the_login_page(): void
    {
        $this->actingAs($this->editor())->get('/admin/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_logout_ends_the_session(): void
    {
        $user = $this->editor();

        $this->actingAs($user)->post('/admin/logout')->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['action' => 'logout', 'user_id' => $user->id]);
    }

    public function test_there_is_no_registration_or_password_reset_route(): void
    {
        $this->get('/admin/register')->assertNotFound();
        $this->get('/admin/forgot-password')->assertNotFound();
        $this->get('/register')->assertNotFound();
    }
}
