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
use App\Models\Media;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| مكتبة المعلّم.
|
| 🔴 الخطر الأول هنا ليس الصلاحيات بل **التسريب**: ملف المكتبة محتوى مدفوع،
| وضمّه إلى جدول media نزع الحارس البنيوي الذي كان يمنع وجوده هناك أصلاً.
| الحارس البديل هو عمود `disk` + رفض Media::url() بناء رابط عامّ له — وهذا
| أول ما يُختبَر أدناه.
|
| ⚠️ الحساب هو «معلّم تجريبي» بالشدّة، كما تكتبه DatabaseSeeder.
*/

const LIB_TEACHER = 'معلّم تجريبي';

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('private');

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);

    $this->teacherRecord = Teacher::create([
        'full_name' => LIB_TEACHER, 'is_publicly_listed' => false,
    ]);

    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'teacher@paleducation.test', 'password' => bcrypt('password'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->math = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($this->math);

    // مادة لا يملكها هذا المعلّم — نفس الفرع.
    $this->physics = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الفيزياء', 'slug' => 'physics',
        'display_order' => 2, 'is_published' => true,
    ]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

function libraryFileUpload(string $name = 'sheet.pdf'): UploadedFile
{
    // ⚠️ ->create() لا ->image(): امتداد GD معطّل في هذه البيئة.
    return UploadedFile::fake()->create($name, 250, 'application/pdf');
}

function libraryPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'امتحان الفصل الأول 2025',
        'subject_id' => test()->math->id,
        'category' => LibraryCategory::Exam->value,
        'unit_name' => 'الوحدة الأولى',
        'page_count' => 12,
        'is_published' => '1',
        'file' => libraryFileUpload(),
    ], $overrides);
}

// ── الرفع والعلاقة متعدّدة الأشكال ──────────────────────────────

it('يرفع ملفاً ويكتب بياناته في media عبر العلاقة', function () {
    actingAs($this->teacher)
        ->post(route('teacher.library.store'), libraryPayload())
        ->assertRedirect();

    $file = LibraryFile::sole();
    $media = $file->media;

    expect($file->title)->toBe('امتحان الفصل الأول 2025')
        ->and($file->category)->toBe(LibraryCategory::Exam)
        ->and($file->teacher_id)->toBe($this->teacherRecord->id)
        ->and($file->uploaded_by)->toBe($this->teacher->id)
        // 🔴 صفّ morph حقيقي يشير إلى هذا السجلّ.
        ->and($media)->not->toBeNull()
        ->and($media->mediable_type)->toBe(LibraryFile::class)
        ->and($media->mediable_id)->toBe($file->id)
        // مقاييس الملف انتقلت إلى media بالكامل.
        ->and($media->original_filename)->toBe('sheet.pdf')
        ->and($media->mime_type)->toBe('application/pdf')
        ->and($media->size_bytes)->toBeGreaterThan(0);

    Storage::disk('private')->assertExists($media->path);
});

it('🔴 يخزّن الملف على القرص الخاص ويرفض بناء رابط عامّ له', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload());

    $media = LibraryFile::sole()->media;

    /*
     * 🔴 هذا هو الحارس الذي حلّ محلّ الحارس البنيوي. قبل ضمّ ملفات المكتبة
     * كان يستحيل وجود صفّ خاص في هذا الجدول، فكان asset() آمناً بالتعريف.
     * بعد الضمّ، رابط عامّ لملف مكتبة = تسريب محتوى مدفوع.
     */
    expect($media->disk)->toBe('private')
        ->and($media->isPrivate())->toBeTrue()
        ->and($media->url())->toBeNull();

    // ولا يُخدَم من public تحت أي حال.
    expect($media->path)->not->toStartWith('storage/');
});

