<?php

use App\Enums\MediaType;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Testimonial;
use App\Support\PermissionCatalog;
use App\Support\TrashableResources;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Student reviews on the public home page.
|
| 🔁 The testimonials table was built deliberately WITHOUT a link to
| `students`, so that deleting an account could not tear a section out of the
| home page. A student_id link was requested and built — and the snapshot
| columns were kept, so the original guarantee still holds. Most of these
| tests exist to prove both halves of that.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->branch = Branch::create(['name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create([
        'full_name' => 'طالب', 'branch_id' => $this->branch->id, 'governorate' => 'nablus',
    ])->user()->create([
        'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

function reviewer(string $name = 'ليان', string $governorate = 'ramallah'): Student
{
    return Student::create([
        'full_name' => $name,
        'branch_id' => Branch::where('slug', 'scientific')->value('id'),
        'governorate' => $governorate,
        'country_code' => 'ps',
    ]);
}

function reviewPayload(array $overrides = []): array
{
    return array_merge([
        'student_id' => null,
        'name' => 'اسم مسجَّل يدوياً',
        'title' => 'الفرع العلمي · نابلس',
        'rate' => 5,
        'description' => 'رأي تجريبي عن المنصة.',
        'display_order' => 1,
        'is_active' => 1,
        // The radio group always submits this — one option is selected by
        // default — so the shared payload mirrors a real form post.
        'avatar_source' => Testimonial::AVATAR_STUDENT,
    ], $overrides);
}

// ── Schema, settings and seeding ────────────────────────────────

it('links reviews to students without a second rating column', function () {
    expect(Schema::hasColumn('testimonials', 'student_id'))->toBeTrue()
        ->and(Schema::hasColumn('testimonials', 'deleted_at'))->toBeTrue()
        // 🔴 `rate` IS the rating. A second `rating` column would be two
        // numbers for one score with nothing keeping them equal.
        ->and(Schema::hasColumn('testimonials', 'rate'))->toBeTrue()
        ->and(Schema::hasColumn('testimonials', 'rating'))->toBeFalse();
});

it('seeds the section header', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::get('testimonials.badge_text'))->toBe('آراء الطلبة')
        ->and(Setting::get('testimonials.title'))->toBe('ماذا قال من جرّبها')
        ->and(Setting::get('testimonials.sub_title'))->toContain('طلبة من فروع ومدن مختلفة');
});

it('seeds reviews linked to real student accounts', function () {
    $this->seed([BranchSeeder::class, TestimonialSeeder::class]);

    $reviews = Testimonial::active()->with('student.branch')->ordered()->get();

    expect($reviews)->toHaveCount(3);

    foreach ($reviews as $review) {
        expect($review->student_id)->not->toBeNull()
            ->and($review->student->user)->not->toBeNull()
            ->and($review->student->user->roleName())->toBe('student')
            ->and($review->rate)->toBeGreaterThanOrEqual(Testimonial::MIN_RATE)
            ->and($review->rate)->toBeLessThanOrEqual(Testimonial::MAX_RATE);
    }
});

it('does not duplicate reviews or accounts when reseeded', function () {
    $this->seed([BranchSeeder::class, TestimonialSeeder::class]);
    $this->seed(TestimonialSeeder::class);

    expect(Testimonial::count())->toBe(3)
        ->and(Student::where('full_name', 'ليان أبو ديّة')->count())->toBe(1);
});

// ── Live details vs stored snapshot ─────────────────────────────

it('reads the name, branch and city from the linked account', function () {
    $student = reviewer('ليان أبو ديّة', 'ramallah');
    $review = Testimonial::create([
        'student_id' => $student->id,
        'name' => 'اسم قديم',
        'title' => 'سطر قديم',
        'rate' => 5,
        'description' => 'رأي.',
    ]);

    $review->load('student.branch');

    // Live values win over the snapshot while the account exists.
    expect($review->displayName())->toBe('ليان أبو ديّة')
        ->and($review->displayMeta())->toBe('الفرع العلمي · رام الله والبيرة');
});

