<?php

use App\Enums\LectureStatus;
use App\Enums\MediaType;
use App\Models\AcademicTerm;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Support\PermissionCatalog;
use App\Support\TrashableResources;
use Database\Seeders\AcademicTermSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\SubjectSeeder;
use Database\Seeders\TeacherSeeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Teachers — the public roster section.
|
| 🔴 Nothing on a teacher card is typed copy. The portrait comes from the
| polymorphic media relation, the branch through the teacher's subjects, and
| the lecture count is COUNTED. These tests exist mostly to prove each of
| those three really is derived rather than stored.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $branch = Branch::create(['name' => 'علمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

/** A publicly listed teacher on a subject in the given branch. */
function teacherOn(string $branchSlug, string $subjectSlug, array $attributes = []): Teacher
{
    $branch = Branch::firstOrCreate(
        ['slug' => $branchSlug],
        ['name' => $branchSlug, 'display_order' => 1],
    );

    $subject = Subject::firstOrCreate(
        ['branch_id' => $branch->id, 'slug' => $subjectSlug],
        ['name' => 'مادة '.$subjectSlug, 'display_order' => 1, 'is_published' => true],
    );

    $teacher = Teacher::create(array_merge([
        'full_name' => 'أ. معلّم',
        'is_publicly_listed' => true,
    ], $attributes));

    $teacher->subjects()->attach($subject->id, ['is_primary' => true, 'assigned_at' => now()]);

    return $teacher;
}

function addLectures(Teacher $teacher, int $count, LectureStatus $status = LectureStatus::Published): void
{
    $subject = $teacher->subjects()->sole();
    // starts_on / ends_on are NOT NULL — a term is a calendar window, and
    // the schema refuses one without dates.
    $term = AcademicTerm::firstOrCreate(
        ['academic_year' => '2026/2027', 'name' => 'فصل الاختبار'],
        ['starts_on' => now()->subMonth(), 'ends_on' => now()->addMonths(4)],
    );

    for ($i = 1; $i <= $count; $i++) {
        Lecture::create([
            'subject_id' => $subject->id,
            'academic_term_id' => $term->id,
            'teacher_id' => $teacher->id,
            'title' => 'محاضرة '.$i,
            'slug' => 't'.$teacher->id.'-'.$status->value.'-'.$i,
            'status' => $status,
        ]);
    }
}

// ── Seeding ─────────────────────────────────────────────────────

it('seeds the section header settings', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get('teachers.badge_text'))->toBe('نخبة المعلّمين')
        ->and(Setting::get('teachers.title'))->toBe('معلّمون تعرفهم من صفوفهم')
        ->and(Setting::get('teachers.sub_title'))->toContain('كل معلّم مسؤول عن مادته وحدها')
        ->and(Setting::get('teachers.btn_text'))->toBe('تعرّف على جميع المعلّمين')
        ->and(Setting::get('teachers.btn_link'))->toBe('/teachers');
});

it('seeds four teachers with real lectures behind the counts', function () {
    // 🔴 The counts are DERIVED, so the seeder writes 147 actual lectures.
    // Storing the figures on a column would advertise a number the content
    // contradicts the moment anything is published or removed.
    $this->seed([BranchSeeder::class, AcademicTermSeeder::class, SubjectSeeder::class, TeacherSeeder::class]);

    $roster = Teacher::publiclyListed()
        ->withCount(['lectures as lectures_count' => fn ($q) => $q->published()])
        ->ordered()
        ->get();

    expect($roster->pluck('full_name')->all())
        ->toBe(['أ. سامر خليل', 'أ. رنا عوض', 'أ. وليد حمد', 'أ. مازن سالم'])
        ->and($roster->pluck('lectures_count')->all())->toBe([42, 36, 31, 38])
        ->and(Lecture::count())->toBe(147);
});

it('attaches a portrait through the polymorphic media relation', function () {
    $this->seed([BranchSeeder::class, AcademicTermSeeder::class, SubjectSeeder::class, TeacherSeeder::class]);

    $teacher = Teacher::where('full_name', 'أ. سامر خليل')->sole();

    expect($teacher->media)->toHaveCount(1)
        ->and($teacher->image->type)->toBe(MediaType::Image)
        ->and($teacher->avatarUrl())->toContain('images/teachers/team-1.jpg');
});

