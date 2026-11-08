<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Testimonial;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/*
| Student review submission — the home page modal.
|
| 🔴 Two guarantees run through every test here:
|   1. the reviewer comes from the SESSION, never from the request; and
|   2. a submission is stored INACTIVE and waits for an admin.
|
| The second one is why the form can be open to every student at all: this
| is the public landing page, and publishing straight to it would make the
| form the cheapest defacement surface in the product.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->branch = Branch::create(['name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = Student::create([
        'full_name' => 'ليان أبو ديّة',
        'branch_id' => $this->branch->id,
        'governorate' => 'ramallah',
        'country_code' => 'ps',
    ]);

    $this->studentUser = tap($this->student->user()->create([
        'email' => 'layan@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

function submission(array $overrides = []): array
{
    return array_merge([
        'rate' => 5,
        'description' => 'المنصة رتّبت دراستي بشكل لم أتوقّعه، والاختبار بعد كل محاضرة غيّر طريقتي.',
    ], $overrides);
}

// ── Settings ────────────────────────────────────────────────────

it('seeds the button text and an empty link', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get('testimonials.btn_text'))->toBe('أضف رأيك')
        // ⚠️ Empty is the NORMAL state for the link, not a missing value:
        // it means "open the modal in place".
        ->and(Setting::get('testimonials.btn_link'))->toBe('');
});

it('saves the button fields from the settings tab', function () {
    $admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    actingAs($admin)->put(route('admin.settings.testimonials.update'), [
        'testimonials' => [
            'badge_text' => 'شارة', 'title' => 'عنوان',
            'btn_text' => 'شاركنا رأيك', 'btn_link' => '/register',
        ],
    ])->assertRedirect(route('admin.settings.index', ['tab' => 'testimonials']));

    expect(Setting::get('testimonials.btn_text'))->toBe('شاركنا رأيك')
        ->and(Setting::get('testimonials.btn_link'))->toBe('/register');

    // The link still lands in an href on a public page.
    actingAs($admin)->put(route('admin.settings.testimonials.update'), [
        'testimonials' => [
            'badge_text' => 'شارة', 'title' => 'عنوان', 'btn_link' => 'javascript:alert(1)',
        ],
    ])->assertSessionHasErrors('testimonials.btn_link');
});

// ── The button on the home page ─────────────────────────────────

it('renders the button as a modal trigger when no link is set', function () {
    $this->seed(SettingSeeder::class);

    get(route('home'))
        ->assertOk()
        ->assertSee('أضف رأيك')
        ->assertSee('open-testimonial-modal', escape: false)
        ->assertSee('data-testimonial-open', escape: false);
});

it('renders the button as a plain link when one is set', function () {
    $this->seed(SettingSeeder::class);
    Setting::put('testimonials.btn_link', '/register');

    $html = get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('أضف رأيك')
        // A link, not a trigger: no dispatch on this button.
        ->not->toContain('data-testimonial-open');
});

it('hides the button when the text is cleared', function () {
    // 🔴 Empty text is the off switch.
    $this->seed(SettingSeeder::class);
    Setting::put('testimonials.btn_text', '');

    get(route('home'))
        ->assertOk()
        ->assertDontSee('data-testimonial-open', escape: false);
});

// ── What the modal shows ────────────────────────────────────────

it('shows guests an invitation with login and register actions', function () {
    get(route('home'))
        ->assertOk()
        ->assertSee('يرجى تسجيل الدخول أو إنشاء حساب لمشاركة رأيك مع زملائك.')
        ->assertSee(route('auth.login'), escape: false)
        ->assertSee(route('auth.register'), escape: false)
        // 🔴 No form for a guest — the refusal is server-side, not a
        // disabled button.
        ->assertDontSee(route('testimonials.store'), escape: false);
});

it('shows a signed-in student the rating form', function () {
    actingAs($this->studentUser)->get(route('home'))
        ->assertOk()
        ->assertSee(route('testimonials.store'), escape: false)
        ->assertSee('name="rate"', escape: false)
        ->assertSee('name="description"', escape: false)
        // Says plainly that nothing appears instantly.
        ->assertSee('يُنشر الرأي بعد مراجعته.')
        ->assertDontSee('يرجى تسجيل الدخول أو إنشاء حساب لمشاركة رأيك مع زملائك.');
});

it('never asks the student who they are', function () {
    // 🔴 The reviewer comes from the session. A student_id input would let
    // any signed-in account file a review under someone else's name.
    $html = actingAs($this->studentUser)->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('name="student_id"')
        ->not->toContain('name="name"')
        ->not->toContain('name="title"');
});

// ── Storing a submission ────────────────────────────────────────

it('stores a student submission as pending, never live', function () {
    /*
     * 🔴 The guarantee that makes an open form acceptable on a landing
     * page. If this ever flips to true by default, any student can publish
     * arbitrary text above the fold.
     */
    actingAs($this->studentUser)
        ->post(route('testimonials.store'), submission(['rate' => 4]))
        ->assertRedirect(route('home').'#voices');

    $review = Testimonial::sole();

    expect($review->is_active)->toBeFalse()
        ->and($review->student_id)->toBe($this->student->id)
        ->and($review->rate)->toBe(4)
        // Snapshot captured while the account exists.
        ->and($review->name)->toBe('ليان أبو ديّة')
        ->and($review->title)->toBe('الفرع العلمي · رام الله والبيرة');

    // And it is genuinely absent from the public page until approved.
    get(route('home'))->assertOk()->assertDontSee($review->description);
});

