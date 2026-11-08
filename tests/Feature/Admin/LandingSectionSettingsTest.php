<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Student;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Landing page section settings: about · why_us · branches · video_tour.
|
| These four panels share one page with the three that came before, so the
| tests cover the seam as much as the fields: namespaced input names, the
| fixed key list, and uploads that must not blank a stored path.
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

    // The custom disk root is public/ — a real directory. Faking it keeps
    // the suite from writing into the document root.
    Storage::fake('custom');
});

function aboutPayload(array $overrides = []): array
{
    return ['about' => array_merge([
        'badge_text' => 'شارة من نحن',
        'title1' => 'العنوان الأول',
        'content1' => 'النصّ الأول.',
        'title2' => 'العنوان الثاني',
        'content2' => 'النصّ الثاني.',
        'title_box' => 'نصّ الصندوق.',
        'btn_text' => 'ابدأ',
        'btn_link' => '/register',
    ], $overrides)];
}

function whyUsPayload(array $overrides = []): array
{
    return ['why_us' => array_merge([
        'badge_text' => 'شارة لماذا نحن',
        'title' => 'عنوان لماذا نحن',
        'description' => 'وصف القسم.',
        'btn1_text' => 'الزرّ الأول',
        'btn1_link' => '/register',
        'btn2_text' => 'الزرّ الثاني',
        'btn2_link' => '#branches',
    ], $overrides)];
}

function videoPayload(array $overrides = []): array
{
    return ['video_tour' => array_merge([
        'badge_text' => 'شارة الجولة',
        'title' => 'عنوان الجولة',
        'sub_title' => 'وصف الجولة.',
    ], $overrides)];
}

// ── Seeder ──────────────────────────────────────────────────────

it('seeds all four section groups with the live page text', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get('about.badge_text'))->toBe('من نحن')
        ->and(Setting::get('about.title1'))->toBe('بنيناها لأننا عبرنا هذا الطريق')
        ->and(Setting::get('about.title2'))->toBe('رسالتنا')
        ->and(Setting::get('about.img'))->toBe('images/about/classroom.jpg')
        ->and(Setting::get('why_us.badge_text'))->toBe('لماذا نحن')
        ->and(Setting::get('why_us.title'))->toBe('محتوى حقيقي، لا وعود')
        ->and(Setting::get('branches.badge_text'))->toBe('اختر مسارك')
        ->and(Setting::get('branches.title'))->toBe('لكل فرع عالمه الخاص')
        ->and(Setting::get('video_tour.badge_text'))->toBe('جولة سريعة')
        ->and(Setting::get('video_tour.title'))->toBe('شاهد المنصة قبل أن تسجّل');
});

it('does not overwrite edited copy when the seeder runs again', function () {
    $this->seed(SettingSeeder::class);
    Setting::put('about.title1', 'عنوان حرّره الأدمن');

    $this->seed(SettingSeeder::class);

    expect(Setting::get('about.title1'))->toBe('عنوان حرّره الأدمن')
        ->and(Setting::where('key', 'about.title1')->count())->toBe(1);
});

it('seeds branch card copy onto the branches table', function () {
    $this->seed(BranchSeeder::class);

    $sci = Branch::where('slug', 'scientific')->sole();

    expect($sci->name)->toBe('الفرع العلمي')
        ->and($sci->description)->toContain('رياضيات')
        ->and($sci->icon)->toBe('beaker')
        ->and($sci->accent_token)->toBe('accent')
        ->and(Branch::count())->toBe(4);
});

it('carries no stored count columns — the figures are derived', function () {
    // 🔴 The regression this guards: subjects_count / teachers_count were
    // columns once, so the card advertised whatever was typed there long
    // after the content moved on.
    $columns = Schema::getColumnListing('branches');

    expect($columns)->not->toContain('subjects_count')
        ->and($columns)->not->toContain('teachers_count');
});

// ── Guards ──────────────────────────────────────────────────────

it('blocks guests and students on every new section route', function () {
    $routes = [
        'admin.settings.about.update',
        'admin.settings.why-us.update',
        'admin.settings.branches.update',
        'admin.settings.video-tour.update',
    ];

    // ⚠️ Guests first, in their own loop: actingAs() persists for the rest
    // of the test, so interleaving the two would run the "guest" leg of
    // every later iteration as the student — and pass for the wrong reason.
    foreach ($routes as $route) {
        $this->put(route($route))->assertRedirect(route('auth.login'));
    }

    foreach ($routes as $route) {
        actingAs($this->student)->put(route($route))->assertForbidden();
    }

    expect(Setting::count())->toBe(0);
});