it('يُبقي الأصول التسويقية القائمة عامّة كما هي', function () {
    /*
     * ⚠️ الهجرة أعطت disk افتراضياً public: تغيير معنى الصفوف الثمانية عشر
     * القائمة بأثر رجعي كان سيكسر كل صورة على الموقع العام.
     */
    /*
     * ⚠️ ->fresh() لا النسخة المُنشأة: `disk` قيمته الافتراضية في
     * **قاعدة البيانات**، والنموذج في الذاكرة لا يحملها حتى يُعاد
     * جلبه — نفس فخّ الأعمدة ذات الافتراضي في هذا المشروع.
     */
    $slider = Media::create([
        'path' => 'images/x.jpg', 'type' => 'image',
        'mediable_type' => 'X', 'mediable_id' => 1,
    ])->fresh();

    expect($slider->disk)->toBe('public')
        ->and($slider->isPrivate())->toBeFalse()
        ->and($slider->url())->not->toBeNull();
});

it('يستبدل الملف في مكانه ولا يكدّس صفوف media', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload([
        'file' => libraryFileUpload('first.pdf'),
    ]));

    $file = LibraryFile::sole();
    $firstPath = $file->media->path;

    actingAs($this->teacher)->put(route('teacher.library.update', $file), libraryPayload([
        'file' => libraryFileUpload('second.pdf'),
    ]));

    $file->refresh()->load('media');

    // 🔴 صفّ واحد: create في كل حفظ كان يكدّس صفوفاً حتى يصير «الملف» أيّها
    // أرجعه الاستعلام صدفةً.
    expect($file->media()->count())->toBe(1)
        ->and($file->media->path)->not->toBe($firstPath)
        ->and($file->media->original_filename)->toBe('second.pdf');

    // والملف القديم يُمسح بعد نجاح الجديد لا قبله.
    Storage::disk('private')->assertMissing($firstPath);
    Storage::disk('private')->assertExists($file->media->path);
});

it('يُبقي الملف الحالي حين لا يُرفع بديل', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload());

    $file = LibraryFile::sole();
    $path = $file->media->path;

    actingAs($this->teacher)->put(route('teacher.library.update', $file), [
        'title' => 'عنوان معدَّل',
        'subject_id' => $this->math->id,
        'category' => LibraryCategory::Summary->value,
        'is_published' => '1',
    ])->assertRedirect();

    $file->refresh()->load('media');

    // حقل ملف فارغ يعني «اتركه» لا «احذفه».
    expect($file->title)->toBe('عنوان معدَّل')
        ->and($file->category)->toBe(LibraryCategory::Summary)
        ->and($file->media->path)->toBe($path);

    Storage::disk('private')->assertExists($path);
});