it('publishes only after an admin approves it', function () {
    actingAs($this->studentUser)->post(route('testimonials.store'), submission());

    $review = Testimonial::sole();

    $admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    actingAs($admin)->patch(route('admin.testimonials.toggle', $review));

    expect($review->fresh()->is_active)->toBeTrue();

    get(route('home'))->assertOk()->assertSee($review->description);
});

it('replaces a students earlier review and returns it to pending', function () {
    /*
     * 🔴 One review per student. Without this the carousel becomes one
     * student's personal feed. And an edited review has not been approved —
     * only its predecessor was.
     */
    actingAs($this->studentUser)->post(route('testimonials.store'), submission());

    Testimonial::sole()->update(['is_active' => true]);

    actingAs($this->studentUser)
        ->post(route('testimonials.store'), submission(['description' => 'رأي محدَّث بعد فصل كامل على المنصة.']));

    $review = Testimonial::sole();

    expect(Testimonial::count())->toBe(1)
        ->and($review->description)->toBe('رأي محدَّث بعد فصل كامل على المنصة.')
        ->and($review->is_active)->toBeFalse();
});

// ── Who may post ────────────────────────────────────────────────

it('refuses a guest and sends them to log in', function () {
    post(route('testimonials.store'), submission())->assertRedirect(route('auth.login'));

    expect(Testimonial::count())->toBe(0);
});

it('refuses an authenticated non-student', function () {
    /*
     * ⚠️ A teacher is authenticated but has no `students` row, and
     * testimonials.student_id points at that table. Without the explicit
     * check this would be a foreign-key 500 instead of a refusal.
     */
    $teacher = tap(Teacher::create(['full_name' => 'أ. معلّم'])->user()->create([
        'email' => 'teacher@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    actingAs($teacher)->post(route('testimonials.store'), submission())->assertForbidden();

    expect(Testimonial::count())->toBe(0);
});

it('ignores a reviewer id posted in the request', function () {
    // 🔴 The reviewer is resolved from the session, so a forged student_id
    // is simply not read.
    $other = Student::create([
        'full_name' => 'طالب آخر', 'branch_id' => $this->branch->id, 'governorate' => 'nablus',
    ]);

    actingAs($this->studentUser)
        ->post(route('testimonials.store'), submission(['student_id' => $other->id, 'is_active' => 1]));

    $review = Testimonial::sole();

    expect($review->student_id)->toBe($this->student->id)
        ->and($review->is_active)->toBeFalse();
});

// ── Validation ──────────────────────────────────────────────────

it('rejects a rating outside the displayed range', function () {
    foreach ([0, 6, 255] as $bad) {
        actingAs($this->studentUser)
            ->post(route('testimonials.store'), submission(['rate' => $bad]))
            ->assertSessionHasErrors('rate');
    }

    expect(Testimonial::count())->toBe(0);
});

it('rejects an empty or throwaway review', function () {
    actingAs($this->studentUser)
        ->post(route('testimonials.store'), submission(['description' => '']))
        ->assertSessionHasErrors('description');

    actingAs($this->studentUser)
        ->post(route('testimonials.store'), submission(['description' => 'جيدة']))
        ->assertSessionHasErrors('description');

    expect(Testimonial::count())->toBe(0);
});

it('answers a fetch submission with JSON rather than a redirect', function () {
    // The modal submits asynchronously; the same route serves both paths.
    actingAs($this->studentUser)
        ->postJson(route('testimonials.store'), submission())
        ->assertOk()
        ->assertJson(['pending' => true]);

    expect(Testimonial::sole()->is_active)->toBeFalse();
});
