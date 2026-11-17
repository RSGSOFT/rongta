<?php

namespace Tests\Feature\Admin\Core;

use App\Livewire\Admin\Activity\Index as ActivityIndex;
use App\Livewire\Admin\Staff\Form;
use App\Livewire\Admin\Staff\Index;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use MakesStaff, RefreshDatabase;

    public function test_admin_creates_an_editor_with_a_hashed_password(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(Form::class)
            ->set('name', 'Dilshod')
            ->set('email', 'Dilshod@Example.test')
            ->set('role', 'editor')
            ->set('password', 'strong-pass-123')
            ->set('password_confirmation', 'strong-pass-123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.staff.index'));

        $user = User::where('email', 'dilshod@example.test')->firstOrFail();
        $this->assertSame('editor', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('strong-pass-123', $user->password));

        $log = ActivityLog::where('action', 'created')->where('subject_type', User::class)->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($user->id, $log->subject_id);
        $this->assertArrayNotHasKey('password', $log->changes);
    }

    public function test_validation_of_a_new_account(): void
    {
        $admin = $this->admin(['email' => 'boss@example.test']);
        $this->actingAs($admin);

        Livewire::test(Form::class)
            ->set('name', '')
            ->set('email', 'boss@example.test')
            ->set('role', 'owner')
            ->set('password', 'short1')
            ->set('password_confirmation', 'short1')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'email' => 'unique', 'role' => 'in', 'password'])
            ->assertNoRedirect();

        Livewire::test(Form::class)
            ->set('name', 'A')
            ->set('email', 'not-an-email')
            ->set('password', 'strong-pass-123')
            ->set('password_confirmation', 'different-pass-123')
            ->call('save')
            ->assertHasErrors(['email' => 'email', 'password' => 'confirmed']);

        Livewire::test(Form::class)
            ->set('name', 'A')
            ->set('email', 'a@example.test')
            ->call('save')
            ->assertHasErrors(['password' => 'required']);

        $this->assertSame(1, User::count());
    }

    public function test_admin_edits_role_and_keeps_password_when_left_empty(): void
    {
        $this->actingAs($this->admin());
        $editor = $this->editor(['password' => 'old-password-1']);

        Livewire::test(Form::class, ['user' => $editor])
            ->assertSet('email', $editor->email)
            ->set('name', 'Renamed')
            ->set('role', 'admin')
            ->call('save')
            ->assertHasNoErrors();

        $editor->refresh();
        $this->assertSame('Renamed', $editor->name);
        $this->assertSame('admin', $editor->role);
        $this->assertTrue(Hash::check('old-password-1', $editor->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'updated', 'subject_type' => User::class, 'subject_id' => $editor->id]);
    }

    public function test_admin_resets_a_password(): void
    {
        $this->actingAs($this->admin());
        $editor = $this->editor(['password' => 'old-password-1']);

        Livewire::test(Form::class, ['user' => $editor])
            ->set('password', 'brand-new-pass-9')
            ->set('password_confirmation', 'brand-new-pass-9')
            ->call('save')
            ->assertHasNoErrors();

        $editor->refresh();
        $this->assertTrue(Hash::check('brand-new-pass-9', $editor->password));
        $this->assertNull($editor->remember_token);

        $log = ActivityLog::where('action', 'updated')->latest('id')->firstOrFail();
        $this->assertSame(['password_changed' => true], $log->changes);
    }

    public function test_admin_cannot_demote_or_deactivate_themself(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(Form::class, ['user' => $admin])->set('role', 'editor')->call('save')->assertHasErrors('role');
        Livewire::test(Form::class, ['user' => $admin])->set('is_active', false)->call('save')->assertHasErrors('is_active');
        Livewire::test(Index::class)->call('toggleActive', $admin->id);

        $admin->refresh();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->is_active);

        // Other fields of the own account can still be changed.
        Livewire::test(Form::class, ['user' => $admin])->set('name', 'Boss')->call('save')->assertHasNoErrors();
        $this->assertSame('Boss', $admin->fresh()->name);
    }

    public function test_admin_deactivates_and_reactivates_another_account(): void
    {
        $this->actingAs($this->admin());
        $editor = $this->editor(['remember_token' => 'token']);

        Livewire::test(Index::class)->call('toggleActive', $editor->id)->assertSee('отключён');
        $this->assertFalse($editor->fresh()->is_active);
        $this->assertNull($editor->fresh()->remember_token);
        $this->assertDatabaseHas('activity_logs', ['action' => 'deactivated', 'subject_id' => $editor->id]);

        Livewire::test(Index::class)->call('toggleActive', $editor->id);
        $this->assertTrue($editor->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'activated', 'subject_id' => $editor->id]);
    }

    public function test_staff_list_searches_by_name_and_email(): void
    {
        $this->actingAs($this->admin(['name' => 'Boss', 'email' => 'boss@example.test']));
        $this->editor(['name' => 'Aziza', 'email' => 'aziza@example.test']);

        Livewire::test(Index::class)
            ->assertSee('Aziza')->assertSee('Boss')
            ->set('search', 'aziza@')
            ->assertSee('Aziza')->assertDontSee('boss@example.test');
    }

    public function test_activity_viewer_filters_by_user_action_and_subject(): void
    {
        $admin = $this->admin(['name' => 'Boss']);
        $editor = $this->editor(['name' => 'Aziza']);
        $family = $this->family();

        $this->actingAs($editor);
        ActivityLog::record('updated', $family, ['sort' => 5]);
        $this->actingAs($admin);
        ActivityLog::record('deleted', $editor, ['name' => 'Aziza']);

        $page = Livewire::test(ActivityIndex::class);
        $this->assertSame(2, $page->viewData('rows')->total());
        $this->assertSame('deleted', $page->viewData('rows')->first()->action); // newest first
        $page->assertSee('Семейство #'.$family->id)->assertSee('Изменение');

        $page->set('filterUser', (string) $editor->id);
        $this->assertSame(['updated'], $page->viewData('rows')->pluck('action')->all());

        $page->set('filterUser', '')->set('filterAction', 'deleted');
        $this->assertSame(['deleted'], $page->viewData('rows')->pluck('action')->all());

        $page->set('filterAction', '')->set('filterSubject', $family->getMorphClass());
        $this->assertSame(['updated'], $page->viewData('rows')->pluck('action')->all());

        $page->set('filterSubject', '')->set('filterFrom', now()->addDay()->format('Y-m-d'));
        $this->assertSame(0, $page->viewData('rows')->total());

        $page->call('clearFilters');
        $this->assertSame(2, $page->viewData('rows')->total());

        // A broken date is ignored instead of failing.
        $page->set('filterTo', 'not-a-date');
        $this->assertSame(2, $page->viewData('rows')->total());
    }
}
