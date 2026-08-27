<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->branch = Branch::create(['name' => 'علمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

// ═══ المستخدمون ═══════════════════════════════════════════════

it('يعرض قائمة المستخدمين ويفلترها بالنوع', function () {
    actingAs($this->admin)->get(route('admin.users.index'))
        ->assertOk()->assertSee('أدمن')->assertSee('طالب');

    actingAs($this->admin)->get(route('admin.users.index', ['type' => 'admin']))
        ->assertOk()->assertSee('أدمن')->assertDontSee('student@test.local');
});

it('ينشئ طالباً بسجلّ مجال ودور في معاملة واحدة', function () {
    actingAs($this->admin)->post(route('admin.users.store'), [
        'type' => 'student',
        'full_name' => 'طالب جديد',
        'email' => 'new@test.local',
        'password' => 'Password123', 'password_confirmation' => 'Password123',
        'branch_id' => $this->branch->id,
        'is_active' => '1',
    ])->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'new@test.local')->sole();

    // 🔴 الحساب وسجلّ المجال والدور — ثلاثتها معاً أو لا شيء.
    expect($user->userable)->toBeInstanceOf(Student::class)
        ->and($user->userable->full_name)->toBe('طالب جديد')
        ->and($user->userable->branch_id)->toBe($this->branch->id)
        ->and($user->hasRole('student'))->toBeTrue();
});

it('يرفض طالباً بلا فرع ويقبل معلّماً بلا فرع', function () {
    $payload = [
        'full_name' => 'س', 'email' => 'a@test.local',
        'password' => 'Password123', 'password_confirmation' => 'Password123',
    ];

    // students.branch_id إجباري بمفتاح أجنبي — لا طالب بلا فرع.
    actingAs($this->admin)->post(route('admin.users.store'), [...$payload, 'type' => 'student'])
        ->assertSessionHasErrors('branch_id');

    // والمعلّم لا فرع له أصلاً.
    actingAs($this->admin)->post(route('admin.users.store'), [
        ...$payload, 'type' => 'teacher', 'email' => 'b@test.local',
    ])->assertRedirect(route('admin.users.index'));

    expect(User::where('email', 'b@test.local')->sole()->userable)->toBeInstanceOf(Teacher::class);
});

it('يُبقي كلمة المرور عند تركها فارغة في التعديل', function () {
    $before = $this->student->password;

    actingAs($this->admin)->put(route('admin.users.update', $this->student), [
        'full_name' => 'طالب معدَّل', 'email' => 'student@test.local',
        'branch_id' => $this->branch->id, 'is_active' => '1',
    ])->assertRedirect(route('admin.users.index'));

    expect($this->student->fresh()->password)->toBe($before)
        ->and($this->student->fresh()->userable->full_name)->toBe('طالب معدَّل');
});

it('يمنع الأدمن من حذف حسابه أو تعطيله', function () {
    actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin))
        ->assertRedirect();
    actingAs($this->admin)->patch(route('admin.users.toggle', $this->admin))
        ->assertRedirect();

    expect(User::withTrashed()->find($this->admin->id))->not->toBeNull()
        ->and($this->admin->fresh()->is_active)->toBeTrue();
});

