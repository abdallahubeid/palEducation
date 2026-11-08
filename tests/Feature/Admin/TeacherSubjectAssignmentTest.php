<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Assigning subjects to a teacher, and the branch → teacher query budget.
|
| 🔴 The pivot is what a teacher may manage. Getting it wrong in either
| direction is a permissions bug: an id nobody verified attaches a stranger to
| a public subject card, and an empty list read as "leave as is" makes the
| assignment one-way — grantable and never removable.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'سارة الأدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);

    $this->teacherRecord = Teacher::create(['full_name' => 'أ. سامر خليل', 'is_publicly_listed' => true]);

    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'samer@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();

    $this->math = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);

    $this->physics = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الفيزياء', 'slug' => 'physics',
        'display_order' => 2, 'is_published' => true,
    ]);
});

/** The teacher edit form's payload, which posts the whole account. */
function teacherPayload(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'أ. سامر خليل',
        'email' => 'samer@test.local',
        'is_active' => '1',
        'is_publicly_listed' => '1',
    ], $overrides);
}

// ── Assignment ──────────────────────────────────────────────────

it('shows the subject picker on a teacher, grouped by branch', function () {
    $response = actingAs($this->admin)
        ->get(route('admin.users.edit', $this->teacher))
        ->assertOk()
        ->assertSee('المواد المسندة')
        ->assertSee('الرياضيات')
        ->assertSee('الفيزياء');

    // ⚠️ Grouped by branch on purpose: the same subject NAME repeats across
    // branches, and a flat list would let an admin assign another branch's
    // subject without noticing.
    expect($response->viewData('subjectsByBranch')->keys())->toContain('الفرع العلمي');
});

it('does not build the picker for a student or an admin', function () {
    // Two wasted queries on every edit of an account that can never use it.
    expect(actingAs($this->admin)->get(route('admin.users.edit', $this->student))->viewData('subjectsByBranch'))
        ->toBeEmpty()
        ->and(actingAs($this->admin)->get(route('admin.users.edit', $this->admin))->viewData('subjectsByBranch'))
        ->toBeEmpty();
});

it('assigns subjects to a teacher', function () {
    actingAs($this->admin)
        ->put(route('admin.users.update', $this->teacher), teacherPayload([
            'subject_ids' => [$this->math->id, $this->physics->id],
        ]))
        ->assertRedirect(route('admin.users.index'));

    $assigned = $this->teacherRecord->fresh()->subjects;

    expect($assigned)->toHaveCount(2)
        ->and($assigned->pluck('id')->all())->toContain($this->math->id, $this->physics->id)
        // Who assigned it and when — the pivot is the only record of that.
        ->and($assigned->first()->pivot->assigned_by)->toBe($this->admin->id)
        ->and($assigned->first()->pivot->assigned_at)->not->toBeNull();
});

it('unassigns when the list shrinks, and when it empties', function () {
    $this->teacherRecord->subjects()->attach([$this->math->id, $this->physics->id]);

    actingAs($this->admin)->put(route('admin.users.update', $this->teacher), teacherPayload([
        'subject_ids' => [$this->math->id],
    ]))->assertRedirect();

    expect($this->teacherRecord->fresh()->subjects->pluck('id')->all())->toBe([$this->math->id]);

    /*
     * 🔴 An empty list means unassign everything. Reading it as "leave as is"
     * would make the checkboxes one-way — assignable, never removable.
     */
    actingAs($this->admin)->put(route('admin.users.update', $this->teacher), teacherPayload([
        'subject_ids' => [],
    ]))->assertRedirect();

    expect($this->teacherRecord->fresh()->subjects)->toBeEmpty();
});

it('keeps the assignment date of a subject that stays', function () {
    actingAs($this->admin)->put(route('admin.users.update', $this->teacher), teacherPayload([
        'subject_ids' => [$this->math->id],
    ]));

    $original = $this->teacherRecord->fresh()->subjects->first()->pivot->assigned_at;

    $this->travel(5)->days();

    actingAs($this->admin)->put(route('admin.users.update', $this->teacher), teacherPayload([
        'full_name' => 'أ. سامر عبد الله',
        'subject_ids' => [$this->math->id, $this->physics->id],
    ]))->assertRedirect();

    $subjects = $this->teacherRecord->fresh()->subjects->keyBy('id');

    // ⚠️ Only the NEW row is stamped; the existing one keeps its date.
    expect($subjects[$this->math->id]->pivot->assigned_at->eq($original))->toBeTrue()
        ->and($subjects[$this->physics->id]->pivot->assigned_at->gt($original))->toBeTrue();
});