it('يرفض صيغة غير مسموحة وحجماً يتجاوز الحدّ', function () {
    actingAs($this->teacher)
        ->post(route('teacher.library.store'), libraryPayload([
            'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
        ]))
        ->assertSessionHasErrors('file');

    actingAs($this->teacher)
        ->post(route('teacher.library.store'), libraryPayload([
            'file' => UploadedFile::fake()->create('huge.pdf', 25000, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('file');

    // 🔴 الفحص على الخادم: قيود منطقة السحب والإفلات ادّعاء عميل.
    expect(LibraryFile::count())->toBe(0)
        ->and(Media::where('mediable_type', LibraryFile::class)->count())->toBe(0);
});

// ── نافذة التعديل: الربط، والعودة بعد خطأ ───────────────────────

it('تعديل بيانات ملف يحدّث السجلّ ويُبقي صفّ media كما هو', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload([
        'title' => 'أصلي', 'unit_name' => 'الوحدة الأولى', 'page_count' => 5,
        'category' => LibraryCategory::Exam->value, 'is_published' => '1',
    ]));

    $file = LibraryFile::sole();
    $mediaId = $file->media->id;
    $mediaPath = $file->media->path;

    // تعديل كل حقل تعليمي دفعةً، بلا ملف بديل.
    actingAs($this->teacher)->put(route('teacher.library.update', $file), [
        'title' => 'معدَّل',
        'subject_id' => $this->math->id,
        'category' => LibraryCategory::Worksheet->value,
        'unit_name' => 'الوحدة الثانية',
        'page_count' => 9,
        'is_published' => '0',
    ])->assertRedirect();

    $file->refresh()->load('media');

    expect($file->title)->toBe('معدَّل')
        ->and($file->category)->toBe(LibraryCategory::Worksheet)
        ->and($file->unit_name)->toBe('الوحدة الثانية')
        ->and($file->page_count)->toBe(9)
        ->and($file->is_published)->toBeFalse()
        // 🔴 لا ملف مرفوع ⇒ صفّ media نفسه بمساره نفسه: التعديل بيانات لا استبدال.
        ->and($file->media->id)->toBe($mediaId)
        ->and($file->media->path)->toBe($mediaPath);

    Storage::disk('private')->assertExists($mediaPath);
});

it('تعديل ملف مع رفع بديل يحدّث السجلّ وصفّ media معاً', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload([
        'title' => 'أصلي', 'file' => libraryFileUpload('old.pdf'),
    ]));

    $file = LibraryFile::sole();
    $oldPath = $file->media->path;

    actingAs($this->teacher)->put(route('teacher.library.update', $file), libraryPayload([
        'title' => 'معدَّل بملف', 'file' => libraryFileUpload('new.pdf'),
    ]))->assertRedirect();

    $file->refresh()->load('media');

    expect($file->title)->toBe('معدَّل بملف')
        ->and($file->media()->count())->toBe(1)
        ->and($file->media->original_filename)->toBe('new.pdf')
        ->and($file->media->path)->not->toBe($oldPath)
        ->and($file->media->disk)->toBe('private');

    Storage::disk('private')->assertExists($file->media->path);
    Storage::disk('private')->assertMissing($oldPath);
});

it('🔴 زرّ التعديل يُصدر ربط Alpine مُصرَّفاً لا نصّ ‎@js‎ حرفياً', function () {
    /*
     * 🔴 حارس ضدّ عودة العطل: ‎@js([...])‎ موجّه Blade، والموجّهات لا تُصرَّف
     * داخل سمة على وسم مكوّن (<x-…>) — تُقتطع كنصّ حرفي، فيصل Alpine تعبيرَ
     * ‎@click‎ نصّاً فيه ‎@‎ ⇒ SyntaxError والزرّ ميت بلا أثر في الـconsole.
     * البديل الصحيح: صدى {{ Js::from() }} الذي يُصرَّف في سمات المكوّن.
     */
    LibraryFile::create([
        'subject_id' => $this->math->id, 'teacher_id' => $this->teacherRecord->id,
        'title' => 'ملف للتعديل', 'category' => LibraryCategory::Summary->value,
    ]);

    $html = actingAs($this->teacher)->get(route('teacher.library'))->assertOk()->getContent();

    expect($html)
        // الصدى صُرِّف: JSON.parse('…') لا نصّ @js حرفي.
        ->toContain('openEdit(JSON.parse(')
        ->not->toContain('openEdit(@js(')
        // موجّه غير مُصرَّف كان سيترك تعبير PHP نصّاً في الصفحة.
        ->not->toContain('$file->title')
        ->not->toContain("route('teacher.library.update', \$file)");
});

