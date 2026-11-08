<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Faq;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Testimonial;
use App\Support\PermissionCatalog;
use App\Support\TrashableResources;
use Database\Seeders\FaqSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Frequently asked questions.
|
| ⚠️ The `faqs` table already existed (landing-content cluster) with
| display_order and is_active beyond the requested question/answer pair.
| Reused rather than duplicated — a second table would have split the same
| content across two places.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $branch = Branch::create(['name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

function faqPayload(array $overrides = []): array
{
    return array_merge([
        'question' => 'هل يمكنني تغيير فرعي بعد اختياره؟',
        'answer' => 'اختيار الفرع شبه دائم لأنه يحدّد كل المحتوى الذي تراه.',
        'display_order' => 1,
        'is_active' => 1,
    ], $overrides);
}

// ── The approve button, matched to its neighbours ───────────────

it('renders the approve button at the same metrics as edit and delete', function () {
    /*
     * 🔴 The base classes already matched — padding, radius, border and text
     * size — but an icon child plus its gap made the button visibly bulkier
     * than the two text-only buttons beside it. Text-only now, so the three
     * differ in label length and colour alone.
     */
    Testimonial::create(['name' => 'أ', 'rate' => 5, 'description' => 'رأي معلّق.', 'is_active' => false]);

    $html = actingAs($this->admin)->get(route('admin.testimonials.index'))->assertOk()->getContent();

    /*
     * Every OUTLINE button on the page — approve, edit, delete. The page-head
     * "رأي جديد" is a filled primary and legitimately different, so keying on
     * `border border-hairline` excludes it without hard-coding positions.
     */
    // ⚠️ Anchored to the x-admin.btn BASE, not just the outline colours:
    // a bare `border border-hairline bg-canvas` also matches the table
    // wrapper and the topbar link, which are not buttons at all.
    preg_match_all(
        '/class="(inline-flex cursor-pointer[^"]*border border-hairline bg-canvas[^"]*)"/',
        $html,
        $matches,
    );

    $shapes = collect($matches[1])
        // Collapse the multi-line class string, then mask the one thing that
        // is ALLOWED to differ: the colour token.
        ->map(fn (string $class): string => preg_replace('/\s+/', ' ', trim($class)))
        ->map(fn (string $class): string => preg_replace('/text-\w+-deep|text-steel/', 'COLOUR', $class))
        ->map(fn (string $class): string => preg_replace('/hover:\S+/', 'HOVER', $class))
        ->unique()
        ->values();

    expect(count($matches[1]))->toBeGreaterThanOrEqual(3)
        // Once colour is masked out every one reduces to the SAME string —
        // that is what "identical shape" means concretely.
        ->and($shapes)->toHaveCount(1);

    // And the approve button carries no icon child.
    expect($html)->not->toMatch('/>\s*موافقة ونشر[^<]*<svg/u');
});

// ── Settings ────────────────────────────────────────────────────

it('seeds the FAQ section header', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get('faq.badge_text'))->toBe('أسئلة شائعة')
        ->and(Setting::get('faq.title'))->toBe('أسئلة تدور في ذهن كل طالب')
        ->and(Setting::get('faq.sub_title'))->toBe('لم تجد إجابتك؟ راسلنا وسنردّ خلال يوم عمل واحد.')
        ->and(Setting::get('faq.btn_text'))->toBe('تواصل معنا');
});

it('saves the FAQ header from the settings tab', function () {
    actingAs($this->admin)->put(route('admin.settings.faq.update'), [
        'faq' => [
            'badge_text' => 'شارة', 'title' => 'عنوان القسم', 'sub_title' => 'تمهيد.',
            'btn_text' => 'راسلنا', 'btn_link' => '/contact',
        ],
    ])->assertRedirect(route('admin.settings.index', ['tab' => 'faq']));

    expect(Setting::get('faq.title'))->toBe('عنوان القسم')
        ->and(Setting::get('faq.btn_link'))->toBe('/contact');

    // The link lands in an href on a public page.
    actingAs($this->admin)->put(route('admin.settings.faq.update'), [
        'faq' => ['badge_text' => 'شارة', 'title' => 'عنوان', 'btn_link' => 'javascript:alert(1)'],
    ])->assertSessionHasErrors('faq.btn_link');
});

// ── Permissions ─────────────────────────────────────────────────

