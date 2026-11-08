<?php

use App\Enums\LectureStatus;
use App\Enums\VideoSource;
use App\Enums\VideoStatus;
use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;

/*
| Unit grouping on the watch page, and the teacher layout's structure.
|
| 🔴 `unit_name` is free text, nullable, and every lecture created before it
| existed has none. So the grouping's first duty is that an ungrouped lecture
| still appears in its own subject's navigation rather than vanishing.
*/

const UNIT_TEACHER = 'معلّم تجريبي';
const UNIT_VIDEO = 'dQw4w9WgXcQ';

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);

    $this->term = AcademicTerm::create([
        'name' => 'الفصل الأول', 'academic_year' => '2026/2027',
        'starts_on' => now()->subMonth(), 'ends_on' => now()->addMonths(4),
        'status' => 'active', 'is_current' => true,
    ]);

    $this->teacherRecord = Teacher::create([
        'full_name' => UNIT_TEACHER, 'is_publicly_listed' => false,
    ]);

    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'teacher@paleducation.test', 'password' => bcrypt('password'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->math = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($this->math);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

function makeUnitLecture(Subject $subject, Teacher $author, array $overrides = []): Lecture
{
    static $order = 0;

    return Lecture::create(array_merge([
        'subject_id' => $subject->id,
        'academic_term_id' => test()->term->id,
        'teacher_id' => $author->id,
        'title' => 'محاضرة',
        'slug' => 'lecture-'.uniqid(),
        'display_order' => ++$order,
        'status' => LectureStatus::Published,
        'published_at' => now(),
        'video_source' => VideoSource::YouTube,
        'video_reference' => UNIT_VIDEO,
        'video_status' => VideoStatus::Ready,
    ], $overrides));
}

// ── Grouping ────────────────────────────────────────────────────

it('passes the outline grouped by unit', function () {
    $first = makeUnitLecture($this->math, $this->teacherRecord, [
        'title' => 'مقدمة', 'unit_name' => 'الوحدة الأولى: أساسيات التفاضل',
    ]);
    makeUnitLecture($this->math, $this->teacherRecord, [
        'title' => 'النهايات', 'unit_name' => 'الوحدة الأولى: أساسيات التفاضل',
    ]);
    makeUnitLecture($this->math, $this->teacherRecord, [
        'title' => 'التكامل', 'unit_name' => 'الوحدة الثانية: التكامل',
    ]);

    $grouped = actingAs($this->student)
        ->get(route('student.lectures.show', $first))
        ->assertOk()
        ->viewData('lecturesByUnit');

    expect($grouped->keys()->all())
        ->toBe(['الوحدة الأولى: أساسيات التفاضل', 'الوحدة الثانية: التكامل'])
        ->and($grouped['الوحدة الأولى: أساسيات التفاضل'])->toHaveCount(2)
        ->and($grouped['الوحدة الثانية: التكامل'])->toHaveCount(1);
});

it('keeps an ungrouped lecture visible under a default unit', function () {
    /*
     * 🔴 `unit_name` is nullable and every lecture that predates it has none.
     * Grouping on a raw null key would drop those lectures out of their own
     * subject's navigation — the failure would look like missing content, not
     * like a missing field.
     */
    $lecture = makeUnitLecture($this->math, $this->teacherRecord, ['title' => 'بلا وحدة']);

    $grouped = actingAs($this->student)
        ->get(route('student.lectures.show', $lecture))
        ->assertOk()
        ->viewData('lecturesByUnit');

    expect($grouped->keys()->all())->toBe([__('student.lecture_default_unit')])
        ->and($grouped[__('student.lecture_default_unit')])->toHaveCount(1);
});

it('mixes grouped and ungrouped lectures without losing either', function () {
    $lecture = makeUnitLecture($this->math, $this->teacherRecord, [
        'title' => 'داخل وحدة', 'unit_name' => 'الوحدة الأولى',
    ]);
    makeUnitLecture($this->math, $this->teacherRecord, ['title' => 'خارج وحدة']);

    $grouped = actingAs($this->student)
        ->get(route('student.lectures.show', $lecture))
        ->viewData('lecturesByUnit');

    expect($grouped->flatten()->pluck('title')->all())
        ->toContain('داخل وحدة', 'خارج وحدة');
});

// ── Auto-expand ─────────────────────────────────────────────────

it('opens the unit holding the lecture being watched', function () {
    makeUnitLecture($this->math, $this->teacherRecord, [
        'title' => 'أولى', 'unit_name' => 'الوحدة الأولى',
    ]);

    $watching = makeUnitLecture($this->math, $this->teacherRecord, [
        'title' => 'ثانية', 'unit_name' => 'الوحدة الثانية',
    ]);

    $response = actingAs($this->student)
        ->get(route('student.lectures.show', $watching))
        ->assertOk();

    /*
     * 🔴 Comes from the SERVER, not from a fixed initial value. A student
     * landing on a wall of collapsed panels has no idea which one they are
     * inside — the orientation this accordion exists to give.
     */
    expect($response->viewData('activeUnit'))->toBe('الوحدة الثانية');

    // And it reaches Alpine as the initial state.
    expect($response->getContent())->toContain('activeUnit:');
});

it('opens the default unit when the watched lecture has none', function () {
    $lecture = makeUnitLecture($this->math, $this->teacherRecord, ['title' => 'بلا وحدة']);

    // ⚠️ The fallback name must match the grouping key exactly, or the
    // accordion opens on a unit that does not exist and everything looks shut.
    $response = actingAs($this->student)->get(route('student.lectures.show', $lecture))->assertOk();

    expect($response->viewData('activeUnit'))->toBe(__('student.lecture_default_unit'))
        ->and($response->viewData('lecturesByUnit')->keys())
        ->toContain($response->viewData('activeUnit'));
});

it('marks the playing lecture with more than colour', function () {
    $lecture = makeUnitLecture($this->math, $this->teacherRecord, [
        'title' => 'الجارية', 'unit_name' => 'الوحدة الأولى',
    ]);

    $html = actingAs($this->student)->get(route('student.lectures.show', $lecture))->assertOk()->getContent();

    /*
     * ⚠️ Three signals together — a border, a background and explicit text.
     * Colour alone does not reach a reader who cannot distinguish it.
     */
    expect($html)->toContain(__('student.lecture_now_playing'))
        ->toContain('aria-current="page"')
        ->toContain('border-accent');
});

it('keeps drafts out of a student outline but shows them to the teacher', function () {
    $published = makeUnitLecture($this->math, $this->teacherRecord, [
        'title' => 'منشورة', 'unit_name' => 'الوحدة الأولى',
    ]);

    makeUnitLecture($this->math, $this->teacherRecord, [
        'title' => 'مسودّة', 'unit_name' => 'الوحدة الأولى',
        'status' => LectureStatus::Draft, 'published_at' => null,
    ]);

    expect(actingAs($this->student)->get(route('student.lectures.show', $published))
        ->viewData('lecturesByUnit')->flatten())->toHaveCount(1)
        ->and(actingAs($this->teacher)->get(route('student.lectures.show', $published))
            ->viewData('lecturesByUnit')->flatten())->toHaveCount(2);
});

// ── The teacher layout ──────────────────────────────────────────

it('renders the teacher lectures page without the stray dark block', function () {
    makeUnitLecture($this->math, $this->teacherRecord);

    $html = actingAs($this->teacher)->get(route('teacher.lectures'))->assertOk()->getContent();

    /*
     * 🔴 The artifact this guards: a deleted preview modal left an orphaned
     * `aspect-video w-full bg-ink` container OUTSIDE the Alpine root, so it
     * rendered unconditionally — a full-width dark band under the table — and
     * took three unbalanced </div> with it.
     *
     * ⚠️ `view:cache` did NOT catch it: Blade compiled fine. Unbalanced HTML
     * is not a compile error, so "the page compiles" is not "the page is
     * sound".
     */
    expect($html)->not->toContain('aspect-video w-full bg-ink')
        ->not->toContain('preview?.embed');
});

it('closes every div it opens on the teacher lectures page', function () {
    makeUnitLecture($this->math, $this->teacherRecord);

    $html = actingAs($this->teacher)->get(route('teacher.lectures'))->assertOk()->getContent();

    // A crude balance check, but it is exactly what failed before: three
    // extra closers left the shell open and the background spilled below it.
    expect(preg_match_all('/<div\b/', $html))
        ->toBe(preg_match_all('/<\/div>/', $html));
});

it('bounds the teacher shell to the viewport height', function () {
    $html = actingAs($this->teacher)->get(route('teacher.lectures'))->assertOk()->getContent();

    /*
     * ⚠️ Without min-h-screen the shell ends at its last element and the rest
     * of the viewport falls through to the <body> background — any difference
     * between the two reads as a stray panel.
     */
    expect($html)->toContain('data-shell class="flex min-h-screen flex-col lg:flex-row"');
});

// ── The teacher can set a unit ──────────────────────────────────

it('saves the unit a teacher types, trimmed', function () {
    actingAs($this->teacher)
        ->post(route('teacher.lectures.store'), [
            'title' => 'محاضرة بوحدة',
            'subject_id' => $this->math->id,
            'video_url' => 'https://www.youtube.com/watch?v='.UNIT_VIDEO,
            // ⚠️ Stray whitespace would otherwise produce a second unit that
            // looks identical to the first.
            'unit_name' => '  الوحدة الأولى  ',
            'status' => 'published',
        ])
        ->assertRedirect();

    expect(Lecture::sole()->unit_name)->toBe('الوحدة الأولى');
});

it('accepts a lecture with no unit at all', function () {
    actingAs($this->teacher)
        ->post(route('teacher.lectures.store'), [
            'title' => 'بلا وحدة', 'subject_id' => $this->math->id,
            'video_url' => 'https://www.youtube.com/watch?v='.UNIT_VIDEO, 'status' => 'draft',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // Not every subject is divided into units, and the field must not force it.
    expect(Lecture::sole()->unit_name)->toBeNull();
});

it('suggests the units already in use', function () {
    makeUnitLecture($this->math, $this->teacherRecord, ['unit_name' => 'الوحدة الأولى']);
    makeUnitLecture($this->math, $this->teacherRecord, ['unit_name' => 'الوحدة الأولى']);
    makeUnitLecture($this->math, $this->teacherRecord, ['unit_name' => 'الوحدة الثانية']);

    $response = actingAs($this->teacher)->get(route('teacher.lectures'))->assertOk();

    // Distinct: a datalist repeating the same suggestion is noise.
    expect($response->viewData('knownUnits')->all())
        ->toBe(['الوحدة الأولى', 'الوحدة الثانية']);
});
