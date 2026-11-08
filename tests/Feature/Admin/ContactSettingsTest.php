<?php

use App\Models\Admin;
use App\Models\Setting;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| The contact page, driven from `settings`.
|
| ⚠️ /contact moved from Route::view to a controller once it gained
| database-backed content. A closure route would have failed route:cache for
| the whole application, not just itself.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

/** A complete, valid payload — override one field to test it. */
function contactPayload(array $overrides = []): array
{
    return ['contact' => array_merge([
        'badge_text' => 'تواصل معنا',
        'title' => 'كيف نقدر نساعدك؟',
        'sub_title' => 'تمهيد.',
        'whatsapp' => '+970 599 000 111',
        'phone' => '+970 599 000 222',
        'email' => 'hello@paleducation.test',
        'address' => 'رام الله',
        'working_hours' => "السبت — الخميس: 9:00 ص — 6:00 م\nالجمعة: مغلق",
        'faq_notice' => 'تنبيه.',
        'faq_btn_text' => 'الأسئلة الشائعة',
        'faq_btn_link' => '/#faq',
    ], $overrides)];
}

// ── Seeded defaults ─────────────────────────────────────────────

it('seeds every contact key', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get('contact.badge_text'))->toBe('تواصل معنا')
        ->and(Setting::get('contact.title'))->toBe('كيف نقدر نساعدك؟')
        ->and(Setting::get('contact.sub_title'))->toContain('ونردّ عادةً خلال يوم عمل واحد.')
        ->and(Setting::get('contact.whatsapp'))->toBe('+970 599 123 456')
        ->and(Setting::get('contact.phone'))->toBe('+970 599 123 456')
        ->and(Setting::get('contact.email'))->toBe('support@paleducation.ps')
        ->and(Setting::get('contact.address'))->toBe('رام الله — شارع الإرسال، عمارة النخيل، ط5')
        ->and(Setting::get('contact.faq_btn_text'))->toBe('الأسئلة الشائعة')
        ->and(Setting::get('contact.faq_btn_link'))->toBe('/#faq')
        ->and(Setting::get('contact.working_hours'))->toContain('الجمعة: مغلق');
});

// ── Parsing helpers ─────────────────────────────────────────────

it('splits an hours row on the first colon only', function () {
    /*
     * 🔴 Arabic clock times carry colons of their own. Splitting on the last
     * colon, or on every colon, would cut "9:00 ص — 6:00 م" in half.
     */
    $this->seed(SettingSeeder::class);

    expect(Setting::workingHoursRows())->toBe([
        ['label' => 'السبت — الخميس', 'value' => '9:00 ص — 6:00 م'],
        ['label' => 'الجمعة', 'value' => 'مغلق'],
        // A line with no colon is a note, not a row with a blank label.
        ['label' => null, 'value' => 'رسائل واتساب خارج الدوام يُرَدّ عليها صباح اليوم التالي.'],
    ]);
});

it('reduces a human-readable number to something tel: and wa.me accept', function () {
    /*
     * 🔴 Both reject spaces; wa.me additionally rejects the leading plus.
     * Passing the display string straight in produces a link that silently
     * does nothing on a real phone.
     */
    expect(Setting::dialable('+970 599 123 456'))->toBe('+970599123456')
        ->and(Setting::dialable('+970 599 123 456', keepPlus: false))->toBe('970599123456')
        ->and(Setting::dialable('(02) 295-1234'))->toBe('+022951234')
        ->and(Setting::dialable(null))->toBe('');
});

// ── Saving from the admin panel ─────────────────────────────────

it('saves every contact key from its settings tab', function () {
    actingAs($this->admin)->put(route('admin.settings.contact.update'), contactPayload())
        ->assertRedirect(route('admin.settings.index', ['tab' => 'contact']));

    expect(Setting::get('contact.title'))->toBe('كيف نقدر نساعدك؟')
        ->and(Setting::get('contact.whatsapp'))->toBe('+970 599 000 111')
        ->and(Setting::get('contact.email'))->toBe('hello@paleducation.test')
        ->and(Setting::get('contact.working_hours'))->toContain('الجمعة: مغلق');
});

it('requires the header fields the page cannot render without', function () {
    foreach (['badge_text', 'title'] as $field) {
        actingAs($this->admin)
            ->put(route('admin.settings.contact.update'), contactPayload([$field => '']))
            ->assertSessionHasErrors('contact.'.$field);
    }
});

it('refuses to leave the page with no way to reach anyone', function () {
    /*
     * 🔴 A contact page whose every channel has been cleared invites people
     * to get in touch with no route to do it, and nothing on the screen
     * would say so.
     */
    actingAs($this->admin)->put(route('admin.settings.contact.update'), contactPayload([
        'whatsapp' => '', 'phone' => '', 'email' => '',
    ]))->assertSessionHasErrors('contact.whatsapp');

    // Any ONE of them is enough.
    actingAs($this->admin)->put(route('admin.settings.contact.update'), contactPayload([
        'whatsapp' => '', 'phone' => '', 'email' => 'only@paleducation.test',
    ]))->assertSessionHasNoErrors();
});

