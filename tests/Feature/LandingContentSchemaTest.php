<?php

use App\Enums\MediaType;
use App\Models\Branch;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\Lecture;
use App\Models\LibraryFile;
use App\Models\Media;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\Step;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Testimonial;
use Illuminate\Database\QueryException;

/*
| يثبّت ثلاثة أشياء يسهل انزلاقها لاحقاً:
|   ١) كل حقل مُصرَّح يصل عموده فعلاً (فخّ $fillable الصامت)
|   ٢) الوسائط تُرفَق بـSlider و Testimonial **فقط**
|   ٣) المحتوى المدفوع لا طريق له إلى جدول media العام
*/

// ── الإسناد الجماعي ─────────────────────────────────────────────

it('يحفظ كل حقول الشريحة الممرّرة جملةً', function () {
    $slider = Slider::create([
        'badge_text' => 'جديد', 'title' => 'تعلّم بثقة', 'sub_title' => 'منصة التوجيهي',
        'btn1_text' => 'ابدأ', 'btn1_link' => '/register',
        'btn2_text' => 'الفروع', 'btn2_link' => '/branches',
        'display_order' => 3, 'is_active' => true,
    ]);

    expect($slider->fresh())
        ->badge_text->toBe('جديد')->sub_title->toBe('منصة التوجيهي')
        ->btn1_link->toBe('/register')->btn2_link->toBe('/branches')
        ->display_order->toBe(3)
        ->and($slider->hasPrimaryAction())->toBeTrue()
        ->and($slider->hasSecondaryAction())->toBeTrue();
});

it('يحفظ كل حقول الميزة والخطوة والسؤال', function () {
    $feature = Feature::create([
        'title' => 'كويز بعد كل محاضرة', 'description' => 'يفتح تلقائياً',
        'btn_text' => 'اعرف أكثر', 'btn_link' => '/features',
        'icon' => 'clipboard', 'accent_token' => 'accent', 'display_order' => 2,
    ]);
    $step = Step::create(['title' => 'سجّل', 'sub_title' => 'بالبريد أو جوجل', 'icon' => 'user']);
    $faq = Faq::create(['question' => 'هل أغيّر فرعي؟', 'answer' => 'عبر الإدارة.']);

    expect($feature->fresh())->btn_link->toBe('/features')->accent_token->toBe('accent')->display_order->toBe(2)
        ->and($feature->hasAction())->toBeTrue()
        ->and($step->fresh())->sub_title->toBe('بالبريد أو جوجل')->icon->toBe('user')
        ->and($faq->fresh())->answer->toBe('عبر الإدارة.');
});

it('يقبل عمودَي زرّ الفرع الجديدين بلا إسقاط', function () {
    $branch = Branch::create(['name' => 'علمي', 'slug' => 'scientific', 'display_order' => 1]);

    expect($branch->hasAction())->toBeFalse(); // فرع بلا زرّ مشروع

    $branch->update(['btn_text' => 'استكشف', 'btn_link' => '/branches/scientific']);

    expect($branch->fresh())->btn_text->toBe('استكشف')->btn_link->toBe('/branches/scientific')
        ->and($branch->fresh()->hasAction())->toBeTrue();
});

// ── الوسائط متعدّدة الأشكال ─────────────────────────────────────

it('يرفق وسائط متعدّدة بالشريحة ويعيد الأولى عبر morphOne', function () {
    $slider = Slider::create(['title' => 'شريحة']);
    $slider->media()->createMany([
        ['path' => 'images/hero/b.jpg', 'type' => MediaType::Image, 'display_order' => 2],
        ['path' => 'images/hero/a.jpg', 'type' => MediaType::Image, 'display_order' => 1],
    ]);

    expect($slider->media()->count())->toBe(2)
        ->and($slider->image->path)->toBe('images/hero/a.jpg')  // الأدنى ترتيباً
        ->and($slider->image->type)->toBe(MediaType::Image);
});

it('يعيد morphTo المالك الصحيح لكل نوع', function () {
    $slider = Slider::create(['title' => 'شريحة']);
    $slider->media()->create(['path' => 'a.jpg', 'type' => MediaType::Image]);

    $testimonial = Testimonial::create(['name' => 'سارة', 'rate' => 5, 'description' => 'ممتاز']);
    $testimonial->photo()->create(['path' => 'sara.jpg', 'type' => MediaType::Image]);

    expect(Media::where('path', 'a.jpg')->first()->mediable)->toBeInstanceOf(Slider::class)
        ->and(Media::where('path', 'sara.jpg')->first()->mediable)->toBeInstanceOf(Testimonial::class)
        ->and(Media::where('path', 'sara.jpg')->first()->mediable->name)->toBe('سارة');
});