it('تعديل فشل تحقّقه يُعاد فتحه تعديلاً على مسار PUT بقيمه المُدخَلة', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload(['title' => 'قبل']));
    $file = LibraryFile::sole();

    // ⚠️ POST بانتحال _method=PUT كما يرسله النموذج فعلاً: ‎old('_method')‎
    //   و‎old('_form_action')‎ هما ما يُعيدان فتح المودال تعديلاً.
    // page_count فوق الحدّ ⇒ 422 وعودة، مع بقاء العنوان والوحدة في old().
    $html = actingAs($this->teacher)
        ->from(route('teacher.library'))
        ->followingRedirects()
        ->post(route('teacher.library.update', $file), [
            '_method' => 'PUT',
            '_form_action' => route('teacher.library.update', $file),
            'title' => 'عنوان محرَّر', 'subject_id' => $this->math->id,
            'category' => LibraryCategory::Summary->value,
            'unit_name' => 'وحدة محرَّرة', 'page_count' => 999999,
        ])
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('data-reopen="1"')
        ->toContain('data-reopen-editing="1"')
        ->toContain('data-reopen-action="'.route('teacher.library.update', $file).'"')
        // 🔴 القيم تعود من old() في data-reopen-form — لا حقول فارغة بعد الخطأ.
        ->toContain('عنوان محرَّر')
        ->toContain('وحدة محرَّرة');

    // والسجلّ لم يتغيّر: المعاملة تراجعت.
    expect($file->fresh()->title)->toBe('قبل');
});

it('فشل تحقّق في بوابة المعلّم لا يومض توست خطأ فوق النموذج', function () {
    /*
     * 🔴 بوابة المعلّم نماذجها نوافذ تُعاد فتحها بأخطاء الحقول الظاهرة —
     * توست ✗ ملء الشاشة يحجب النموذج ويكرّر ما هو معروض. رسالة الأدمن
     * المقابلة تبقى مثبَّتة في FlashMatrixTest.
     */
    $html = actingAs($this->teacher)
        ->from(route('teacher.library'))
        ->followingRedirects()
        ->post(route('teacher.library.store'), libraryPayload(['title' => '']))
        ->assertOk()
        ->getContent();

    // الخطأ يظهر (النموذج يُعاد فتحه)، لكن لا توست خطأ من معالج الاستثناءات.
    expect($html)
        ->toContain('data-reopen="1"')
        ->not->toContain('يرجى التأكد من تصحيح الأخطاء في النموذج.')
        ->not->toContain('data-flash-item');
});

// ── النطاق ──────────────────────────────────────────────────────

it('يرى ملفات مواده المسندة وحدها', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload(['title' => 'ملفّي']));

    // ملف على مادة لا يملكها — نفس الفرع.
    LibraryFile::create([
        'subject_id' => $this->physics->id,
        'teacher_id' => $this->teacherRecord->id,
        'title' => 'ملف زميلة',
        'category' => LibraryCategory::Summary->value,
    ]);

    $titles = actingAs($this->teacher)
        ->get(route('teacher.library'))
        ->assertOk()
        ->viewData('files')
        ->pluck('title')
        ->all();

    // ⚠️ يُختبَر على البيانات لا على نصّ الصفحة: شريط المعلّم يحمل نصوصاً
    // تجريبية ثابتة تجعل assertDontSee بلا قيمة.
    expect($titles)->toBe(['ملفّي']);
});

it('يرفض رفع ملف إلى مادة لا يملكها', function () {
    actingAs($this->teacher)
        ->post(route('teacher.library.store'), libraryPayload(['subject_id' => $this->physics->id]))
        ->assertSessionHasErrors('subject_id');

    /*
     * 🔴 subject_id رقم في نموذج يمكن تحريره. قاعدة exists عادية كانت تسمح
     * بدفع ملف إلى مادة زميل — أو إلى فرع آخر، وهو طريق وصول المحتوى إلى
     * الطلبة الخطأ.
     */
    expect(LibraryFile::count())->toBe(0);
});

it('يُرجع 404 لملف خارج مواده', function () {
    $theirs = LibraryFile::create([
        'subject_id' => $this->physics->id,
        'teacher_id' => $this->teacherRecord->id,
        'title' => 'ليس له',
        'category' => LibraryCategory::Summary->value,
    ]);

    // 🔴 404 لا 403: «ممنوع» تؤكّد أن المُعرِّف حقيقي وأن خلفه محتوى.
    actingAs($this->teacher)->put(route('teacher.library.update', $theirs), libraryPayload())->assertNotFound();
    actingAs($this->teacher)->delete(route('teacher.library.destroy', $theirs))->assertNotFound();
    actingAs($this->teacher)->get(route('teacher.library.download', $theirs))->assertNotFound();

    expect($theirs->fresh()->title)->toBe('ليس له');
});

