<?php

use App\Http\Controllers\Admin\PricingPlanController;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\PricingPlan;
use App\Models\Setting;
use App\Models\Student;
use App\Support\PermissionCatalog;
use App\Support\TrashableResources;
use Database\Seeders\PricingPlanSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Pricing plans — the tier cards.
|
| Same split as branches and features: the section HEADER lives in
| `settings`, the tiers are a domain entity with their own screens, four
| permissions and a trash tab.
|
| 🔴 These rows are MARKETING copy, never the billing record. What a student
| actually bought belongs to `subscriptions` (phase 8, unbuilt).
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

function planPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'خطة جديدة',
        'badge' => 'وصف الخطة',
        'price' => 150,
        'period' => '/ للفصل',
        'features' => "الميزة الأولى\nالميزة الثانية",
        'highlight_badge' => null,
        'btn_text' => 'اشترك',
        'btn_link' => '/register',
        'display_order' => 4,
        'is_active' => 1,
    ], $overrides);
}

// ── Schema and seeding ──────────────────────────────────────────

it('creates a soft-deleting pricing_plans table', function () {
    expect(Schema::hasTable('pricing_plans'))->toBeTrue()
        ->and(Schema::hasColumn('pricing_plans', 'deleted_at'))->toBeTrue()
        ->and(Schema::hasColumn('pricing_plans', 'features'))->toBeTrue()
        ->and(Schema::hasColumn('pricing_plans', 'highlight_badge'))->toBeTrue();
});

it('seeds the header settings', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get('pricing.badge_text'))->toBe('الأسعار')
        ->and(Setting::get('pricing.title'))->toBe('ادفع لما تحتاجه فقط')
        ->and(Setting::get('pricing.sub_title'))->toContain('بلا رسوم خفيّة')
        ->and(Setting::get('pricing.btn_text'))->toBe('قارن الاشتراكين بالتفصيل');
});

it('seeds the three tiers with prices, periods and bullets', function () {
    $this->seed(PricingPlanSeeder::class);

    $plans = PricingPlan::ordered()->get();

    expect($plans->pluck('name')->all())->toBe(['تجربة', 'فصل دراسي', 'سنة كاملة'])
        ->and($plans->pluck('price')->all())->toBe([0, 120, 200])
        ->and($plans[0]->period)->toBe('مجاناً')
        ->and($plans[1]->period)->toBe('/ للفصل')
        // Exactly one ribbon: two would cancel each other out.
        ->and($plans->whereNotNull('highlight_badge')->count())->toBe(1)
        ->and($plans[1]->highlight_badge)->toBe('الأكثر اختياراً')
        ->and($plans[0]->featureList())->toBe([
            'أول محاضرة من كل مادة',
            'الاختبار المرافق لها',
            'تصفّح المكتبة دون تنزيل',
        ])
        ->and($plans[1]->featureList())->toHaveCount(4);
});

it('does not overwrite an edited price when reseeded', function () {
    // 🔴 The one field where a silent revert costs money.
    $this->seed(PricingPlanSeeder::class);
    PricingPlan::where('name', 'فصل دراسي')->update(['price' => 99]);

    $this->seed(PricingPlanSeeder::class);

    expect(PricingPlan::count())->toBe(3)
        ->and(PricingPlan::where('name', 'فصل دراسي')->value('price'))->toBe(99);
});

// ── The removed footer note ─────────────────────────────────────

it('purges the shekel footer note from every surface', function () {
    /*
     * 🔴 Deleted, not relocated. It claimed billing terms nothing in the
     * system enforces ("cancel any time"), and the subscription layer that
     * would make it true is unbuilt. A setting key would quietly bring it
     * back, so this guards the lang file, both views, and the settings map.
     */
    $this->seed(SettingSeeder::class);

    expect(array_key_exists('pricing_note', require lang_path('ar/home.php')))->toBeFalse()
        ->and(Setting::where('key', 'like', '%note%')->exists())->toBeFalse()
        ->and(array_keys(Setting::PRICING_DEFAULTS))->not->toContain('pricing.note');

    foreach (['pages/home.blade.php', 'pages/pricing.blade.php'] as $view) {
        expect(file_get_contents(resource_path('views/'.$view)))
            ->not->toContain('pricing_note')
            ->not->toContain('الأسعار بالشيكل');
    }

    get(route('home'))->assertOk()->assertDontSee('الأسعار بالشيكل');
    get(route('pricing'))->assertOk()->assertDontSee('الأسعار بالشيكل');
});

// ── Permissions ─────────────────────────────────────────────────

