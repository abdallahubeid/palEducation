<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Student;
use Database\Seeders\CtaSettingSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| بانر الدعوة — من جدول settings إلى الصفحة الرئيسية.
|
| الاختبارات هنا تغطّي الحلقة كاملة لا طرفيها: البذرة تكتب، والنموذج
| يحفظ، والصفحة العامة تعرض. كسر أي وصلة منها يُنتج بانراً صامتاً —
| يعمل ولا يعرض ما في القاعدة.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $branch = Branch::create(['name' => 'علمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

/**
 * ⚠️ الحقول تحت اسم اللوح: name="cta[title]" لا name="title". الألواح
 * كلها تُرسَل في صفحة واحدة، و`old()` و`$errors` نطاقهما الطلب لا
 * النموذج — فاسم مسطّح يجعل خطأ لوحٍ يظهر تحت حقل لوحٍ آخر يشاركه الاسم.
 */
function ctaPayload(array $overrides = []): array
{
    return ['cta' => array_merge([
        'title' => 'عنوان البانر',
        'sub_title' => 'نصّ تمهيدي للبانر.',
        'btn_text' => 'ابدأ الآن',
        'btn_link' => '/register',
    ], $overrides)];
}

// ── البذرة ──────────────────────────────────────────────────────

it('تبذر المفاتيح الأربعة بقيمها المحدّدة', function () {
    $this->seed(CtaSettingSeeder::class);

    expect(Setting::get('cta.title'))->toBe('أول محاضرة من كل مادة — مجاناً')
        ->and(Setting::get('cta.sub_title'))->toBe('جرّب المنصة كاملة قبل أن تدفع شيكلاً واحداً، ودون بطاقة ائتمان.')
        ->and(Setting::get('cta.btn_text'))->toBe('ابدأ التجربة')
        ->and(Setting::get('cta.btn_link'))->toBe('/register');
});

it('لا تمسح البذرة نصّاً حرّره الأدمن عند إعادة تشغيلها', function () {
    // 🔴 هذا بالضبط الفرق بين firstOrCreate وupdateOrCreate: بذرة تُعاد
    // بعد كل نشر يجب ألّا تُرجع نصّ المصنع فوق نصّ المنصة.
    $this->seed(CtaSettingSeeder::class);
    Setting::put('cta.title', 'عنوان حرّره الأدمن');

    $this->seed(CtaSettingSeeder::class);

    expect(Setting::get('cta.title'))->toBe('عنوان حرّره الأدمن')
        ->and(Setting::where('key', 'cta.title')->count())->toBe(1);
});

// ── الحراسة ─────────────────────────────────────────────────────

it('يمنع الزائر والطالب من حفظ بانر الدعوة', function () {
    $this->put(route('admin.settings.cta.update'), ctaPayload())
        ->assertRedirect(route('auth.login'));

    actingAs($this->student)->put(route('admin.settings.cta.update'), ctaPayload())
        ->assertForbidden();

    expect(Setting::count())->toBe(0);
});

it('يرفض أدمناً نُزعت منه manage-settings رغم بقاء دوره', function () {
    // النزع من **الدور** لا من المستخدم — الصلاحية ممنوحة عبر الدور.
    Role::findByName('admin', 'web')->revokePermissionTo('manage-settings');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($this->admin)->put(route('admin.settings.cta.update'), ctaPayload())
        ->assertForbidden();

    expect(Setting::count())->toBe(0);
});

// ── الشاشة ──────────────────────────────────────────────────────

it('يعرض تبويب بانر الدعوة بقيمه المحفوظة', function () {
    Setting::put('cta.title', 'عنوان محفوظ');
    Setting::put('cta.btn_link', '#pricing');

    actingAs($this->admin)->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('بانر الدعوة')
        ->assertSee('عنوان محفوظ')
        ->assertSee('#pricing');
});

// ── الحفظ ───────────────────────────────────────────────────────

it('يحفظ الحقول الأربعة ويعود إلى تبويبها', function () {
    actingAs($this->admin)->put(route('admin.settings.cta.update'), ctaPayload())
        ->assertRedirect(route('admin.settings.index', ['tab' => 'cta']));

    expect(Setting::get('cta.title'))->toBe('عنوان البانر')
        ->and(Setting::get('cta.sub_title'))->toBe('نصّ تمهيدي للبانر.')
        ->and(Setting::get('cta.btn_text'))->toBe('ابدأ الآن')
        ->and(Setting::get('cta.btn_link'))->toBe('/register');
});

it('يكتب فوق القيمة القائمة بلا تكرار الصفّ', function () {
    $this->seed(CtaSettingSeeder::class);

    actingAs($this->admin)->put(route('admin.settings.cta.update'), ctaPayload(['title' => 'عنوان جديد']));

    expect(Setting::where('key', 'cta.title')->count())->toBe(1)
        ->and(Setting::get('cta.title'))->toBe('عنوان جديد');
});

// ── التحقّق ─────────────────────────────────────────────────────

it('يرفض الحقول الإجبارية الفارغة ولا يكتب شيئاً', function () {
    foreach (['title', 'btn_text', 'btn_link'] as $field) {
        actingAs($this->admin)
            ->put(route('admin.settings.cta.update'), ctaPayload([$field => '']))
            ->assertSessionHasErrors('cta.'.$field);
    }

    expect(Setting::count())->toBe(0);
});

it('يقبل النصّ التمهيدي فارغاً — إخفاؤه قرار تصميم لا خطأ', function () {
    actingAs($this->admin)
        ->put(route('admin.settings.cta.update'), ctaPayload(['sub_title' => '']))
        ->assertSessionHasNoErrors();

    // ⚠️ null لا '': وسيط ConvertEmptyStringsToNull يحوّل الحقل الفارغ قبل
    // أن يصل الكنترولر. الصفّ **موجود** بقيمة فارغة — وهذا ما يميّزه عن
    // «لم يُبذَر بعد»، فيُخفي العرض الفقرة بدل أن يُرجع الافتراضي.
    expect(Setting::get('cta.sub_title'))->toBeNull()
        ->and(Setting::where('key', 'cta.sub_title')->exists())->toBeTrue()
        ->and(Setting::cta()['sub_title'])->toBe('');
});

it('يرفض مخطَّطات الوجهة الخطرة ويقبل الصيغ الثلاث المسموحة', function () {
    // 🔴 القيمة تهبط في href على صفحة عامة — javascript: فيها تنفيذ في
    // متصفّح كل زائر، لا مجرّد رابط مكسور.
    foreach (['javascript:alert(1)', 'data:text/html,x', 'register', 'ftp://x.test'] as $bad) {
        actingAs($this->admin)
            ->put(route('admin.settings.cta.update'), ctaPayload(['btn_link' => $bad]))
            ->assertSessionHasErrors('cta.btn_link');
    }

    expect(Setting::count())->toBe(0);

    foreach (['/register', '#pricing', 'https://paledu.test/x'] as $good) {
        actingAs($this->admin)
            ->put(route('admin.settings.cta.update'), ctaPayload(['btn_link' => $good]))
            ->assertSessionHasNoErrors();

        expect(Setting::get('cta.btn_link'))->toBe($good);
    }
});

it('يعيد أخطاء التحقّق إلى تبويب البانر لا إلى أول تبويب', function () {
    // ⚠️ back() الافتراضية تتبع ترويسة referer، وهي غائبة هنا كما قد تغيب
    // في متصفّح يحجبها — فتهبط الأخطاء على لوح مخفيّ ويبدو الحفظ صامتاً.
    actingAs($this->admin)
        ->put(route('admin.settings.cta.update'), ctaPayload(['title' => '']))
        ->assertRedirect(route('admin.settings.index', ['tab' => 'cta']));
});

// ── الصفحة الرئيسية ─────────────────────────────────────────────

it('تعرض الصفحة الرئيسية القيم المحفوظة لا نصّاً ثابتاً', function () {
    actingAs($this->admin)->put(route('admin.settings.cta.update'), ctaPayload([
        'title' => 'عنوان من القاعدة',
        'sub_title' => 'تمهيد من القاعدة',
        'btn_text' => 'زرّ من القاعدة',
        'btn_link' => '/register',
    ]));

    get(route('home'))
        ->assertOk()
        ->assertSee('عنوان من القاعدة')
        ->assertSee('تمهيد من القاعدة')
        ->assertSee('زرّ من القاعدة');
});

it('تُسقط الصفحة الرئيسية النصّ التمهيدي عند مسحه ولا تُرجع الافتراضي', function () {
    $this->seed(CtaSettingSeeder::class);

    actingAs($this->admin)->put(route('admin.settings.cta.update'), ctaPayload(['sub_title' => '']));

    get(route('home'))
        ->assertOk()
        ->assertDontSee('جرّب المنصة كاملة قبل أن تدفع شيكلاً واحداً، ودون بطاقة ائتمان.');
});

it('تعرض الصفحة الرئيسية الافتراضي حين لا يوجد صفّ أصلاً', function () {
    // قاعدة سبقت هذه الميزة: لا صفوف cta. البانر يجب أن يبقى مكتملاً.
    expect(Setting::count())->toBe(0);

    get(route('home'))
        ->assertOk()
        ->assertSee('أول محاضرة من كل مادة — مجاناً')
        ->assertSee('ابدأ التجربة');
});