it('لا يُعرّف علاقة وسائط على الخطوات ولا المزايا ولا الأسئلة', function () {
    // الغياب هو الضمانة: لا يوجد ما يُرفَق به، فلا تتسرّب صورة إلى قسم نصّي.
    foreach ([Step::class, Feature::class, Faq::class] as $model) {
        expect(method_exists($model, 'media'))->toBeFalse()
            ->and(method_exists($model, 'image'))->toBeFalse()
            ->and(method_exists($model, 'photo'))->toBeFalse();
    }
});

it('لا يفتح للمحتوى المدفوع أي طريق إلى قرص عام', function () {
    /*
     * 🔁 انعكاس مسجَّل. كان الحارس **بنيوياً**: لا LibraryFile يُعرّف media()
     * إطلاقاً، فيستحيل وصول محتوى مدفوع إلى جدول عام. وحّد القرار المعماري
     * بيانات الملفات في media، فسقط ذلك الحارس ووجب أن يحلّ محلّه أقوى منه
     * لا أضعف.
     *
     * الحارس الجديد ثلاثي، وهذا الاختبار يثبته كاملاً:
     *   ١) صفّ ملف المكتبة يعلن قرصه `private`
     *   ٢) Media::url() **يرفض** بناء رابط عامّ له ويُرجع null
     *   ٣) Lecture ما زال بلا media() — الفيديو عند مزوّد خارجي لا عندنا
     */
    expect(method_exists(Lecture::class, 'media'))->toBeFalse();

    $branch = Branch::create(['name' => 'علمي', 'slug' => 'sci-guard', 'display_order' => 9]);
    $subject = Subject::create([
        'branch_id' => $branch->id, 'name' => 'مادة', 'slug' => 'guard-subject', 'is_published' => true,
    ]);
    $teacher = Teacher::create(['full_name' => 'أ. حارس', 'is_publicly_listed' => false]);

    $file = LibraryFile::create([
        'subject_id' => $subject->id, 'teacher_id' => $teacher->id,
        'title' => 'ملف مدفوع', 'category' => 'summary',
    ]);

    $media = $file->media()->create([
        'path' => 'library/1/secret.pdf',
        'disk' => 'private',
        'type' => MediaType::Document,
        'mime_type' => 'application/pdf',
        'size_bytes' => 1024,
    ]);

    expect($media->disk)->toBe('private')
        ->and($media->isPrivate())->toBeTrue()
        // 🔴 لبّ الضمانة: لا رابط عامّ لمحتوى مدفوع مهما استُدعيت الدالة.
        ->and($media->url())->toBeNull()
        // ولا يعيش تحت public/ أصلاً.
        ->and($media->path)->not->toStartWith('storage/');
});

// ── التقييم ─────────────────────────────────────────────────────

it('يقصّ التقييم على المدى المعروض', function () {
    expect(Testimonial::create(['name' => 'أ', 'rate' => 5, 'description' => 'x'])->stars())->toBe(5)
        ->and(Testimonial::create(['name' => 'ب', 'rate' => 200, 'description' => 'x'])->stars())->toBe(5)
        ->and(Testimonial::create(['name' => 'ج', 'rate' => 0, 'description' => 'x'])->stars())->toBe(1);
});

// ── الإعدادات ───────────────────────────────────────────────────

it('يمنع تكرار مفتاح الإعداد ويحدّثه عبر put', function () {
    Setting::put('site.name', 'المنصة');
    Setting::put('site.name', 'المنصة الفلسطينية');

    expect(Setting::where('key', 'site.name')->count())->toBe(1)
        ->and(Setting::get('site.name'))->toBe('المنصة الفلسطينية')
        ->and(Setting::get('missing', '—'))->toBe('—')
        ->and(fn () => Setting::create(['key' => 'site.name', 'value' => 'x']))
        ->toThrow(QueryException::class);
});

it('يقرأ كل الإعدادات باستعلام واحد', function () {
    Setting::put('a', '1');
    Setting::put('b', '2');
    Setting::put('c', null);

    expect(Setting::map())->toBe(['a' => '1', 'b' => '2', 'c' => null]);
});

// ── الحذف الناعم ────────────────────────────────────────────────

it('يُخفي المحتوى المحذوف ناعماً ويستعيده بوسائطه', function () {
    $slider = Slider::create(['title' => 'شريحة']);
    $slider->media()->create(['path' => 'a.jpg', 'type' => MediaType::Image]);

    $slider->delete();

    expect(Slider::count())->toBe(0)
        ->and(Slider::withTrashed()->count())->toBe(1)
        ->and(Media::count())->toBe(1); // الوسائط لا تُحذف مع الأب

    $slider->restore();

    expect(Slider::first()->image->path)->toBe('a.jpg');
});
