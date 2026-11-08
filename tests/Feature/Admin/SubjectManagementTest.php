<?php

use App\Enums\MediaType;
use App\Models\AcademicTerm;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\Media;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Support\PermissionCatalog;
use App\Support\TrashableResources;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Subjects — the hinge of the whole domain.
|
| 🔴 subjects.branch_id is the ONLY path by which a branch reaches content.
| Lectures and library files hang off a subject, and teachers are assigned
| through it — which is why deletion is guarded and why every posted teacher
| id is verified rather than trusted.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');

    $this->admin = tap(Admin::create(['full_name' => 'سارة الأدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();

    $this->teacher = Teacher::create(['full_name' => 'أ. سامر خليل', 'is_publicly_listed' => true]);
});

function subjectPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'الرياضيات',
        'branch_id' => test()->branch->id,
        'description' => 'مادة الرياضيات للفرع العلمي.',
        'icon' => 'beaker',
        'accent_token' => 'accent',
        'display_order' => 1,
        'is_published' => '1',
    ], $overrides);
}

/**
 * ⚠️ Built by hand: there is no LectureFactory, and `academic_term_id` and
 * `teacher_id` are both NOT NULL — no lecture exists without a term or an
 * author, by design.
 */
function makeSubjectLecture(Subject $subject, Teacher $teacher): void
{
    $term = AcademicTerm::firstOrCreate(
        ['name' => 'الفصل الأول'],
        [
            'academic_year' => '2026/2027',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addMonths(4),
            'status' => 'active',
        ]
    );

    Lecture::create([
        'subject_id' => $subject->id,
        'academic_term_id' => $term->id,
        'teacher_id' => $teacher->id,
        'title' => 'محاضرة',
        'slug' => 'lecture-'.uniqid(),
        'display_order' => 1,
    ]);
}
// ── Create ──────────────────────────────────────────────────────

it('creates a subject with teachers attached', function () {
    actingAs($this->admin)
        ->post(route('admin.subjects.store'), subjectPayload([
            'teacher_ids' => [$this->teacher->id],
        ]))
        ->assertRedirect(route('admin.subjects.index'));

    $subject = Subject::sole();

    expect($subject->name)->toBe('الرياضيات')
        ->and($subject->branch_id)->toBe($this->branch->id)
        ->and($subject->is_published)->toBeTrue()
        ->and($subject->teachers)->toHaveCount(1)
        // 🔴 The first in the list becomes primary — it is the name the
        // public subject card shows.
        ->and($subject->teachers->first()->pivot->is_primary)->toBeTrue()
        ->and($subject->teachers->first()->pivot->assigned_by)->toBe($this->admin->id);
});

it('builds a usable unique slug from an Arabic name', function () {
    /*
     * 🔴 Str::slug() strips non-latin characters entirely, so an Arabic name
     * reduces to an EMPTY string — and with `slug` unique, the second subject
     * would collide on ''.
     */
    actingAs($this->admin)->post(route('admin.subjects.store'), subjectPayload())->assertRedirect();
    actingAs($this->admin)->post(route('admin.subjects.store'), subjectPayload())->assertRedirect();

    $slugs = Subject::pluck('slug');

    expect($slugs)->toHaveCount(2)
        ->and($slugs->unique())->toHaveCount(2)
        ->and($slugs->first())->not->toBe('');
});

it('attaches a cover as a polymorphic media row', function () {
    actingAs($this->admin)
        ->post(route('admin.subjects.store'), subjectPayload([
            // ⚠️ ->create(), not ->image(): GD is disabled here.
            'cover' => UploadedFile::fake()->create('cover.jpg', 150, 'image/jpeg'),
        ]))
        ->assertRedirect();

    $subject = Subject::sole();
    $media = $subject->image;

    expect($media)->not->toBeNull()
        ->and($media->mediable_type)->toBe(Subject::class)
        ->and($media->mediable_id)->toBe($subject->id)
        ->and($media->type)->toBe(MediaType::Image)
        ->and($media->path)->toStartWith('storage/subjects/')
        ->and($subject->cover_url)->toContain($media->path);

    Storage::disk('public')->assertExists(str_replace('storage/', '', $media->path));
});

