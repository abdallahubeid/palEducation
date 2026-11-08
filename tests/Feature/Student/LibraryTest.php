<?php

use App\Enums\LibraryCategory;
use App\Models\Branch;
use App\Models\LibraryFile;
use App\Models\Student;
use App\Models\StudentLibraryAccess;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| مكتبة الطالب — ملفات فرعه المنشورة، تسليم موقَّع.
|
| 🔴 فحص الفرع فوق فحص التوقيع: توقيع مسرَّب لا يخترق فرعاً آخر.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('private');

    $this->sci = Branch::create(['name' => 'العلمي', 'slug' => 'scientific', 'display_order' => 1]);
    $this->lit = Branch::create(['name' => 'الأدبي', 'slug' => 'literary', 'display_order' => 2]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->sci->id])
        ->user()->create(['email' => 'student@test.local', 'password' => bcrypt('x'), 'email_verified_at' => now()]))
        ->syncRoleToShape();

    $this->teacher = Teacher::create(['full_name' => 'أ. سامر', 'is_publicly_listed' => false]);

    $this->math = Subject::create([
        'branch_id' => $this->sci->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->arabic = Subject::create([
        'branch_id' => $this->lit->id, 'name' => 'العربية', 'slug' => 'arabic', 'is_published' => true,
    ]);
});

function branchFile(Subject $subject, array $overrides = []): LibraryFile
{
    $file = LibraryFile::create(array_merge([
        'subject_id' => $subject->id,
        'teacher_id' => test()->teacher->id,
        'title' => 'ورقة عمل',
        'category' => LibraryCategory::Worksheet->value,
        'is_published' => true,
        'published_at' => now()->subDay(),
    ], $overrides));

    $file->media()->create([
        'path' => 'library/'.$subject->id.'/'.uniqid().'.pdf',
        'disk' => 'private', 'type' => 'document',
        'original_filename' => 'sheet.pdf', 'mime_type' => 'application/pdf',
        'size_bytes' => 2048, 'display_order' => 1,
    ]);
    Storage::disk('private')->put($file->media->path, 'PDF');

    return $file->fresh('media');
}

function signedDownload(LibraryFile $file): string
{
    return URL::temporarySignedRoute('student.library.download', now()->addMinutes(10), ['file' => $file->id]);
}

// ── القائمة ───────────────────────────────────────────────────

it('يعرض ملفات فرع الطالب المنشورة فقط', function () {
    branchFile($this->math, ['title' => 'ملف فرعي']);
    branchFile($this->math, ['title' => 'مسودّة', 'is_published' => false]);
    branchFile($this->math, ['title' => 'مجدول', 'published_at' => now()->addWeek()]);
    branchFile($this->arabic, ['title' => 'ملف فرع آخر']);

    $titles = actingAs($this->student)->get(route('student.library'))
        ->assertOk()
        ->viewData('files')
        ->pluck('title')
        ->all();

    expect($titles)->toBe(['ملف فرعي']);
});

it('يصفّي بالمادة ويُسقط مادة خارج الفرع', function () {
    $physics = Subject::create([
        'branch_id' => $this->sci->id, 'name' => 'فيزياء', 'slug' => 'phys', 'is_published' => true,
    ]);
    branchFile($this->math, ['title' => 'ملف رياضيات']);
    branchFile($physics, ['title' => 'ملف فيزياء']);

    expect(actingAs($this->student)->get(route('student.library', ['subject' => $this->math->id]))
        ->viewData('files')->pluck('title')->all())->toBe(['ملف رياضيات']);

    // مادة فرع آخر → تُسقَط، فتظهر كل الملفات.
    $dropped = actingAs($this->student)->get(route('student.library', ['subject' => $this->arabic->id]))->assertOk();
    expect($dropped->viewData('filters')['subject'])->toBeNull()
        ->and($dropped->viewData('files'))->toHaveCount(2);
});

// ── التنزيل ───────────────────────────────────────────────────

it('ينزّل ملفاً برابط موقَّع ويزيد العدّادات', function () {
    $file = branchFile($this->math);

    actingAs($this->student)->get(signedDownload($file))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename="sheet.pdf"');

    expect($file->fresh()->download_count)->toBe(1);

    $access = StudentLibraryAccess::sole();
    expect($access->student_id)->toBe($this->student->userable->id)
        ->and($access->library_file_id)->toBe($file->id)
        ->and($access->download_count)->toBe(1)
        ->and($access->first_accessed_at)->not->toBeNull();

    // تنزيل ثانٍ: صفّ واحد، عدّاد داخلي 2.
    actingAs($this->student)->get(signedDownload($file))->assertOk();
    expect(StudentLibraryAccess::count())->toBe(1)
        ->and(StudentLibraryAccess::sole()->download_count)->toBe(2)
        ->and($file->fresh()->download_count)->toBe(2);
});

it('يرفض رابطاً غير موقَّع بـ403', function () {
    $file = branchFile($this->math);

    actingAs($this->student)->get(route('student.library.download', $file))->assertForbidden();
    expect($file->fresh()->download_count)->toBe(0);
});

it('🔴 يرفض تنزيل ملف فرع آخر حتى برابط موقَّع صحيح', function () {
    $foreign = branchFile($this->arabic);

    // توقيع صحيح تماماً، لكن الملف خارج فرع الطالب.
    actingAs($this->student)->get(signedDownload($foreign))->assertNotFound();
    expect($foreign->fresh()->download_count)->toBe(0)
        ->and(StudentLibraryAccess::count())->toBe(0);
});

it('يرفض تنزيل ملف مسودّة أو مجدول', function () {
    $draft = branchFile($this->math, ['is_published' => false]);
    $scheduled = branchFile($this->math, ['published_at' => now()->addWeek()]);

    actingAs($this->student)->get(signedDownload($draft))->assertNotFound();
    actingAs($this->student)->get(signedDownload($scheduled))->assertNotFound();
});

// ── الحراسة ───────────────────────────────────────────────────

it('يمنع الضيوف من مكتبة الطالب', function () {
    get(route('student.library'))->assertRedirect(route('auth.login'));
});