it('validates the email and the FAQ link', function () {
    actingAs($this->admin)
        ->put(route('admin.settings.contact.update'), contactPayload(['email' => 'ليس بريداً']))
        ->assertSessionHasErrors('contact.email');

    // The FAQ destination lands in an href on a public page.
    actingAs($this->admin)
        ->put(route('admin.settings.contact.update'), contactPayload(['faq_btn_link' => 'javascript:alert(1)']))
        ->assertSessionHasErrors('contact.faq_btn_link');

    // ⚠️ Text and destination are filled together or not at all.
    actingAs($this->admin)
        ->put(route('admin.settings.contact.update'), contactPayload(['faq_btn_link' => '']))
        ->assertSessionHasErrors('contact.faq_btn_link');

    actingAs($this->admin)
        ->put(route('admin.settings.contact.update'), contactPayload(['faq_btn_text' => '', 'faq_btn_link' => '']))
        ->assertSessionHasNoErrors();
});

// ── The public page ─────────────────────────────────────────────

it('renders the header, channels, hours and notice from the database', function () {
    $this->seed(SettingSeeder::class);

    get(route('contact'))
        ->assertOk()
        ->assertSee('كيف نقدر نساعدك؟')
        ->assertSee('سؤال عن اشتراك، مشكلة تقنية، أو اقتراح — اكتب لنا ونردّ عادةً خلال يوم عمل واحد.')
        // Channels, each with the destination its scheme requires.
        ->assertSee('+970 599 123 456')
        ->assertSee('https://wa.me/970599123456', escape: false)
        ->assertSee('tel:+970599123456', escape: false)
        ->assertSee('mailto:support@paleducation.ps', escape: false)
        ->assertSee('رام الله — شارع الإرسال، عمارة النخيل، ط5')
        // Hours, split into rows and a note.
        ->assertSee('السبت — الخميس')
        ->assertSee('9:00 ص — 6:00 م')
        ->assertSee('رسائل واتساب خارج الدوام يُرَدّ عليها صباح اليوم التالي.')
        // FAQ deflection.
        ->assertSee('معظم الأسئلة عن الاشتراكات والدفع مُجابة في صفحة الأسعار — قد توفّر على نفسك انتظار الردّ.')
        ->assertSee('href="/#faq"', escape: false);
});

it('drops a channel row whose value was cleared', function () {
    $this->seed(SettingSeeder::class);
    Setting::put('contact.phone', '');
    Setting::put('contact.address', '');

    get(route('contact'))
        ->assertOk()
        // WhatsApp and email survive…
        ->assertSee('https://wa.me/970599123456', escape: false)
        ->assertSee('mailto:support@paleducation.ps', escape: false)
        // …and the cleared rows leave nothing behind.
        ->assertDontSee('tel:', escape: false)
        ->assertDontSee('رام الله — شارع الإرسال، عمارة النخيل، ط5');
});

it('hides the FAQ notice entirely when it is cleared', function () {
    $this->seed(SettingSeeder::class);
    Setting::put('contact.faq_notice', '');
    Setting::put('contact.faq_btn_text', '');
    Setting::put('contact.faq_btn_link', '');

    /*
     * ⚠️ Scoped to the <aside>, not the whole page. The FOOTER also links
     * `/#faq` from its support column, so a page-wide assertDontSee reports
     * the notice as still present when the aside has correctly dropped it.
     */
    $html = get(route('contact'))->assertOk()->getContent();
    $start = strpos($html, '<aside');
    $aside = substr($html, $start, strpos($html, '</aside>', $start) - $start);

    expect($aside)->not->toContain('معظم الأسئلة عن الاشتراكات والدفع')
        ->not->toContain('href="/#faq"');
});

it('reflects an admin edit immediately on the public page', function () {
    $this->seed(SettingSeeder::class);

    actingAs($this->admin)->put(route('admin.settings.contact.update'), contactPayload([
        'title' => 'عنوان معدَّل',
        'whatsapp' => '+970 598 777 000',
        'working_hours' => "الأحد: 8:00 ص — 2:00 م\nملاحظة معدَّلة.",
    ]));

    get(route('contact'))
        ->assertOk()
        ->assertSee('عنوان معدَّل')
        // The dial-safe form is derived, so it follows the display value.
        ->assertSee('https://wa.me/970598777000', escape: false)
        ->assertSee('الأحد')
        ->assertSee('8:00 ص — 2:00 م')
        ->assertSee('ملاحظة معدَّلة.');
});
