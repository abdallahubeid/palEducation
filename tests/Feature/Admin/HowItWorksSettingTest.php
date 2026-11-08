<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Step;
use App\Models\Student;
use Database\Seeders\HowSettingSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\StepSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| قسم «آلية العمل» — ترويسة من settings وخطوات من جدول steps.
|
| القسم نصفان يُداران معاً ويُعرضان معاً، فالاختبارات تغطّي الوصلتين:
| الترويسة نموذج ثابت، والخطوات قائمة متغيّرة الطول لها CRUD خاصّ.
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

function howPayload(array $overrides = []): array
{
    return ['how' => array_merge([
        'badge_text' => 'شارة القسم',
        'title' => 'عنوان القسم',
        'sub_title' => 'تمهيد القسم.',
    ], $overrides)];
}

function stepPayload(string $key, array $overrides = []): array
{
    return ['steps' => [$key => array_merge([
        'title' => 'خطوة جديدة',
        'sub_title' => 'شرح الخطوة.',
        'display_order' => 4,
    ], $overrides)]];
}

// ── البذور ──────────────────────────────────────────────────────

it('تبذر ترويسة القسم بقيمها المحدّدة', function () {
    $this->seed(HowSettingSeeder::class);

    expect(Setting::get('how.badge_text'))->toBe('آلية العمل')
        ->and(Setting::get('how.title'))->toBe('ثلاث خطوات تفصلك عن أول اختبار')
        ->and(Setting::get('how.sub_title'))->toBe('من إنشاء الحساب إلى أول تقييم — أقل من خمس دقائق.');
});

it('تبذر الخطوات الثلاث بترتيبها الدلالي', function () {
    $this->seed(StepSeeder::class);

    $titles = Step::ordered()->pluck('title')->all();

    expect($titles)->toBe(['حدّد فرعك', 'تابع محاضرتك', 'قِس فهمك فوراً'])
        ->and(Step::first()->sub_title)
        ->toBe('خطوة واحدة في بداية رحلتك، تُعيد ترتيب المنصة بأكملها حولك فلا يظهر لك إلا منهجك.');
});

it('لا تكرّر البذرة الخطوات ولا تمسح تحريراً عند إعادتها', function () {
    $this->seed(StepSeeder::class);
    Step::where('title', 'حدّد فرعك')->update(['sub_title' => 'شرح حرّره الأدمن']);

    $this->seed(StepSeeder::class);

    expect(Step::count())->toBe(3)
        ->and(Step::where('title', 'حدّد فرعك')->value('sub_title'))->toBe('شرح حرّره الأدمن');
});

// ── الحراسة ─────────────────────────────────────────────────────

it('يمنع الزائر والطالب من إدارة القسم', function () {
    $this->put(route('admin.settings.how.update'), howPayload())
        ->assertRedirect(route('auth.login'));

    actingAs($this->student)->put(route('admin.settings.how.update'), howPayload())
        ->assertForbidden();

    actingAs($this->student)->post(route('admin.settings.steps.store'), stepPayload('new'))
        ->assertForbidden();

    expect(Setting::count())->toBe(0)
        ->and(Step::count())->toBe(0);
});

// ── الترويسة ────────────────────────────────────────────────────

it('يحفظ ترويسة القسم ويعود إلى تبويبها', function () {
    actingAs($this->admin)->put(route('admin.settings.how.update'), howPayload())
        ->assertRedirect(route('admin.settings.index', ['tab' => 'how']));

    expect(Setting::get('how.badge_text'))->toBe('شارة القسم')
        ->and(Setting::get('how.title'))->toBe('عنوان القسم')
        ->and(Setting::get('how.sub_title'))->toBe('تمهيد القسم.');
});

it('يرفض الشارة والعنوان فارغين', function () {
    foreach (['badge_text', 'title'] as $field) {
        actingAs($this->admin)
            ->put(route('admin.settings.how.update'), howPayload([$field => '']))
            ->assertSessionHasErrors('how.'.$field);
    }

    expect(Setting::count())->toBe(0);
});

it('لا يتسرّب خطأ لوحٍ إلى حقل لوحٍ آخر يشاركه الاسم', function () {
    // 🔴 حارس التصادم: cta و how كلاهما يملك حقل title، وكلاهما يُرسَل
    // في صفحة واحدة. باسم مسطّح كان خطأ الأول يُرسَم تحت حقل الثاني.
    actingAs($this->admin)->put(route('admin.settings.cta.update'), [
        'cta' => ['title' => '', 'btn_text' => 'زر', 'btn_link' => '/register'],
    ])
        ->assertSessionHasErrors('cta.title')
        ->assertSessionDoesntHaveErrors('how.title');
});

// ── الخطوات ─────────────────────────────────────────────────────

