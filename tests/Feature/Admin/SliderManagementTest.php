<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Media;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    // الأدوار والصلاحيات تُبذَر صراحةً: هجرة الردم تُنشئ الأدوار وحدها.
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
 * ملف صورة مزيّف بلا امتداد GD.
 *
 * ⚠️ UploadedFile::fake()->image() يستدعي imagecreatetruecolor، وGD غير
 * مثبَّت في بيئة XAMPP هذه (`extension=gd` معطّل في php.ini). create()
 * بنوع MIME صريح يكفي: قاعدة `image` تتحقّق من النوع لا من البكسلات.
 */
function fakeImage(string $name): UploadedFile
{
    return UploadedFile::fake()->create($name, 512, 'image/jpeg');
}

function sliderPayload(array $overrides = []): array
{
    return array_merge([
        'badge_text' => 'شارة',
        'title' => 'عنوان الشريحة',
        'sub_title' => 'نصّ تمهيدي',
        'btn1_text' => 'ابدأ',
        'btn1_link' => '/register',
        'btn2_text' => 'الفروع',
        'btn2_link' => '#branches',
        'display_order' => 1,
        'is_active' => '1',
        'alt_text' => 'وصف الصورة',
    ], $overrides);
}

// ── الحراسة ─────────────────────────────────────────────────────

it('يمنع الزائر من كل مسارات الأدمن', function () {
    get(route('admin.sliders.index'))->assertRedirect(route('auth.login'));
    get(route('admin.settings.navbar'))->assertRedirect(route('auth.login'));
});

it('يمنع الطالب من الشرائح والإعدادات — 403 لا إخفاء رابط', function () {
    actingAs($this->student)->get(route('admin.sliders.index'))->assertForbidden();
    actingAs($this->student)->get(route('admin.settings.navbar'))->assertForbidden();

    // 🔴 الأهم: الطلب المباشر بـPOST يُرفض أيضاً، لا الشاشة وحدها.
    actingAs($this->student)->post(route('admin.sliders.store'), sliderPayload())
        ->assertForbidden();

    expect(Slider::count())->toBe(0);
});

it('يرفض أدمناً نُزعت منه الصلاحية رغم بقاء دوره', function () {
    // 🔴 النزع من **الدور** لا من المستخدم: الصلاحية ممنوحة عبر الدور،
    // و revokePermissionTo على المستخدم ينزع منحاً مباشراً لم يوجد أصلاً
    // فلا يفعل شيئاً — ويمرّ الاختبار كاذباً لو كان يؤكّد 200.
    Role::findByName('admin', 'web')->revokePermissionTo('view-sliders');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($this->admin)->get(route('admin.sliders.index'))->assertForbidden();

    // الدور باقٍ، والصلاحية الأخرى باقية — الحراستان مستقلّتان.
    actingAs($this->admin)->get(route('admin.settings.navbar'))->assertOk();
});

// ── الشرائح ─────────────────────────────────────────────────────

it('يعرض الأدمن قائمة الشرائح ونموذج الإنشاء', function () {
    actingAs($this->admin)->get(route('admin.sliders.index'))->assertOk();
    actingAs($this->admin)->get(route('admin.sliders.create'))->assertOk();
});

it('يعرض شاشة التعديل بقيم الشريحة وصورتها الحالية', function () {
    Storage::fake('public');

    actingAs($this->admin)->post(route('admin.sliders.store'), sliderPayload([
        'image' => fakeImage('current.jpg'),
    ]));

    $slider = Slider::sole();

    // assertOk وحدها تُثبت أن Blade تُرجم بلا خطأ؛ assertSee تُثبت أن
    // القيم وصلت الحقول فعلاً — الفرق بين «الصفحة عملت» و«الصفحة صحيحة».
    actingAs($this->admin)->get(route('admin.sliders.edit', $slider))
        ->assertOk()
        ->assertSee('عنوان الشريحة')
        ->assertSee('/register')
        ->assertSee('#branches')
        ->assertSee($slider->image->path);   // معاينة الصورة الحالية
});

it('يعرض شاشة إعدادات الشريط العلوي بالقيم المحفوظة', function () {
    Setting::put('navbar.social_link1', 'https://facebook.com/paledu');

    actingAs($this->admin)->get(route('admin.settings.navbar'))
        ->assertOk()
        ->assertSee('https://facebook.com/paledu');
});

it('يعرض حالة القائمة الفارغة بدل جدول فارغ', function () {
    actingAs($this->admin)->get(route('admin.sliders.index'))
        ->assertOk()
        ->assertSee(__('admin.sliders_empty'));
});

it('ينشئ شريحة ويربط الصورة بعلاقة متعدّدة الأشكال', function () {
    Storage::fake('public');

    actingAs($this->admin)->post(route('admin.sliders.store'), sliderPayload([
        'image' => fakeImage('hero.jpg'),
    ]))->assertRedirect(route('admin.sliders.index'));

    $slider = Slider::sole();

    expect($slider)
        ->badge_text->toBe('شارة')->title->toBe('عنوان الشريحة')
        ->btn1_link->toBe('/register')->btn2_link->toBe('#branches')
        ->is_active->toBeTrue();

    expect($slider->image)->not->toBeNull()
        ->mediable_type->toBe(Slider::class)
        ->mediable_id->toBe($slider->id)
        ->alt_text->toBe('وصف الصورة');

    // الملف وصل القرص فعلاً، والمسار مخزَّن بالبادئة التي يخدمها asset().
    expect($slider->image->path)->toStartWith('storage/sliders/');
    Storage::disk('public')->assertExists(str_replace('storage/', '', $slider->image->path));
});