it('does not reseed lectures or portraits when run again', function () {
    $this->seed([BranchSeeder::class, AcademicTermSeeder::class, SubjectSeeder::class, TeacherSeeder::class]);
    $this->seed(TeacherSeeder::class);

    expect(Teacher::publiclyListed()->count())->toBe(4)
        ->and(Lecture::count())->toBe(147)
        ->and(Teacher::where('full_name', 'أ. سامر خليل')->sole()->media()->count())->toBe(1);
});

// ── Derived data, not stored ────────────────────────────────────

it('counts only published lectures', function () {
    // A draft is not something to advertise on the landing page.
    $teacher = teacherOn('scientific', 'math');
    addLectures($teacher, 3);
    addLectures($teacher, 2, LectureStatus::Draft);

    $counted = Teacher::withCount(['lectures as lectures_count' => fn ($q) => $q->published()])
        ->find($teacher->id);

    expect($counted->lectures_count)->toBe(3)
        ->and($teacher->lectures()->count())->toBe(5);
});

it('falls back to the subject branch when no branch is set', function () {
    /*
     * 🔁 This test previously asserted teachers.branch_id did NOT exist. The
     * column was then added deliberately so the admin form can offer a branch
     * dropdown, so the assertion is obsolete — but the property it was really
     * protecting is not: a teacher nobody has set a branch for must still
     * show the branch of the subject they teach, rather than an empty chip.
     */
    expect(Schema::hasColumn('teachers', 'branch_id'))->toBeTrue();

    $teacher = teacherOn('literary', 'arabic');
    $teacher->load(['branch', 'subjects.branch']);

    expect($teacher->branch_id)->toBeNull()
        ->and($teacher->primaryBranch()?->slug)->toBe('literary');
});

it('prefers an explicitly set branch over the subject one', function () {
    // ⚠️ The two CAN disagree, which is the drift a stored copy always risks.
    // The explicit value wins because an admin stated it on purpose; only the
    // public card reads it, and content access still runs through subjects.
    $teacher = teacherOn('scientific', 'math');
    $literary = Branch::where('slug', 'literary')->first()
        ?? Branch::create(['name' => 'أدبي', 'slug' => 'literary', 'display_order' => 2]);

    $teacher->update(['branch_id' => $literary->id]);
    $teacher->load(['branch', 'subjects.branch']);

    expect($teacher->primaryBranch()?->slug)->toBe('literary');
});

it('falls back from media to avatar_path to initials', function () {
    // The precedence is documented rather than accidental, because a
    // portrait can live in two places.
    $teacher = teacherOn('scientific', 'math', [
        'full_name' => 'أ. سامر خليل',
        'avatar_path' => 'images/teachers/team-1.jpg',
    ]);

    expect($teacher->avatarUrl())->toContain('images/teachers/team-1.jpg');

    $teacher->media()->create([
        'path' => 'uploads/settings/portrait.jpg',
        'type' => MediaType::Image,
        'display_order' => 1,
    ]);

    // An attached Media row wins over the legacy column.
    expect($teacher->fresh()->avatarUrl())->toContain('uploads/settings/portrait.jpg');

    $bare = Teacher::create(['full_name' => 'أ. مازن سالم', 'is_publicly_listed' => true]);

    expect($bare->avatarUrl())->toBeNull()
        // No portrait at all: the card draws initials instead of a gap.
        ->and($bare->initials())->toBe('مس');
});

// ── Settings tab ────────────────────────────────────────────────

it('saves the section header from the settings tab', function () {
    actingAs($this->admin)->put(route('admin.settings.teachers.update'), [
        'teachers' => [
            'badge_text' => 'شارة', 'title' => 'عنوان القسم', 'sub_title' => 'تمهيد.',
            'btn_text' => 'الكل', 'btn_link' => '/teachers',
        ],
    ])->assertRedirect(route('admin.settings.index', ['tab' => 'teachers']));

    expect(Setting::get('teachers.title'))->toBe('عنوان القسم')
        ->and(Setting::get('teachers.btn_link'))->toBe('/teachers');
});

it('rejects a dangerous scheme in the roster link', function () {
    // The value lands in an href on a public page.
    actingAs($this->admin)->put(route('admin.settings.teachers.update'), [
        'teachers' => [
            'badge_text' => 'شارة', 'title' => 'عنوان',
            'btn_text' => 'الكل', 'btn_link' => 'javascript:alert(1)',
        ],
    ])->assertSessionHasErrors('teachers.btn_link');
});

it('blocks students from the teachers settings tab', function () {
    actingAs($this->student)->put(route('admin.settings.teachers.update'))->assertForbidden();
});

