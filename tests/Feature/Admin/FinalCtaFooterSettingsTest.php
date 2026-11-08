<?php

use App\Models\Admin;
use App\Models\Setting;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Closing CTA and footer, both driven from `settings`.
|
| 🔴 The footer reads its settings DIRECTLY rather than through a controller:
| it renders on every public page, and half of those are still Route::view
| with no controller to pass anything through.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

/**
 * A COMPLETE, valid footer payload — override one field to test it.
 *
 * ⚠️ Every slot is included, empty ones as ''. That mirrors a real form
 * post: the browser submits all 36 fields whether filled or not, and
 * persist() only writes keys the request actually carried. A partial
 * payload here would leave omitted keys falling back to their seeded
 * defaults and make a "cleared field" assertion pass for the wrong reason.
 */
function footerPayload(array $overrides = []): array
{
    $footer = [
        'newsletter_title' => 'عنوان النشرة',
        'newsletter_sub_title' => 'وصف.',
        'copyright_text' => 'حقوق :year',
        'made_in_text' => 'صُنع هنا',
    ];

    foreach (Setting::FOOTER_COLUMNS as $column) {
        $footer[$column.'_title'] = $column === 'col1' ? 'عمود أول' : '';

        for ($slot = 1; $slot <= Setting::FOOTER_LINKS_PER_COLUMN; $slot++) {
            $footer["{$column}_link{$slot}_text"] = '';
            $footer["{$column}_link{$slot}_url"] = '';
        }
    }

    return ['footer' => array_merge($footer, $overrides)];
}

// ── Seeded defaults ─────────────────────────────────────────────

it('seeds the closing CTA copy', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get('final_cta.title'))->toBe('فرعك بانتظارك')
        ->and(Setting::get('final_cta.sub_title'))->toBe('سجّل مجاناً وشاهد أول محاضرة من كل مادة قبل أن تقرّر.')
        ->and(Setting::get('final_cta.btn_text'))->toBe('أنشئ حسابك المجاني')
        ->and(Setting::get('final_cta.btn_link'))->toBe('/register')
        ->and(Setting::get('final_cta.sub_title2'))->toBe('دون بطاقة ائتمان · يمكنك الإلغاء في أي وقت');
});

it('seeds the footer copy and all four link columns', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get('footer.newsletter_title'))->toBe('كن أول من يعرف')
        ->and(Setting::get('footer.newsletter_sub_title'))->toContain('رسالة واحدة أسبوعياً، دون حشو.')
        ->and(Setting::get('footer.made_in_text'))->toBe('صُنع في فلسطين');

    // Explicit keys now — one per label, one per destination.
    expect(Setting::get('footer.col1_title'))->toBe('المنصة')
        ->and(Setting::get('footer.col1_link1_text'))->toBe('الفروع')
        ->and(Setting::get('footer.col1_link1_url'))->toBe('/branches')
        // ⚠️ Slot 4 of columns 3 and 4 ships EMPTY: three links today, room
        // for a fourth without a migration.
        ->and(Setting::get('footer.col3_link4_text'))->toBe('');

    $columns = Setting::footerColumns();

    expect($columns)->toHaveCount(4)
        ->and(array_column($columns, 'title'))->toBe(['المنصة', 'عن المنصة', 'الدعم', 'قانوني'])
        ->and($columns[0]['links'])->toHaveCount(4)
        // The empty slot is skipped, not rendered as a blank row.
        ->and($columns[2]['links'])->toHaveCount(3)
        ->and($columns[0]['links'][0])->toBe(['label' => 'الفروع', 'href' => '/branches']);
});

it('keeps the copyright year live rather than freezing it', function () {
    /*
     * 🔴 The requested default was "… pal education 2026". Stored literally
     * that is correct for a few months and wrong forever after, so the
     * default stores `:year` and the footer substitutes at render time —
     * which renders the requested string today and stays right later.
     */
    $this->seed(SettingSeeder::class);

    expect(Setting::get('footer.copyright_text'))->toContain(':year')
        ->not->toContain('2026');

    get(route('home'))
        ->assertOk()
        ->assertSee('جميع الحقوق محفوظة — pal education '.date('Y'));
});

// ── Saving from the admin panel ─────────────────────────────────