it('refuses a subject with no name or no branch', function () {
    actingAs($this->admin)
        ->post(route('admin.subjects.store'), subjectPayload(['name' => '', 'branch_id' => '']))
        ->assertSessionHasErrors(['name', 'branch_id']);

    expect(Subject::count())->toBe(0);
});

it('refuses a teacher id that is not a real teacher', function () {
    /*
     * 🔴 The form posts a checkbox list — a list of integers anyone can edit.
     * Attaching an arbitrary id would put a stranger's name on a public
     * subject card.
     */
    actingAs($this->admin)
        ->post(route('admin.subjects.store'), subjectPayload(['teacher_ids' => [9999]]))
        ->assertSessionHasErrors('teacher_ids.0');

    expect(Subject::count())->toBe(0);
});

// ── Update ──────────────────────────────────────────────────────

it('updates a subject and re-syncs its teachers', function () {
    $second = Teacher::create(['full_name' => 'أ. رنا عوض', 'is_publicly_listed' => true]);

    actingAs($this->admin)->post(route('admin.subjects.store'), subjectPayload([
        'teacher_ids' => [$this->teacher->id],
    ]));

    $subject = Subject::sole();

    actingAs($this->admin)
        ->put(route('admin.subjects.update', $subject), subjectPayload([
            'name' => 'الرياضيات المتقدّمة',
            'teacher_ids' => [$second->id],
        ]))
        ->assertRedirect();

    $subject->refresh()->load('teachers');

    expect($subject->name)->toBe('الرياضيات المتقدّمة')
        ->and($subject->teachers)->toHaveCount(1)
        ->and($subject->teachers->first()->id)->toBe($second->id);
});

it('keeps the assignment date of a teacher who stays', function () {
    actingAs($this->admin)->post(route('admin.subjects.store'), subjectPayload([
        'teacher_ids' => [$this->teacher->id],
    ]));

    $subject = Subject::sole();
    $originalDate = $subject->teachers->first()->pivot->assigned_at;

    $this->travel(3)->days();

    actingAs($this->admin)->put(route('admin.subjects.update', $subject), subjectPayload([
        'description' => 'وصف معدَّل لا علاقة له بالإسناد.',
        'teacher_ids' => [$this->teacher->id],
    ]))->assertRedirect();

    /*
     * ⚠️ sync() with a flat attribute array would rewrite assigned_at on
     * every save, so a teacher assigned last term would look newly assigned
     * after an edit to the description.
     */
    expect($subject->fresh()->teachers->first()->pivot->assigned_at->eq($originalDate))->toBeTrue();
});

it('keeps the slug when the name is unchanged', function () {
    $subject = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'مادة', 'slug' => 'fixed-slug', 'is_published' => true,
    ]);

    actingAs($this->admin)
        ->put(route('admin.subjects.update', $subject), subjectPayload(['name' => 'مادة']))
        ->assertRedirect();

    // An edit that fixed a typo in the description must not break shared links.
    expect($subject->fresh()->slug)->toBe('fixed-slug');
});

it('turns the published switch off as well as on', function () {
    $subject = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'مادة', 'slug' => 'subj', 'is_published' => true,
    ]);

    // 🔴 An unchecked checkbox sends NOTHING; read with ?? the toggle would
    // be one-way.
    actingAs($this->admin)
        ->put(route('admin.subjects.update', $subject), subjectPayload(['name' => 'مادة', 'is_published' => '0']))
        ->assertRedirect();

    expect($subject->fresh()->is_published)->toBeFalse();
});

// ── Delete safety ───────────────────────────────────────────────

it('refuses to delete a subject that still holds content', function () {
    $subject = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'مادة', 'slug' => 'subj', 'is_published' => true,
    ]);

    makeSubjectLecture($subject, $this->teacher);

    actingAs($this->admin)
        ->delete(route('admin.subjects.destroy', $subject))
        ->assertRedirect(route('admin.subjects.index'));

    /*
     * 🔴 Soft-deleting the subject would hide every lecture beneath it from
     * every student in the branch WITHOUT deleting those rows — unreachable
     * content with no screen that lists it. Refusing is recoverable; silent
     * orphaning is not.
     */
    expect(Subject::count())->toBe(1)
        ->and($subject->fresh()->trashed())->toBeFalse();
});