// ── Trash ───────────────────────────────────────────────────────

it('registers a teachers tab in the central trash', function () {
    expect(TrashableResources::keys())->toContain('teachers')
        ->and(TrashableResources::permissionFor('teachers'))->toBe('delete-teachers')
        ->and(PermissionCatalog::names())->toContain('delete-teachers');
});

it('restores a trashed teacher from the trash tab', function () {
    $teacher = tap(teacherOn('scientific', 'math', ['full_name' => 'أ. محذوف']))->delete();

    actingAs($this->admin)->get(route('admin.trash.index'))
        ->assertOk()
        ->assertSee('المعلّمون')
        ->assertSee('أ. محذوف');

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'teachers', 'id' => $teacher->id]))
        ->assertRedirect();

    expect(Teacher::whereKey($teacher->id)->exists())->toBeTrue();
});

it('restores an account-backed teacher together with its user', function () {
    /*
     * 🔴 A teacher may carry a login account, and deleting one through the
     * Users screen soft deletes BOTH rows. Restoring only the teacher would
     * leave an account that cannot sign in — the mirror of the users-tab
     * bug this registry already guards against.
     */
    $teacher = teacherOn('scientific', 'math', ['full_name' => 'أ. بحساب']);
    $user = tap($teacher->user()->create([
        'email' => 'teach@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $user->delete();
    $teacher->delete();

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'teachers', 'id' => $teacher->id]));

    expect(Teacher::whereKey($teacher->id)->exists())->toBeTrue()
        ->and(User::whereKey($user->id)->exists())->toBeTrue();
});

it('force deletes a teacher together with its account', function () {
    $teacher = teacherOn('scientific', 'math');
    $user = tap($teacher->user()->create([
        'email' => 'teach2@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $user->delete();
    $teacher->delete();

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'teachers', 'id' => $teacher->id]));

    expect(Teacher::withTrashed()->whereKey($teacher->id)->exists())->toBeFalse()
        // Leaving this behind would orphan a row nothing can reach.
        ->and(User::withTrashed()->whereKey($user->id)->exists())->toBeFalse();
});

it('guards the teachers trash tab with its own permission', function () {
    $teacher = tap(teacherOn('scientific', 'math'))->delete();

    Role::findByName('admin', 'web')->syncPermissions(['delete-branches']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'teachers', 'id' => $teacher->id]))
        ->assertForbidden();

    expect(Teacher::withTrashed()->whereKey($teacher->id)->first()->deleted_at)->not->toBeNull();
});

// ── Public page ─────────────────────────────────────────────────

it('renders the roster on the home page from the database', function () {
    $this->seed([BranchSeeder::class, AcademicTermSeeder::class, SubjectSeeder::class, TeacherSeeder::class, SettingSeeder::class]);

    get(route('home'))
        ->assertOk()
        ->assertSee('نخبة المعلّمين')
        ->assertSee('معلّمون تعرفهم من صفوفهم')
        ->assertSee('أ. سامر خليل')
        ->assertSee('أ. مازن سالم')
        // The lecture figures on the cards are the counted ones.
        ->assertSee('42')
        ->assertSee('38')
        ->assertSee('تعرّف على جميع المعلّمين');
});

it('keeps a teacher off the roster unless they are publicly listed', function () {
    // 🔴 The dev login fixture used to be publicly listed, which put a
    // portrait-less "معلّم تجريبي" with zero lectures beside the real four.
    teacherOn('scientific', 'math', ['full_name' => 'أ. ظاهر']);
    teacherOn('scientific', 'physics', ['full_name' => 'أ. مخفي', 'is_publicly_listed' => false]);

    get(route('home'))
        ->assertOk()
        ->assertSee('أ. ظاهر')
        ->assertDontSee('أ. مخفي');
});

it('drops the roster button when its text or link is cleared', function () {
    $this->seed(SettingSeeder::class);

    get(route('home'))->assertOk()->assertSee('تعرّف على جميع المعلّمين');

    Setting::put('teachers.btn_text', '');
    Setting::put('teachers.btn_link', '');

    get(route('home'))->assertOk()->assertDontSee('تعرّف على جميع المعلّمين');
});

it('renders a teacher with no subject, portrait or lectures', function () {
    // ⚠️ Every card value is derived, so each one can be absent. A teacher
    // with nothing attached must still render rather than throw.
    Teacher::create(['full_name' => 'أ. بلا شيء', 'is_publicly_listed' => true]);

    get(route('home'))->assertOk()->assertSee('أ. بلا شيء');
});