it('saves the closing CTA from its settings tab', function () {
    actingAs($this->admin)->put(route('admin.settings.final-cta.update'), [
        'final_cta' => [
            'title' => 'عنوان ختامي', 'sub_title' => 'تمهيد.',
            'btn_text' => 'ابدأ', 'btn_link' => '/register', 'sub_title2' => 'بلا بطاقة.',
        ],
    ])->assertRedirect(route('admin.settings.index', ['tab' => 'final_cta']));

    expect(Setting::get('final_cta.title'))->toBe('عنوان ختامي')
        ->and(Setting::get('final_cta.sub_title2'))->toBe('بلا بطاقة.');
});

it('requires the parts of the closing CTA that cannot be empty', function () {
    // 🔴 A fixed layout band: an empty title or a dead button is a blank
    // stripe in the last thing a visitor sees.
    foreach (['title', 'btn_text', 'btn_link'] as $field) {
        actingAs($this->admin)->put(route('admin.settings.final-cta.update'), [
            'final_cta' => array_merge(
                ['title' => 'عنوان', 'btn_text' => 'ابدأ', 'btn_link' => '/register'],
                [$field => ''],
            ),
        ])->assertSessionHasErrors('final_cta.'.$field);
    }

    // The destination lands in an href on a public page.
    actingAs($this->admin)->put(route('admin.settings.final-cta.update'), [
        'final_cta' => ['title' => 'عنوان', 'btn_text' => 'ابدأ', 'btn_link' => 'javascript:alert(1)'],
    ])->assertSessionHasErrors('final_cta.btn_link');
});

it('saves the footer copy and its columns from the settings tab', function () {
    actingAs($this->admin)->put(route('admin.settings.footer.update'), footerPayload([
        'newsletter_sub_title' => 'وصف النشرة.',
        'col1_link1_text' => 'رابط أول', 'col1_link1_url' => '/one',
        'col1_link2_text' => 'رابط ثانٍ', 'col1_link2_url' => '/two',
    ]))->assertRedirect(route('admin.settings.index', ['tab' => 'footer']));

    $columns = Setting::footerColumns();

    // ⚠️ A column with no title or no links is dropped entirely rather than
    // rendered as a lone heading over empty space.
    expect($columns)->toHaveCount(1)
        ->and($columns[0]['title'])->toBe('عمود أول')
        ->and($columns[0]['links'])->toBe([
            ['label' => 'رابط أول', 'href' => '/one'],
            ['label' => 'رابط ثانٍ', 'href' => '/two'],
        ]);
});

// ── Link parsing ────────────────────────────────────────────────

it('refuses an unsafe destination instead of silently rewriting it', function () {
    /*
     * 🔁 This is the behaviour that changed. With the pipe-separated
     * textarea a bad destination was rewritten to `#` on read and the admin
     * never learned. Explicit fields are validated on save, so it comes
     * back as an error against the exact field.
     */
    actingAs($this->admin)->put(route('admin.settings.footer.update'), footerPayload([
        'col1_link1_text' => 'سيّئ',
        'col1_link1_url' => 'javascript:alert(1)',
    ]))->assertSessionHasErrors('footer.col1_link1_url');

    expect(Setting::get('footer.col1_link1_url'))->not->toBe('javascript:alert(1)');
});

it('still neutralises an unsafe destination written straight to the database', function () {
    /*
     * 🔴 Defence in depth, and the reason safeHref() survived the refactor.
     * Validation refuses bad INPUT; this makes bad STORED data harmless —
     * rows written before that validation existed, or edited by hand, would
     * otherwise reach every public page unchecked.
     */
    $this->seed(SettingSeeder::class);
    Setting::put('footer.col1_link1_url', 'javascript:alert(1)');

    expect(Setting::footerColumns()[0]['links'][0]['href'])->toBe('#')
        ->and(Setting::safeHref('data:text/html,x'))->toBe('#')
        ->and(Setting::safeHref('/ok'))->toBe('/ok')
        ->and(Setting::safeHref('https://x.test'))->toBe('https://x.test')
        ->and(Setting::safeHref(null))->toBe('#');

    get(route('home'))->assertOk()->assertDontSee('javascript:alert(1)', escape: false);
});

