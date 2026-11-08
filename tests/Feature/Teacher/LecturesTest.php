<?php

use App\Enums\LectureStatus;
use App\Enums\VideoSource;
use App\Enums\VideoStatus;
use App\Models\AcademicTerm;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| The teacher's lectures screen.
|
| 🔴 Scoped by SUBJECT, not by lectures.teacher_id: a teacher manages their
| subject's content including what a predecessor uploaded, and must never
| reach a subject they are not assigned to. Both directions are asserted.
|
| ⚠️ The fixture is «معلّم تجريبي» with a shadda, exactly as DatabaseSeeder
| spells it — see SubjectsTest for why that matters.
*/

const LECTURE_TEACHER = 'معلّم تجريبي';
const SAMPLE_VIDEO = 'dQw4w9WgXcQ';

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);

    $this->term = AcademicTerm::create([
        'name' => 'الفصل الأول',
        'academic_year' => '2026/2027',
        'starts_on' => now()->subMonth(),
        'ends_on' => now()->addMonths(4),
        'status' => 'active',
        'is_current' => true,
    ]);

    $this->teacherRecord = Teacher::create([
        'full_name' => LECTURE_TEACHER, 'title' => 'معلّم الرياضيات', 'is_publicly_listed' => false,
    ]);

    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'teacher@paleducation.test', 'password' => bcrypt('password'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->math = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($this->math);

    // A subject this teacher is NOT assigned to, in the same branch.
    $this->physics = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الفيزياء', 'slug' => 'physics',
        'display_order' => 2, 'is_published' => true,
    ]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();

    $this->admin = tap(Admin::create(['full_name' => 'أدمن تجريبي'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

function makeTeacherLecture(Subject $subject, Teacher $author, array $overrides = []): Lecture
{
    return Lecture::create(array_merge([
        'subject_id' => $subject->id,
        'academic_term_id' => test()->term->id,
        'teacher_id' => $author->id,
        'title' => 'محاضرة',
        'slug' => 'lecture-'.uniqid(),
        'display_order' => 1,
        'status' => LectureStatus::Published,
        'video_source' => VideoSource::YouTube,
        'video_reference' => SAMPLE_VIDEO,
        'video_status' => VideoStatus::Ready,
    ], $overrides));
}

function lecturePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'قواعد الاشتقاق',
        'subject_id' => test()->math->id,
        'video_url' => 'https://www.youtube.com/watch?v='.SAMPLE_VIDEO,
        'description' => 'شرح قواعد الاشتقاق الأساسية.',
        'status' => 'draft',
    ], $overrides);
}

// ── Listing and filters ─────────────────────────────────────────

it('shows معلّم تجريبي the lectures of their own subjects', function () {
    makeTeacherLecture($this->math, $this->teacherRecord, ['title' => 'محاضرتي']);

    // Same branch, a subject this teacher is not assigned to.
    $colleague = Teacher::create(['full_name' => 'أ. رنا عوض', 'is_publicly_listed' => true]);
    $this->physics->teachers()->attach($colleague);
    makeTeacherLecture($this->physics, $colleague, ['title' => 'محاضرة زميلة']);

    $titles = actingAs($this->teacher)
        ->get(route('teacher.lectures'))
        ->assertOk()
        ->viewData('lectures')
        ->pluck('title')
        ->all();

    /*
     * 🔴 The isolation check, asserted on DATA — the teacher topbar carries
     * hard-coded demo text, so a page-wide assertDontSee would match chrome
     * rather than a row.
     */
    expect($titles)->toBe(['محاضرتي']);
});

it('shows a lecture on the teacher subject even when a colleague uploaded it', function () {
    /*
     * ⚠️ The other direction. Scoping by lectures.teacher_id would hide the
     * teacher's OWN subject's material from them the moment a predecessor
     * had uploaded it.
     */
    $predecessor = Teacher::create(['full_name' => 'أ. سابق', 'is_publicly_listed' => false]);
    makeTeacherLecture($this->math, $predecessor, ['title' => 'محاضرة سابقة']);

    expect(actingAs($this->teacher)->get(route('teacher.lectures'))->viewData('lectures')->pluck('title')->all())
        ->toBe(['محاضرة سابقة']);
});

it('filters by subject, status and title', function () {
    $second = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الفيزياء المسندة', 'slug' => 'physics-2',
        'display_order' => 3, 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($second);

    makeTeacherLecture($this->math, $this->teacherRecord, ['title' => 'الاشتقاق']);
    makeTeacherLecture($second, $this->teacherRecord, ['title' => 'الحركة', 'status' => LectureStatus::Draft]);

    $titles = fn (array $query) => actingAs($this->teacher)
        ->get(route('teacher.lectures', $query))
        ->assertOk()
        ->viewData('lectures')
        ->pluck('title')
        ->all();

    expect($titles(['subject_id' => $this->math->id]))->toBe(['الاشتقاق'])
        ->and($titles(['status' => 'draft']))->toBe(['الحركة'])
        ->and($titles(['search' => 'حركة']))->toBe(['الحركة'])
        ->and($titles([]))->toHaveCount(2);
});

