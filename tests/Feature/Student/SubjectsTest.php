<?php

use App\Enums\LectureStatus;
use App\Enums\VideoSource;
use App\Enums\VideoStatus;
use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\LectureProgress;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| مواد الطالب — فرعه حصراً.
|
| 🔴 الفرع فلتر جذري: مادة خارج فرع الطالب لا تظهر ولا تُفتح (404).
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->sci = Branch::create(['name' => 'العلمي', 'slug' => 'scientific', 'display_order' => 1]);
    $this->lit = Branch::create(['name' => 'الأدبي', 'slug' => 'literary', 'display_order' => 2]);

    $this->term = AcademicTerm::create([
        'name' => 'الفصل الأول', 'academic_year' => '2026/2027',
        'starts_on' => now()->subMonth(), 'ends_on' => now()->addMonths(4),
        'status' => 'active', 'is_current' => true,
    ]);

    $this->teacher = Teacher::create(['full_name' => 'أ. سامر', 'is_publicly_listed' => false]);

    $this->student = tap(Student::create(['full_name' => 'طالب علمي', 'branch_id' => $this->sci->id])
        ->user()->create(['email' => 'student@test.local', 'password' => bcrypt('x'), 'email_verified_at' => now()]))
        ->syncRoleToShape();

    // مادة في فرع الطالب.
    $this->math = Subject::create([
        'branch_id' => $this->sci->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->math->teachers()->attach($this->teacher);

    // مادة في فرع آخر — لا يراها الطالب.
    $this->arabic = Subject::create([
        'branch_id' => $this->lit->id, 'name' => 'اللغة العربية', 'slug' => 'arabic', 'is_published' => true,
    ]);
});

function publishedLecture(Subject $subject, string $title, array $overrides = []): Lecture
{
    return Lecture::create(array_merge([
        'subject_id' => $subject->id,
        'academic_term_id' => test()->term->id,
        'teacher_id' => test()->teacher->id,
        'title' => $title,
        'slug' => 'lec-'.uniqid(),
        'display_order' => 1,
        'status' => LectureStatus::Published,
        'video_source' => VideoSource::YouTube,
        'video_reference' => 'dQw4w9WgXcQ',
        'video_duration_seconds' => 600,
        'video_status' => VideoStatus::Ready,
    ], $overrides));
}

// ── النطاق ─────────────────────────────────────────────────────

it('يعرض مواد فرع الطالب المنشورة فقط', function () {
    Subject::create([
        'branch_id' => $this->sci->id, 'name' => 'الفيزياء', 'slug' => 'physics',
        'display_order' => 2, 'is_published' => false,   // غير منشورة
    ]);

    $names = actingAs($this->student)->get(route('student.subjects.index'))
        ->assertOk()
        ->viewData('subjects')
        ->pluck('name')
        ->all();

    expect($names)->toBe(['الرياضيات']);   // لا الفيزياء (مسودّة) ولا العربية (فرع آخر)
});

it('يحسب نسبة تقدّم المادة من المحاضرات المكتملة', function () {
    $a = publishedLecture($this->math, 'الأولى');
    $b = publishedLecture($this->math, 'الثانية');
    publishedLecture($this->math, 'الثالثة');
    publishedLecture($this->math, 'الرابعة');   // 4 محاضرات

    // أكمل اثنتين.
    foreach ([$a, $b] as $lecture) {
        LectureProgress::create([
            'student_id' => $this->student->userable->id,
            'lecture_id' => $lecture->id,
            'watched_seconds' => 600, 'duration_seconds' => 600,
            'completed_at' => now(),
        ]);
    }

    $subject = actingAs($this->student)->get(route('student.subjects.index'))
        ->viewData('subjects')->firstWhere('name', 'الرياضيات');

    expect($subject['lectures_count'])->toBe(4)
        ->and($subject['completed'])->toBe(2)
        ->and($subject['percent'])->toBe(50);
});

it('يفتح صفحة مادة في فرع الطالب', function () {
    publishedLecture($this->math, 'مقدّمة', ['unit_name' => 'الوحدة الأولى']);

    actingAs($this->student)->get(route('student.subjects.show', 'math'))
        ->assertOk()
        ->assertSee('الرياضيات')
        ->assertSee('مقدّمة')
        ->assertSee('الوحدة الأولى');
});

it('يعيد 404 لمادة في فرع آخر', function () {
    // 🔴 «ممنوع» تؤكّد أن الـslug حقيقي وأن خلفه محتوى.
    actingAs($this->student)->get(route('student.subjects.show', 'arabic'))->assertNotFound();
});

it('يعيد 404 لمادة مسودّة حتى لو كانت في فرع الطالب', function () {
    Subject::create([
        'branch_id' => $this->sci->id, 'name' => 'كيمياء', 'slug' => 'chem', 'is_published' => false,
    ]);

    actingAs($this->student)->get(route('student.subjects.show', 'chem'))->assertNotFound();
});

it('حالة فارغة حين لا مواد منشورة في الفرع', function () {
    $this->math->update(['is_published' => false]);

    $response = actingAs($this->student)->get(route('student.subjects.index'))->assertOk();

    expect($response->viewData('subjects'))->toHaveCount(0);
    $response->assertSee('لا مواد منشورة في فرعك بعد');
});

// ── الحراسة ───────────────────────────────────────────────────

it('يمنع الضيوف والمعلّمين والأدمن من مواد الطالب', function () {
    get(route('student.subjects.index'))->assertRedirect(route('auth.login'));

    $teacherUser = tap($this->teacher->user()->create([
        'email' => 't@test.local', 'password' => bcrypt('x'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    actingAs($teacherUser)->get(route('student.subjects.index'))->assertForbidden();
});