it('يرفض إنشاء شريحة بلا صورة أو بنصّ زرّ بلا رابط', function () {
    actingAs($this->admin)->post(route('admin.sliders.store'), sliderPayload())
        ->assertSessionHasErrors('image');

    Storage::fake('public');

    actingAs($this->admin)->post(route('admin.sliders.store'), sliderPayload([
        'image' => fakeImage('a.jpg'),
        'btn1_link' => '',   // نصّ بلا رابط = زرّ ميّت
    ]))->assertSessionHasErrors('btn1_link');

    expect(Slider::count())->toBe(0);
});

it('يستبدل الصورة عند التعديل ولا يُراكم صفوف وسائط', function () {
    Storage::fake('public');

    actingAs($this->admin)->post(route('admin.sliders.store'), sliderPayload([
        'image' => fakeImage('first.jpg'),
    ]));

    $slider = Slider::sole();
    $firstPath = $slider->image->path;

    actingAs($this->admin)->put(route('admin.sliders.update', $slider), sliderPayload([
        'title' => 'عنوان معدَّل',
        'image' => fakeImage('second.jpg'),
    ]))->assertRedirect(route('admin.sliders.index'));

    $slider->refresh()->load('image');

    expect($slider->title)->toBe('عنوان معدَّل')
        ->and(Media::count())->toBe(1)              // استُبدل لا أُضيف
        ->and($slider->image->path)->not->toBe($firstPath);

    // الملف القديم نُظّف بعد نجاح الجديد.
    Storage::disk('public')->assertMissing(str_replace('storage/', '', $firstPath));
});

it('يُبقي الصورة الحالية عند تعديل بلا ملف جديد', function () {
    Storage::fake('public');

    actingAs($this->admin)->post(route('admin.sliders.store'), sliderPayload([
        'image' => fakeImage('keep.jpg'),
    ]));

    $slider = Slider::sole();
    $path = $slider->image->path;

    actingAs($this->admin)->put(route('admin.sliders.update', $slider), sliderPayload([
        'title' => 'عنوان آخر', 'alt_text' => 'وصف جديد',
    ]))->assertRedirect(route('admin.sliders.index'));

    expect($slider->fresh()->load('image')->image)
        ->path->toBe($path)                 // الصورة لم تُمَسّ
        ->alt_text->toBe('وصف جديد');       // والنصّ البديل تحدّث
});

it('يحذف الشريحة حذفاً ناعماً وتحتفظ بصورتها للاسترجاع', function () {
    Storage::fake('public');

    actingAs($this->admin)->post(route('admin.sliders.store'), sliderPayload([
        'image' => fakeImage('x.jpg'),
    ]));

    $slider = Slider::sole();

    // back() لا redirect ثابت: الحذف يقع من القائمة ومن السلّة، والعودة
    // إلى حيث كان المستخدم أصحّ من رميه إلى شاشة ثابتة.
    actingAs($this->admin)
        ->from(route('admin.sliders.index'))
        ->delete(route('admin.sliders.destroy', $slider))
        ->assertRedirect(route('admin.sliders.index'));

    expect(Slider::count())->toBe(0)
        ->and(Slider::withTrashed()->count())->toBe(1)
        ->and(Media::count())->toBe(1);

    // ⚠️ إعادة الجلب بـwithTrashed إجبارية. استدعاء restore() على النسخة
    // المجلوبة **قبل** الحذف لا يفعل شيئاً: deleted_at في الذاكرة ما زال
    // null، فإسناد null إليه لا يجعله متّسخاً، و save() لا يُصدر أي UPDATE.
    // لا خطأ ولا تحذير — الصفّ يبقى محذوفاً والاختبار يبدو كأنه فشل منتج.
    Slider::withTrashed()->sole()->restore();

    expect(Slider::sole()->image)->not->toBeNull();
});

// ── إعدادات الشريط العلوي ───────────────────────────────────────

it('يحفظ روابط التواصل الثلاثة', function () {
    actingAs($this->admin)->put(route('admin.settings.navbar.update'), [
        'social_link1' => 'https://facebook.com/paledu',
        'social_link2' => 'https://instagram.com/paledu',
        'social_link3' => null,
    ])->assertRedirect(route('admin.settings.navbar'));

    expect(Setting::get('navbar.social_link1'))->toBe('https://facebook.com/paledu')
        ->and(Setting::get('navbar.social_link2'))->toBe('https://instagram.com/paledu')
        ->and(Setting::get('navbar.social_link3'))->toBeNull();
});

it('يرفض رابطاً غير https ويرفض ما ليس رابطاً', function () {
    actingAs($this->admin)->put(route('admin.settings.navbar.update'), [
        'social_link1' => 'http://facebook.com/paledu',   // غير مشفّر
    ])->assertSessionHasErrors('social_link1');

    actingAs($this->admin)->put(route('admin.settings.navbar.update'), [
        'social_link2' => 'ليس رابطاً',
    ])->assertSessionHasErrors('social_link2');

    expect(Setting::get('navbar.social_link1'))->toBeNull();
});

it('لا يكرّر صفّ الإعداد عند الحفظ المتكرّر', function () {
    foreach (['https://a.test/1', 'https://a.test/2'] as $url) {
        actingAs($this->admin)->put(route('admin.settings.navbar.update'), ['social_link1' => $url]);
    }

    expect(Setting::where('key', 'navbar.social_link1')->count())->toBe(1)
        ->and(Setting::get('navbar.social_link1'))->toBe('https://a.test/2');
});