it('registers four granular pricing permissions', function () {
    expect(PermissionCatalog::names())
        ->toContain('view-pricings')
        ->toContain('create-pricings')
        ->toContain('edit-pricings')
        ->toContain('delete-pricings');
});

it('blocks guests and students from every pricing route', function () {
    get(route('admin.pricing.index'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.pricing.index'))->assertForbidden();

    // 🔴 The direct POST is the one that matters: hiding a link is not a guard.
    actingAs($this->student)->post(route('admin.pricing.store'), planPayload())->assertForbidden();

    expect(PricingPlan::count())->toBe(0);
});

it('enforces each pricing permission independently', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('delete-pricings');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $plan = PricingPlan::create(['name' => 'خطة', 'price' => 10]);

    actingAs($this->admin)->delete(route('admin.pricing.destroy', $plan))->assertForbidden();

    actingAs($this->admin)->get(route('admin.pricing.index'))->assertOk();
    actingAs($this->admin)->get(route('admin.pricing.create'))->assertOk();
});

// ── CRUD ────────────────────────────────────────────────────────

it('creates, edits and toggles a plan', function () {
    actingAs($this->admin)->post(route('admin.pricing.store'), planPayload())
        ->assertRedirect(route('admin.pricing.index'));

    $plan = PricingPlan::sole();

    expect($plan->name)->toBe('خطة جديدة')
        ->and($plan->price)->toBe(150)
        ->and($plan->features)->toBe(['الميزة الأولى', 'الميزة الثانية'])
        ->and($plan->is_active)->toBeTrue();

    actingAs($this->admin)->get(route('admin.pricing.index'))->assertOk()->assertSee('خطة جديدة');
    actingAs($this->admin)->get(route('admin.pricing.edit', $plan))->assertOk()
        // The bullets come back as text, one per line, for the textarea.
        ->assertSee('الميزة الأولى');

    actingAs($this->admin)->put(
        route('admin.pricing.update', $plan),
        planPayload(['name' => 'اسم معدَّل', 'price' => 175]),
    );

    expect($plan->fresh()->name)->toBe('اسم معدَّل')
        ->and($plan->fresh()->price)->toBe(175);

    actingAs($this->admin)->patch(route('admin.pricing.toggle', $plan));

    expect($plan->fresh()->is_active)->toBeFalse();
});

it('turns one bullet per line into a JSON array and strips the blanks', function () {
    // ⚠️ A trailing newline is the normal way to leave a textarea; storing
    // it would render as a lone tick with no text beside it.
    expect(PricingPlanController::parseFeatures("أولى\n\n  ثانية  \n\n"))
        ->toBe(['أولى', 'ثانية'])
        ->and(PricingPlanController::parseFeatures(null))->toBe([])
        ->and(PricingPlanController::parseFeatures('   '))->toBe([]);

    // And the inverse, for the edit form.
    expect(PricingPlanController::featuresToText(['أولى', 'ثانية']))
        ->toBe('أولى'.PHP_EOL.'ثانية');
});

it('rejects a fractional price rather than truncating it', function () {
    // 🔴 `numeric` would accept 120.50 and the unsigned int column would
    // silently drop the fraction on write. Whole shekels only.
    actingAs($this->admin)->post(route('admin.pricing.store'), planPayload(['price' => '120.50']))
        ->assertSessionHasErrors('price');

    actingAs($this->admin)->post(route('admin.pricing.store'), planPayload(['price' => -5]))
        ->assertSessionHasErrors('price');

    expect(PricingPlan::count())->toBe(0);
});

it('rejects a dangerous scheme in the plan button link', function () {
    // The value lands in an href on a public page.
    actingAs($this->admin)->post(route('admin.pricing.store'), planPayload(['btn_link' => 'javascript:alert(1)']))
        ->assertSessionHasErrors('btn_link');

    // Text and link are filled together or not at all.
    actingAs($this->admin)->post(route('admin.pricing.store'), planPayload(['btn_link' => '']))
        ->assertSessionHasErrors('btn_link');

    expect(PricingPlan::count())->toBe(0);
});

// ── Soft delete and trash ───────────────────────────────────────

it('soft deletes and offers undo pointing at the trash restore route', function () {
    $plan = PricingPlan::create(['name' => 'خطة', 'price' => 10]);

    actingAs($this->admin)->from(route('admin.pricing.index'))
        ->delete(route('admin.pricing.destroy', $plan));

    expect(PricingPlan::count())->toBe(0)
        ->and(PricingPlan::withTrashed()->count())->toBe(1);

    // 🔴 Undo is a PATCH form with CSRF in the shell, never a link in a
    // notice: a restore mutates server state.
    expect(session('admin.undo'))->toBe([
        'url' => route('admin.trash.restore', ['type' => 'pricings', 'id' => $plan->id]),
        'label' => 'تراجع',
    ]);
});

it('registers a pricing tab in the central trash', function () {
    expect(TrashableResources::keys())->toContain('pricings')
        ->and(TrashableResources::permissionFor('pricings'))->toBe('delete-pricings');
});

it('restores and force deletes from the trash tab', function () {
    $plan = tap(PricingPlan::create(['name' => 'خطة محذوفة', 'price' => 10]))->delete();

    actingAs($this->admin)->get(route('admin.trash.index'))
        ->assertOk()
        ->assertSee('الأسعار')
        ->assertSee('خطة محذوفة');

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'pricings', 'id' => $plan->id]))
        ->assertRedirect();

    expect(PricingPlan::whereKey($plan->id)->exists())->toBeTrue();

    $plan->delete();

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'pricings', 'id' => $plan->id]));

    expect(PricingPlan::withTrashed()->whereKey($plan->id)->exists())->toBeFalse();
});