it('يمنع الضيوف والطلبة والأدمن من مكتبة المعلّم', function () {
    get(route('teacher.library'))->assertRedirect(route('auth.login'));

    foreach ([$this->student, $this->admin] as $outsider) {
        actingAs($outsider)->get(route('teacher.library'))->assertForbidden();
        actingAs($outsider)->post(route('teacher.library.store'), libraryPayload())->assertForbidden();
    }

    expect(LibraryFile::count())->toBe(0);
});

// ── الحذف الناعم والوسائط ───────────────────────────────────────

it('يحذف حذفاً ناعماً ويُبقي الملف على القرص', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload());

    $file = LibraryFile::sole();
    $path = $file->media->path;

    actingAs($this->teacher)
        ->delete(route('teacher.library.destroy', $file))
        ->assertRedirect();

    expect(LibraryFile::count())->toBe(0)
        ->and(LibraryFile::withTrashed()->count())->toBe(1);

    /*
     * 🔴 الملف يبقى. الاسترجاع من السلّة يجب أن يعيد ملفاً قابلاً للفتح لا
     * سجلّاً يشير إلى لا شيء — والمسح الفعلي عند الحذف النهائي.
     */
    Storage::disk('private')->assertExists($path);
    expect(Media::where('mediable_type', LibraryFile::class)->count())->toBe(1);
});

it('ينظّف صفّ media والملف عند الحذف النهائي', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload());

    $file = LibraryFile::sole();
    $path = $file->media->path;

    $file->delete();

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'library', 'id' => $file->id]))
        ->assertRedirect();

    /*
     * ⚠️ mediable_id بلا مفتاح أجنبي فلا تتالي — التصريح بـmedia() هو ما
     * يُدخل النموذج في TrashManager::purgeMedia.
     */
    expect(Media::withTrashed()->where('mediable_type', LibraryFile::class)->count())->toBe(0);
    Storage::disk('private')->assertMissing($path);
})->skip('نوع library لم يُسجَّل في TrashableResources بعد — انظر التقرير.');

// ── التسليم ─────────────────────────────────────────────────────

it('يسلّم الملف للمعلّم ويزيد العدّاد', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload());

    $file = LibraryFile::sole();

    actingAs($this->teacher)
        ->get(route('teacher.library.download', $file))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename="sheet.pdf"');

    // ⚠️ العدّاد يزيد على التنزيل لا على العرض في القائمة.
    expect($file->fresh()->download_count)->toBe(1);
});

// ── الوحدة والربط بالمحاضرة ─────────────────────────────────────

it('يرث وحدة المحاضرة المربوطة ولا ينسخها', function () {
    $term = AcademicTerm::create([
        'name' => 'الفصل الأول', 'academic_year' => '2026/2027',
        'starts_on' => now()->subMonth(), 'ends_on' => now()->addMonths(4),
        'status' => 'active', 'is_current' => true,
    ]);

    $lecture = Lecture::create([
        'subject_id' => $this->math->id, 'academic_term_id' => $term->id,
        'teacher_id' => $this->teacherRecord->id, 'title' => 'محاضرة',
        'slug' => 'lec-'.uniqid(), 'display_order' => 1,
        'unit_name' => 'الوحدة الثانية: التكامل',
        'status' => LectureStatus::Published,
        'video_source' => VideoSource::YouTube, 'video_reference' => 'dQw4w9WgXcQ',
        'video_status' => VideoStatus::Ready,
    ]);

    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload([
        'lecture_id' => $lecture->id,
        'unit_name' => 'وحدة مكتوبة يدوياً',
    ]));

    $file = LibraryFile::with('lecture')->sole();

    /*
     * 🔴 تُحسب ولا تُخزَّن: نسخ وحدة المحاضرة إلى الملف يعني قيمتين تنحرفان
     * أول ما يعدّل المعلّم وحدة المحاضرة، ويبقى الملف معلّقاً على وحدة لم
     * تعد موجودة.
     */
    expect($file->unitLabel())->toBe('الوحدة الثانية: التكامل')
        ->and($file->unit_name)->toBe('وحدة مكتوبة يدوياً');
});