it('deletes an empty subject to the trash and restores it', function () {
    $subject = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'مادة فارغة', 'slug' => 'empty', 'is_published' => true,
    ]);

    actingAs($this->admin)
        ->delete(route('admin.subjects.destroy', $subject))
        ->assertRedirect()
        ->assertSessionHas('admin.undo');

    expect(Subject::count())->toBe(0)
        ->and(Subject::withTrashed()->count())->toBe(1);

    actingAs($this->admin)
        ->get(route('admin.trash.index', ['type' => 'subjects']))
        ->assertOk()
        ->assertSee('مادة فارغة');

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'subjects', 'id' => $subject->id]))
        ->assertRedirect();

    expect(Subject::count())->toBe(1);
});

// ── Index, search and filters ───────────────────────────────────

it('lists subjects with branch, teachers and content counts', function () {
    $subject = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $subject->teachers()->attach($this->teacher);
    makeSubjectLecture($subject, $this->teacher);

    $response = actingAs($this->admin)
        ->get(route('admin.subjects.index'))
        ->assertOk()
        ->assertSee('الرياضيات')
        ->assertSee('الفرع العلمي');

    expect($response->viewData('subjects')->first()->lectures_count)->toBe(1);
});

it('filters by branch, by teacher and by keyword', function () {
    $other = Branch::create(['name' => 'الفرع الأدبي', 'slug' => 'literary', 'display_order' => 2]);

    $math = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $math->teachers()->attach($this->teacher);

    Subject::create([
        'branch_id' => $other->id, 'name' => 'التاريخ', 'slug' => 'history', 'is_published' => true,
    ]);

    $names = fn (array $query) => actingAs($this->admin)
        ->get(route('admin.subjects.index', $query))
        ->assertOk()
        ->viewData('subjects')
        ->pluck('name')
        ->all();

    expect($names(['branch_id' => $this->branch->id]))->toBe(['الرياضيات'])
        ->and($names(['teacher_id' => $this->teacher->id]))->toBe(['الرياضيات'])
        ->and($names(['q' => 'تاريخ']))->toBe(['التاريخ'])
        ->and($names([]))->toHaveCount(2);
});

// ── Authorisation ───────────────────────────────────────────────

it('enforces each subject permission independently', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('delete-subjects');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $subject = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'مادة', 'slug' => 'subj', 'is_published' => true,
    ]);

    actingAs($this->admin)->delete(route('admin.subjects.destroy', $subject))->assertForbidden();

    expect(Subject::count())->toBe(1);

    actingAs($this->admin)->get(route('admin.subjects.index'))->assertOk();
    actingAs($this->admin)->get(route('admin.subjects.create'))->assertOk();
    actingAs($this->admin)->get(route('admin.subjects.edit', $subject))->assertOk();
});

it('keeps subject management away from guests and students', function () {
    $subject = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'مادة', 'slug' => 'subj', 'is_published' => true,
    ]);

    get(route('admin.subjects.index'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.subjects.index'))->assertForbidden();

    // The direct POST is the one that matters — hiding a link is not a guard.
    actingAs($this->student)->post(route('admin.subjects.store'), subjectPayload())->assertForbidden();
    actingAs($this->student)->delete(route('admin.subjects.destroy', $subject))->assertForbidden();

    expect(Subject::count())->toBe(1);
});

it('registers the subject permissions and its trash type', function () {
    expect(PermissionCatalog::names())
        ->toContain('view-subjects')
        ->toContain('create-subjects')
        ->toContain('edit-subjects')
        ->toContain('delete-subjects')
        ->and(array_keys(TrashableResources::all()))->toContain('subjects');
});

it('purges the cover when a subject is permanently deleted', function () {
    actingAs($this->admin)->post(route('admin.subjects.store'), subjectPayload([
        'cover' => UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg'),
    ]));

    $subject = Subject::sole();
    $subject->delete();

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'subjects', 'id' => $subject->id]))
        ->assertRedirect();

    /*
     * ⚠️ mediable_id has no foreign key, so nothing cascades. Declaring
     * media() is what opts the model into TrashManager::purgeMedia.
     */
    expect(Media::withTrashed()->where('mediable_type', Subject::class)->count())->toBe(0);
});