it('ignores a subject filter for a subject the teacher does not own', function () {
    makeTeacherLecture($this->math, $this->teacherRecord, ['title' => 'محاضرتي']);

    $response = actingAs($this->teacher)
        ->get(route('teacher.lectures', ['subject_id' => $this->physics->id]))
        ->assertOk();

    /*
     * ⚠️ Dropped rather than obeyed, and rather than erroring: a hand-edited
     * query string must not become a way to probe which subject ids exist.
     */
    expect($response->viewData('filters')['subject_id'])->toBeNull()
        ->and($response->viewData('lectures')->pluck('title')->all())->toBe(['محاضرتي']);
});

it('offers only the teacher own subjects in the filter and the form', function () {
    $html = actingAs($this->teacher)->get(route('teacher.lectures'))->assertOk()->getContent();

    // 🔴 A dropdown listing every subject on the platform would expose the
    // content structure to someone who owns none of it.
    expect($html)->toContain('الرياضيات')
        ->not->toContain('value="'.$this->physics->id.'"');
});

// ── Creating from a YouTube link ────────────────────────────────

it('creates a lecture and stores only the parsed video id', function () {
    actingAs($this->teacher)
        ->post(route('teacher.lectures.store'), lecturePayload([
            // Deliberately messy: a playlist and a timestamp ride along.
            'video_url' => 'https://www.youtube.com/watch?v='.SAMPLE_VIDEO.'&list=PLabc&t=42',
        ]))
        ->assertRedirect();

    $lecture = Lecture::sole();

    expect($lecture->title)->toBe('قواعد الاشتقاق')
        ->and($lecture->subject_id)->toBe($this->math->id)
        ->and($lecture->teacher_id)->toBe($this->teacherRecord->id)
        ->and($lecture->video_source)->toBe(VideoSource::YouTube)
        /*
         * 🔴 The ID alone. Storing the pasted URL would carry the playlist,
         * the timestamp and any tracking parameters straight into an iframe
         * rendered on a student's page.
         */
        ->and($lecture->video_reference)->toBe(SAMPLE_VIDEO)
        ->and($lecture->embed_url)->toBe('https://www.youtube.com/embed/'.SAMPLE_VIDEO)
        // Playable the moment it is linked — there is no upload to process.
        ->and($lecture->video_status)->toBe(VideoStatus::Ready)
        // The term is taken from the current one, never from the form.
        ->and($lecture->academic_term_id)->toBe($this->term->id);
});

