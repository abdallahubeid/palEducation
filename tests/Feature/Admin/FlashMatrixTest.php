<?php

use App\Models\Admin;
use App\Models\Slider;
use Database\Seeders\RoleSeeder;
use Flasher\Laravel\Storage\SessionBag;
use Flasher\Prime\Notification\Envelope;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

/*
| جدول الإشعارات المتّفق عليه، مثبَّتاً كاختبار.
|
| السبب: الرسائل الستّ عقد مع المستخدم لا تفصيل تنفيذي. تغييرها سهو
| لا يُنتج أي خطأ — الشاشة تعمل، والنبرة وحدها تنحرف. هذا الملف يجعل
| الانحراف مرئياً.
|
| ⚠️ يُقرأ من مخزن Flasher لا من الجلسة الخام: الحزمة تُغلّف الإشعار في
| Envelope وتخزّنه بمفتاحها الخاص.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    Storage::fake('public');
});

/**
 * آخر إشعار مُومَض: [النوع, الرسالة].
 *
 * ⚠️ يُقرأ من الجلسة مباشرةً لا من مدير التخزين: المدير يُحلّ في نسخة
 * التطبيق الخاصة بالاختبار، وهي غير النسخة التي خدمت الطلب — فيُرجع
 * قائمة فارغة على إشعار مُومَض فعلاً. الجلسة تعبر الحدّ بين الطلبين.
 */
function lastFlash(): array
{
    $stored = session(SessionBag::ENVELOPES_NAMESPACE, []);
    $last = end($stored);

    // ⚠️ SessionBag::set يخزّن الأظرف **مُسلسَلة** (serialize) لا ككائنات.
    // القراءة الخام من الجلسة تُرجع نصوصاً؛ فكّ التسلسل هنا لا في الادّعاء.
    if (is_string($last)) {
        $last = @unserialize($last);
    }

    return $last instanceof Envelope
        ? [$last->getType(), $last->getMessage()]
        : ['', ''];
}

function slider(): Slider
{
    return Slider::create(['title' => 'شريحة', 'is_active' => true]);
}

function payload(array $overrides = []): array
{
    return array_merge([
        'title' => 'عنوان', 'display_order' => 0, 'is_active' => '1',
        'image' => UploadedFile::fake()->create('a.jpg', 200, 'image/jpeg'),
    ], $overrides);
}

it('إنشاء ← success بنصّ الإضافة', function () {
    actingAs($this->admin)->post(route('admin.sliders.store'), payload());

    expect(lastFlash())->toBe(['success', 'تم إنشاء الحساب / الإضافة بنجاح.']);
});

it('تحديث ← success بنصّ التحديث', function () {
    actingAs($this->admin)->put(route('admin.sliders.update', slider()), payload(['image' => null]));

    expect(lastFlash())->toBe(['success', 'تم تحديث البيانات بنجاح.']);
});

it('حذف ← warning مع زرّ تراجع يشير لمسار الاستعادة', function () {
    $slider = slider();

    actingAs($this->admin)
        ->from(route('admin.sliders.index'))
        ->delete(route('admin.sliders.destroy', $slider));

    expect(lastFlash())->toBe(['warning', 'تم حذف العنصر بنجاح.']);

    // 🔴 زرّ التراجع نموذج PATCH بـCSRF لا رابط داخل توست: الاستعادة تغيّر
    // حالة الخادم، ومسار GET يُنفَّذ بجالب مسبق أو بزاحف.
    expect(session('admin.undo'))->toBe([
        'url' => route('admin.trash.restore', ['type' => 'sliders', 'id' => $slider->id]),
        'label' => 'تراجع',
    ]);
});

it('خطأ تحقّق ← error بنصّ تصحيح النموذج', function () {
    actingAs($this->admin)->post(route('admin.sliders.store'), payload(['title' => '']));

    expect(lastFlash())->toBe(['error', 'يرجى التأكد من تصحيح الأخطاء في النموذج.']);
});

it('تبديل الحالة ← info', function () {
    actingAs($this->admin)
        ->from(route('admin.sliders.index'))
        ->patch(route('admin.sliders.toggle', slider()));

    expect(lastFlash())->toBe(['info', 'تم تغيير الحالة بنجاح.']);
});