it('loads the roster without an N+1 query', function () {
    /*
     * Three derived values per card means three extra queries each unless
     * they are eager loaded. With four teachers that is the difference
     * between a handful of queries and over a dozen.
     */
    foreach (['math', 'physics', 'chemistry'] as $slug) {
        addLectures(teacherOn('scientific', $slug, ['full_name' => 'أ. '.$slug]), 2);
    }

    DB::enableQueryLog();
    get(route('home'))->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    /*
     * ⚠️ A ceiling this generous only proves the page is not catastrophic,
     * so the real assertion is below: the count must NOT grow with the
     * number of teachers. The first version of this test passed at < 30
     * while the debug bar was still reporting an N+1 — subjects and their
     * branches were being loaded twice, once via subjects and once via
     * primarySubjects.
     */
    expect($queries)->toBeLessThan(30);

    /*
     * ⚠️ Re-measured from a WARM baseline. The settings map is resolved once
     * per container, and in tests the container survives between get() calls
     * — so the first measured request pays for it and the second does not.
     * Without this the counts differ by one and the comparison below reads as
     * an N+1 regression when nothing regressed.
     */
    DB::flushQueryLog();
    DB::enableQueryLog();
    get(route('home'))->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    addLectures(teacherOn('literary', 'arabic', ['full_name' => 'أ. رابع']), 2);

    // ⚠️ flushQueryLog first: enableQueryLog APPENDS to whatever is already
    // there, so measuring twice without it just reports the running total —
    // which is exactly how this assertion first read 34 instead of 17.
    DB::flushQueryLog();
    DB::enableQueryLog();
    get(route('home'))->assertOk();
    $withMore = count(DB::getQueryLog());
    DB::disableQueryLog();

    // A fourth teacher must cost nothing: the loads are eager, so the query
    // count is flat regardless of how many cards render.
    expect($withMore)->toBe($queries);
});

// ── Managed through Users → Teachers ────────────────────────────

it('seeds every teacher as a real user account with the teacher role', function () {
    /*
     * 🔁 The reversal this pins. These four used to be seeded with NO
     * account, so a roster entry was detached from the users table: nobody
     * could sign in as them, edit them through the Users screen, or assign
     * them content.
     */
    $this->seed([BranchSeeder::class, AcademicTermSeeder::class, SubjectSeeder::class, TeacherSeeder::class]);

    $teachers = Teacher::publiclyListed()->with('user')->get();

    expect($teachers)->toHaveCount(4);

    foreach ($teachers as $teacher) {
        expect($teacher->user)->not->toBeNull()
            // 🔴 A role assigned outside the creating transaction is the drift
            // the identity rules warn about: the account works until the first
            // permission check, then fails.
            ->and($teacher->user->roleName())->toBe('teacher')
            ->and($teacher->user->hasRole('teacher'))->toBeTrue()
            ->and($teacher->branch_id)->not->toBeNull()
            ->and($teacher->is_publicly_listed)->toBeTrue();
    }

    expect(User::where('userable_type', Teacher::class)->count())->toBe(4);
});

it('never seeds a guessable password', function () {
    // These are real addresses on a public roster; a known seeded password
    // would be four open doors.
    $this->seed([BranchSeeder::class, AcademicTermSeeder::class, SubjectSeeder::class, TeacherSeeder::class]);

    $user = Teacher::where('full_name', 'أ. سامر خليل')->sole()->user;

    foreach (['password', 'secret', '12345678', $user->email] as $guess) {
        expect(Hash::check($guess, $user->password))->toBeFalse();
    }
});

it('does not create a second account when reseeded', function () {
    $this->seed([BranchSeeder::class, AcademicTermSeeder::class, SubjectSeeder::class, TeacherSeeder::class]);
    $this->seed(TeacherSeeder::class);

    expect(User::where('userable_type', Teacher::class)->count())->toBe(4);
});

it('creates a teacher with a branch and public listing from the users screen', function () {
    $branch = Branch::where('slug', 'scientific')->sole();

    actingAs($this->admin)->post(route('admin.users.store'), [
        'type' => 'teacher',
        'full_name' => 'أ. جديد',
        'email' => 'new.teacher@test.local',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'branch_id' => $branch->id,
        'title' => 'الأحياء',
        'is_publicly_listed' => 1,
        'is_active' => 1,
    ])->assertRedirect(route('admin.users.index'));

    $teacher = Teacher::where('full_name', 'أ. جديد')->sole();

    expect($teacher->branch_id)->toBe($branch->id)
        ->and($teacher->title)->toBe('الأحياء')
        ->and($teacher->is_publicly_listed)->toBeTrue()
        ->and($teacher->user->roleName())->toBe('teacher');
});