it('keeps the review standing when the student is deleted', function () {
    /*
     * 🔴 The guarantee the original snapshot design existed to protect: a
     * deleted account must not tear a section out of the home page. The FK
     * is nullOnDelete, so the link drops and the stored name takes over.
     */
    $student = reviewer('ليان أبو ديّة');
    $review = Testimonial::create([
        'student_id' => $student->id,
        'name' => 'ليان',
        'title' => 'الفرع العلمي · رام الله',
        'rate' => 5,
        'description' => 'رأي يبقى بعد الحساب.',
    ]);

    $student->forceDelete();

    $review->refresh()->load('student');

    expect($review->student_id)->toBeNull()
        ->and($review->displayName())->toBe('ليان')
        ->and($review->displayMeta())->toBe('الفرع العلمي · رام الله')
        ->and($review->description)->toBe('رأي يبقى بعد الحساب.');

    get(route('home'))->assertOk()->assertSee('رأي يبقى بعد الحساب.');
});

it('clamps a rating the column would happily store', function () {
    // ⚠️ unsignedTinyInteger accepts 255, and the database has no idea that
    // seven stars do not exist. A raw loop would print 255 of them.
    $review = Testimonial::create(['name' => 'س', 'rate' => 99, 'description' => 'رأي.']);

    expect($review->stars())->toBe(Testimonial::MAX_RATE);

    get(route('home'))->assertOk();
});

// ── Permissions ─────────────────────────────────────────────────

it('registers four granular testimonial permissions', function () {
    expect(PermissionCatalog::names())
        ->toContain('view-testimonials')
        ->toContain('create-testimonials')
        ->toContain('edit-testimonials')
        ->toContain('delete-testimonials');
});