it('استثناء نظام ← error بنصّ الخطأ غير المتوقّع', function () {
    // قرص مزيّف يرمي عند الكتابة: أقرب محاكاة لفشل تخزين حقيقي.
    Storage::shouldReceive('disk')->andThrow(new RuntimeException('disk offline'));

    actingAs($this->admin)->post(route('admin.sliders.store'), payload());

    expect(lastFlash())->toBe(['error', 'حدث خطأ غير متوقع، يرجى المحاولة لاحقاً.']);

    // ⚠️ الصفّ يبقى: الكنترولر بلا DB::transaction تبعاً لأسلوب الكود
    // المعتمد. فشل رفع الصورة بعد إنشاء الشريحة يترك شريحة بلا صورة رغم
    // أن image مطلوبة في التحقّق. مقصود ومسجَّل — لا اكتشاف عرضي.
    expect(Slider::count())->toBe(1)
        ->and(Slider::sole()->image)->toBeNull();
});

it('زرّ التراجع يُعرض في القشرة كنموذج PATCH لا كرابط', function () {
    $slider = slider();

    actingAs($this->admin)
        ->from(route('admin.sliders.index'))
        ->delete(route('admin.sliders.destroy', $slider));

    actingAs($this->admin)->get(route('admin.sliders.index'))
        ->assertOk()
        ->assertSee('تراجع')
        ->assertSee('action="'.route('admin.trash.restore', ['type' => 'sliders', 'id' => $slider->id]).'"', false)
        ->assertSee('name="_method" value="PATCH"', false);
});

/*
| 🔴 حرّاس ضدّ عودة العطل.
|
| الإشعار لم يظهر ثلاث مرّات لثلاثة أسباب مختلفة، وكلها صامتة:
|   ١) أصول الحزمة غير منشورة → 404 على flasher.min.js
|   ٢) @flasher_render يستهلك الأظرف بلا echo → "envelopes":[]
|   ٣) الحمولة تُحقن بـ "styles":[] → CSS لا يُحمَّل، فالتوست بلا تنسيق
|
| الحلّ النهائي: الرسم على الخادم في x-admin.flash. الاختبارات أدناه
| تثبّت أن النصّ يصل HTML **خاماً ومرئياً** لا مُرمَّزاً في حمولة JSON.
*/
it('يرسم الإشعار في HTML نصّاً مرئياً على الصفحة التالية', function () {
    $slider = slider();

    actingAs($this->admin)
        ->from(route('admin.sliders.index'))
        ->patch(route('admin.sliders.toggle', $slider));

    $html = actingAs($this->admin)->get(route('admin.sliders.index'))->getContent();

    // 🔴 الإشعار يُرسَم على الخادم الآن: النصّ العربي يظهر **خاماً** في
    // HTML لا مُرمَّزاً داخل JSON. هذا بالضبط ما يجعله مرئياً للمستخدم.
    expect($html)->toContain('تم تغيير الحالة بنجاح.')
        ->toContain('data-flash-item');
});

it('لا يعرض الإشعار مرّتين — يُمسَح من المخزن بعد الرسم', function () {
    actingAs($this->admin)
        ->from(route('admin.sliders.index'))
        ->patch(route('admin.sliders.toggle', slider()));

    // الزيارة الأولى تعرضه…
    actingAs($this->admin)->get(route('admin.sliders.index'))
        ->assertSee('تم تغيير الحالة بنجاح.');

    // …والثانية لا. بلا clear() يبقى الظرف ويتكرّر في كل صفحة.
    actingAs($this->admin)->get(route('admin.sliders.index'))
        ->assertDontSee('تم تغيير الحالة بنجاح.');
});

it('لا يحمل قالب الأدمن موجّه flasher_render فعّالاً', function () {
    $layout = file_get_contents(resource_path('views/components/layouts/admin.blade.php'));

    // ⚠️ تُجرَّد تعليقات Blade أولاً: القالب **يشرح** الموجّه في تعليق،
    // والبحث الخام كان يعدّ الشرح استعمالاً فيسقط الحارس على قالب سليم.
    $code = preg_replace('/\{\{--.*?--\}\}/s', '', $layout);

    // الموجّه يُصرَّف بلا echo فيبتلع الأظرف قبل أن يراها المكوّن.
    expect($code)->not->toContain('@flasher_render');
});
