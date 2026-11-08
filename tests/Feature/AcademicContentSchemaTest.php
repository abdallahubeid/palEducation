<?php

use App\Enums\LectureStatus;
use App\Enums\TermStatus;
use App\Enums\VideoStatus;
use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\LibraryFile;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\QueryException;

/*
| يختبر **ضمانات القاعدة** لا سلوك الشاشات: القيود التي يجب أن تفشل، وتلك
| التي يجب أن تنجح. كتبت هنا لأن أخطر ما في هذه المرحلة صامت — مفتاح أجنبي
| ناقص أو تتالٍ في غير موضعه لا يظهر إلا يوم يمحو محتوى مدفوعاً.
*/

beforeEach(function () {
    $this->branch = Branch::create(['name' => 'علمي', 'slug' => 'scientific', 'display_order' => 1]);
    $this->term = AcademicTerm::create([
        'name' => 'الفصل الأول',
        'academic_year' => '2026/2027',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-01-15',
        'status' => TermStatus::Active,
        'is_current' => true,
    ]);
    $this->teacher = Teacher::create(['full_name' => 'معلّم']);
    $this->subject = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'رياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
});

function makeLecture(array $overrides = []): Lecture
{
    return Lecture::create(array_merge([
        'subject_id' => test()->subject->id,
        'academic_term_id' => test()->term->id,
        'teacher_id' => test()->teacher->id,
        'title' => 'محاضرة',
        'slug' => 'lecture-one',
        'video_duration_seconds' => 1200,
        'video_reference' => 'asset-1',
        'video_status' => VideoStatus::Ready,
        'status' => LectureStatus::Published,
    ], $overrides));
}

// ── المفتاح المركّب ──────────────────────────────────────────────

it('يسمح بنفس الـslug في فرعين مختلفين ويمنعه داخل الفرع الواحد', function () {
    $literary = Branch::create(['name' => 'أدبي', 'slug' => 'literary', 'display_order' => 2]);

    // نفس المادة في فرع آخر: منتج مختلف بسعر مختلف — مسموح.
    Subject::create(['branch_id' => $literary->id, 'name' => 'رياضيات', 'slug' => 'math']);
    expect(Subject::where('slug', 'math')->count())->toBe(2);

    // نفس الفرع: ممنوع.
    expect(fn () => Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'رياضيات مكرّرة', 'slug' => 'math',
    ]))->toThrow(QueryException::class);
});

// ── التبعية الحاجزة ──────────────────────────────────────────────

it('يرفض محاضرة بلا فصل دراسي', function () {
    expect(fn () => makeLecture(['academic_term_id' => null]))->toThrow(QueryException::class);
});

// ── سلوك الحذف ──────────────────────────────────────────────────

it('يمنع حذف فرع أو مادة أو فصل أو معلّم تحته محتوى', function () {
    makeLecture();

    expect(fn () => $this->branch->forceDelete())->toThrow(QueryException::class)
        ->and(fn () => $this->subject->forceDelete())->toThrow(QueryException::class)
        ->and(fn () => $this->term->delete())->toThrow(QueryException::class)
        ->and(fn () => $this->teacher->forceDelete())->toThrow(QueryException::class);
});

it('يُبقي ملف المكتبة حيّاً بعد حذف محاضرته ويعيده لمستوى المادة', function () {
    $lecture = makeLecture();
    $file = LibraryFile::create([
        'subject_id' => $this->subject->id,
        'lecture_id' => $lecture->id,
        'teacher_id' => $this->teacher->id,
        'title' => 'ورقة عمل',
        'original_filename' => 'ws.pdf',
        'storage_path' => 'private/ws.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 2048,
    ]);

    $lecture->forceDelete();

    expect($file->fresh())->not->toBeNull()
        ->lecture_id->toBeNull()          // انفصل عن المحاضرة
        ->subject_id->toBe($this->subject->id);   // وبقي له بيت
});

it('يمحو الإسناد عند حذف المعلّم — الإسناد علاقة لا محتوى', function () {
    $this->subject->teachers()->attach($this->teacher->id, ['assigned_at' => now()]);
    expect($this->subject->teachers()->count())->toBe(1);

    $this->teacher->forceDelete();

    expect($this->subject->fresh()->teachers()->count())->toBe(0);
});

// ── الفصل الجاري ────────────────────────────────────────────────

it('يُبقي فصلاً جارياً واحداً فقط ويجعله مفتوحاً', function () {
    $second = AcademicTerm::create([
        'name' => 'الفصل الثاني', 'academic_year' => '2026/2027',
        'starts_on' => '2027-01-25', 'ends_on' => '2027-06-10',
        'status' => TermStatus::Upcoming,
    ]);

    $second->makeCurrent();

    expect(AcademicTerm::where('is_current', true)->count())->toBe(1)
        ->and(AcademicTerm::current()->id)->toBe($second->id)
        ->and(AcademicTerm::current()->status)->toBe(TermStatus::Active) // لا يبقى upcoming
        ->and($this->term->fresh()->is_current)->toBeFalse();
});

// ── الإسناد الجماعي والحسابات ───────────────────────────────────

it('يحفظ كل حقول المحاضرة الممرّرة جملةً — لا إسقاط صامت', function () {
    $lecture = makeLecture(['description' => 'وصف', 'thumbnail_path' => 'private/t.jpg', 'display_order' => 7]);

    expect($lecture->fresh())
        ->description->toBe('وصف')
        ->thumbnail_path->toBe('private/t.jpg')
        ->display_order->toBe(7)
        ->video_duration_seconds->toBe(1200)
        ->status->toBe(LectureStatus::Published);
});

it('يحسب عتبة الاكتمال من المدة الخادمية ويمتنع بدونها', function () {
    expect(makeLecture()->completionThresholdSeconds())->toBe(1080)  // 90% من 1200
        ->and(makeLecture(['slug' => 'no-video', 'video_duration_seconds' => null])
            ->completionThresholdSeconds())->toBeNull();
});

it('يمنع اعتبار محاضرة بلا مدة خادمية جاهزة للنشر', function () {
    expect(makeLecture()->isReadyToPublish())->toBeTrue()
        ->and(makeLecture(['slug' => 'b', 'video_duration_seconds' => null])->isReadyToPublish())->toBeFalse()
        ->and(makeLecture(['slug' => 'c', 'video_status' => VideoStatus::Processing])->isReadyToPublish())->toBeFalse();
});

it('يحجب مسار الملف عن أي تسلسل ويقرأ حجمه من media', function () {
    /*
     * 🔁 انعكاس مسجَّل. كانت مقاييس الملف أعمدة على library_files، فصارت في
     * media بعد توحيد بيانات الملفات. الضمانة نفسها لم تتغيّر: المسار
     * الداخلي لا يظهر في أي تسلسل — تسريبه يكشف بنية القرص الخاص.
     */
    $file = LibraryFile::create([
        'subject_id' => $this->subject->id, 'teacher_id' => $this->teacher->id,
        'title' => 'ملف', 'category' => 'summary',
    ]);

    $file->media()->create([
        'path' => 'library/secret/a.pdf',
        'disk' => 'private',
        'type' => 'document',
        'original_filename' => 'a.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1048576,
    ]);

    expect($file->fresh()->toJson())->not->toContain('library/secret')
        // ⚠️ humanSize بقيت باسمها وتُفوَّض إلى media — الاسم عقد قائم.
        ->and($file->fresh()->humanSize())->toBe('1 MB');
});