// ── About ───────────────────────────────────────────────────────

it('saves the about panel and returns to its tab', function () {
    actingAs($this->admin)->put(route('admin.settings.about.update'), aboutPayload())
        ->assertRedirect(route('admin.settings.index', ['tab' => 'about']));

    expect(Setting::get('about.badge_text'))->toBe('شارة من نحن')
        ->and(Setting::get('about.title1'))->toBe('العنوان الأول')
        ->and(Setting::get('about.content2'))->toBe('النصّ الثاني.')
        ->and(Setting::get('about.btn_link'))->toBe('/register');
});

it('stores an uploaded about image on the custom disk and records its path', function () {
    // GD is not enabled in this environment, so fake()->image() throws.
    // create() with an explicit MIME satisfies the `image` rule, which
    // checks the type rather than the pixels.
    $file = UploadedFile::fake()->create('about.jpg', 512, 'image/jpeg');

    actingAs($this->admin)
        ->put(route('admin.settings.about.update'), aboutPayload(['img' => $file]))
        ->assertSessionHasNoErrors();

    $path = Setting::get('about.img');

    expect($path)->toStartWith('uploads/settings/');
    Storage::disk('custom')->assertExists($path);
});

it('keeps the stored image when the file input is left empty', function () {
    // 🔴 The trap this guards: an admin fixing a typo in the heading must
    // not silently drop the section photo because they touched no file.
    Setting::put('about.img', 'images/about/classroom.jpg');

    actingAs($this->admin)->put(route('admin.settings.about.update'), aboutPayload(['title1' => 'عنوان معدَّل']));

    expect(Setting::get('about.img'))->toBe('images/about/classroom.jpg')
        ->and(Setting::get('about.title1'))->toBe('عنوان معدَّل');
});

it('rejects a dangerous scheme in the about button link', function () {
    foreach (['javascript:alert(1)', 'data:text/html,x', 'register'] as $bad) {
        actingAs($this->admin)
            ->put(route('admin.settings.about.update'), aboutPayload(['btn_link' => $bad]))
            ->assertSessionHasErrors('about.btn_link');
    }

    expect(Setting::count())->toBe(0);
});

// ── Why us ──────────────────────────────────────────────────────

it('saves the why-us panel including both buttons', function () {
    actingAs($this->admin)->put(route('admin.settings.why-us.update'), whyUsPayload())
        ->assertRedirect(route('admin.settings.index', ['tab' => 'why_us']));

    expect(Setting::get('why_us.title'))->toBe('عنوان لماذا نحن')
        ->and(Setting::get('why_us.btn1_link'))->toBe('/register')
        ->and(Setting::get('why_us.btn2_link'))->toBe('#branches');
});

it('rejects a dangerous scheme in either why-us button', function () {
    foreach (['btn1_link', 'btn2_link'] as $field) {
        actingAs($this->admin)
            ->put(route('admin.settings.why-us.update'), whyUsPayload([$field => 'javascript:alert(1)']))
            ->assertSessionHasErrors('why_us.'.$field);
    }

    expect(Setting::count())->toBe(0);
});

// ── Branches header ─────────────────────────────────────────────

it('saves the branches header and leaves the cards alone', function () {
    $this->seed(BranchSeeder::class);

    actingAs($this->admin)->put(route('admin.settings.branches.update'), [
        'branches' => ['badge_text' => 'شارة', 'title' => 'عنوان الفروع', 'sub_title' => 'تمهيد.'],
    ])->assertRedirect(route('admin.settings.index', ['tab' => 'branches']));

    expect(Setting::get('branches.title'))->toBe('عنوان الفروع')
        // The four card rows are untouched — they are not settings keys.
        ->and(Branch::where('slug', 'scientific')->value('name'))->toBe('الفرع العلمي');
});

// ── Video tour ──────────────────────────────────────────────────

it('saves the video panel and stores an uploaded file', function () {
    $file = UploadedFile::fake()->create('tour.mp4', 2048, 'video/mp4');

    actingAs($this->admin)
        ->put(route('admin.settings.video-tour.update'), videoPayload(['video_path' => $file]))
        ->assertSessionHasNoErrors();

    $path = Setting::get('video_tour.video_path');

    expect(Setting::get('video_tour.title'))->toBe('عنوان الجولة')
        ->and($path)->toStartWith('uploads/settings/');
    Storage::disk('custom')->assertExists($path);
});

