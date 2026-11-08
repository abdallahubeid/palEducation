<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Feature;
use App\Models\Setting;
use App\Models\Student;
use App\Support\PermissionCatalog;
use Database\Seeders\FeatureSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Features — the "what sets us apart" cards.
|
| Same split as branches: the section HEADER lives in `settings`, the cards
| are a domain entity with their own screens, four permissions and a trash
| tab. The tests cover the seam as much as the CRUD.
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

function featurePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'ميزة جديدة',
        'description' => 'شرح الميزة.',
        'icon' => 'clipboard',
        'accent_token' => 'accent',
        'display_order' => 5,
        'is_active' => 1,
    ], $overrides);
}

// ── Seeding ─────────────────────────────────────────────────────

it('seeds the section header settings', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get('features.badge_text'))->toBe('ما يميّزنا')
        ->and(Setting::get('features.title'))->toBe('أربعة قرارات تصنع الفرق');
});

it('seeds the four cards in order with their exact copy', function () {
    $this->seed(FeatureSeeder::class);

    $titles = Feature::ordered()->pluck('title')->all();

    expect($titles)->toBe([
        'الاختبار يتبع الفيديو تلقائياً',
        'مكتبة مستقلّة ومنظّمة',
        'معلّم المادة هو واضع الاختبار',
        'محتوى فرعك دون سواه',
    ])->and(Feature::ordered()->first()->description)
        ->toContain('تنتهي المحاضرة فيبدأ التقييم');
});

it('does not duplicate or overwrite edited cards when reseeded', function () {
    $this->seed(FeatureSeeder::class);
    Feature::where('title', 'محتوى فرعك دون سواه')->update(['description' => 'شرح حرّره الأدمن']);

    $this->seed(FeatureSeeder::class);

    expect(Feature::count())->toBe(4)
        ->and(Feature::where('title', 'محتوى فرعك دون سواه')->value('description'))
        ->toBe('شرح حرّره الأدمن');
});

// ── Permissions ─────────────────────────────────────────────────

it('registers four granular feature permissions', function () {
    expect(PermissionCatalog::names())
        ->toContain('view-features')
        ->toContain('create-features')
        ->toContain('edit-features')
        ->toContain('delete-features');
});

it('blocks guests and students from every feature route', function () {
    get(route('admin.features.index'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.features.index'))->assertForbidden();

    // 🔴 The direct POST is the one that matters: hiding a link is not a guard.
    actingAs($this->student)->post(route('admin.features.store'), featurePayload())->assertForbidden();

    expect(Feature::count())->toBe(0);
});

it('enforces each permission independently', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('delete-features');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $feature = Feature::create(featurePayload());

    actingAs($this->admin)->delete(route('admin.features.destroy', $feature))->assertForbidden();

    // The other three are untouched.
    actingAs($this->admin)->get(route('admin.features.index'))->assertOk();
    actingAs($this->admin)->get(route('admin.features.create'))->assertOk();
});

// ── CRUD ────────────────────────────────────────────────────────

it('lists, creates, edits and toggles features', function () {
    actingAs($this->admin)->post(route('admin.features.store'), featurePayload())
        ->assertRedirect(route('admin.features.index'));

    $feature = Feature::sole();

    expect($feature->title)->toBe('ميزة جديدة')
        ->and($feature->icon)->toBe('clipboard')
        ->and($feature->accent_token)->toBe('accent')
        ->and($feature->is_active)->toBeTrue();

    actingAs($this->admin)->get(route('admin.features.index'))->assertOk()->assertSee('ميزة جديدة');
    actingAs($this->admin)->get(route('admin.features.edit', $feature))->assertOk()->assertSee('شرح الميزة.');

    actingAs($this->admin)->put(
        route('admin.features.update', $feature),
        featurePayload(['title' => 'عنوان معدَّل', 'accent_token' => 'warn']),
    );

    expect($feature->fresh()->title)->toBe('عنوان معدَّل')
        ->and($feature->fresh()->accent_token)->toBe('warn');

    actingAs($this->admin)->patch(route('admin.features.toggle', $feature));

    expect($feature->fresh()->is_active)->toBeFalse();
});

it('rejects an icon or tone the card cannot render', function () {
    // 🔴 Both columns hold NAMES resolved at render time. A value outside
    // the known sets draws a card with no icon or no colour, silently.
    actingAs($this->admin)->post(route('admin.features.store'), featurePayload(['icon' => 'rocket']))
        ->assertSessionHasErrors('icon');

    actingAs($this->admin)->post(route('admin.features.store'), featurePayload(['accent_token' => 'plum']))
        ->assertSessionHasErrors('accent_token');

    expect(Feature::count())->toBe(0);
});

it('rejects a dangerous scheme in the card link', function () {
    // The value lands in an href on a public page.
    actingAs($this->admin)
        ->post(route('admin.features.store'), featurePayload(['btn_text' => 'اقرأ', 'btn_link' => 'javascript:alert(1)']))
        ->assertSessionHasErrors('btn_link');

    actingAs($this->admin)
        ->post(route('admin.features.store'), featurePayload(['btn_text' => 'اقرأ']))
        ->assertSessionHasErrors('btn_link');

    expect(Feature::count())->toBe(0);
});

// ── Soft delete and trash ───────────────────────────────────────

it('soft deletes and offers undo pointing at the trash restore route', function () {
    $feature = Feature::create(featurePayload());

    actingAs($this->admin)->from(route('admin.features.index'))
        ->delete(route('admin.features.destroy', $feature));

    expect(Feature::count())->toBe(0)
        ->and(Feature::withTrashed()->count())->toBe(1);

    // 🔴 Undo is a PATCH form with CSRF in the shell, never a link in a
    // notice: a restore mutates server state.
    expect(session('admin.undo'))->toBe([
        'url' => route('admin.trash.restore', ['type' => 'features', 'id' => $feature->id]),
        'label' => 'تراجع',
    ]);
});

it('shows a features tab in the trash and restores from it', function () {
    $feature = tap(Feature::create(featurePayload(['title' => 'ميزة محذوفة'])))->delete();

    actingAs($this->admin)->get(route('admin.trash.index'))
        ->assertOk()
        ->assertSee('المميزات')
        ->assertSee('ميزة محذوفة');

    actingAs($this->admin)->get(route('admin.trash.index', ['type' => 'features']))
        ->assertOk()
        ->assertSee('ميزة محذوفة');

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'features', 'id' => $feature->id]))
        ->assertRedirect();

    expect(Feature::whereKey($feature->id)->exists())->toBeTrue();
});