it('blocks guests and students from every testimonial route', function () {
    get(route('admin.testimonials.index'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.testimonials.index'))->assertForbidden();

    // 🔴 The direct POST is what matters: hiding a link is not a guard.
    actingAs($this->student)->post(route('admin.testimonials.store'), reviewPayload())->assertForbidden();

    expect(Testimonial::count())->toBe(0);
});

it('enforces each testimonial permission independently', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('delete-testimonials');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $review = Testimonial::create(['name' => 'س', 'rate' => 5, 'description' => 'رأي.']);

    actingAs($this->admin)->delete(route('admin.testimonials.destroy', $review))->assertForbidden();

    actingAs($this->admin)->get(route('admin.testimonials.index'))->assertOk();
    actingAs($this->admin)->get(route('admin.testimonials.create'))->assertOk();
});

// ── CRUD ────────────────────────────────────────────────────────

it('creates, edits and toggles a review', function () {
    $student = reviewer('ليان أبو ديّة');

    actingAs($this->admin)->post(route('admin.testimonials.store'), reviewPayload([
        'student_id' => $student->id,
        'rate' => 4,
    ]))->assertRedirect(route('admin.testimonials.index'));

    $review = Testimonial::sole();

    expect($review->student_id)->toBe($student->id)
        ->and($review->rate)->toBe(4)
        ->and($review->is_active)->toBeTrue();

    actingAs($this->admin)->get(route('admin.testimonials.index'))->assertOk()->assertSee('ليان أبو ديّة');
    actingAs($this->admin)->get(route('admin.testimonials.edit', $review))->assertOk();

    actingAs($this->admin)->put(
        route('admin.testimonials.update', $review),
        reviewPayload(['student_id' => $student->id, 'description' => 'رأي معدَّل.', 'rate' => 5]),
    );

    expect($review->fresh()->description)->toBe('رأي معدَّل.')
        ->and($review->fresh()->rate)->toBe(5);

    actingAs($this->admin)->patch(route('admin.testimonials.toggle', $review));

    expect($review->fresh()->is_active)->toBeFalse();
});

it('rejects a rating outside the displayed range', function () {
    foreach ([0, 6, 255, 'خمسة'] as $bad) {
        actingAs($this->admin)
            ->post(route('admin.testimonials.store'), reviewPayload(['rate' => $bad]))
            ->assertSessionHasErrors('rate');
    }

    expect(Testimonial::count())->toBe(0);
});

it('requires a name only when no account backs the review', function () {
    // Without a student, the stored name is the only one the card has.
    actingAs($this->admin)
        ->post(route('admin.testimonials.store'), reviewPayload(['name' => '', 'student_id' => null]))
        ->assertSessionHasErrors('name');

    $student = reviewer('ليان أبو ديّة');

    actingAs($this->admin)
        ->post(route('admin.testimonials.store'), reviewPayload(['name' => '', 'title' => '', 'student_id' => $student->id]))
        ->assertSessionHasNoErrors();

    $review = Testimonial::sole();

    /*
     * 🔴 The snapshot was CAPTURED on save, not left blank. Two things this
     * pins: testimonials.name is NOT NULL and ConvertEmptyStringsToNull
     * turns a blank field into null, which used to be a 500 on save; and a
     * review only survives its student if the fallback was written while
     * the account still existed.
     */
    expect($review->displayName())->toBe('ليان أبو ديّة')
        ->and($review->name)->toBe('ليان أبو ديّة')
        ->and($review->title)->toBe('الفرع العلمي · رام الله والبيرة');
});

// ── Soft delete and trash ───────────────────────────────────────

it('soft deletes and offers undo pointing at the trash restore route', function () {
    $review = Testimonial::create(['name' => 'ليان', 'rate' => 5, 'description' => 'رأي.']);

    actingAs($this->admin)->from(route('admin.testimonials.index'))
        ->delete(route('admin.testimonials.destroy', $review));

    expect(Testimonial::count())->toBe(0)
        ->and(Testimonial::withTrashed()->count())->toBe(1);

    // 🔴 Undo is a PATCH form with CSRF in the shell, never a link inside a
    // notice: a restore mutates server state.
    expect(session('admin.undo'))->toBe([
        'url' => route('admin.trash.restore', ['type' => 'testimonials', 'id' => $review->id]),
        'label' => 'تراجع',
    ]);
});

it('registers a testimonials tab in the central trash', function () {
    expect(TrashableResources::keys())->toContain('testimonials')
        ->and(TrashableResources::permissionFor('testimonials'))->toBe('delete-testimonials');
});

it('restores and force deletes from the trash tab', function () {
    $student = reviewer('ليان أبو ديّة');
    $review = tap(Testimonial::create([
        'student_id' => $student->id, 'name' => 'ليان', 'rate' => 5, 'description' => 'رأي محذوف.',
    ]))->delete();

    actingAs($this->admin)->get(route('admin.trash.index'))
        ->assertOk()
        ->assertSee('آراء الطلبة')
        // The trash row shows the reviewer, never a bare id.
        ->assertSee('ليان أبو ديّة');

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'testimonials', 'id' => $review->id]))
        ->assertRedirect();

    expect(Testimonial::whereKey($review->id)->exists())->toBeTrue();

    $review->delete();

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'testimonials', 'id' => $review->id]));

    expect(Testimonial::withTrashed()->whereKey($review->id)->exists())->toBeFalse();
});

it('guards the testimonials trash tab with its own permission', function () {
    $review = tap(Testimonial::create(['name' => 'س', 'rate' => 5, 'description' => 'رأي.']))->delete();

    Role::findByName('admin', 'web')->syncPermissions(['delete-branches']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'testimonials', 'id' => $review->id]))
        ->assertForbidden();

    expect(Testimonial::withTrashed()->whereKey($review->id)->first()->deleted_at)->not->toBeNull();
});

// ── Settings tab ────────────────────────────────────────────────

it('saves the section header from the settings tab', function () {
    actingAs($this->admin)->put(route('admin.settings.testimonials.update'), [
        'testimonials' => ['badge_text' => 'شارة', 'title' => 'عنوان القسم', 'sub_title' => 'تمهيد.'],
    ])->assertRedirect(route('admin.settings.index', ['tab' => 'testimonials']));

    expect(Setting::get('testimonials.title'))->toBe('عنوان القسم');
});

it('rejects an empty badge or title', function () {
    foreach (['badge_text', 'title'] as $field) {
        actingAs($this->admin)->put(route('admin.settings.testimonials.update'), [
            'testimonials' => array_merge(
                ['badge_text' => 'شارة', 'title' => 'عنوان'],
                [$field => ''],
            ),
        ])->assertSessionHasErrors('testimonials.'.$field);
    }
});

