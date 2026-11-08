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
use function Pest\Laravel\post;

/*
| تقدّم الطالب + نبضة المشاهدة — م-5.
|
| 🔴 «اكتمل» تحقّق خادمي: مجموع الثواني الفريدة ≥ 90% من المدّة. لا حقل
| «اكتمل» يرسله العميل.
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

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->sci->id])
        ->user()->create(['email' => 'student@test.local', 'password' => bcrypt('x'), 'email_verified_at' => now()]))
        ->syncRoleToShape();

    $this->math = Subject::create([
        'branch_id' => $this->sci->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->math->teachers()->attach($this->teacher);

    $this->lecture = Lecture::create([
        'subject_id' => $this->math->id, 'academic_term_id' => $this->term->id, 'teacher_id' => $this->teacher->id,
        'title' => 'مقدّمة', 'slug' => 'intro', 'display_order' => 1,
        'status' => LectureStatus::Published,
        'video_source' => VideoSource::YouTube, 'video_reference' => 'dQw4w9WgXcQ',
        'video_duration_seconds' => 600, 'video_status' => VideoStatus::Ready,
    ]);
});

// ── النبضة ────────────────────────────────────────────────────

it('تدمج المقاطع وتعيد النسبة', function () {
    actingAs($this->student)
        ->postJson(route('student.lectures.progress', $this->lecture), ['from' => 0, 'to' => 120])
        ->assertOk()
        ->assertJson(['watched_seconds' => 120, 'percent' => 20, 'completed' => false]);

    // مقطع متداخل — لا يزيد الثواني كثيراً.
    actingAs($this->student)
        ->postJson(route('student.lectures.progress', $this->lecture), ['from' => 60, 'to' => 180])
        ->assertOk()
        ->assertJson(['watched_seconds' => 180]);

    $progress = LectureProgress::sole();
    expect($progress->covered_intervals)->toBe([[0, 180]])
        ->and($progress->student_id)->toBe($this->student->userable->id);
});

it('🔴 القفز للنهاية لا يُكمل المحاضرة', function () {
    // شاهد آخر 30 ثانية فقط (570→600) — 5% من الفيديو.
    actingAs($this->student)
        ->postJson(route('student.lectures.progress', $this->lecture), ['from' => 570, 'to' => 600])
        ->assertOk()
        ->assertJson(['completed' => false]);

    expect(LectureProgress::sole()->completed_at)->toBeNull();
});

it('تكتمل المحاضرة عند بلوغ 90% من المدّة', function () {
    // 90% من 600 = 540 ثانية.
    actingAs($this->student)
        ->postJson(route('student.lectures.progress', $this->lecture), ['from' => 0, 'to' => 540])
        ->assertOk()
        ->assertJson(['completed' => true]);

    $progress = LectureProgress::sole();
    expect($progress->completed_at)->not->toBeNull();

    // إعادة مشاهدة جزء لا تُلغي الاكتمال.
    $completedAt = $progress->completed_at;
    actingAs($this->student)
        ->postJson(route('student.lectures.progress', $this->lecture), ['from' => 0, 'to' => 10]);

    expect(LectureProgress::sole()->completed_at->timestamp)->toBe($completedAt->timestamp);
});

it('ترفض النبضة لمقطع مقلوب', function () {
    actingAs($this->student)
        ->postJson(route('student.lectures.progress', $this->lecture), ['from' => 100, 'to' => 50])
        ->assertStatus(422);
});

it('🔴 ترفض النبضة على محاضرة خارج فرع الطالب بـ404', function () {
    $arabic = Subject::create([
        'branch_id' => $this->lit->id, 'name' => 'عربية', 'slug' => 'arb', 'is_published' => true,
    ]);
    $foreign = Lecture::create([
        'subject_id' => $arabic->id, 'academic_term_id' => $this->term->id, 'teacher_id' => $this->teacher->id,
        'title' => 'ليست له', 'slug' => 'foreign', 'display_order' => 1,
        'status' => LectureStatus::Published,
        'video_source' => VideoSource::YouTube, 'video_reference' => 'dQw4w9WgXcQ',
        'video_duration_seconds' => 300, 'video_status' => VideoStatus::Ready,
    ]);

    actingAs($this->student)
        ->postJson(route('student.lectures.progress', $foreign), ['from' => 0, 'to' => 300])
        ->assertNotFound();

    expect(LectureProgress::count())->toBe(0);
});

it('ترفض النبضة على محاضرة مسودّة', function () {
    $this->lecture->update(['status' => LectureStatus::Draft]);

    actingAs($this->student)
        ->postJson(route('student.lectures.progress', $this->lecture), ['from' => 0, 'to' => 100])
        ->assertNotFound();
});

// ── الشاشة ────────────────────────────────────────────────────

it('صفحة التقدّم تعرض حالة فارغة بلا نشاط', function () {
    $response = actingAs($this->student)->get(route('student.progress'))->assertOk();

    expect($response->viewData('hasActivity'))->toBeFalse()
        ->and($response->viewData('completedLectures'))->toBe(0);
    $response->assertSee('لم يبدأ نشاطك بعد');
});

it('صفحة التقدّم تجمع الأرقام الحقيقية', function () {
    $b = Lecture::create([
        'subject_id' => $this->math->id, 'academic_term_id' => $this->term->id, 'teacher_id' => $this->teacher->id,
        'title' => 'الثانية', 'slug' => 'two', 'display_order' => 2,
        'status' => LectureStatus::Published,
        'video_source' => VideoSource::YouTube, 'video_reference' => 'x', 'video_duration_seconds' => 600,
        'video_status' => VideoStatus::Ready,
    ]);

    LectureProgress::create([
        'student_id' => $this->student->userable->id, 'lecture_id' => $this->lecture->id,
        'watched_seconds' => 600, 'duration_seconds' => 600, 'completed_at' => now(),
    ]);
    LectureProgress::create([
        'student_id' => $this->student->userable->id, 'lecture_id' => $b->id,
        'watched_seconds' => 120, 'duration_seconds' => 600,
    ]);

    $response = actingAs($this->student)->get(route('student.progress'))->assertOk();

    expect($response->viewData('hasActivity'))->toBeTrue()
        ->and($response->viewData('completedLectures'))->toBe(1)
        ->and($response->viewData('inProgressLectures'))->toBe(1)
        ->and($response->viewData('totalLectures'))->toBe(2)
        ->and($response->viewData('completionRate'))->toBe(50);
});

// ── الحراسة ───────────────────────────────────────────────────

it('يمنع الضيوف من نبضة التقدّم', function () {
    post(route('student.lectures.progress', $this->lecture), ['from' => 0, 'to' => 10])
        ->assertRedirect(route('auth.login'));
});