it('takes a teacher off the public roster from the edit form', function () {
    /*
     * 🔴 The regression this guards: an unchecked checkbox is not submitted
     * at all. Without the hidden 0 in the form and boolean() in the
     * controller, unticking public listing would silently do nothing and the
     * teacher would stay on the home page forever.
     */
    $teacher = teacherOn('scientific', 'math', ['full_name' => 'أ. ظاهر']);
    $user = tap($teacher->user()->create([
        'email' => 'listed@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    get(route('home'))->assertOk()->assertSee('أ. ظاهر');

    // is_publicly_listed omitted — exactly what a browser sends for an
    // unticked box.
    actingAs($this->admin)->put(route('admin.users.update', $user), [
        'full_name' => 'أ. ظاهر',
        'email' => 'listed@test.local',
        'is_active' => 1,
    ])->assertRedirect(route('admin.users.index'));

    expect($teacher->fresh()->is_publicly_listed)->toBeFalse();

    get(route('home'))->assertOk()->assertDontSee('أ. ظاهر');
});

it('shows the counted lecture total on the users list', function () {
    $teacher = teacherOn('scientific', 'math', ['full_name' => 'أ. محاضر']);
    $teacher->user()->create([
        'email' => 'lect@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ])->syncRoleToShape();

    addLectures($teacher, 3);
    addLectures($teacher, 2, LectureStatus::Draft);

    actingAs($this->admin)->get(route('admin.users.index', ['type' => 'teacher']))
        ->assertOk()
        ->assertSee('أ. محاضر')
        // Published only — the same figure the public card advertises.
        ->assertSee('3')
        ->assertSee('محاضرة');
});

it('guards teacher management with the user permissions', function () {
    $teacher = teacherOn('scientific', 'math');
    $user = tap($teacher->user()->create([
        'email' => 'guard@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    Role::findByName('admin', 'web')->syncPermissions(['view-users']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // Viewing is allowed; every mutation is refused.
    actingAs($this->admin)->get(route('admin.users.index', ['type' => 'teacher']))->assertOk();
    actingAs($this->admin)->get(route('admin.users.create'))->assertForbidden();
    actingAs($this->admin)->get(route('admin.users.edit', $user))->assertForbidden();
    actingAs($this->admin)->delete(route('admin.users.destroy', $user))->assertForbidden();

    expect(Teacher::whereKey($teacher->id)->exists())->toBeTrue();
});

it('keeps the users list free of an N+1 as teachers are added', function () {
    foreach (['math', 'physics', 'chemistry'] as $slug) {
        $t = teacherOn('scientific', $slug, ['full_name' => 'أ. '.$slug]);
        $t->user()->create([
            'email' => $slug.'@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ])->syncRoleToShape();
        addLectures($t, 2);
    }

    /*
     * ⚠️ Warm-up request BEFORE measuring. Spatie loads the permission table
     * once per process and caches it, so the very first request runs several
     * extra queries the second one does not. Measuring without this made the
     * count DROP from 15 to 10 as a teacher was added — a difference that
     * had nothing to do with the rows on the page.
     */
    actingAs($this->admin)->get(route('admin.users.index', ['type' => 'teacher']))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();
    actingAs($this->admin)->get(route('admin.users.index', ['type' => 'teacher']))->assertOk();
    $before = count(DB::getQueryLog());
    DB::disableQueryLog();

    $extra = teacherOn('literary', 'arabic', ['full_name' => 'أ. رابع']);
    $extra->user()->create([
        'email' => 'fourth@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ])->syncRoleToShape();
    addLectures($extra, 2);

    DB::flushQueryLog();
    DB::enableQueryLog();
    actingAs($this->admin)->get(route('admin.users.index', ['type' => 'teacher']))->assertOk();
    $after = count(DB::getQueryLog());
    DB::disableQueryLog();

    /*
     * 🔴 Flat, not merely small. The branch and the lecture count are both
     * per-teacher values, and either one reached from the Blade template
     * would add a query per row — morphWith covers the first, a single
     * loadCount covers the second.
     */
    expect($after)->toBe($before);
});
