<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Branches — a first-class admin module.
|
| The point of these tests is the boundary as much as the CRUD: branches are
| a domain entity with their own screens and their own four permissions, and
| the card figures are DERIVED, never stored.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'icon' => 'beaker',
        'accent_token' => 'accent', 'display_order' => 1, 'is_active' => true,
    ]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

function branchPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'الفرع الأدبي',
        'slug' => 'literary',
        'description' => 'لغة عربية وتاريخ وجغرافيا.',
        'icon' => 'book',
        'accent_token' => 'tag',
        'display_order' => 2,
        'is_active' => 1,
    ], $overrides);
}

// ── Guards ──────────────────────────────────────────────────────

it('blocks guests from every branch route', function () {
    get(route('admin.branches.index'))->assertRedirect(route('auth.login'));
    get(route('admin.branches.create'))->assertRedirect(route('auth.login'));
    $this->post(route('admin.branches.store'), branchPayload())->assertRedirect(route('auth.login'));
});

it('blocks students from every branch route', function () {
    actingAs($this->student)->get(route('admin.branches.index'))->assertForbidden();

    // 🔴 The direct POST is the one that matters: hiding a sidebar link is
    // not a guard, the route is.
    actingAs($this->student)->post(route('admin.branches.store'), branchPayload())->assertForbidden();

    expect(Branch::count())->toBe(1);
});

it('rejects an admin whose branch permission was revoked from the role', function () {
    // Revoked from the ROLE, not the user: the permission is granted through
    // the role, so revoking it on the user is a silent no-op.
    Role::findByName('admin', 'web')->revokePermissionTo('delete-branches');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($this->admin)->delete(route('admin.branches.destroy', $this->branch))->assertForbidden();

    // The other three are independent and still work.
    actingAs($this->admin)->get(route('admin.branches.index'))->assertOk();
});

// ── CRUD ────────────────────────────────────────────────────────

it('lists branches on their own screen, outside settings', function () {
    actingAs($this->admin)->get(route('admin.branches.index'))
        ->assertOk()
        ->assertSee('الفرع العلمي')
        ->assertSee('scientific');
});

it('creates a branch', function () {
    actingAs($this->admin)->post(route('admin.branches.store'), branchPayload())
        ->assertRedirect(route('admin.branches.index'));

    $branch = Branch::where('slug', 'literary')->sole();

    expect($branch->name)->toBe('الفرع الأدبي')
        ->and($branch->icon)->toBe('book')
        ->and($branch->accent_token)->toBe('tag')
        ->and($branch->display_order)->toBe(2)
        ->and($branch->is_active)->toBeTrue();
});

it('updates a branch', function () {
    actingAs($this->admin)->put(
        route('admin.branches.update', $this->branch),
        branchPayload(['name' => 'اسم معدَّل', 'slug' => 'scientific', 'accent_token' => 'warn']),
    )->assertRedirect(route('admin.branches.index'));

    expect($this->branch->fresh()->name)->toBe('اسم معدَّل')
        ->and($this->branch->fresh()->accent_token)->toBe('warn')
        ->and(Branch::count())->toBe(1);
});

it('toggles visibility from the list', function () {
    actingAs($this->admin)->patch(route('admin.branches.toggle', $this->branch));

    expect($this->branch->fresh()->is_active)->toBeFalse();
});

// ── Validation ──────────────────────────────────────────────────

it('rejects an icon or tone the card cannot render', function () {
    // 🔴 Both columns hold NAMES resolved at render time. A value outside
    // the known sets draws a card with no icon or no colour — silently,
    // with no error anywhere.
    actingAs($this->admin)->post(route('admin.branches.store'), branchPayload(['icon' => 'rocket']))
        ->assertSessionHasErrors('icon');

    actingAs($this->admin)->post(route('admin.branches.store'), branchPayload(['accent_token' => 'plum']))
        ->assertSessionHasErrors('accent_token');

    expect(Branch::count())->toBe(1);
});

it('rejects a duplicate slug but allows a branch to keep its own', function () {
    actingAs($this->admin)->post(route('admin.branches.store'), branchPayload(['slug' => 'scientific']))
        ->assertSessionHasErrors('slug');

    // Same slug on its own row is not a duplicate.
    actingAs($this->admin)->put(
        route('admin.branches.update', $this->branch),
        branchPayload(['slug' => 'scientific', 'name' => 'باقٍ']),
    )->assertSessionHasNoErrors();
});

it('derives a slug from the name when the field is left blank', function () {
    // Str::slug() transliterates Arabic rather than returning an empty
    // string, so a blank field still yields a readable route key.
    actingAs($this->admin)->post(route('admin.branches.store'), branchPayload([
        'slug' => null, 'name' => 'الفرع التجاري',
    ]))->assertSessionHasNoErrors();

    expect(Branch::where('name', 'الفرع التجاري')->value('slug'))->toBe('alfraa-altgary');
});