it('يضيف خطوة جديدة ويعود إلى لوح آلية العمل', function () {
    actingAs($this->admin)->post(route('admin.settings.steps.store'), stepPayload('new'))
        ->assertRedirect(route('admin.settings.index', ['tab' => 'how']));

    $step = Step::sole();

    expect($step->title)->toBe('خطوة جديدة')
        ->and($step->sub_title)->toBe('شرح الخطوة.')
        ->and($step->display_order)->toBe(4)
        ->and($step->is_active)->toBeTrue();
});

it('يعدّل خطوة قائمة بمفتاح معرّفها', function () {
    $this->seed(StepSeeder::class);
    $step = Step::ordered()->first();

    actingAs($this->admin)->put(
        route('admin.settings.steps.update', $step),
        stepPayload((string) $step->id, ['title' => 'عنوان معدَّل', 'display_order' => 9]),
    )->assertRedirect(route('admin.settings.index', ['tab' => 'how']));

    expect($step->fresh()->title)->toBe('عنوان معدَّل')
        ->and($step->fresh()->display_order)->toBe(9)
        ->and(Step::count())->toBe(3);
});

it('يحذف خطوة حذفاً ناعماً لا نهائياً', function () {
    $this->seed(StepSeeder::class);
    $step = Step::ordered()->first();

    actingAs($this->admin)->delete(route('admin.settings.steps.destroy', $step))
        ->assertRedirect(route('admin.settings.index', ['tab' => 'how']));

    expect(Step::count())->toBe(2)
        ->and(Step::withTrashed()->count())->toBe(3)
        ->and(Step::withTrashed()->find($step->id)->deleted_at)->not->toBeNull();
});

it('يرفض خطوة بلا عنوان أو بترتيب غير رقمي', function () {
    actingAs($this->admin)
        ->post(route('admin.settings.steps.store'), stepPayload('new', ['title' => '']))
        ->assertSessionHasErrors('steps.new.title');

    actingAs($this->admin)
        ->post(route('admin.settings.steps.store'), stepPayload('new', ['display_order' => 'ثالثاً']))
        ->assertSessionHasErrors('steps.new.display_order');

    expect(Step::count())->toBe(0);
});

// ── الشاشة ──────────────────────────────────────────────────────

it('يعرض لوح آلية العمل بترويسته وخطواته', function () {
    $this->seed([HowSettingSeeder::class, StepSeeder::class]);

    actingAs($this->admin)->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('آلية العمل')
        ->assertSee('ثلاث خطوات تفصلك عن أول اختبار')
        ->assertSee('حدّد فرعك')
        ->assertSee('قِس فهمك فوراً');
});

// ── الصفحة الرئيسية ─────────────────────────────────────────────

it('تعرض الصفحة الرئيسية الترويسة والخطوات من القاعدة', function () {
    $this->seed([HowSettingSeeder::class, StepSeeder::class]);

    actingAs($this->admin)->put(route('admin.settings.how.update'), howPayload([
        'title' => 'عنوان من القاعدة',
    ]));

    get(route('home'))
        ->assertOk()
        ->assertSee('عنوان من القاعدة')
        ->assertSee('حدّد فرعك')
        ->assertSee('تابع محاضرتك')
        ->assertSee('قِس فهمك فوراً');
});

it('ترقّم الخطوات بموضعها لا بعمود الترتيب', function () {
    // 🔴 display_order يرتّب وقد يقفز (10 · 20 · 30)؛ الرقم المعروض يَعُدّ
    // فيجب أن يبقى متّصلاً: 01 · 02 · 03 مهما كانت قيم العمود.
    Step::create(['title' => 'ألف', 'display_order' => 30]);
    Step::create(['title' => 'باء', 'display_order' => 10]);
    Step::create(['title' => 'جيم', 'display_order' => 20]);

    $html = get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('01')->toContain('02')->toContain('03');

    // الترتيب المعروض يتبع display_order: باء ← جيم ← ألف
    expect(strpos($html, 'باء'))->toBeLessThan(strpos($html, 'جيم'))
        ->and(strpos($html, 'جيم'))->toBeLessThan(strpos($html, 'ألف'));
});

it('تُخفي الصفحة الرئيسية الخطوة غير النشطة', function () {
    Step::create(['title' => 'خطوة ظاهرة', 'display_order' => 1]);
    Step::create(['title' => 'خطوة مخفيّة', 'display_order' => 2, 'is_active' => false]);

    get(route('home'))
        ->assertOk()
        ->assertSee('خطوة ظاهرة')
        ->assertDontSee('خطوة مخفيّة');
});

it('يسقط القسم كلّه حين لا خطوة نشطة', function () {
    $this->seed(HowSettingSeeder::class);

    expect(Step::count())->toBe(0);

    // الترويسة تبقى — لكن لا قائمة فارغة تحتها.
    get(route('home'))
        ->assertOk()
        ->assertSee('آلية العمل')
        ->assertDontSee('<ol', escape: false);   // قائمة الخطوات وحدها في الصفحة
});