it('registers four granular FAQ permissions', function () {
    expect(PermissionCatalog::names())
        ->toContain('view-faqs')
        ->toContain('create-faqs')
        ->toContain('edit-faqs')
        ->toContain('delete-faqs');
});

it('blocks guests and students from every FAQ route', function () {
    get(route('admin.faqs.index'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.faqs.index'))->assertForbidden();

    // 🔴 The direct POST is what matters: hiding a link is not a guard.
    actingAs($this->student)->post(route('admin.faqs.store'), faqPayload())->assertForbidden();

    expect(Faq::count())->toBe(0);
});

it('enforces each FAQ permission independently', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('delete-faqs');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $faq = Faq::create(['question' => 'سؤال؟', 'answer' => 'جواب.']);

    actingAs($this->admin)->delete(route('admin.faqs.destroy', $faq))->assertForbidden();

    actingAs($this->admin)->get(route('admin.faqs.index'))->assertOk();
    actingAs($this->admin)->get(route('admin.faqs.create'))->assertOk();
});

// ── CRUD ────────────────────────────────────────────────────────

it('creates, edits and toggles a question', function () {
    actingAs($this->admin)->post(route('admin.faqs.store'), faqPayload())
        ->assertRedirect(route('admin.faqs.index'));

    $faq = Faq::sole();

    expect($faq->question)->toBe('هل يمكنني تغيير فرعي بعد اختياره؟')
        ->and($faq->is_active)->toBeTrue();

    actingAs($this->admin)->get(route('admin.faqs.index'))->assertOk()->assertSee($faq->question);
    actingAs($this->admin)->get(route('admin.faqs.edit', $faq))->assertOk()->assertSee($faq->answer);

    actingAs($this->admin)->put(
        route('admin.faqs.update', $faq),
        faqPayload(['answer' => 'جواب محدَّث.', 'display_order' => 3]),
    );

    expect($faq->fresh()->answer)->toBe('جواب محدَّث.')
        ->and($faq->fresh()->display_order)->toBe(3);

    actingAs($this->admin)->patch(route('admin.faqs.toggle', $faq));

    expect($faq->fresh()->is_active)->toBeFalse();
});

it('rejects an empty question or answer', function () {
    foreach (['question', 'answer'] as $field) {
        actingAs($this->admin)
            ->post(route('admin.faqs.store'), faqPayload([$field => '']))
            ->assertSessionHasErrors($field);
    }

    // ⚠️ Bounded to the column width: validation looser than the column
    // means MySQL truncates on write in non-strict mode, silently.
    actingAs($this->admin)
        ->post(route('admin.faqs.store'), faqPayload(['question' => str_repeat('س', 300)]))
        ->assertSessionHasErrors('question');

    expect(Faq::count())->toBe(0);
});

// ── Soft delete and trash ───────────────────────────────────────

it('soft deletes and offers undo pointing at the trash restore route', function () {
    $faq = Faq::create(['question' => 'سؤال؟', 'answer' => 'جواب.']);

    actingAs($this->admin)->from(route('admin.faqs.index'))
        ->delete(route('admin.faqs.destroy', $faq));

    expect(Faq::count())->toBe(0)
        ->and(Faq::withTrashed()->count())->toBe(1);

    // 🔴 Undo is a PATCH form with CSRF in the shell, never a link inside a
    // notice: a restore mutates server state.
    expect(session('admin.undo'))->toBe([
        'url' => route('admin.trash.restore', ['type' => 'faqs', 'id' => $faq->id]),
        'label' => 'تراجع',
    ]);
});

it('registers a FAQ tab in the central trash', function () {
    expect(TrashableResources::keys())->toContain('faqs')
        ->and(TrashableResources::permissionFor('faqs'))->toBe('delete-faqs');
});

it('restores and force deletes from the trash tab', function () {
    $faq = tap(Faq::create(['question' => 'سؤال محذوف؟', 'answer' => 'جواب.']))->delete();

    actingAs($this->admin)->get(route('admin.trash.index'))
        ->assertOk()
        ->assertSee('الأسئلة الشائعة')
        ->assertSee('سؤال محذوف؟');

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'faqs', 'id' => $faq->id]))
        ->assertRedirect();

    expect(Faq::whereKey($faq->id)->exists())->toBeTrue();

    $faq->delete();

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'faqs', 'id' => $faq->id]));

    expect(Faq::withTrashed()->whereKey($faq->id)->exists())->toBeFalse();
});

