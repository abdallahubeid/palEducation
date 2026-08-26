<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Media;
use App\Models\Slider;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

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

function makeSlider(array $overrides = []): Slider
{
    $slider = Slider::create(array_merge([
        'title' => 'شريحة', 'display_order' => 1, 'is_active' => true,
    ], $overrides));

    $slider->media()->create([
        'path' => 'storage/sliders/'.uniqid().'.jpg', 'type' => 'image',
    ]);

    return $slider->load('image');
}

// ── التوجيه بعد الدخول ──────────────────────────────────────────

it('يوجّه الأدمن إلى الشرائح بعد الدخول، والطالب إلى لوحته', function () {
    expect($this->admin->homeRoute())->toBe(route('admin.dashboard'))
        ->and($this->student->homeRoute())->toBe(route('student.dashboard'));
});

// ── لوحة التحكّم ────────────────────────────────────────────────

it('يعرض لوحة التحكّم بمؤشّراتها وقائمة الفروع', function () {
    makeSlider(['title' => 'شريحة ظاهرة']);

    actingAs($this->admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('لوحة التحكّم')
        ->assertSee('شريحة ظاهرة')
        ->assertSee('علمي')            // الفرع من beforeEach
        ->assertSee('المحاضرات');       // بطاقة مؤشّر
});

it('يمنع الطالب من لوحة التحكّم', function () {
    actingAs($this->student)->get(route('admin.dashboard'))->assertForbidden();
});

// ── سلّة المحذوفات ──────────────────────────────────────────────

it('يعرض سلّة المحذوفات مع الشرائح المحذوفة وحدها', function () {
    makeSlider(['title' => 'باقية']);
    makeSlider(['title' => 'محذوفة'])->delete();

    actingAs($this->admin)->get(route('admin.trash.index'))
        ->assertOk()
        ->assertSee('محذوفة')
        ->assertDontSee('باقية');
});

it('يستعيد شريحة من السلّة بصورتها', function () {
    $slider = makeSlider();
    $slider->delete();

    actingAs($this->admin)
        ->from(route('admin.trash.index'))
        ->patch(route('admin.trash.restore', ['type' => 'sliders', 'id' => $slider->id]))
        ->assertRedirect(route('admin.trash.index'));

    expect(Slider::count())->toBe(1)
        ->and(Slider::sole()->image)->not->toBeNull();
});

it('يمحو نهائياً الشريحة وصفوف وسائطها وملفها', function () {
    Storage::fake('public');

    $slider = makeSlider();
    $path = str_replace('storage/', '', $slider->image->path);
    Storage::disk('public')->put($path, 'x');
    $slider->delete();

    actingAs($this->admin)
        ->from(route('admin.trash.index'))
        ->delete(route('admin.trash.force-destroy', ['type' => 'sliders', 'id' => $slider->id]))
        ->assertRedirect(route('admin.trash.index'));

    // 🔴 mediable_id بلا مفتاح أجنبي، فلا تتالي يمسح media. التنظيف يدوي،
    // وغيابه كان سينتج صفوفاً يتيمة وملفات لا يعرف أحد أنها فائضة.
    expect(Slider::withTrashed()->count())->toBe(0)
        ->and(Media::withTrashed()->count())->toBe(0);

    Storage::disk('public')->assertMissing($path);
});

it('لا يمحو الحذف النهائي أصلاً مبذوراً تحت public/images', function () {
    Storage::fake('public');

    $slider = Slider::create(['title' => 'مبذورة']);
    $slider->media()->create(['path' => 'images/hero/slide-1-focus.jpg', 'type' => 'image']);
    $slider->delete();

    actingAs($this->admin)->delete(route('admin.trash.force-destroy', ['type' => 'sliders', 'id' => $slider->id]));

    // الصفّ ذهب، وملف المستودع لم يُلمس — حذفه يكسر البذرة في كل بيئة.
    expect(Slider::withTrashed()->count())->toBe(0);
    Storage::disk('public')->assertMissing('images/hero/slide-1-focus.jpg');
});

it('يمنع الطالب من كل مسارات السلّة', function () {
    $slider = makeSlider();
    $slider->delete();

    actingAs($this->student)->get(route('admin.trash.index'))->assertForbidden();
    actingAs($this->student)->patch(route('admin.trash.restore', ['type' => 'sliders', 'id' => $slider->id]))->assertForbidden();
    actingAs($this->student)->delete(route('admin.trash.force-destroy', ['type' => 'sliders', 'id' => $slider->id]))->assertForbidden();

    expect(Slider::withTrashed()->count())->toBe(1);
});

// ── عمود الترقيم ────────────────────────────────────────────────

it('يعرض عمود # في كل جدول', function () {
    makeSlider();
    makeSlider(['title' => 'ثانية'])->delete();

    // ⚠️ التأكيد على **بنية** الترويسة لا على سلسلة الأصناف كاملة: ربط
    // الاختبار بأصناف Tailwind يجعله يسقط عند أي تعديل بصري بلا عطل حقيقي.
    foreach (['admin.sliders.index', 'admin.trash.index', 'admin.roles.index'] as $route) {
        actingAs($this->admin)->get(route($route))
            ->assertOk()
            ->assertSee('>#</th>', false);
    }
});

// ── تبديل الحالة ────────────────────────────────────────────────

it('يبدّل ظهور الشريحة من القائمة', function () {
    $slider = makeSlider(['is_active' => true]);

    actingAs($this->admin)
        ->from(route('admin.sliders.index'))
        ->patch(route('admin.sliders.toggle', $slider))
        ->assertRedirect(route('admin.sliders.index'));

    expect($slider->fresh()->is_active)->toBeFalse();
});

// ── الأدوار والصلاحيات ──────────────────────────────────────────

it('يعرض الأدوار الثلاثة وعدد صلاحيات كل دور', function () {
    actingAs($this->admin)->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('student')
        ->assertSee('teacher')
        ->assertSee('admin');
});

it('يعرض شاشة الصلاحيات بمفاتيح التبديل', function () {
    $role = Role::findByName('teacher', 'web');

    actingAs($this->admin)->get(route('admin.roles.edit', $role))
        ->assertOk()
        ->assertSee('view-sliders')
        ->assertSee(__('admin.perm_manage-settings'));
});

it('يمنح صلاحية وينزعها بتبديل واحد', function () {
    $role = Role::findByName('teacher', 'web');

    actingAs($this->admin)->patchJson(route('admin.roles.permissions.toggle', $role), [
        'permission' => 'view-sliders', 'granted' => true,
    ])->assertOk()->assertJson(['ok' => true]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect($role->fresh()->hasPermissionTo('view-sliders'))->toBeTrue();

    actingAs($this->admin)->patchJson(route('admin.roles.permissions.toggle', $role), [
        'permission' => 'view-sliders', 'granted' => false,
    ])->assertOk();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect($role->fresh()->hasPermissionTo('view-sliders'))->toBeFalse();
});

it('يمنع الأدمن من قفل نفسه خارج شاشة الأدوار', function () {
    $admin = Role::findByName('admin', 'web');

    // نزع manage-roles عن دور admin يُغلق الشاشة على كل أدمن بلا عودة
    // إلا من قاعدة البيانات. الحارس يرفض الطلب لا يُنفّذه بهدوء.
    actingAs($this->admin)->patchJson(route('admin.roles.permissions.toggle', $admin), [
        'permission' => 'manage-roles', 'granted' => false,
    ])->assertStatus(422)->assertJson(['ok' => false]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect($admin->fresh()->hasPermissionTo('manage-roles'))->toBeTrue();
});

it('يُبقي الصلاحية المقفلة حتى لو أسقطها الحفظ الجماعي', function () {
    $admin = Role::findByName('admin', 'web');

    // نموذج بلا manage-roles إطلاقاً — كأن المستخدم عبث بالـHTML.
    actingAs($this->admin)->put(route('admin.roles.update', $admin), [
        'permissions' => ['view-sliders'],
    ])->assertRedirect(route('admin.roles.edit', $admin));

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($admin->fresh()->hasPermissionTo('manage-roles'))->toBeTrue()
        ->and($admin->fresh()->hasPermissionTo('view-sliders'))->toBeTrue()
        ->and($admin->fresh()->hasPermissionTo('manage-settings'))->toBeFalse();
});

it('يمنع الطالب من إدارة الأدوار', function () {
    $role = Role::findByName('teacher', 'web');

    actingAs($this->student)->get(route('admin.roles.index'))->assertForbidden();
    actingAs($this->student)->patchJson(route('admin.roles.permissions.toggle', $role), [
        'permission' => 'view-sliders', 'granted' => true,
    ])->assertForbidden();
});

// ── السلّة المركزية: تبويبات وإجراءات جماعية ───────────────────

it('يفلتر السلّة بالنوع ويرفض نوعاً مجهولاً بلا خطأ', function () {
    makeSlider(['title' => 'محذوفة'])->delete();

    actingAs($this->admin)->get(route('admin.trash.index', ['type' => 'sliders']))
        ->assertOk()->assertSee('محذوفة');

    // نوع مجهول يُهمَل ويعود لعرض «الكل» — لا 500 ولا 404.
    actingAs($this->admin)->get(route('admin.trash.index', ['type' => 'nope']))
        ->assertOk()->assertSee('محذوفة');
});

it('يستعيد المحدَّد دفعةً واحدة', function () {
    $a = tap(makeSlider(['title' => 'أ']))->delete();
    $b = tap(makeSlider(['title' => 'ب']))->delete();
    $c = tap(makeSlider(['title' => 'ج']))->delete();

    actingAs($this->admin)->patch(route('admin.trash.restore-selected'), [
        'items' => ["sliders:{$a->id}", "sliders:{$b->id}"],
    ])->assertRedirect(route('admin.trash.index'));

    expect(Slider::count())->toBe(2)
        ->and(Slider::onlyTrashed()->pluck('id')->all())->toBe([$c->id]);
});

it('يمحو المحدَّد نهائياً دفعةً واحدة', function () {
    $a = tap(makeSlider(['title' => 'أ']))->delete();
    $b = tap(makeSlider(['title' => 'ب']))->delete();

    actingAs($this->admin)->delete(route('admin.trash.force-selected'), [
        'items' => ["sliders:{$a->id}"],
    ])->assertRedirect(route('admin.trash.index'));

    expect(Slider::withTrashed()->pluck('id')->all())->toBe([$b->id]);
});

it('يتجاهل الرموز المزوَّرة بصمت بدل أن يسقط', function () {
    $a = tap(makeSlider())->delete();

    // 🔴 الرموز تأتي من نموذج يستطيع المستخدم تحريره: نوع مجهول، معرّف
    // غير رقمي، وصيغة بلا نقطتين — ثلاثتها تُهمَل ولا تُنفَّذ.
    actingAs($this->admin)->patch(route('admin.trash.restore-selected'), [
        'items' => ['users:1', 'sliders:abc', 'garbage', "sliders:{$a->id}"],
    ])->assertRedirect(route('admin.trash.index'));

    expect(Slider::count())->toBe(1);   // الصحيح وحده نُفِّذ
});

it('يستعيد الكل ويفرّغ السلّة', function () {
    tap(makeSlider(['title' => 'أ']))->delete();
    tap(makeSlider(['title' => 'ب']))->delete();

    actingAs($this->admin)->patch(route('admin.trash.restore-all'));
    expect(Slider::count())->toBe(2);

    Slider::query()->get()->each->delete();

    actingAs($this->admin)->delete(route('admin.trash.empty'));
    expect(Slider::withTrashed()->count())->toBe(0);
});

it('يمنع الطالب من الإجراءات الجماعية', function () {
    $a = tap(makeSlider())->delete();

    actingAs($this->student)->patch(route('admin.trash.restore-selected'), [
        'items' => ["sliders:{$a->id}"],
    ])->assertForbidden();

    actingAs($this->student)->delete(route('admin.trash.empty'))->assertForbidden();

    expect(Slider::withTrashed()->count())->toBe(1);
});

// ── الإعدادات والملف الشخصي ────────────────────────────────────

it('يعرض نظرة الإعدادات وقسم الشريط العلوي', function () {
    actingAs($this->admin)->get(route('admin.settings.index'))
        ->assertOk()->assertSee('الشريط العلوي');

    actingAs($this->admin)->get(route('admin.settings.navbar'))->assertOk();
});

it('يعرض الملف الشخصي ويحدّث الاسم على سجلّ المجال', function () {
    actingAs($this->admin)->get(route('admin.profile.edit'))
        ->assertOk()->assertSee('أدمن');

    actingAs($this->admin)->put(route('admin.profile.update'), [
        'full_name' => 'أدمن معدَّل', 'email' => 'admin@test.local', 'locale' => 'ar',
    ])->assertRedirect(route('admin.profile.edit'));

    // 🔴 الاسم على admins.full_name لا على users — لا عمود name هناك.
    expect($this->admin->fresh()->name)->toBe('أدمن معدَّل');
});

it('يمنع تغيير كلمة المرور بلا كلمة المرور الحالية', function () {
    actingAs($this->admin)->put(route('admin.profile.password'), [
        'current_password' => 'wrong', 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123',
    ])->assertSessionHasErrors('current_password');
});
