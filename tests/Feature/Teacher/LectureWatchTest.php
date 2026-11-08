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
| The in-platform watch page, and the teacher's edit flow.
|
| 🔴 The point of this screen is that the lesson stays on OUR page. A link
| out to youtube.com would end the session here and put the lecture beside
| suggested videos, ads and comments we do not control.
|
| ⚠️ Access is checked in the controller because this project still has no
| Policies and no global branch scope — the documented gap in CLAUDE.md.
*/

const WATCH_TEACHER = 'معلّم تجريبي';
const WATCH_VIDEO = 'dQw4w9WgXcQ';

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);

    $this->otherBranch = Branch::create([
        'name' => 'الفرع الأدبي', 'slug' => 'literary', 'display_order' => 2,
    ]);

    $this->term = AcademicTerm::create([
        'name' => 'الفصل الأول', 'academic_year' => '2026/2027',
        'starts_on' => now()->subMonth(), 'ends_on' => now()->addMonths(4),
        'status' => 'active', 'is_current' => true,
    ]);

    $this->teacherRecord = Teacher::create([
        'full_name' => WATCH_TEACHER, 'title' => 'معلّم الرياضيات', 'is_publicly_listed' => false,
    ]);

    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'teacher@paleducation.test', 'password' => bcrypt('password'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->math = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($this->math);

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

function makeWatchLecture(Subject $subject, Teacher $author, array $overrides = []): Lecture
{
    return Lecture::create(array_merge([
        'subject_id' => $subject->id,
        'academic_term_id' => test()->term->id,
        'teacher_id' => $author->id,
        'title' => 'قواعد الاشتقاق',
        'slug' => 'lecture-'.uniqid(),
        'display_order' => 1,
        'status' => LectureStatus::Published,
        'published_at' => now(),
        'video_source' => VideoSource::YouTube,
        'video_reference' => WATCH_VIDEO,
        'video_status' => VideoStatus::Ready,
    ], $overrides));
}

// ── The embedded player ─────────────────────────────────────────

it('formats a clean embed url for in-platform playback', function () {
    $lecture = makeWatchLecture($this->math, $this->teacherRecord);

    /*
     * 🔴 Built from the stored ID, never from a pasted URL — so playlists,
     * timestamps and tracking parameters cannot ride into the iframe.
     */
    expect($lecture->embed_url)->toBe('https://www.youtube.com/embed/'.WATCH_VIDEO)
        ->and($lecture->watch_url)->toBe('https://www.youtube.com/watch?v='.WATCH_VIDEO);
});

it('plays the video inside our own layout', function () {
    $lecture = makeWatchLecture($this->math, $this->teacherRecord);

    $html = actingAs($this->student)
        ->get(route('student.lectures.show', $lecture))
        ->assertOk()
        ->assertSee('قواعد الاشتقاق')
        ->getContent();

    /*
     * 🔴 enablejsapi=1 for a student: playback drives lecture_progress via the
     * YouTube IFrame API (م-5). The base URL is still built from the stored id
     * only — no playlist or timestamp rides in.
     */
    expect($html)->toContain('<iframe src="https://www.youtube.com/embed/'.WATCH_VIDEO.'?enablejsapi=1"')
        ->toContain('data-lecture-progress-url="'.route('student.lectures.progress', $lecture).'"')
        /*
         * ⚠️ The stage is forced LTR: a video timeline runs left-to-right in
         * every locale, and mirroring it confuses every user.
         */
        ->toContain('dir="ltr"');
});

it('does not track progress on a teacher preview', function () {
    $lecture = makeWatchLecture($this->math, $this->teacherRecord);

    $html = actingAs($this->teacher)
        ->get(route('student.lectures.show', $lecture))
        ->assertOk()
        ->getContent();

    // المعاينة: رابط نظيف بلا enablejsapi، ولا عقدة تتبّع.
    expect($html)->toContain('<iframe src="https://www.youtube.com/embed/'.WATCH_VIDEO.'"')
        ->not->toContain('data-lecture-progress');
});

it('keeps the quiz section as a placeholder without breaking it', function () {
    $lecture = makeWatchLecture($this->math, $this->teacherRecord);

    $html = actingAs($this->student)
        ->get(route('student.lectures.show', $lecture))
        ->assertOk()
        ->assertSee('كويز المحاضرة — قريباً')
        ->getContent();

    /*
     * 🔴 The toggle structure app.js drives must survive: this is the
     * platform's core promise (a quiz after every lecture) holding its place.
     */
    expect($html)->toContain('data-lecture-view="quiz"')
        ->toContain('data-lecture-view="video"')
        // ⚠️ And no invented questions: there is no quizzes table, and five
        // hard-coded questions read as a real quiz that scores nothing.
        ->not->toContain('ما ناتج اشتقاق الدالة');
});

it('lists the real sibling lectures, not an invented outline', function () {
    $first = makeWatchLecture($this->math, $this->teacherRecord, ['title' => 'المحاضرة الأولى', 'display_order' => 1]);
    makeWatchLecture($this->math, $this->teacherRecord, ['title' => 'المحاضرة الثانية', 'display_order' => 2]);

    // A lecture in another subject must not appear in this outline.
    makeWatchLecture($this->physics, $this->teacherRecord, ['title' => 'محاضرة الفيزياء']);

    $response = actingAs($this->student)->get(route('student.lectures.show', $first))->assertOk();

    expect($response->viewData('siblings')->pluck('title')->all())
        ->toBe(['المحاضرة الأولى', 'المحاضرة الثانية']);
});

// ── Who may watch ───────────────────────────────────────────────

it('lets the assigned teacher preview an unpublished lecture', function () {
    $draft = makeWatchLecture($this->math, $this->teacherRecord, [
        'status' => LectureStatus::Draft, 'published_at' => null,
    ]);

    actingAs($this->teacher)
        ->get(route('student.lectures.show', $draft))
        ->assertOk()
        // The preview says plainly that this is the student's own screen.
        ->assertSee('معاينة');
});

it('hides an unpublished lecture from everyone else', function () {
    $draft = makeWatchLecture($this->math, $this->teacherRecord, [
        'status' => LectureStatus::Draft, 'published_at' => null,
    ]);

    /*
     * 🔴 404, not 403. A lecture the viewer may not see is indistinguishable
     * from one that does not exist — "forbidden" confirms content sits there.
     */
    actingAs($this->student)->get(route('student.lectures.show', $draft))->assertNotFound();
    get(route('student.lectures.show', $draft))->assertNotFound();
});

it('keeps a student out of another branch lecture', function () {
    $otherSubject = Subject::create([
        'branch_id' => $this->otherBranch->id, 'name' => 'التاريخ', 'slug' => 'history', 'is_published' => true,
    ]);

    $lecture = makeWatchLecture($otherSubject, $this->teacherRecord);

    // 🔴 The platform's first rule, enforced by hand here for want of a
    // global scope: a student sees only their own branch.
    actingAs($this->student)->get(route('student.lectures.show', $lecture))->assertNotFound();
});

it('lets an admin open any lecture', function () {
    $draft = makeWatchLecture($this->math, $this->teacherRecord, [
        'status' => LectureStatus::Draft, 'published_at' => null,
    ]);

    actingAs($this->admin)->get(route('student.lectures.show', $draft))->assertOk();
});

it('shows a teacher drafts in the outline but hides them from a student', function () {
    $published = makeWatchLecture($this->math, $this->teacherRecord, ['title' => 'منشورة', 'display_order' => 1]);
    makeWatchLecture($this->math, $this->teacherRecord, [
        'title' => 'مسودّة', 'display_order' => 2,
        'status' => LectureStatus::Draft, 'published_at' => null,
    ]);

    // ⚠️ Whoever manages the subject sees drafts — that is what a preview is
    // for. A student must not.
    expect(actingAs($this->teacher)->get(route('student.lectures.show', $published))->viewData('siblings'))
        ->toHaveCount(2)
        ->and(actingAs($this->student)->get(route('student.lectures.show', $published))->viewData('siblings'))
        ->toHaveCount(1);
});

// ── The teacher list links here ─────────────────────────────────

it('points the preview and the title at our watch page, not at YouTube', function () {
    $lecture = makeWatchLecture($this->math, $this->teacherRecord);

    $html = actingAs($this->teacher)->get(route('teacher.lectures'))->assertOk()->getContent();

    expect($html)->toContain(route('student.lectures.show', $lecture))
        /*
         * 🔴 The regression this guards: the row used to carry an external
         * "فتح" link straight to youtube.com, which ends the session on our
         * side and drops the lecture among suggested videos and ads.
         */
        ->not->toContain('youtube.com/watch?v='.WATCH_VIDEO);
});

// ── Editing ─────────────────────────────────────────────────────

it('lets معلّم تجريبي edit a lecture and its YouTube link', function () {
    $lecture = makeWatchLecture($this->math, $this->teacherRecord, ['title' => 'عنوان قديم']);

    actingAs($this->teacher)
        ->put(route('teacher.lectures.update', $lecture), [
            'title' => 'عنوان جديد',
            'subject_id' => $this->math->id,
            'video_url' => 'https://youtu.be/abcdefghijk',
            'description' => 'وصف محدَّث.',
            'status' => 'published',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $lecture->refresh();

    expect($lecture->title)->toBe('عنوان جديد')
        ->and($lecture->description)->toBe('وصف محدَّث.')
        // The new link is parsed the same way as on create.
        ->and($lecture->video_reference)->toBe('abcdefghijk')
        ->and($lecture->embed_url)->toBe('https://www.youtube.com/embed/abcdefghijk');
});

it('rejects an invalid YouTube link on edit too', function () {
    $lecture = makeWatchLecture($this->math, $this->teacherRecord);

    actingAs($this->teacher)
        ->put(route('teacher.lectures.update', $lecture), [
            'title' => 'عنوان', 'subject_id' => $this->math->id,
            'video_url' => 'https://vimeo.com/12345', 'status' => 'draft',
        ])
        ->assertSessionHasErrors('video_url');

    // ⚠️ The whole update is refused, not partially applied.
    expect($lecture->fresh()->video_reference)->toBe(WATCH_VIDEO);
});

it('keeps the first publish date when re-saving a published lecture', function () {
    $lecture = makeWatchLecture($this->math, $this->teacherRecord, [
        'published_at' => now()->subWeek(),
    ]);

    $original = $lecture->published_at;

    $this->travel(3)->days();

    actingAs($this->teacher)->put(route('teacher.lectures.update', $lecture), [
        'title' => 'عنوان معدَّل', 'subject_id' => $this->math->id,
        'video_url' => 'https://www.youtube.com/watch?v='.WATCH_VIDEO, 'status' => 'published',
    ])->assertRedirect();

    // An edit is not a republish — the date records when it first went live.
    expect($lecture->fresh()->published_at->eq($original))->toBeTrue();
});

it('refuses to edit or delete a lecture outside the teacher subjects', function () {
    $colleague = Teacher::create(['full_name' => 'أ. رنا عوض', 'is_publicly_listed' => true]);
    $theirs = makeWatchLecture($this->physics, $colleague, ['title' => 'ليست له']);

    actingAs($this->teacher)
        ->put(route('teacher.lectures.update', $theirs), [
            'title' => 'اختطاف', 'subject_id' => $this->math->id,
            'video_url' => 'https://www.youtube.com/watch?v='.WATCH_VIDEO, 'status' => 'published',
        ])
        ->assertNotFound();

    actingAs($this->teacher)
        ->delete(route('teacher.lectures.destroy', $theirs))
        ->assertNotFound();

    expect($theirs->fresh()->title)->toBe('ليست له')
        ->and($theirs->fresh()->trashed())->toBeFalse();
});

// ── SweetAlert2 wiring ──────────────────────────────────────────

it('renders the flash message the teacher shell used to swallow', function () {
    /*
     * 🔴 The teacher layout had NO flash renderer at all, so every
     * flash()->success() from a teacher controller was stored in the session
     * and never displayed — the save looked like it did nothing. app.js
     * upgrades these server-rendered nodes into SweetAlert2 dialogs.
     */
    actingAs($this->teacher)
        ->post(route('teacher.lectures.store'), [
            'title' => 'محاضرة جديدة', 'subject_id' => $this->math->id,
            'video_url' => 'https://www.youtube.com/watch?v='.WATCH_VIDEO, 'status' => 'draft',
        ])
        ->assertRedirect();

    actingAs($this->teacher)
        ->get(route('teacher.lectures'))
        ->assertOk()
        ->assertSee('data-flash', false)
        ->assertSee('تمت إضافة المحاضرة بنجاح.');
});

it('asks before deleting, through the shared confirm dialog', function () {
    $lecture = makeWatchLecture($this->math, $this->teacherRecord);

    $html = actingAs($this->teacher)->get(route('teacher.lectures'))->assertOk()->getContent();

    /*
     * ⚠️ The dialog is driven by data-confirm on the form, which app.js
     * intercepts with the same SweetAlert2 mixin the admin panel uses — one
     * dialog shape for the whole product rather than a second system here.
     */
    expect($html)->toContain('data-confirm-title="حذف «'.$lecture->title.'»؟"')
        ->toContain('data-confirm-action="نعم، احذفها"');
});
