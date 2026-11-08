<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Slider;
use App\Models\Step;
use App\Models\Student;
use App\Models\User;
use App\Support\TrashableResources;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

/*
| Centralised trash — one screen, one tab per trashable entity.
|
| The point of these tests is the authorisation seam as much as the actions:
| trash access is PER TYPE now. Restoring a branch used to require the
| "delete sliders" permission, which was an artefact of the trash being
| built when sliders were the only trashable thing.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'icon' => 'beaker',
        'accent_token' => 'accent', 'display_order' => 1,
    ]);
});

/** Grants the admin role exactly these permissions and nothing else. */
function onlyPermissions(array $permissions): void
{
    $role = Role::findByName('admin', 'web');
    $role->syncPermissions($permissions);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

function trashedUser(): User
{
    $student = Student::create([
        'full_name' => 'طالب محذوف',
        'branch_id' => Branch::value('id'),
    ]);

    $user = tap($student->user()->create([
        'email' => 'gone@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    // Both rows together — that is how UserController::destroy does it.
    $student->delete();
    $user->delete();

    return $user;
}

// ── Tabs render ─────────────────────────────────────────────────

it('registers a tab for every trashable entity', function () {
    expect(TrashableResources::keys())
        ->toContain('sliders')
        ->toContain('branches')
        ->toContain('users')
        ->toContain('steps');
});

it('renders one tab per entity with its trashed count', function () {
    tap(Slider::create(['title' => 'شريحة']))->delete();
    tap(Step::create(['title' => 'خطوة', 'display_order' => 1]))->delete();
    tap(Branch::create(['name' => 'فرع', 'slug' => 'gone', 'display_order' => 9]))->delete();
    trashedUser();

    actingAs($this->admin)->get(route('admin.trash.index'))
        ->assertOk()
        ->assertSee('الهيرو')
        ->assertSee('الفروع')
        ->assertSee('المستخدمون')
        ->assertSee('خطوات آلية العمل')
        // Every row is listed under "all".
        ->assertSee('شريحة')
        ->assertSee('خطوة')
        ->assertSee('طالب محذوف');
});

it('filters the list to one entity when a tab is selected', function () {
    tap(Slider::create(['title' => 'شريحة محذوفة']))->delete();
    tap(Branch::create(['name' => 'فرع محذوف', 'slug' => 'gone', 'display_order' => 9]))->delete();

    actingAs($this->admin)->get(route('admin.trash.index', ['type' => 'branches']))
        ->assertOk()
        ->assertSee('فرع محذوف')
        ->assertDontSee('شريحة محذوفة');
});

// ── Restore and force delete, per entity ────────────────────────

it('restores a trashed branch', function () {
    $branch = tap(Branch::create(['name' => 'فرع', 'slug' => 'gone', 'display_order' => 9]))->delete();

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'branches', 'id' => $branch->id]))
        ->assertRedirect(route('admin.trash.index'));

    expect(Branch::whereKey($branch->id)->exists())->toBeTrue();
});

it('force deletes a trashed step for good', function () {
    $step = tap(Step::create(['title' => 'خطوة', 'display_order' => 1]))->delete();

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'steps', 'id' => $step->id]));

    expect(Step::withTrashed()->whereKey($step->id)->exists())->toBeFalse();
});

it('restores a user together with its domain record', function () {
    // 🔴 Identity is two rows. Restoring only the account produces a login
    // that lands on nothing — the account exists, the student does not.
    $user = trashedUser();

    expect(Student::withTrashed()->find($user->userable_id)->deleted_at)->not->toBeNull();

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'users', 'id' => $user->id]));

    expect(User::whereKey($user->id)->exists())->toBeTrue()
        ->and(Student::whereKey($user->userable_id)->exists())->toBeTrue();
});

it('force deletes a user together with its domain record', function () {
    $user = trashedUser();
    $studentId = $user->userable_id;

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'users', 'id' => $user->id]));

    expect(User::withTrashed()->whereKey($user->id)->exists())->toBeFalse()
        // Leaving this behind would orphan a row nothing can reach.
        ->and(Student::withTrashed()->whereKey($studentId)->exists())->toBeFalse();
});

// ── Per-type authorisation ──────────────────────────────────────

it('shows only the tabs the admin may act on', function () {
    tap(Slider::create(['title' => 'شريحة محذوفة']))->delete();
    tap(Branch::create(['name' => 'فرع محذوف', 'slug' => 'gone', 'display_order' => 9]))->delete();

    onlyPermissions(['delete-branches']);

    actingAs($this->admin)->get(route('admin.trash.index'))
        ->assertOk()
        ->assertSee('فرع محذوف')
        // 🔴 Not merely hidden from the tab bar — absent from the listing.
        ->assertDontSee('شريحة محذوفة')
        ->assertDontSee('الهيرو');
});

it('refuses a single action on a type the admin may not touch', function () {
    $slider = tap(Slider::create(['title' => 'شريحة']))->delete();

    onlyPermissions(['delete-branches']);

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'sliders', 'id' => $slider->id]))
        ->assertForbidden();

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'sliders', 'id' => $slider->id]))
        ->assertForbidden();

    expect(Slider::withTrashed()->whereKey($slider->id)->first()->deleted_at)->not->toBeNull();
});

it('separates an unknown type from a forbidden one', function () {
    // 404 says "no such thing", 403 says "not yours". Collapsing the two
    // would let a probe map the registry.
    onlyPermissions(['delete-branches']);

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'nonsense', 'id' => 1]))
        ->assertNotFound();
});

it('skips forbidden types inside a bulk selection instead of failing the request', function () {
    $slider = tap(Slider::create(['title' => 'شريحة']))->delete();
    $branch = tap(Branch::create(['name' => 'فرع', 'slug' => 'gone', 'display_order' => 9]))->delete();

    onlyPermissions(['delete-branches']);

    actingAs($this->admin)->patch(route('admin.trash.restore-selected'), [
        'items' => ["sliders:{$slider->id}", "branches:{$branch->id}", 'garbage', 'branches:abc'],
    ]);

    expect(Branch::whereKey($branch->id)->exists())->toBeTrue()
        ->and(Slider::withTrashed()->whereKey($slider->id)->first()->deleted_at)->not->toBeNull();
});

it('limits restore-all and empty to permitted types', function () {
    $slider = tap(Slider::create(['title' => 'شريحة']))->delete();
    $branch = tap(Branch::create(['name' => 'فرع', 'slug' => 'gone', 'display_order' => 9]))->delete();

    onlyPermissions(['delete-branches']);

    actingAs($this->admin)->patch(route('admin.trash.restore-all'));

    expect(Branch::whereKey($branch->id)->exists())->toBeTrue()
        ->and(Slider::withTrashed()->whereKey($slider->id)->first()->deleted_at)->not->toBeNull();
});

it('refuses the whole screen to an admin with no trashable permission', function () {
    onlyPermissions(['view-sliders']);

    actingAs($this->admin)->get(route('admin.trash.index'))->assertForbidden();
});

it('no longer ties branch restore to the slider permission', function () {
    // 🔴 The regression this guards: the trash route group required
    // `delete-sliders` for every action, so restoring a branch depended on
    // a slider permission.
    $branch = tap(Branch::create(['name' => 'فرع', 'slug' => 'gone', 'display_order' => 9]))->delete();

    onlyPermissions(['delete-branches']);

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'branches', 'id' => $branch->id]))
        ->assertRedirect();

    expect(Branch::whereKey($branch->id)->exists())->toBeTrue();
});