// ── Public page ─────────────────────────────────────────────────

it('renders the section on the home page from the database', function () {
    $this->seed([BranchSeeder::class, TestimonialSeeder::class, SettingSeeder::class]);

    get(route('home'))
        ->assertOk()
        ->assertSee('ماذا قال من جرّبها')
        ->assertSee('طلبة من فروع ومدن مختلفة، هذا ما قالوه بعد فصل دراسي كامل على المنصة.')
        ->assertSee('ليان أبو ديّة')
        ->assertSee('أحمد الشوّا')
        // Branch and city, read live from the linked accounts.
        ->assertSee('الفرع الأدبي · نابلس');
});

it('hides an inactive review from the home page', function () {
    Testimonial::create(['name' => 'ظاهر', 'rate' => 5, 'description' => 'رأي ظاهر.', 'display_order' => 1]);
    Testimonial::create([
        'name' => 'مخفي', 'rate' => 5, 'description' => 'رأي مخفي.',
        'display_order' => 2, 'is_active' => false,
    ]);

    get(route('home'))
        ->assertOk()
        ->assertSee('رأي ظاهر.')
        ->assertDontSee('رأي مخفي.');
});

it('renders the page with no reviews at all', function () {
    // ⚠️ Every value on a slide is derived, so the whole list can be empty.
    // The header still has to render rather than throw.
    $this->seed(SettingSeeder::class);

    expect(Testimonial::count())->toBe(0);

    get(route('home'))->assertOk()->assertSee('ماذا قال من جرّبها');
});