it('requires a link text and destination together', function () {
    // ⚠️ Text with no destination is a dead anchor; a destination with no
    // text renders nothing while looking configured. Both empty is the
    // normal way to leave a slot unused.
    actingAs($this->admin)->put(route('admin.settings.footer.update'), footerPayload([
        'col1_link1_text' => 'نصّ بلا وجهة',
        'col1_link1_url' => '',
    ]))->assertSessionHasErrors('footer.col1_link1_url');

    actingAs($this->admin)->put(route('admin.settings.footer.update'), footerPayload([
        'col1_link1_text' => '',
        'col1_link1_url' => '/orphan',
    ]))->assertSessionHasErrors('footer.col1_link1_text');

    actingAs($this->admin)->put(route('admin.settings.footer.update'), footerPayload([
        'col1_link1_text' => '',
        'col1_link1_url' => '',
    ]))->assertSessionHasNoErrors();
});

// ── Public rendering ────────────────────────────────────────────

it('renders the closing CTA on the home page from the database', function () {
    $this->seed(SettingSeeder::class);

    get(route('home'))
        ->assertOk()
        ->assertSee('فرعك بانتظارك')
        ->assertSee('سجّل مجاناً وشاهد أول محاضرة من كل مادة قبل أن تقرّر.')
        ->assertSee('أنشئ حسابك المجاني')
        ->assertSee('دون بطاقة ائتمان · يمكنك الإلغاء في أي وقت')
        ->assertSee(url('/register'), escape: false);
});

it('drops the optional CTA lines when they are cleared', function () {
    $this->seed(SettingSeeder::class);
    Setting::put('final_cta.sub_title', '');
    Setting::put('final_cta.sub_title2', '');

    get(route('home'))
        ->assertOk()
        // The required parts still render.
        ->assertSee('فرعك بانتظارك')
        ->assertSee('أنشئ حسابك المجاني')
        ->assertDontSee('دون بطاقة ائتمان · يمكنك الإلغاء في أي وقت');
});

it('renders the footer newsletter, columns and legal lines', function () {
    $this->seed(SettingSeeder::class);

    get(route('home'))
        ->assertOk()
        ->assertSee('كن أول من يعرف')
        ->assertSee('مستجدّات الوزارة، ومواعيد الامتحانات، والمواد الجديدة على المنصة — رسالة واحدة أسبوعياً، دون حشو.')
        ->assertSee('صُنع في فلسطين')
        // Column headings…
        ->assertSee('المنصة')
        ->assertSee('عن المنصة')
        ->assertSee('الدعم')
        ->assertSee('قانوني')
        // …and the link targets themselves.
        ->assertSee('href="/branches"', escape: false)
        ->assertSee('href="/contact"', escape: false)
        ->assertSee('href="/#faq"', escape: false);
});

it('renders the footer on a page with no controller behind it', function () {
    /*
     * 🔴 Why the footer reads settings directly. /contact is a Route::view
     * with nothing to pass an array through, and the footer still has to
     * render its dynamic copy there.
     */
    $this->seed(SettingSeeder::class);

    get(route('contact'))
        ->assertOk()
        ->assertSee('كن أول من يعرف')
        ->assertSee('صُنع في فلسطين')
        ->assertSee('href="/branches"', escape: false);
});

it('reflects an edited footer immediately on the public page', function () {
    $this->seed(SettingSeeder::class);

    actingAs($this->admin)->put(route('admin.settings.footer.update'), [
        'footer' => [
            'newsletter_title' => 'عنوان معدَّل',
            'newsletter_sub_title' => 'وصف معدَّل.',
            'copyright_text' => 'حقوق معدَّلة :year',
            'made_in_text' => '',
            'col1_title' => 'عمود وحيد',
            'col1_link1_text' => 'وجهة جديدة', 'col1_link1_url' => '/brand-new',
            'col2_title' => '', 'col3_title' => '', 'col4_title' => '',
        ],
    ]);

    get(route('home'))
        ->assertOk()
        ->assertSee('عنوان معدَّل')
        ->assertSee('عمود وحيد')
        ->assertSee('href="/brand-new"', escape: false)
        ->assertSee('حقوق معدَّلة '.date('Y'))
        // Cleared "made in" line disappears entirely.
        ->assertDontSee('صُنع في فلسطين')
        // And the columns that were emptied are gone.
        ->assertDontSee('قانوني');
});