it('guards the FAQ trash tab with its own permission', function () {
    $faq = tap(Faq::create(['question' => 'سؤال؟', 'answer' => 'جواب.']))->delete();

    Role::findByName('admin', 'web')->syncPermissions(['delete-branches']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'faqs', 'id' => $faq->id]))
        ->assertForbidden();

    expect(Faq::withTrashed()->whereKey($faq->id)->first()->deleted_at)->not->toBeNull();
});

// ── Seeding ─────────────────────────────────────────────────────

it('seeds the questions from the design, in order', function () {
    $this->seed(FaqSeeder::class);

    $questions = Faq::active()->ordered()->pluck('question')->all();

    expect($questions)->toHaveCount(6)
        ->and($questions[0])->toBe('هل يمكنني تغيير فرعي بعد اختياره؟')
        ->and($questions[1])->toBe('هل يفتح الاشتراك جميع المواد أم كل مادة على حدة؟')
        ->and($questions[2])->toBe('هل يلزم إنهاء الفيديو كاملاً ليُفتح الاختبار؟')
        ->and($questions[3])->toBe('هل يمكنني إعادة الاختبار إن لم تُرضِني درجتي؟')
        // Every question ships with a real answer, not a placeholder.
        ->and(Faq::whereNull('answer')->orWhere('answer', '')->count())->toBe(0);
});

it('does not overwrite an edited answer when reseeded', function () {
    $this->seed(FaqSeeder::class);
    Faq::where('question', 'هل تعمل المنصة على الهاتف؟')->update(['answer' => 'جواب حرّره الأدمن.']);

    $this->seed(FaqSeeder::class);

    expect(Faq::count())->toBe(6)
        ->and(Faq::where('question', 'هل تعمل المنصة على الهاتف؟')->value('answer'))
        ->toBe('جواب حرّره الأدمن.');
});

// ── Public page ─────────────────────────────────────────────────

it('renders the FAQ section on the home page from the database', function () {
    $this->seed([FaqSeeder::class, SettingSeeder::class]);

    get(route('home'))
        ->assertOk()
        ->assertSee('أسئلة تدور في ذهن كل طالب')
        ->assertSee('لم تجد إجابتك؟ راسلنا وسنردّ خلال يوم عمل واحد.')
        ->assertSee('تواصل معنا')
        ->assertSee('هل يمكنني تغيير فرعي بعد اختياره؟')
        ->assertSee('هل يمكنني إعادة الاختبار إن لم تُرضِني درجتي؟');
});

it('hides an inactive question from the home page', function () {
    Faq::create(['question' => 'سؤال ظاهر؟', 'answer' => 'جواب ظاهر.', 'display_order' => 1]);
    Faq::create([
        'question' => 'سؤال مخفي؟', 'answer' => 'جواب مخفي.',
        'display_order' => 2, 'is_active' => false,
    ]);

    get(route('home'))
        ->assertOk()
        ->assertSee('جواب ظاهر.')
        ->assertDontSee('جواب مخفي.');
});

it('renders the page with no questions at all', function () {
    // ⚠️ The list is admin-managed, so it can legitimately be empty. The
    // header still has to render rather than throw.
    $this->seed(SettingSeeder::class);

    expect(Faq::count())->toBe(0);

    get(route('home'))->assertOk()->assertSee('أسئلة تدور في ذهن كل طالب');
});

it('drops the contact button when its text or link is cleared', function () {
    /*
     * ⚠️ Scoped to the FAQ section, not the whole page. The FOOTER also links
     * "تواصل معنا", so a page-wide assertDontSee reports the button as still
     * present when the section has correctly dropped it.
     */
    $faqSection = function (): string {
        $html = get(route('home'))->assertOk()->getContent();
        $start = strpos($html, 'id="faq"');
        $end = strpos($html, '</section>', $start);

        return substr($html, $start, $end - $start);
    };

    $this->seed(SettingSeeder::class);

    expect($faqSection())->toContain('تواصل معنا');

    Setting::put('faq.btn_text', '');
    Setting::put('faq.btn_link', '');

    expect($faqSection())->not->toContain('تواصل معنا');
});