it('يمنع حذف آخر حساب أدمن', function () {
    // الأدمن الوحيد هو الفاعل نفسه، فالحارسان يتراكبان — نُنشئ ثانياً
    // لنعزل حارس «آخر أدمن» عن حارس «حسابك».
    $other = tap(Admin::create(['full_name' => 'أدمن ٢'])->user()->create([
        'email' => 'admin2@test.local', 'password' => bcrypt('x'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    actingAs($this->admin)->delete(route('admin.users.destroy', $other));
    expect(User::whereNotNull('id')->where('userable_type', Admin::class)->count())->toBe(1);

    // الأخير لا يُحذف — ولو حاول أدمن آخر.
    actingAs($other)->delete(route('admin.users.destroy', $this->admin));
    expect(User::where('userable_type', Admin::class)->count())->toBe(1);
});

it('يحذف الحساب وسجلّ مجاله معاً حذفاً ناعماً', function () {
    actingAs($this->admin)->delete(route('admin.users.destroy', $this->student))
        ->assertRedirect(route('admin.users.index'));

    // 🔴 حساب حيّ يشير إلى سجلّ مجال محذوف = حساب يدخل إلى فراغ.
    expect(User::find($this->student->id))->toBeNull()
        ->and(Student::count())->toBe(0)
        ->and(Student::withTrashed()->count())->toBe(1);
});

it('يمنع الطالب من كل مسارات المستخدمين', function () {
    actingAs($this->student)->get(route('admin.users.index'))->assertForbidden();
    actingAs($this->student)->get(route('admin.users.create'))->assertForbidden();
    actingAs($this->student)->delete(route('admin.users.destroy', $this->admin))->assertForbidden();
});

// ═══ الأدوار ══════════════════════════════════════════════════

it('ينشئ دوراً بصلاحياته ويعدّله ويحذفه', function () {
    actingAs($this->admin)->post(route('admin.roles.store'), [
        'name' => 'content-editor',
        'permissions' => ['view-sliders', 'edit-sliders'],
    ])->assertRedirect();

    $role = Role::findByName('content-editor', 'web');
    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['edit-sliders', 'view-sliders']);

    actingAs($this->admin)->put(route('admin.roles.update', $role), [
        'name' => 'content-manager', 'permissions' => ['view-sliders'],
    ])->assertRedirect();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect($role->fresh()->name)->toBe('content-manager')
        ->and($role->fresh()->permissions)->toHaveCount(1);

    actingAs($this->admin)->delete(route('admin.roles.destroy', $role->fresh()))
        ->assertRedirect(route('admin.roles.index'));

    expect(Role::where('name', 'content-manager')->exists())->toBeFalse();
});

it('يرفض اسم دور بغير الصيغة المسموحة أو مكرّراً', function () {
    actingAs($this->admin)->post(route('admin.roles.store'), ['name' => 'محرّر محتوى'])
        ->assertSessionHasErrors('name');

    actingAs($this->admin)->post(route('admin.roles.store'), ['name' => 'admin'])
        ->assertSessionHasErrors('name');
});

it('يمنع حذف الأدوار النظامية الثلاثة أو تغيير أسمائها', function () {
    foreach (['student', 'teacher', 'admin'] as $name) {
        $role = Role::findByName($name, 'web');

        actingAs($this->admin)->delete(route('admin.roles.destroy', $role));
        expect(Role::where('name', $name)->exists())->toBeTrue();

        // 🔴 الاسم يبقى: User::roleName() تشتقّ الدور من userable_type بهذه
        // الأسماء حرفياً، فتغييره يترك كل حساب بلا صلاحيات بصمت.
        actingAs($this->admin)->put(route('admin.roles.update', $role), [
            'name' => "{$name}-renamed", 'permissions' => [],
        ]);
        expect(Role::where('name', $name)->exists())->toBeTrue()
            ->and(Role::where('name', "{$name}-renamed")->exists())->toBeFalse();
    }
});

it('يمنع حذف دور يحمله مستخدمون', function () {
    actingAs($this->admin)->post(route('admin.roles.store'), ['name' => 'reviewer']);
    $role = Role::findByName('reviewer', 'web');
    $this->student->assignRole($role);

    actingAs($this->admin)->delete(route('admin.roles.destroy', $role));

    expect(Role::where('name', 'reviewer')->exists())->toBeTrue();
});

// ═══ مفاتيح الصلاحيات ═════════════════════════════════════════

it('يبدّل وحدة كاملة بنداء واحد', function () {
    $role = Role::findByName('teacher', 'web');

    actingAs($this->admin)->patchJson(route('admin.roles.permissions.group', $role), [
        'group' => 'sliders', 'granted' => true,
    ])->assertOk()->assertJson(['ok' => true]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // نداء واحد للقسم لا نداء لكل صلاحية: أربعة طلبات متسابقة على مخزن
    // Spatie المؤقّت تجعل آخرها يفوز فتضيع البقية.
    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['create-sliders', 'delete-sliders', 'edit-sliders', 'view-sliders']);

    actingAs($this->admin)->patchJson(route('admin.roles.permissions.group', $role), [
        'group' => 'sliders', 'granted' => false,
    ])->assertOk();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect($role->fresh()->permissions)->toHaveCount(0);
});

it('يستثني الصلاحية المقفلة من إطفاء القسم بدل أن يفشل كاملاً', function () {
    $adminRole = Role::findByName('admin', 'web');

    actingAs($this->admin)->patchJson(route('admin.roles.permissions.group', $adminRole), [
        'group' => 'roles', 'granted' => false,
    ])->assertOk();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // manage-roles باقية، والبقية أُطفئت — لا رفض للطلب كلّه.
    expect($adminRole->fresh()->hasPermissionTo('manage-roles'))->toBeTrue()
        ->and($adminRole->fresh()->hasPermissionTo('view-roles'))->toBeFalse();
});

it('يرفض تبديل صلاحية أو وحدة غير موجودة في الفهرس', function () {
    $role = Role::findByName('teacher', 'web');

    actingAs($this->admin)->patchJson(route('admin.roles.permissions.toggle', $role), [
        'permission' => 'drop-database', 'granted' => true,
    ])->assertStatus(422);

    actingAs($this->admin)->patchJson(route('admin.roles.permissions.group', $role), [
        'group' => 'nope', 'granted' => true,
    ])->assertStatus(422);
});

it('يعرض شبكة الصلاحيات بعمودين وبمفاتيح لكل وحدة', function () {
    $role = Role::findByName('teacher', 'web');

    actingAs($this->admin)->get(route('admin.roles.edit', $role))
        ->assertOk()
        ->assertSee('md:grid-cols-2', false)      // التخطيط عمودان
        ->assertSee('permissionGrid(', false)     // مكوّن Alpine
        ->assertSee('الهيرو')                      // عنوان وحدة
        ->assertSee('view-sliders');
});

it('لا يعرض نصّ التراجع عن JavaScript', function () {
    $role = Role::findByName('teacher', 'web');

    actingAs($this->admin)->get(route('admin.roles.edit', $role))
        ->assertOk()
        ->assertDontSee('زرّ الحفظ للحالات التي يكون فيها JavaScript معطّلاً');
});

it('يربط مفتاح القسم بحالة أبنائه ثنائياً لا بحالة ثالثة', function () {
    $role = Role::findByName('teacher', 'web');

    $html = actingAs($this->admin)->get(route('admin.roles.edit', $role))->getContent();

    // 🔴 المفتاح يقرأ all() ولا يخزّن حالته: هذا هو التزامن في الاتجاهين.
    // تشغيله يشغّل الأبناء، وإطفاء ابن واحد يجعل all() false فينطفئ فوراً.
    expect($html)->toContain(":checked=\"all('sliders')\"")
        ->toContain("toggleGroup('sliders', \$event.target.checked)");

    // ⚠️ لا حالة ثالثة «بعض لا كل» على المفتاح — العدّاد يحمل هذا المعنى.
    expect($html)->not->toContain("some('sliders')");

    // مفتاح لكل وحدة، لا مفتاح واحد للصفحة.
    foreach (array_keys(PermissionCatalog::groups()) as $group) {
        expect($html)->toContain(":checked=\"all('{$group}')\"");
    }
});

it('يسجّل مكوّن Alpine قبل استدعاء start', function () {
    $js = file_get_contents(resource_path('js/app.js'));

    // 🔴 start() تُطلق `alpine:init` **تزامنياً**. أي Alpine.data() بعدها
    // يُسجَّل بعد فوات الحدث فلا يوجد المكوّن أصلاً — والعطل صامت تماماً:
    // x-text لا يكتب شيئاً، و:class لا يُطبَّق فيظهر المفتاح بلا خلفية،
    // أي غير مرئي. وقع هذا فعلاً وكسر كل مفاتيح الصلاحيات.
    $register = strrpos($js, "Alpine.data('permissionGrid'");
    $start = strrpos($js, 'Alpine.start()');

    expect($register)->not->toBeFalse()
        ->and($start)->not->toBeFalse()
        ->and($start)->toBeGreaterThan($register);
});