it('loads the reviews without an N+1 as more are added', function () {
    /*
     * 🔴 student.branch is TWO hops from a testimonial, and the card shows
     * the branch on every slide — the exact shape that turns into two extra
     * queries per review without the eager load.
     */
    foreach (['ليان', 'أحمد', 'سارة'] as $index => $name) {
        Testimonial::create([
            'student_id' => reviewer($name)->id,
            'name' => $name,
            'rate' => 5,
            'description' => 'رأي '.$name,
            'display_order' => $index + 1,
        ]);
    }

    // ⚠️ Warm-up first: Spatie loads its permission table once per process,
    // so the very first request runs queries the second one does not.
    get(route('home'))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();
    get(route('home'))->assertOk();
    $before = count(DB::getQueryLog());
    DB::disableQueryLog();

    Testimonial::create([
        'student_id' => reviewer('رابع')->id,
        'name' => 'رابع',
        'rate' => 4,
        'description' => 'رأي رابع',
        'display_order' => 4,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    get(route('home'))->assertOk();
    $after = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Flat, not merely small.
    expect($after)->toBe($before);
});

// ── The moderation queue ────────────────────────────────────────

it('lists pending submissions rather than hiding them', function () {
    /*
     * 🔴 The whole point of this screen. Student submissions land inactive,
     * so an is_active filter on the base query would hide exactly the rows
     * an admin came to act on — the queue would look permanently empty.
     */
    $student = reviewer('ليان أبو ديّة');

    Testimonial::create([
        'student_id' => $student->id, 'name' => 'ليان', 'rate' => 5,
        'description' => 'رأي بانتظار الموافقة.', 'is_active' => false,
    ]);
    Testimonial::create([
        'name' => 'منشور', 'rate' => 5,
        'description' => 'رأي منشور.', 'is_active' => true,
    ]);

    actingAs($this->admin)->get(route('admin.testimonials.index'))
        ->assertOk()
        ->assertSee('رأي بانتظار الموافقة.')
        ->assertSee('رأي منشور.')
        // Pending is its own state, not "hidden".
        ->assertSee('بانتظار الموافقة');
});

it('filters the list by status', function () {
    Testimonial::create(['name' => 'أ', 'rate' => 5, 'description' => 'رأي معلّق.', 'is_active' => false]);
    Testimonial::create(['name' => 'ب', 'rate' => 5, 'description' => 'رأي منشور.', 'is_active' => true]);

    actingAs($this->admin)->get(route('admin.testimonials.index', ['status' => 'pending']))
        ->assertOk()
        ->assertSee('رأي معلّق.')
        ->assertDontSee('رأي منشور.');

    actingAs($this->admin)->get(route('admin.testimonials.index', ['status' => 'active']))
        ->assertOk()
        ->assertSee('رأي منشور.')
        ->assertDontSee('رأي معلّق.');

    // Both, and an unknown value falls back to both rather than erroring.
    foreach ([['status' => 'all'], ['status' => 'nonsense'], []] as $query) {
        actingAs($this->admin)->get(route('admin.testimonials.index', $query))
            ->assertOk()
            ->assertSee('رأي معلّق.')
            ->assertSee('رأي منشور.');
    }
});

it('shows how many reviews are waiting from every tab', function () {
    // Work waiting is what this screen exists to surface, so the count is
    // computed unfiltered and visible even while another tab is selected.
    Testimonial::create(['name' => 'أ', 'rate' => 5, 'description' => 'رأي معلّق.', 'is_active' => false]);
    Testimonial::create(['name' => 'ب', 'rate' => 5, 'description' => 'آخر معلّق.', 'is_active' => false]);
    Testimonial::create(['name' => 'ج', 'rate' => 5, 'description' => 'رأي منشور.', 'is_active' => true]);

    actingAs($this->admin)->get(route('admin.testimonials.index', ['status' => 'active']))
        ->assertOk()
        ->assertSee('بانتظار الموافقة')
        ->assertSee('رأياً بانتظار الموافقة');
});

it('publishes a pending review in one click and returns to the queue', function () {
    $review = Testimonial::create([
        'name' => 'ليان', 'rate' => 5, 'description' => 'رأي بانتظار الموافقة.', 'is_active' => false,
    ]);

    // Not visible to the public while pending.
    get(route('home'))->assertOk()->assertDontSee('رأي بانتظار الموافقة.');

    actingAs($this->admin)
        ->patch(route('admin.testimonials.toggle', $review), ['status' => 'pending'])
        // ⚠️ Back to the TAB the admin was on, from a hidden field rather
        // than the referer — approving from the queue must not dump them
        // back on "all" and lose their place.
        ->assertRedirect(route('admin.testimonials.index', ['status' => 'pending']));

    expect($review->fresh()->is_active)->toBeTrue();

    get(route('home'))->assertOk()->assertSee('رأي بانتظار الموافقة.');
});

it('offers the approve button only while a review is pending', function () {
    $pending = Testimonial::create(['name' => 'أ', 'rate' => 5, 'description' => 'رأي معلّق.', 'is_active' => false]);

    actingAs($this->admin)->get(route('admin.testimonials.index'))
        ->assertOk()
        ->assertSee('موافقة ونشر');

    $pending->update(['is_active' => true]);

    actingAs($this->admin)->get(route('admin.testimonials.index'))
        ->assertOk()
        ->assertDontSee('موافقة ونشر');
});

it('names the filter in its empty state, not the table', function () {
    // "No reviews yet" under the pending tab reads as "nothing is waiting"
    // when the real message is "nothing is waiting in this view".
    Testimonial::create(['name' => 'ب', 'rate' => 5, 'description' => 'رأي منشور.', 'is_active' => true]);

    actingAs($this->admin)->get(route('admin.testimonials.index', ['status' => 'pending']))
        ->assertOk()
        ->assertSee('لا شيء بانتظار الموافقة')
        ->assertDontSee('لا توجد آراء بعد');
});

// ── Action button styling ───────────────────────────────────────

it('gives the approve button the same outline shape as edit and delete', function () {
    /*
     * 🔴 Same height, padding, radius and border as «تعديل» and «حذف» —
     * differing only in text colour, which is how this panel already
     * separates a destructive action from a neutral one. The filled
     * `primary` it replaced was a different class of object in a row of
     * outline controls.
     */
    Testimonial::create(['name' => 'أ', 'rate' => 5, 'description' => 'رأي معلّق.', 'is_active' => false]);

    $html = actingAs($this->admin)->get(route('admin.testimonials.index'))->assertOk()->getContent();

    $outline = 'border border-hairline bg-canvas';

    expect($html)
        ->toContain($outline.' text-accent-deep')   // موافقة ونشر
        ->toContain($outline.' text-steel')         // تعديل
        ->toContain($outline.' text-error-deep')    // حذف
        // The filled variant must not come back on this row.
        ->not->toContain('shadow-accent-glow hover:bg-accent-deep">'.PHP_EOL.'                                            <svg');
});

// ── Avatar source ───────────────────────────────────────────────

it('defaults an existing review to the student avatar', function () {
    // 🔴 The default preserves current behaviour: every row before this
    // column existed was rendering the account photo.
    expect(Schema::hasColumn('testimonials', 'avatar_source'))->toBeTrue();

    $review = Testimonial::create(['name' => 'أ', 'rate' => 5, 'description' => 'رأي.']);

    expect($review->fresh()->avatar_source)->toBe(Testimonial::AVATAR_STUDENT)
        ->and($review->usesCustomAvatar())->toBeFalse();
});

it('saves the source and attaches a custom portrait morphically', function () {
    Storage::fake('custom');

    // ⚠️ ->create(), not ->image(): GD is not installed in this environment,
    // so UploadedFile::fake()->image() cannot generate a real file.
    actingAs($this->admin)->post(route('admin.testimonials.store'), reviewPayload([
        'avatar_source' => Testimonial::AVATAR_CUSTOM,
        'photo' => UploadedFile::fake()->create('portrait.jpg', 120, 'image/jpeg'),
    ]))->assertRedirect(route('admin.testimonials.index'));

    $review = Testimonial::sole();

    expect($review->avatar_source)->toBe(Testimonial::AVATAR_CUSTOM)
        ->and($review->media)->toHaveCount(1)
        ->and($review->photo->type)->toBe(MediaType::Image)
        ->and($review->photo->path)->toStartWith('uploads/testimonials/')
        ->and($review->photoUrl())->toContain('uploads/testimonials/');
});

it('rejects an unknown source and a non-image upload', function () {
    Storage::fake('custom');

    actingAs($this->admin)
        ->post(route('admin.testimonials.store'), reviewPayload(['avatar_source' => 'nonsense']))
        ->assertSessionHasErrors('avatar_source');

    actingAs($this->admin)->post(route('admin.testimonials.store'), reviewPayload([
        'avatar_source' => Testimonial::AVATAR_CUSTOM,
        'photo' => UploadedFile::fake()->create('script.pdf', 50, 'application/pdf'),
    ]))->assertSessionHasErrors('photo');

    expect(Testimonial::count())->toBe(0);
});

it('keeps the existing portrait when an edit uploads nothing', function () {
    /*
     * 🔴 An empty file input is NOT "remove the picture". An admin fixing a
     * typo in the quote leaves it empty every time, and treating that as a
     * delete would wipe the portrait on an unrelated save.
     */
    Storage::fake('custom');

    actingAs($this->admin)->post(route('admin.testimonials.store'), reviewPayload([
        'avatar_source' => Testimonial::AVATAR_CUSTOM,
        'photo' => UploadedFile::fake()->create('portrait.jpg', 120, 'image/jpeg'),
    ]));

    $review = Testimonial::sole();
    $originalPath = $review->photo->path;

    actingAs($this->admin)->put(route('admin.testimonials.update', $review), reviewPayload([
        'avatar_source' => Testimonial::AVATAR_CUSTOM,
        'description' => 'نصّ معدَّل بلا رفع صورة.',
    ]));

    expect($review->fresh()->photo->path)->toBe($originalPath)
        ->and($review->fresh()->media()->count())->toBe(1);
});

it('leaves a custom upload on disk when the source switches back', function () {
    // The choice stays reversible: switching to `student` must not destroy
    // the file, so switching back does not mean re-uploading it.
    Storage::fake('custom');

    actingAs($this->admin)->post(route('admin.testimonials.store'), reviewPayload([
        'avatar_source' => Testimonial::AVATAR_CUSTOM,
        'photo' => UploadedFile::fake()->create('portrait.jpg', 120, 'image/jpeg'),
    ]));

    $review = Testimonial::sole();

    actingAs($this->admin)->put(route('admin.testimonials.update', $review), reviewPayload([
        'avatar_source' => Testimonial::AVATAR_STUDENT,
    ]));

    expect($review->fresh()->avatar_source)->toBe(Testimonial::AVATAR_STUDENT)
        ->and($review->fresh()->media()->count())->toBe(1);
});

// ── What the public card renders ────────────────────────────────

it('renders the custom portrait when that source is chosen', function () {
    $student = reviewer('ليان أبو ديّة');
    $student->update(['avatar_path' => 'images/voices/student-9.jpg']);

    $review = Testimonial::create([
        'student_id' => $student->id, 'name' => 'ليان', 'rate' => 5,
        'description' => 'رأي بصورة مخصّصة.', 'is_active' => true,
        'avatar_source' => Testimonial::AVATAR_CUSTOM,
    ]);

    $review->media()->create([
        'path' => 'uploads/testimonials/custom.jpg',
        'type' => MediaType::Image,
        'display_order' => 1,
    ]);

    get(route('home'))
        ->assertOk()
        ->assertSee('uploads/testimonials/custom.jpg', escape: false)
        // The account photo must not win when custom was chosen.
        ->assertDontSee('images/voices/student-9.jpg', escape: false);
});

it('renders the account photo when the student source is chosen', function () {
    /*
     * 🔴 The reversal this column exists for. Attached media used to win
     * unconditionally, so an admin who had uploaded once could not go back
     * to the account photo without deleting the file. Here the upload still
     * exists and is deliberately NOT used.
     */
    $student = reviewer('ليان أبو ديّة');
    $student->update(['avatar_path' => 'images/voices/student-9.jpg']);

    $review = Testimonial::create([
        'student_id' => $student->id, 'name' => 'ليان', 'rate' => 5,
        'description' => 'رأي بصورة الحساب.', 'is_active' => true,
        'avatar_source' => Testimonial::AVATAR_STUDENT,
    ]);

    $review->media()->create([
        'path' => 'uploads/testimonials/ignored.jpg',
        'type' => MediaType::Image,
        'display_order' => 1,
    ]);

    get(route('home'))
        ->assertOk()
        ->assertSee('images/voices/student-9.jpg', escape: false)
        ->assertDontSee('uploads/testimonials/ignored.jpg', escape: false);
});

it('falls back rather than rendering an empty frame', function () {
    /*
     * ⚠️ "custom" with nothing uploaded yet is a normal half-finished state,
     * and an empty frame is a worse answer than the account photo. With
     * neither available the card falls through to its placeholder.
     */
    $student = reviewer('ليان أبو ديّة');
    $student->update(['avatar_path' => 'images/voices/student-9.jpg']);

    $chosenButEmpty = Testimonial::create([
        'student_id' => $student->id, 'name' => 'ليان', 'rate' => 5,
        'description' => 'اختير مخصّص بلا رفع.', 'is_active' => true,
        'avatar_source' => Testimonial::AVATAR_CUSTOM,
    ]);

    expect($chosenButEmpty->photoUrl())->toContain('images/voices/student-9.jpg');

    $noPhotoAnywhere = Testimonial::create([
        'name' => 'بلا صورة', 'rate' => 5, 'description' => 'رأي بلا أي صورة.', 'is_active' => true,
    ]);

    expect($noPhotoAnywhere->photoUrl())->toBeNull();

    get(route('home'))->assertOk()->assertSee('رأي بلا أي صورة.');
});

it('seeds both sources so neither path goes unexercised', function () {
    // Seeding all three the same way would leave the student-avatar branch
    // invisible to anyone running the app locally.
    $this->seed([BranchSeeder::class, TestimonialSeeder::class]);

    $sources = Testimonial::ordered()->pluck('avatar_source');

    expect($sources)->toContain(Testimonial::AVATAR_CUSTOM)
        ->toContain(Testimonial::AVATAR_STUDENT);

    $custom = Testimonial::where('avatar_source', Testimonial::AVATAR_CUSTOM)->first();
    $fromAccount = Testimonial::where('avatar_source', Testimonial::AVATAR_STUDENT)->first();

    expect($custom->media()->count())->toBe(1)
        ->and($custom->photoUrl())->toContain('images/voices/')
        // The account-photo row needs no attached media at all.
        ->and($fromAccount->media()->count())->toBe(0)
        ->and($fromAccount->photoUrl())->toContain('images/voices/');
});
