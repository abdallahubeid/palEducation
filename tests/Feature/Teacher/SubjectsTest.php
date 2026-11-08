<?php

use App\Models\AcademicTerm;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\LibraryFile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| The teacher's assigned-subjects page.
|
| 🔴 The platform's second governing rule: a teacher sees the subjects
| assigned to THEM — not a colleague's, not every subject in a branch. The
| query starts from $teacher->subjects(), so the isolation is structural
| rather than a filter someone could forget.
|
| ⚠️ The fixture is spelled «معلّم تجريبي» with a shadda, exactly as
| DatabaseSeeder writes it. The brief spells it «معلم تجريبي» without one —
| a DIFFERENT byte sequence, and a test asserting that spelling would fail
| against the real account while looking correct.
*/

/** The demo teacher's name, as actually seeded. */
const DEMO_TEACHER = 'معلّم تجريبي';

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);

    // The same shape DatabaseSeeder creates: a login fixture kept off the
    // public roster.
    $this->teacherRecord = Teacher::create([
        'full_name' => DEMO_TEACHER,
        'title' => 'معلّم الرياضيات',
        'is_publicly_listed' => false,
    ]);

    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'teacher@paleducation.test',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();

    $this->admin = tap(Admin::create(['full_name' => 'أدمن تجريبي'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

function makeTeacherSubject(Branch $branch, string $name, array $overrides = []): Subject
{
    return Subject::create(array_merge([
        'branch_id' => $branch->id,
        'name' => $name,
        'slug' => 'subject-'.uniqid(),
        'icon' => 'beaker',
        'accent_token' => 'accent',
        'display_order' => 1,
        'is_published' => true,
    ], $overrides));
}

/**
 * ⚠️ Built by hand: no LectureFactory exists, and `academic_term_id` and
 * `teacher_id` are both NOT NULL by design.
 */
function addLecturesTo(Subject $subject, Teacher $teacher, int $count, string $status = 'published'): void
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

    foreach (range(1, $count) as $i) {
        Lecture::create([
            'subject_id' => $subject->id,
            'academic_term_id' => $term->id,
            'teacher_id' => $teacher->id,
            'title' => "محاضرة {$i}",
            'slug' => 'lecture-'.uniqid(),
            'display_order' => $i,
            'status' => $status,
        ]);
    }
}

// ── What the demo teacher sees ──────────────────────────────────

it('shows معلّم تجريبي only the subjects assigned to them', function () {
    $mine = makeTeacherSubject($this->branch, 'الرياضيات');
    $this->teacherRecord->subjects()->attach($mine);

    // A colleague's subject, in the SAME branch — the case a branch-wide
    // query would wrongly include.
    $colleague = Teacher::create(['full_name' => 'أ. رنا عوض', 'is_publicly_listed' => true]);
    $theirs = makeTeacherSubject($this->branch, 'الفيزياء', ['display_order' => 2]);
    $theirs->teachers()->attach($colleague);

    $response = actingAs($this->teacher)->get(route('teacher.subjects'))->assertOk();

    /*
     * 🔴 The isolation check, asserted on the DATA not on page text.
     * "الفيزياء" belongs to the same branch and would appear in any query
     * scoped by branch rather than by assignment.
     *
     * ⚠️ A page-wide assertDontSee would be worthless here: the teacher
     * topbar hard-codes a demo notification reading «انضم طالب جديد لدرس
     * الرياضيات», so a subject name can appear in the chrome without any
     * card rendering it. That exact trap failed this test first time round.
     */
    expect($response->viewData('subjects')->pluck('name')->all())->toBe(['الرياضيات'])
        ->and($response->viewData('subjects')->pluck('id')->all())->toBe([$mine->id]);
});

it('shows an unassigned teacher a polite empty state', function () {
    makeTeacherSubject($this->branch, 'مادة لا تخصّه');

    actingAs($this->teacher)
        ->get(route('teacher.subjects'))
        ->assertOk()
        ->assertSee(__('teacher.subjects_empty_title'))
        ->assertSee(__('teacher.subjects_empty_body'))
        // No empty grid behind the message.
        ->assertDontSee('مادة لا تخصّه');

    expect(actingAs($this->teacher)->get(route('teacher.subjects'))->viewData('subjects'))->toBeEmpty();
});

it('shows a subject even while it is unpublished', function () {
    /*
     * ⚠️ The public branch page filters to published subjects; this screen
     * must not. A teacher whose subject is still a draft would otherwise open
     * their own portal and find nothing, with no way to tell why.
     */
    $draft = makeTeacherSubject($this->branch, 'مادة مسودّة', ['is_published' => false]);
    $this->teacherRecord->subjects()->attach($draft);

    actingAs($this->teacher)
        ->get(route('teacher.subjects'))
        ->assertOk()
        ->assertSee('مادة مسودّة');
});

// ── The card's numbers ──────────────────────────────────────────

it('renders accurate counts and the branch name on each card', function () {
    $subject = makeTeacherSubject($this->branch, 'الرياضيات');
    $this->teacherRecord->subjects()->attach($subject);

    addLecturesTo($subject, $this->teacherRecord, 3);
    addLecturesTo($subject, $this->teacherRecord, 2, 'draft');

    LibraryFile::create([
        'subject_id' => $subject->id,
        'teacher_id' => $this->teacherRecord->id,
        'title' => 'ورقة عمل',
        'storage_path' => 'private/library/sheet.pdf',
        'original_filename' => 'sheet.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1024,
    ]);

    // Three students in this branch, one in another.
    Student::create(['full_name' => 'طالب ٢', 'branch_id' => $this->branch->id]);
    Student::create(['full_name' => 'طالب ٣', 'branch_id' => $this->branch->id]);

    $otherBranch = Branch::create(['name' => 'الفرع الأدبي', 'slug' => 'literary', 'display_order' => 2]);
    Student::create(['full_name' => 'طالب آخر', 'branch_id' => $otherBranch->id]);

    $response = actingAs($this->teacher)->get(route('teacher.subjects'))->assertOk();

    $card = $response->viewData('subjects')->sole();

    expect($card->lectures_count)->toBe(5)
        // 🔴 Drafts counted separately, not subtracted: the card shows how
        // much content exists AND how much is still unpublished.
        ->and($card->draft_lectures_count)->toBe(2)
        ->and($card->library_files_count)->toBe(1)
        /*
         * ⚠️ Students of the BRANCH — there is no enrolment table, so this is
         * the only question the schema can answer. The label says so.
         */
        ->and($response->viewData('branchStudentCounts')[$this->branch->id])->toBe(3);

    $response->assertSee('الفرع العلمي')
        ->assertSee(__('teacher.subject_branch_students'))
        ->assertSee(__('teacher.status_draft'));
});

it('does not invent an average score', function () {
    $subject = makeTeacherSubject($this->branch, 'الرياضيات');
    $this->teacherRecord->subjects()->attach($subject);

    /*
     * 🔴 The static page this replaced printed "متوسط النتائج 82%" per
     * subject. There is no quizzes, attempts or progress table in this
     * project, so the figure had nothing behind it. A teacher acting on an
     * invented average is worse off than one shown nothing.
     */
    actingAs($this->teacher)
        ->get(route('teacher.subjects'))
        ->assertOk()
        ->assertDontSee(__('teacher.subject_avg'));
});

it('links each card to that subject own content', function () {
    $subject = makeTeacherSubject($this->branch, 'الرياضيات');
    $this->teacherRecord->subjects()->attach($subject);

    $html = actingAs($this->teacher)->get(route('teacher.subjects'))->assertOk()->getContent();

    // ⚠️ subject_id carried through, so the link filters once the lectures
    // screen is built rather than dumping every subject's content.
    expect($html)->toContain('subject_id='.$subject->id);
});

it('loads on a flat query budget however many subjects are assigned', function () {
    foreach (range(1, 6) as $i) {
        $subject = makeTeacherSubject($this->branch, "مادة {$i}", ['display_order' => $i]);
        $this->teacherRecord->subjects()->attach($subject);
        addLecturesTo($subject, $this->teacherRecord, 2);
    }

    actingAs($this->teacher)->get(route('teacher.subjects'))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();

    actingAs($this->teacher)->get(route('teacher.subjects'))->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    /*
     * 🔴 A fixed budget. The branch badge, the cover, both counts and the
     * student totals are all eager or grouped — six subjects must not cost
     * six times anything.
     */
    expect($count)->toBeLessThan(15);
});

// ── Authorisation ───────────────────────────────────────────────

it('keeps the page away from guests', function () {
    get(route('teacher.subjects'))->assertRedirect(route('auth.login'));
});

it('keeps students and admins out', function () {
    /*
     * ⚠️ An admin is refused too. This is one specific teacher's own workspace
     * — role:teacher says exactly that, and an admin has the subjects screen
     * for oversight.
     */
    actingAs($this->student)->get(route('teacher.subjects'))->assertForbidden();
    actingAs($this->admin)->get(route('teacher.subjects'))->assertForbidden();
});

it('gives a second teacher their own list, not this one', function () {
    $mine = makeTeacherSubject($this->branch, 'الرياضيات');
    $this->teacherRecord->subjects()->attach($mine);

    $otherRecord = Teacher::create(['full_name' => 'أ. رنا عوض', 'is_publicly_listed' => true]);
    $other = tap($otherRecord->user()->create([
        'email' => 'rana@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $theirs = makeTeacherSubject($this->branch, 'الفيزياء', ['display_order' => 2]);
    $otherRecord->subjects()->attach($theirs);

    // 🔴 No id in the route — each account resolves its own list from the
    // session, so there is nothing to tamper with.
    $names = actingAs($other)
        ->get(route('teacher.subjects'))
        ->assertOk()
        ->viewData('subjects')
        ->pluck('name')
        ->all();

    // Structural again — see the note above about the topbar's demo text.
    expect($names)->toBe(['الفيزياء']);
});

it('matches the account DatabaseSeeder actually creates', function () {
    /*
     * 🔴 Guards the fixture itself. The brief spells the demo teacher
     * «معلم تجريبي» without a shadda; the seeder writes «معلّم تجريبي» with
     * one. They are different strings, so a test written to the brief's
     * spelling would pass its own assertion and prove nothing about the real
     * account.
     */
    $seeded = User::query()
        ->whereHas('userable', fn ($query) => $query->where('full_name', DEMO_TEACHER))
        ->where('userable_type', Teacher::class)
        ->first();

    expect($seeded)->not->toBeNull()
        ->and($seeded->displayName())->toBe(DEMO_TEACHER)
        ->and(DEMO_TEACHER)->not->toBe('معلم تجريبي');
});
