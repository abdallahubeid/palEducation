<?php

use App\Enums\LectureStatus;
use App\Enums\LibraryCategory;
use App\Enums\VideoSource;
use App\Enums\VideoStatus;
use App\Models\AcademicTerm;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\LibraryFile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| سلّة محذوفات المعلّم — استرجاع فقط.
|
| 🔴 لا مسار حذف نهائي إطلاقاً (م-9). النطاق على المادة: المعلّم يرى ويسترجع
| سلّة مواده المسندة كلها، بما حذفه زميل سبقه — ولا يلمس محتوى مادة لا يملكها.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->branch = Branch::create(['name' => 'العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->term = AcademicTerm::create([
        'name' => 'الفصل الأول', 'academic_year' => '2026/2027',
        'starts_on' => now()->subMonth(), 'ends_on' => now()->addMonths(4),
        'status' => 'active', 'is_current' => true,
    ]);

    $this->teacherRecord = Teacher::create(['full_name' => 'معلّم تجريبي', 'is_publicly_listed' => false]);
    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'teacher@paleducation.test', 'password' => bcrypt('password'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->math = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($this->math);

    // مادة لا يملكها هذا المعلّم.
    $this->physics = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الفيزياء', 'slug' => 'physics',
        'display_order' => 2, 'is_published' => true,
    ]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create(['email' => 's@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now()]))
        ->syncRoleToShape();

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'a@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

function makeTrashedLecture(Subject $subject, array $overrides = []): Lecture
{
    return Lecture::create(array_merge([
        'subject_id' => $subject->id,
        'academic_term_id' => test()->term->id,
        'teacher_id' => test()->teacherRecord->id,
        'title' => 'محاضرة محذوفة',
        'slug' => 'lec-'.uniqid(),
        'display_order' => 1,
        'status' => LectureStatus::Published,
        'video_source' => VideoSource::YouTube,
        'video_reference' => 'dQw4w9WgXcQ',
        'video_status' => VideoStatus::Ready,
    ], $overrides));
}

function makeTrashedFile(Subject $subject, array $overrides = []): LibraryFile
{
    return LibraryFile::create(array_merge([
        'subject_id' => $subject->id,
        'teacher_id' => test()->teacherRecord->id,
        'title' => 'ملف محذوف',
        'category' => LibraryCategory::Summary->value,
    ], $overrides));
}

// ── العرض والنطاق ──────────────────────────────────────────────

it('يعرض المحذوف من مواد المعلّم فقط ولا يعرض محتوى مادة لا يملكها', function () {
    tap(makeTrashedLecture($this->math, ['title' => 'محاضرتي المحذوفة']))->delete();
    tap(makeTrashedFile($this->math, ['title' => 'ملفّي المحذوف']))->delete();

    // محذوف على مادة لا يملكها المعلّم — نفس الفرع.
    tap(makeTrashedLecture($this->physics, ['title' => 'محاضرة زميلة محذوفة']))->delete();

    $items = actingAs($this->teacher)->get(route('teacher.trash'))
        ->assertOk()
        ->viewData('items')
        ->pluck('title')
        ->all();

    expect($items)->toHaveCount(2)
        ->toContain('محاضرتي المحذوفة')
        ->toContain('ملفّي المحذوف')
        ->not->toContain('محاضرة زميلة محذوفة');
});

it('لا يعرض العناصر النشطة — المحذوف فقط', function () {
    makeTrashedLecture($this->math, ['title' => 'محاضرة نشطة']);      // لم تُحذف
    tap(makeTrashedLecture($this->math, ['title' => 'محاضرة محذوفة']))->delete();

    $items = actingAs($this->teacher)->get(route('teacher.trash'))
        ->viewData('items')->pluck('title')->all();

    expect($items)->toBe(['محاضرة محذوفة']);
});

it('يصفّي بالنوع مع عدّادات صحيحة', function () {
    tap(makeTrashedLecture($this->math))->delete();
    tap(makeTrashedFile($this->math))->delete();
    tap(makeTrashedFile($this->math))->delete();

    $all = actingAs($this->teacher)->get(route('teacher.trash'))->assertOk();
    expect($all->viewData('counts'))->toMatchArray(['all' => 3, 'lectures' => 1, 'files' => 2]);

    $lectures = actingAs($this->teacher)->get(route('teacher.trash', ['type' => 'lectures']))->assertOk();
    expect($lectures->viewData('items'))->toHaveCount(1)
        ->and($lectures->viewData('filter'))->toBe('lectures');

    // نوع مجهول يسقط إلى «الكل».
    expect(actingAs($this->teacher)->get(route('teacher.trash', ['type' => 'nope']))->viewData('filter'))->toBe('all');
});

// ── الاسترجاع ──────────────────────────────────────────────────

it('يسترجع محاضرة محذوفة فتعود إلى القائمة النشطة', function () {
    $lecture = tap(makeTrashedLecture($this->math))->delete();

    expect(Lecture::count())->toBe(0)
        ->and(Lecture::withTrashed()->count())->toBe(1);

    actingAs($this->teacher)
        ->patch(route('teacher.trash.restore', ['type' => 'lectures', 'id' => $lecture->id]))
        ->assertRedirect(route('teacher.trash'));

    expect(Lecture::count())->toBe(1)
        ->and($lecture->fresh()->trashed())->toBeFalse();
});

it('يسترجع ملف مكتبة محذوفاً', function () {
    $file = tap(makeTrashedFile($this->math))->delete();

    actingAs($this->teacher)
        ->patch(route('teacher.trash.restore', ['type' => 'files', 'id' => $file->id]))
        ->assertRedirect();

    expect(LibraryFile::whereKey($file->id)->exists())->toBeTrue();
});

it('يعيد 404 لاسترجاع عنصر خارج مواد المعلّم', function () {
    $theirs = tap(makeTrashedLecture($this->physics))->delete();

    // 🔴 404 لا 403: «ممنوع» تؤكّد أن خلف المُعرِّف محتوى زميل.
    actingAs($this->teacher)
        ->patch(route('teacher.trash.restore', ['type' => 'lectures', 'id' => $theirs->id]))
        ->assertNotFound();

    expect($theirs->fresh()->trashed())->toBeTrue();
});

it('يعيد 404 لنوع مجهول', function () {
    actingAs($this->teacher)
        ->patch(route('teacher.trash.restore', ['type' => 'homework', 'id' => 1]))
        ->assertNotFound();
});

it('يعيد 404 لعنصر غير محذوف أصلاً', function () {
    $active = makeTrashedLecture($this->math);

    actingAs($this->teacher)
        ->patch(route('teacher.trash.restore', ['type' => 'lectures', 'id' => $active->id]))
        ->assertNotFound();
});

// ── لا حذف نهائي (م-9) ─────────────────────────────────────────

it('لا يسجّل أي مسار حذف نهائي في سلّة المعلّم', function () {
    // 🔴 حارس بنيوي: لو أُضيف forceDestroy لاحقاً يجب أن يُسأل عنه صراحةً.
    $names = collect(app('router')->getRoutes()->getRoutesByName())->keys()
        ->filter(fn (string $name) => str_starts_with($name, 'teacher.trash'))
        ->values()
        ->all();

    expect($names)->toEqualCanonicalizing(['teacher.trash', 'teacher.trash.restore']);
});

// ── الحراسة ────────────────────────────────────────────────────

it('يمنع الضيوف والطلبة والأدمن من سلّة المعلّم', function () {
    get(route('teacher.trash'))->assertRedirect(route('auth.login'));

    foreach ([$this->student, $this->admin] as $outsider) {
        actingAs($outsider)->get(route('teacher.trash'))->assertForbidden();
    }
});