it('refuses a subject id that is not a real subject', function () {
    /*
     * 🔴 The form posts a list of integers anyone can edit. An unverified id
     * would bind a teacher to another branch's subject — breaking the single
     * access path (subjects.branch_id) from somewhere nobody would look.
     */
    actingAs($this->admin)
        ->put(route('admin.users.update', $this->teacher), teacherPayload(['subject_ids' => [9999]]))
        ->assertSessionHasErrors('subject_ids.0');

    expect($this->teacherRecord->fresh()->subjects)->toBeEmpty();
});

it('shows an assigned-subjects badge on the teacher row', function () {
    actingAs($this->admin)
        ->get(route('admin.users.index', ['type' => 'teacher']))
        ->assertOk()
        // A teacher with no assignment sees nothing they can manage.
        ->assertSee('بلا مواد');

    $this->teacherRecord->subjects()->attach($this->math->id);

    actingAs($this->admin)
        ->get(route('admin.users.index', ['type' => 'teacher']))
        ->assertOk()
        ->assertSee('مادة');
});

// ── Authorisation ───────────────────────────────────────────────

it('blocks a non-admin from changing assignments', function () {
    $this->teacherRecord->subjects()->attach($this->math->id);

    // A teacher must not be able to grant themselves another subject.
    actingAs($this->teacher)
        ->put(route('admin.users.update', $this->teacher), teacherPayload([
            'subject_ids' => [$this->math->id, $this->physics->id],
        ]))
        ->assertForbidden();

    actingAs($this->student)
        ->put(route('admin.users.update', $this->teacher), teacherPayload(['subject_ids' => []]))
        ->assertForbidden();

    expect($this->teacherRecord->fresh()->subjects)->toHaveCount(1);
});

it('requires the edit-users permission', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('edit-users');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($this->admin)
        ->put(route('admin.users.update', $this->teacher), teacherPayload([
            'subject_ids' => [$this->math->id],
        ]))
        ->assertForbidden();

    expect($this->teacherRecord->fresh()->subjects)->toBeEmpty();
});

// ── Branch → teacher query budget ───────────────────────────────

it('loads a branch page of subjects and teachers on a fixed query budget', function () {
    /*
     * 🔴 The regression this guards: reaching teachers THROUGH a branch's
     * subjects without nested eager loading is one query per subject, then
     * one more per portrait. Invisible with three rows in development and
     * quadratic in production.
     */
    foreach (range(1, 8) as $i) {
        $subject = Subject::create([
            'branch_id' => $this->branch->id, 'name' => "مادة {$i}",
            'slug' => "subject-{$i}", 'display_order' => $i, 'is_published' => true,
        ]);

        $teacher = Teacher::create(['full_name' => "أستاذ {$i}", 'is_publicly_listed' => true]);
        $subject->teachers()->attach($teacher);
    }

    // Warm-up: the settings map is resolved once per request, and the first
    // render also primes the route/view caches.
    get(route('branches.show', $this->branch))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();

    get(route('branches.show', $this->branch))->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    /*
     * A fixed budget under ten, with ten subjects and ten teachers on the
     * page. The number must not move when content is added — that is what
     * separates "eager loaded" from "happens to be small".
     */
    expect($count)->toBeLessThan(10);
});

it('lists each teacher once per branch, however many subjects they carry', function () {
    $teacher = Teacher::create(['full_name' => 'أ. مشترك', 'is_publicly_listed' => true]);

    // One person, two subjects in the same branch.
    $this->math->teachers()->attach($teacher);
    $this->physics->teachers()->attach($teacher);

    $response = get(route('branches.show', $this->branch))->assertOk();

    /*
     * 🔴 The classic many-to-many double count: flattening the pivot without
     * keying by id lists the same person twice — a duplicate face in the grid
     * AND a wrong number in the stats, one bug with two symptoms.
     */
    expect($response->viewData('teachers'))->toHaveCount(1)
        ->and($response->viewData('stats')['teachers'])->toBe(1);
});

it('names the subject that put a teacher on the branch page', function () {
    $teacher = Teacher::create(['full_name' => 'أ. سامر', 'is_publicly_listed' => true]);
    $this->math->teachers()->attach($teacher);

    $listed = get(route('branches.show', $this->branch))->viewData('teachers')->first();

    // ⚠️ Captured while flattening, so it can never name a subject from a
    // different branch.
    expect($listed->branch_subject_name)->toBe('الرياضيات');
});