it('rejects a non-video upload by its real type, not its extension', function () {
    // 🔴 mimetypes, not mimes: the extension is attacker controlled, and
    // this file lands under the document root.
    $disguised = UploadedFile::fake()->create('tour.mp4', 64, 'text/html');

    actingAs($this->admin)
        ->put(route('admin.settings.video-tour.update'), videoPayload(['video_path' => $disguised]))
        ->assertSessionHasErrors('video_tour.video_path');

    expect(Setting::count())->toBe(0);
});

// ── The fixed key list ──────────────────────────────────────────

it('ignores request fields that are not declared keys', function () {
    // 🔴 Persistence loops over the declared key map, never over request
    // input: a crafted or stale form must not be able to create arbitrary
    // settings rows, and no value may reach the table unvalidated.
    actingAs($this->admin)->put(route('admin.settings.branches.update'), [
        'branches' => [
            'badge_text' => 'شارة', 'title' => 'عنوان', 'sub_title' => 'تمهيد.',
            'evil_key' => 'injected',
        ],
    ]);

    expect(Setting::where('key', 'branches.evil_key')->exists())->toBeFalse()
        ->and(Setting::where('key', 'like', 'branches.%')->count())->toBe(3);
});

// ── Dashboard screen ────────────────────────────────────────────

it('renders all seven settings tabs with their stored values', function () {
    $this->seed([BranchSeeder::class, SettingSeeder::class]);

    actingAs($this->admin)->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('من نحن')
        ->assertSee('لماذا نحن')
        ->assertSee('الفروع')
        ->assertSee('جولة سريعة')
        ->assertSee('بنيناها لأننا عبرنا هذا الطريق')
        ->assertSee('محتوى حقيقي، لا وعود')
        // Branches themselves are NOT here any more — only a pointer to
        // the screen that owns them.
        ->assertDontSee('الفرع الصناعي')
        ->assertSee(route('admin.branches.index'), escape: false);
});

// ── Landing page ────────────────────────────────────────────────

it('renders the four sections from the database', function () {
    $this->seed([BranchSeeder::class, SettingSeeder::class]);

    actingAs($this->admin)->put(route('admin.settings.about.update'), aboutPayload(['title1' => 'من القاعدة']));
    actingAs($this->admin)->put(route('admin.settings.why-us.update'), whyUsPayload(['title' => 'لماذا من القاعدة']));

    get(route('home'))
        ->assertOk()
        ->assertSee('من القاعدة')
        ->assertSee('لماذا من القاعدة')
        ->assertSee('اختر مسارك')
        ->assertSee('جولة سريعة')
        // Branch cards and their figures come from the branches table.
        ->assertSee('الفرع العلمي')
        ->assertSee('الفرع الأدبي');
});

it('drops optional copy from the landing page when it is cleared', function () {
    $this->seed(SettingSeeder::class);

    // A label unique to this section: the hero carries its own
    // "تصفّح الفروع" button, so asserting on that string proves nothing.
    actingAs($this->admin)->put(route('admin.settings.why-us.update'), whyUsPayload([
        'btn2_text' => 'زرّ ثانوي مميّز',
    ]));

    get(route('home'))->assertOk()->assertSee('زرّ ثانوي مميّز');

    actingAs($this->admin)->put(route('admin.settings.why-us.update'), whyUsPayload([
        'description' => '',
        'btn2_text' => '',
        'btn2_link' => '',
    ]));

    get(route('home'))
        ->assertOk()
        ->assertDontSee('كل رقم هنا يمثّل محتوى رفعه معلّم')
        ->assertDontSee('زرّ ثانوي مميّز');
});

it('shows the video player only once a file is stored', function () {
    $this->seed(SettingSeeder::class);

    get(route('home'))->assertOk()->assertDontSee('<video', escape: false);

    Setting::put('video_tour.video_path', 'uploads/settings/tour.mp4');

    get(route('home'))->assertOk()->assertSee('uploads/settings/tour.mp4');
});

it('falls back to the shipped defaults when no rows exist at all', function () {
    // A database that predates this feature: the page must still render.
    expect(Setting::count())->toBe(0);

    get(route('home'))
        ->assertOk()
        ->assertSee('بنيناها لأننا عبرنا هذا الطريق')
        ->assertSee('محتوى حقيقي، لا وعود')
        ->assertSee('لكل فرع عالمه الخاص')
        ->assertSee('شاهد المنصة قبل أن تسجّل');
});