it('accepts every real YouTube link shape', function () {
    $shapes = [
        'https://youtu.be/'.SAMPLE_VIDEO,
        'https://www.youtube.com/embed/'.SAMPLE_VIDEO,
        'https://m.youtube.com/watch?v='.SAMPLE_VIDEO,
        // Teachers copy from the address bar, often without the scheme.
        'youtube.com/watch?v='.SAMPLE_VIDEO,
    ];

    foreach ($shapes as $index => $url) {
        actingAs($this->teacher)
            ->post(route('teacher.lectures.store'), lecturePayload([
                'title' => 'محاضرة '.$index,
                'video_url' => $url,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    expect(Lecture::pluck('video_reference')->unique()->all())->toBe([SAMPLE_VIDEO]);
});

it('rejects anything that is not a YouTube link', function () {
    $rejected = [
        'https://vimeo.com/12345678',
        'https://example.test/video.mp4',
        // 🔴 The host-spoofing case: a substring check for "youtube" would
        // accept this and embed a third party's page in a student's browser.
        'https://evil.test/youtube.com/watch?v='.SAMPLE_VIDEO,
        'javascript:alert(1)',
        'ليس رابطاً',
    ];

    foreach ($rejected as $url) {
        actingAs($this->teacher)
            ->post(route('teacher.lectures.store'), lecturePayload(['video_url' => $url]))
            ->assertSessionHasErrors('video_url');
    }

    expect(Lecture::count())->toBe(0);
});

it('refuses a lecture with no title or no subject', function () {
    actingAs($this->teacher)
        ->post(route('teacher.lectures.store'), lecturePayload(['title' => '', 'subject_id' => '']))
        ->assertSessionHasErrors(['title', 'subject_id']);

    expect(Lecture::count())->toBe(0);
});

it('refuses to file a lecture under a subject the teacher does not own', function () {
    /*
     * 🔴 subject_id is a number in a form anyone can edit. A plain `exists`
     * rule would let a teacher push content into a colleague's subject — or
     * into another branch, which is how material reaches the wrong students.
     */
    actingAs($this->teacher)
        ->post(route('teacher.lectures.store'), lecturePayload(['subject_id' => $this->physics->id]))
        ->assertSessionHasErrors('subject_id');

    expect(Lecture::count())->toBe(0);
});

it('publishes immediately or saves as a draft', function () {
    actingAs($this->teacher)->post(route('teacher.lectures.store'), lecturePayload([
        'title' => 'مسودّة', 'status' => 'draft',
    ]));

    actingAs($this->teacher)->post(route('teacher.lectures.store'), lecturePayload([
        'title' => 'منشورة', 'status' => 'published',
    ]));

    $draft = Lecture::where('title', 'مسودّة')->sole();
    $published = Lecture::where('title', 'منشورة')->sole();

    expect($draft->status)->toBe(LectureStatus::Draft)
        // A draft has no publish date — it was never published.
        ->and($draft->published_at)->toBeNull()
        ->and($published->status)->toBe(LectureStatus::Published)
        ->and($published->published_at)->not->toBeNull();
});

it('builds a usable unique slug from an Arabic title', function () {
    // 🔴 Str::slug() strips non-latin characters, so an Arabic title reduces
    // to an EMPTY string and the second lecture would collide on ''.
    actingAs($this->teacher)->post(route('teacher.lectures.store'), lecturePayload());
    actingAs($this->teacher)->post(route('teacher.lectures.store'), lecturePayload());

    $slugs = Lecture::pluck('slug');

    expect($slugs)->toHaveCount(2)
        ->and($slugs->unique())->toHaveCount(2)
        ->and($slugs->first())->not->toBe('');
});

it('refuses to create anything when no term is current', function () {
    /*
     * ⚠️ lectures.academic_term_id is NOT NULL. Without a current term the
     * insert would fail with a database error; the guard turns that into a
     * message that names the actual problem.
     */
    $this->term->update(['is_current' => false]);

    actingAs($this->teacher)
        ->post(route('teacher.lectures.store'), lecturePayload())
        ->assertSessionHasErrors('subject_id');

    expect(Lecture::count())->toBe(0);
});

// ── Editing and deleting ────────────────────────────────────────

it('updates a lecture on the teacher own subject', function () {
    $lecture = makeTeacherLecture($this->math, $this->teacherRecord, ['title' => 'قديم']);

    actingAs($this->teacher)
        ->put(route('teacher.lectures.update', $lecture), lecturePayload(['title' => 'جديد']))
        ->assertRedirect();

    expect($lecture->fresh()->title)->toBe('جديد');
});

it('renders the edit button binding compiled, not a literal @js directive', function () {
    /*
     * 🔴 Regression guard. @js([...]) is a Blade DIRECTIVE, and directives are
     * not compiled inside an attribute on a component tag (<x-…>): the string
     * is extracted verbatim before the directive pass, so Alpine receives an
     * @click expression starting with "@" → SyntaxError, and the button is
     * silently dead. The working form is a {{ Js::from() }} ECHO.
     */
    makeTeacherLecture($this->math, $this->teacherRecord, ['title' => 'محاضرة للتعديل']);

    $html = actingAs($this->teacher)->get(route('teacher.lectures'))->assertOk()->getContent();

    expect($html)
        ->toContain('openEdit(JSON.parse(')
        ->not->toContain('openEdit(@js(')
        ->not->toContain('$lecture->title');
});

it('soft deletes a lecture on the teacher own subject', function () {
    $lecture = makeTeacherLecture($this->math, $this->teacherRecord);

    actingAs($this->teacher)
        ->delete(route('teacher.lectures.destroy', $lecture))
        ->assertRedirect();

    expect(Lecture::count())->toBe(0)
        ->and(Lecture::withTrashed()->count())->toBe(1);
});

it('answers 404 for a lecture outside the teacher subjects', function () {
    $colleague = Teacher::create(['full_name' => 'أ. رنا عوض', 'is_publicly_listed' => true]);
    $theirs = makeTeacherLecture($this->physics, $colleague, ['title' => 'ليست له']);

    /*
     * 🔴 404, not 403. A lecture outside this teacher's subjects is
     * indistinguishable from one that does not exist — "forbidden" would
     * confirm the id is real and that a colleague has content there.
     */
    actingAs($this->teacher)
        ->put(route('teacher.lectures.update', $theirs), lecturePayload(['title' => 'اختطاف']))
        ->assertNotFound();

    actingAs($this->teacher)
        ->delete(route('teacher.lectures.destroy', $theirs))
        ->assertNotFound();

    expect($theirs->fresh()->title)->toBe('ليست له')
        ->and($theirs->fresh()->trashed())->toBeFalse();
});

// ── Authorisation ───────────────────────────────────────────────

it('keeps the page away from guests', function () {
    get(route('teacher.lectures'))->assertRedirect(route('auth.login'));
});

it('keeps students and admins out of every lecture route', function () {
    $lecture = makeTeacherLecture($this->math, $this->teacherRecord);

    foreach ([$this->student, $this->admin] as $outsider) {
        actingAs($outsider)->get(route('teacher.lectures'))->assertForbidden();
        actingAs($outsider)->post(route('teacher.lectures.store'), lecturePayload())->assertForbidden();
        actingAs($outsider)->delete(route('teacher.lectures.destroy', $lecture))->assertForbidden();
    }

    expect(Lecture::count())->toBe(1);
});