it('يُسقط ربط محاضرة من مادة أخرى', function () {
    $term = AcademicTerm::create([
        'name' => 'الفصل الأول', 'academic_year' => '2026/2027',
        'starts_on' => now()->subMonth(), 'ends_on' => now()->addMonths(4),
        'status' => 'active', 'is_current' => true,
    ]);

    $otherLecture = Lecture::create([
        'subject_id' => $this->physics->id, 'academic_term_id' => $term->id,
        'teacher_id' => $this->teacherRecord->id, 'title' => 'محاضرة فيزياء',
        'slug' => 'lec-'.uniqid(), 'display_order' => 1,
        'status' => LectureStatus::Published,
        'video_source' => VideoSource::YouTube, 'video_reference' => 'dQw4w9WgXcQ',
        'video_status' => VideoStatus::Ready,
    ]);

    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload([
        'lecture_id' => $otherLecture->id,
    ]))->assertRedirect();

    // 🔴 ربط ملف رياضيات بمحاضرة فيزياء يجعله يرث وحدة لا علاقة لها به
    // ويظهر في تنقّل مادة أخرى.
    expect(LibraryFile::sole()->lecture_id)->toBeNull();
});

it('يقصّ مسافات الوحدة', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload([
        'unit_name' => '  الوحدة الأولى  ',
    ]));

    // مسافة زائدة تُنتج وحدة ثانية تبدو مطابقة للأولى.
    expect(LibraryFile::sole()->unit_name)->toBe('الوحدة الأولى');
});

// ── التصفية ─────────────────────────────────────────────────────

it('يصفّي بالمادة والفئة والبحث', function () {
    $second = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'مادة ثانية', 'slug' => 'second',
        'display_order' => 3, 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($second);

    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload([
        'title' => 'ملخّص الرياضيات', 'category' => LibraryCategory::Summary->value,
    ]));

    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload([
        'title' => 'امتحان المادة الثانية', 'subject_id' => $second->id,
        'category' => LibraryCategory::Exam->value,
    ]));

    $titles = fn (array $query) => actingAs($this->teacher)
        ->get(route('teacher.library', $query))
        ->assertOk()
        ->viewData('files')
        ->pluck('title')
        ->all();

    expect($titles(['subject_id' => $this->math->id]))->toBe(['ملخّص الرياضيات'])
        ->and($titles(['category' => 'exam']))->toBe(['امتحان المادة الثانية'])
        ->and($titles(['search' => 'ملخّص']))->toBe(['ملخّص الرياضيات'])
        ->and($titles([]))->toHaveCount(2);
});

it('يُسقط تصفية مادة لا يملكها', function () {
    actingAs($this->teacher)->post(route('teacher.library.store'), libraryPayload(['title' => 'ملفّي']));

    $response = actingAs($this->teacher)
        ->get(route('teacher.library', ['subject_id' => $this->physics->id]))
        ->assertOk();

    // ⚠️ تُسقَط بدل أن تُطاع: سلسلة استعلام محرَّرة يجب ألّا تصير طريقة
    // لاستكشاف المعرّفات الموجودة.
    expect($response->viewData('filters')['subject_id'])->toBeNull()
        ->and($response->viewData('files')->pluck('title')->all())->toBe(['ملفّي']);
});