it('force deletes a feature for good', function () {
    $feature = tap(Feature::create(featurePayload()))->delete();

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'features', 'id' => $feature->id]));

    expect(Feature::withTrashed()->whereKey($feature->id)->exists())->toBeFalse();
});

it('guards the features trash tab with its own permission', function () {
    $feature = tap(Feature::create(featurePayload()))->delete();

    Role::findByName('admin', 'web')->syncPermissions(['delete-branches']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'features', 'id' => $feature->id]))
        ->assertForbidden();

    expect(Feature::withTrashed()->whereKey($feature->id)->first()->deleted_at)->not->toBeNull();
});

// ── Settings header tab ─────────────────────────────────────────

it('saves the section header from the settings tab', function () {
    actingAs($this->admin)->put(route('admin.settings.features.update'), [
        'features' => ['badge_text' => 'شارة', 'title' => 'عنوان القسم'],
    ])->assertRedirect(route('admin.settings.index', ['tab' => 'features']));

    expect(Setting::get('features.badge_text'))->toBe('شارة')
        ->and(Setting::get('features.title'))->toBe('عنوان القسم');
});

it('requires both header fields', function () {
    foreach (['badge_text', 'title'] as $field) {
        actingAs($this->admin)->put(route('admin.settings.features.update'), [
            'features' => ['badge_text' => 'شارة', 'title' => 'عنوان'] + [$field => ''],
        ]);
    }

    actingAs($this->admin)->put(route('admin.settings.features.update'), [
        'features' => ['badge_text' => '', 'title' => 'عنوان'],
    ])->assertSessionHasErrors('features.badge_text');
});

it('links the settings panel to the features screen', function () {
    actingAs($this->admin)->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('ما يميّزنا')
        ->assertSee(route('admin.features.index'), escape: false);
});

// ── Public page ─────────────────────────────────────────────────

it('renders the section on the home page from the database', function () {
    $this->seed([SettingSeeder::class, FeatureSeeder::class]);

    get(route('home'))
        ->assertOk()
        ->assertSee('ما يميّزنا')
        ->assertSee('أربعة قرارات تصنع الفرق')
        ->assertSee('الاختبار يتبع الفيديو تلقائياً')
        ->assertSee('محتوى فرعك دون سواه');
});

it('hides an inactive card from the home page', function () {
    Feature::create(featurePayload(['title' => 'بطاقة ظاهرة', 'display_order' => 1]));
    Feature::create(featurePayload(['title' => 'بطاقة مخفيّة', 'display_order' => 2, 'is_active' => 0]));

    get(route('home'))
        ->assertOk()
        ->assertSee('بطاقة ظاهرة')
        ->assertDontSee('بطاقة مخفيّة');
});

it('renders the card link only when text and destination are both set', function () {
    // ⚠️ Asserted on the link's own text, not on the tile__link class: that
    // class is shared with the branch and news cards, so asserting its
    // absence could never pass and would prove nothing if it did.
    Feature::create(featurePayload([
        'title' => 'بطاقة برابط',
        'btn_text' => 'اقرأ المزيد عن الميزة',
        'btn_link' => '/register',
    ]));

    get(route('home'))->assertOk()->assertSee('اقرأ المزيد عن الميزة');

    // A dead link is worse than no link: text with no href goes nowhere.
    Feature::query()->update(['btn_text' => null, 'btn_link' => null]);

    get(route('home'))
        ->assertOk()
        ->assertSee('بطاقة برابط')
        ->assertDontSee('اقرأ المزيد عن الميزة');
});