it('guards the pricing trash tab with its own permission', function () {
    $plan = tap(PricingPlan::create(['name' => 'خطة', 'price' => 10]))->delete();

    Role::findByName('admin', 'web')->syncPermissions(['delete-branches']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'pricings', 'id' => $plan->id]))
        ->assertForbidden();

    expect(PricingPlan::withTrashed()->whereKey($plan->id)->first()->deleted_at)->not->toBeNull();
});

// ── Settings header tab ─────────────────────────────────────────

it('saves the pricing header from the settings tab', function () {
    actingAs($this->admin)->put(route('admin.settings.pricing.update'), [
        'pricing' => [
            'badge_text' => 'شارة', 'title' => 'عنوان القسم', 'sub_title' => 'تمهيد.',
            'btn_text' => 'قارن', 'btn_link' => '/pricing',
        ],
    ])->assertRedirect(route('admin.settings.index', ['tab' => 'pricing']));

    expect(Setting::get('pricing.title'))->toBe('عنوان القسم')
        ->and(Setting::get('pricing.btn_link'))->toBe('/pricing');
});

it('rejects a dangerous scheme in the comparison link', function () {
    actingAs($this->admin)->put(route('admin.settings.pricing.update'), [
        'pricing' => [
            'badge_text' => 'شارة', 'title' => 'عنوان',
            'btn_text' => 'قارن', 'btn_link' => 'javascript:alert(1)',
        ],
    ])->assertSessionHasErrors('pricing.btn_link');
});

it('links the settings panel to the pricing screen', function () {
    actingAs($this->admin)->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee(route('admin.pricing.index'), escape: false);
});

// ── Public page ─────────────────────────────────────────────────

it('renders the pricing section on the home page from the database', function () {
    $this->seed([SettingSeeder::class, PricingPlanSeeder::class]);

    get(route('home'))
        ->assertOk()
        ->assertSee('ادفع لما تحتاجه فقط')
        ->assertSee('تجربة')
        ->assertSee('فصل دراسي')
        ->assertSee('سنة كاملة')
        ->assertSee('الأكثر اختياراً')
        ->assertSee('أول محاضرة من كل مادة')
        ->assertSee('قارن الاشتراكين بالتفصيل');
});

it('hides an inactive plan from the home page', function () {
    PricingPlan::create(['name' => 'خطة ظاهرة', 'price' => 10, 'display_order' => 1]);
    PricingPlan::create(['name' => 'خطة مسحوبة', 'price' => 20, 'display_order' => 2, 'is_active' => false]);

    get(route('home'))
        ->assertOk()
        ->assertSee('خطة ظاهرة')
        ->assertDontSee('خطة مسحوبة');
});

it('drops the comparison button when its text or link is cleared', function () {
    $this->seed([SettingSeeder::class, PricingPlanSeeder::class]);

    get(route('home'))->assertOk()->assertSee('قارن الاشتراكين بالتفصيل');

    Setting::put('pricing.btn_text', '');
    Setting::put('pricing.btn_link', '');

    get(route('home'))->assertOk()->assertDontSee('قارن الاشتراكين بالتفصيل');
});

it('renders a plan whose bullet list was never filled', function () {
    // ⚠️ The JSON column is nullable, so an untouched row casts to null and
    // a @foreach over it would throw. featureList() normalises that away.
    PricingPlan::create(['name' => 'بلا مزايا', 'price' => 0, 'features' => null]);

    get(route('home'))->assertOk()->assertSee('بلا مزايا');
});