it('keeps a derived slug unique instead of hitting a database error', function () {
    // 🔴 The `unique` rule validated the SUBMITTED value, which was empty.
    // Without a second check the derived slug can collide, and the failure
    // surfaces as a 500 rather than a field message.
    Branch::create([
        'name' => 'موجود', 'slug' => 'alfraa-altgary', 'icon' => 'book',
        'accent_token' => 'tag', 'display_order' => 5,
    ]);

    actingAs($this->admin)->post(route('admin.branches.store'), branchPayload([
        'slug' => null, 'name' => 'الفرع التجاري',
    ]))->assertSessionHasNoErrors();

    expect(Branch::where('name', 'الفرع التجاري')->value('slug'))->toBe('alfraa-altgary-2');
});

it('requires button text and link together', function () {
    actingAs($this->admin)->post(route('admin.branches.store'), branchPayload(['btn_text' => 'تصفّح']))
        ->assertSessionHasErrors('btn_link');

    actingAs($this->admin)->post(route('admin.branches.store'), branchPayload(['btn_link' => '/x']))
        ->assertSessionHasErrors('btn_text');
});

// ── Deletion ────────────────────────────────────────────────────

it('refuses to delete a branch that still holds students or subjects', function () {
    // The student created in beforeEach belongs to this branch.
    actingAs($this->admin)->delete(route('admin.branches.destroy', $this->branch));

    expect(Branch::count())->toBe(1);
});

it('soft deletes an empty branch and records who did it', function () {
    $empty = Branch::create([
        'name' => 'فرع فارغ', 'slug' => 'empty', 'icon' => 'book',
        'accent_token' => 'tag', 'display_order' => 9,
    ]);

    actingAs($this->admin)->delete(route('admin.branches.destroy', $empty));

    $trashed = Branch::withTrashed()->find($empty->id);

    expect(Branch::count())->toBe(1)
        ->and($trashed->deleted_at)->not->toBeNull()
        // deleted_by is an ACTOR reference (users), not a domain one.
        ->and($trashed->deleted_by)->toBe($this->admin->id);
});

// ── Derived counts ──────────────────────────────────────────────

it('counts each teacher once even when they teach several subjects', function () {
    // 🔴 The many-to-many miscount this guards: counting pivot rows instead
    // of people. One teacher on two subjects in the same branch is ONE
    // teacher on the card, not two.
    $teacher = Teacher::create(['full_name' => 'معلّم', 'is_publicly_listed' => true]);

    foreach (['math', 'physics'] as $i => $slug) {
        $subject = Subject::create([
            'branch_id' => $this->branch->id,
            'name' => 'مادة '.$slug,
            'slug' => $slug,
            'display_order' => $i + 1,
            'is_published' => true,
        ]);

        $subject->teachers()->attach($teacher->id, [
            'is_primary' => true, 'assigned_at' => now(), 'assigned_by' => $this->admin->id,
        ]);
    }

    $branch = Branch::withCardCounts()->find($this->branch->id);

    expect($branch->subjects_count)->toBe(2)
        ->and($branch->teachers_count)->toBe(1);
});

it('excludes unpublished subjects from the public figures', function () {
    Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'منشورة', 'slug' => 'published',
        'display_order' => 1, 'is_published' => true,
    ]);
    Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'مسودّة', 'slug' => 'draft',
        'display_order' => 2, 'is_published' => false,
    ]);

    $branch = Branch::withCardCounts()->find($this->branch->id);

    expect($branch->subjects_count)->toBe(1);
});

it('shows the derived figures on the public branch card', function () {
    Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'رياضيات', 'slug' => 'math',
        'display_order' => 1, 'is_published' => true,
    ]);

    get(route('home'))
        ->assertOk()
        ->assertSee('الفرع العلمي');

    $branch = Branch::withCardCounts()->find($this->branch->id);

    expect($branch->subjects_count)->toBe(1)
        ->and($branch->teachers_count)->toBe(0);
});

// ── Screens render ──────────────────────────────────────────────

it('renders the create and edit screens', function () {
    actingAs($this->admin)->get(route('admin.branches.create'))
        ->assertOk()
        ->assertSee('فرع جديد')
        // The icon and tone pickers are closed lists, not free text.
        ->assertSee('beaker')
        ->assertSee('كهرماني');

    actingAs($this->admin)->get(route('admin.branches.edit', $this->branch))
        ->assertOk()
        ->assertSee('الفرع العلمي')
        ->assertSee('scientific');
});

it('links the settings header panel to the branches screen', function () {
    actingAs($this->admin)->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee(route('admin.branches.index'), escape: false);
});
