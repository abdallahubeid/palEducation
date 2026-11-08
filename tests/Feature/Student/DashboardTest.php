<?php

use App\Enums\LectureStatus;
use App\Enums\LibraryCategory;
use App\Enums\VideoSource;
use App\Enums\VideoStatus;
use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\LectureProgress;
use App\Models\LibraryFile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Notifications\StudentAnnouncementNotification;
use App\Support\StudentNotifier;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| لوحة الطالب + أمر students:announce.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->sci = Branch::create(['name' => 'العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->term = AcademicTerm::create([
        'name' => 'الفصل الأول', 'academic_year' => '2026/2027',
        'starts_on' => now()->subMonth(), 'ends_on' => now()->addMonths(4),
        'status' => 'active', 'is_current' => true,
    ]);

    $this->teacher = Teacher::create(['full_name' => 'أ. سامر', 'is_publicly_listed' => false]);

    $this->student = tap(Student::create(['full_name' => 'محمد', 'branch_id' => $this->sci->id])
        ->user()->create(['email' => 'student@test.local', 'password' => bcrypt('x'), 'email_verified_at' => now()]))
        ->syncRoleToShape();

    $this->math = Subject::create([
        'branch_id' => $this->sci->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
});

it('تعرض اللوحة اسم الطالب وفرعه وأرقامه', function () {
    $lecture = Lecture::create([
        'subject_id' => $this->math->id, 'academic_term_id' => $this->term->id, 'teacher_id' => $this->teacher->id,
        'title' => 'مقدّمة', 'slug' => 'intro', 'display_order' => 1,
        'status' => LectureStatus::Published,
        'video_source' => VideoSource::YouTube, 'video_reference' => 'x', 'video_duration_seconds' => 600,
        'video_status' => VideoStatus::Ready,
    ]);
    LectureProgress::create([
        'student_id' => $this->student->userable->id, 'lecture_id' => $lecture->id,
        'watched_seconds' => 600, 'duration_seconds' => 600, 'completed_at' => now(),
    ]);

    $response = actingAs($this->student)->get(route('student.dashboard'))->assertOk();

    expect($response->viewData('subjectsCount'))->toBe(1)
        ->and($response->viewData('lecturesCompleted'))->toBe(1)
        ->and($response->viewData('lecturesTotal'))->toBe(1)
        ->and($response->viewData('completionRate'))->toBe(100);
    $response->assertSee('محمد')->assertSee('العلمي');
});

it('تعرض آخر ملفات مكتبة الفرع', function () {
    LibraryFile::create([
        'subject_id' => $this->math->id, 'teacher_id' => $this->teacher->id,
        'title' => 'ورقة عمل حديثة', 'category' => LibraryCategory::Worksheet->value,
        'is_published' => true, 'published_at' => now()->subDay(),
    ]);

    actingAs($this->student)->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('ورقة عمل حديثة');
});

it('تعرض إعلانات الطالب على اللوحة', function () {
    StudentNotifier::notify(
        $this->student->userable,
        new StudentAnnouncementNotification('صيانة الليلة', 'المنصّة غير متاحة 2–3 صباحاً.'),
    );

    actingAs($this->student)->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('صيانة الليلة')
        ->assertSee('المنصّة غير متاحة 2–3 صباحاً.');
});

it('اللوحة بلا إعلانات تعرض حالة فارغة', function () {
    actingAs($this->student)->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('لا إعلانات حالياً');
});

// ── أمر students:announce ────────────────────────────────────

it('أمر students:announce يبثّ لكل الطلبة الفاعلين', function () {
    $other = tap(Student::create(['full_name' => 'طالب ثانٍ', 'branch_id' => $this->sci->id])
        ->user()->create(['email' => 's2@test.local', 'password' => bcrypt('x'), 'email_verified_at' => now()]))
        ->syncRoleToShape();

    $this->artisan('students:announce', ['heading' => 'خبر', 'body' => 'نصّ الخبر'])
        ->assertSuccessful();

    foreach ([$this->student, $other] as $s) {
        expect($s->fresh()->notifications()->first()->data['title'])->toBe('خبر');
    }
});

it('students:announce يتخطّى الطالب المعطَّل', function () {
    $disabled = tap(Student::create(['full_name' => 'معطَّل', 'branch_id' => $this->sci->id])
        ->user()->create(['email' => 's3@test.local', 'password' => bcrypt('x'), 'is_active' => false]))
        ->syncRoleToShape();

    $this->artisan('students:announce', ['heading' => 'خبر', 'body' => 'نصّ'])->assertSuccessful();

    expect($this->student->fresh()->notifications()->count())->toBe(1)
        ->and($disabled->fresh()->notifications()->count())->toBe(0);
});

// ── الحراسة ───────────────────────────────────────────────────

it('يمنع الضيوف والمعلّمين من لوحة الطالب', function () {
    get(route('student.dashboard'))->assertRedirect(route('auth.login'));

    $teacherUser = tap($this->teacher->user()->create([
        'email' => 't@test.local', 'password' => bcrypt('x'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    actingAs($teacherUser)->get(route('student.dashboard'))->assertForbidden();
});
