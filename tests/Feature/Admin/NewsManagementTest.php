<?php

use App\Enums\MediaType;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\Media;
use App\Models\News;
use App\Models\Setting;
use App\Models\Student;
use App\Support\PermissionCatalog;
use App\Support\TrashableResources;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| News — admin CRUD, the "أخبارنا" settings tab, and the public pages.
|
| 🔴 Two gates decide public visibility and BOTH must pass: `is_published`
| (the editor's switch) and `published_at` (the clock). An article switched on
| and dated next week is published to the editor and invisible to the visitor.
| Most of what follows is that distinction checked from different angles.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'سارة المحرّرة'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $branch = Branch::create(['name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

function newsPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'إعلان مواعيد امتحانات التوجيهي',
        'summary' => 'أعلنت الوزارة الجدول الرسمي للامتحانات.',
        'content' => 'نصّ الخبر الكامل الذي يظهر في صفحة التفاصيل، بطول كافٍ للقبول.',
        'is_published' => '1',
        'published_at' => now()->format('Y-m-d\TH:i'),
    ], $overrides);
}

// ── Admin CRUD ──────────────────────────────────────────────────

it('creates an article attributed to the logged-in admin', function () {
    actingAs($this->admin)
        ->post(route('admin.news.store'), newsPayload())
        ->assertRedirect(route('admin.news.index'));

    $article = News::sole();

    expect($article->title)->toBe('إعلان مواعيد امتحانات التوجيهي')
        /*
         * 🔴 From the SESSION, never the form. A posted author_id would let
         * anyone attribute an article to a colleague.
         */
        ->and($article->author_id)->toBe($this->admin->id)
        ->and($article->authorName())->toBe('سارة المحرّرة')
        ->and($article->is_published)->toBeTrue()
        ->and($article->slug)->not->toBe('');
});

it('ignores an author_id posted in the form', function () {
    actingAs($this->admin)
        ->post(route('admin.news.store'), newsPayload(['author_id' => $this->student->id]))
        ->assertRedirect();

    expect(News::sole()->author_id)->toBe($this->admin->id);
});

it('builds a usable unique slug from an Arabic title', function () {
    /*
     * 🔴 Str::slug() strips non-latin characters entirely, so an Arabic title
     * reduces to an EMPTY string — and with `slug` unique, the second article
     * would collide on ''. Two identical titles must still produce two rows.
     */
    actingAs($this->admin)->post(route('admin.news.store'), newsPayload())->assertRedirect();
    actingAs($this->admin)->post(route('admin.news.store'), newsPayload())->assertRedirect();

    $slugs = News::pluck('slug');

    expect($slugs)->toHaveCount(2)
        ->and($slugs->unique())->toHaveCount(2)
        ->and($slugs->first())->not->toBe('');
});

it('keeps the slug when the title is unchanged, and moves it when it changes', function () {
    $article = News::factory()->create(['title' => 'عنوان أصلي', 'slug' => 'original-slug']);

    // ⚠️ An edit that fixed a typo in the body must not break links already
    // shared for this article.
    actingAs($this->admin)
        ->put(route('admin.news.update', $article), newsPayload([
            'title' => 'عنوان أصلي',
            'content' => 'نصّ معدَّل بطول كافٍ للقبول في التحقّق.',
        ]))
        ->assertRedirect();

    expect($article->fresh()->slug)->toBe('original-slug');

    actingAs($this->admin)
        ->put(route('admin.news.update', $article), newsPayload(['title' => 'عنوان جديد تماماً']))
        ->assertRedirect();

    expect($article->fresh()->slug)->not->toBe('original-slug');
});

it('updates an article without changing its author', function () {
    $article = News::factory()->create(['author_id' => $this->admin->id]);

    $other = tap(Admin::create(['full_name' => 'أدمن آخر'])->user()->create([
        'email' => 'other@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    actingAs($other)
        ->put(route('admin.news.update', $article), newsPayload(['title' => 'عنوان معدَّل']))
        ->assertRedirect();

    $article->refresh();

    // The by-line records who WROTE it, not who last touched it.
    expect($article->title)->toBe('عنوان معدَّل')
        ->and($article->author_id)->toBe($this->admin->id);
});

it('turns the published switch off as well as on', function () {
    /*
     * 🔴 An unchecked checkbox sends NOTHING. Read with ??, the toggle would
     * be one-way: switchable on and never off.
     */
    $article = News::factory()->create(['is_published' => true]);

    actingAs($this->admin)
        ->put(route('admin.news.update', $article), newsPayload(['is_published' => '0']))
        ->assertRedirect();

    expect($article->fresh()->is_published)->toBeFalse();
});

it('toggles publication without moving the publish date', function () {
    $article = News::factory()->create([
        'is_published' => false,
        'published_at' => now()->subMonth(),
    ]);

    $originalDate = $article->published_at;

    actingAs($this->admin)
        ->patch(route('admin.news.toggle', $article))
        ->assertRedirect(route('admin.news.index'));

    $article->refresh();

    // ⚠️ Moving the date to now would reorder the whole list every time
    // somebody unhid an old notice.
    expect($article->is_published)->toBeTrue()
        ->and($article->published_at->eq($originalDate))->toBeTrue();
});

it('attaches an uploaded cover as a media row', function () {
    Storage::fake('public');

    // ⚠️ ->create(), not ->image(): the GD extension is disabled in this
    // environment and fake image generation throws.
    actingAs($this->admin)
        ->post(route('admin.news.store'), newsPayload([
            'image' => UploadedFile::fake()->create('cover.jpg', 120, 'image/jpeg'),
        ]))
        ->assertRedirect();

    $article = News::sole();
    $media = $article->image;

    expect($media)->not->toBeNull()
        // 🔴 A real polymorphic row pointing back at this article, not a
        // string column: the whole point of the refactor.
        ->and($media->mediable_type)->toBe(News::class)
        ->and($media->mediable_id)->toBe($article->id)
        ->and($media->type)->toBe(MediaType::Image)
        // The 'storage/' prefix is what makes asset() resolve through the
        // public symlink — the same convention the sliders use.
        ->and($media->path)->toStartWith('storage/news/')
        ->and($article->cover_url)->toContain($media->path);

    Storage::disk('public')->assertExists(str_replace('storage/', '', $media->path));
});

it('replaces the cover in place instead of stacking media rows', function () {
    Storage::fake('public');

    actingAs($this->admin)
        ->post(route('admin.news.store'), newsPayload([
            'image' => UploadedFile::fake()->create('first.jpg', 100, 'image/jpeg'),
        ]))
        ->assertRedirect();

    $article = News::sole();
    $firstPath = $article->image->path;

    actingAs($this->admin)
        ->put(route('admin.news.update', $article), newsPayload([
            'image' => UploadedFile::fake()->create('second.jpg', 100, 'image/jpeg'),
        ]))
        ->assertRedirect();

    /*
     * 🔴 ONE row, still. A plain create() on every edit would stack a media
     * row per save until "first by display order" was whichever the query
     * happened to return.
     */
    expect($article->media()->count())->toBe(1)
        ->and($article->fresh()->image->path)->not->toBe($firstPath);

    // The replaced file is removed rather than left orphaned on disk.
    Storage::disk('public')->assertMissing(str_replace('storage/', '', $firstPath));
});

it('keeps the current cover when an edit uploads none', function () {
    Storage::fake('public');

    $article = News::factory()->create();
    $article->media()->create([
        'path' => 'images/img/blog/1.jpg', 'type' => 'image', 'display_order' => 1,
    ]);

    actingAs($this->admin)
        ->put(route('admin.news.update', $article), newsPayload())
        ->assertRedirect();

    // A blank file field means "leave it alone", never "remove it".
    expect($article->fresh()->image->path)->toBe('images/img/blog/1.jpg')
        ->and($article->media()->count())->toBe(1);
});

it('never unlinks a repository asset when replacing a cover', function () {
    Storage::fake('public');

    /*
     * 🔴 Seeded covers point at REPOSITORY files under public/images. Deleting
     * one would remove it from the working tree of every environment and it
     * would never come back from the trash screen — hence the 'storage/'
     * prefix guard in deleteFile().
     */
    $article = News::factory()->create();
    $article->media()->create([
        'path' => 'images/img/blog/1.jpg', 'type' => 'image', 'display_order' => 1,
    ]);

    actingAs($this->admin)
        ->put(route('admin.news.update', $article), newsPayload([
            'image' => UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg'),
        ]))
        ->assertRedirect();

    expect(File::exists(public_path('images/img/blog/1.jpg')))->toBeTrue();
});
it('refuses an article with no title or too little content', function () {
    actingAs($this->admin)
        ->post(route('admin.news.store'), newsPayload(['title' => '', 'content' => 'قصير']))
        ->assertSessionHasErrors(['title', 'content']);

    expect(News::count())->toBe(0);
});

it('lists articles and separates scheduled from published', function () {
    News::factory()->create(['title' => 'خبر منشور', 'author_id' => $this->admin->id]);
    News::factory()->draft()->create(['title' => 'خبر مسودّة']);
    News::factory()->scheduled()->create(['title' => 'خبر مجدول']);

    $ids = fn (array $query) => actingAs($this->admin)
        ->get(route('admin.news.index', $query))
        ->assertOk()
        ->viewData('news')
        ->pluck('title')
        ->all();

    expect($ids(['status' => 'published']))->toBe(['خبر منشور'])
        ->and($ids(['status' => 'draft']))->toBe(['خبر مسودّة'])
        /*
         * 🔴 Scheduled is its own state. Without this tab those articles look
         * published in the list and are nowhere on the site.
         */
        ->and($ids(['status' => 'scheduled']))->toBe(['خبر مجدول'])
        ->and($ids([]))->toHaveCount(3);

    actingAs($this->admin)
        ->get(route('admin.news.index'))
        ->assertSee('سارة المحرّرة')
        ->assertSee('مجدول');
});

it('sends a deleted article to the trash and restores it', function () {
    $article = News::factory()->create(['title' => 'خبر للحذف']);

    actingAs($this->admin)
        ->delete(route('admin.news.destroy', $article))
        ->assertRedirect(route('admin.news.index'))
        ->assertSessionHas('admin.undo');

    expect(News::count())->toBe(0)
        ->and(News::withTrashed()->count())->toBe(1);

    actingAs($this->admin)
        ->get(route('admin.trash.index', ['type' => 'news']))
        ->assertOk()
        ->assertSee('خبر للحذف');

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'news', 'id' => $article->id]))
        ->assertRedirect();

    expect(News::count())->toBe(1);
});

it('does not reuse the slug of a trashed article', function () {
    // ⚠️ `slug` is unique and the index does not ignore deleted_at, so a
    // trashed row still occupies its slug — a plain create() would collide.
    $trashed = News::factory()->create(['title' => 'عنوان مكرّر']);
    $trashed->delete();

    actingAs($this->admin)
        ->post(route('admin.news.store'), newsPayload(['title' => 'عنوان مكرّر']))
        ->assertRedirect();

    expect(News::sole()->slug)->not->toBe($trashed->slug);
});

// ── Permissions ─────────────────────────────────────────────────

it('enforces each news permission independently', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('delete-news');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $article = News::factory()->create();

    actingAs($this->admin)
        ->delete(route('admin.news.destroy', $article))
        ->assertForbidden();

    expect(News::count())->toBe(1);

    actingAs($this->admin)->get(route('admin.news.index'))->assertOk();
    actingAs($this->admin)->get(route('admin.news.create'))->assertOk();
    actingAs($this->admin)->get(route('admin.news.edit', $article))->assertOk();
});

it('keeps news administration away from guests and students', function () {
    $article = News::factory()->create();

    get(route('admin.news.index'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.news.index'))->assertForbidden();

    // The direct POST is the one that matters — hiding a link is not a guard.
    actingAs($this->student)->post(route('admin.news.store'), newsPayload())->assertForbidden();
    actingAs($this->student)->delete(route('admin.news.destroy', $article))->assertForbidden();

    expect(News::count())->toBe(1);
});

it('registers the news permissions and its trash type', function () {
    expect(PermissionCatalog::names())
        ->toContain('view-news')
        ->toContain('create-news')
        ->toContain('edit-news')
        ->toContain('delete-news')
        ->and(array_keys(TrashableResources::all()))->toContain('news');
});

// ── The "أخبارنا" settings tab ──────────────────────────────────

it('saves the news header settings and shows them on the public page', function () {
    actingAs($this->admin)
        ->put(route('admin.settings.news.update'), [
            'news' => [
                'badge_text' => 'شارة مخصّصة',
                'title' => 'عنوان مخصّص للأخبار',
                'sub_title' => 'نصّ تمهيدي مخصّص.',
            ],
        ])
        ->assertRedirect();

    expect(Setting::get('news.title'))->toBe('عنوان مخصّص للأخبار');

    get(route('news.index'))
        ->assertOk()
        ->assertSee('شارة مخصّصة')
        ->assertSee('عنوان مخصّص للأخبار')
        ->assertSee('نصّ تمهيدي مخصّص.');
});

it('falls back to the default header when nothing is configured', function () {
    expect(Setting::query()->where('key', 'like', 'news.%')->count())->toBe(0);

    get(route('news.index'))
        ->assertOk()
        ->assertSee(Setting::NEWS_DEFAULTS['news.title']);
});

it('refuses an empty news header title', function () {
    // The header is the page's h1; an empty one leaves the page untitled.
    actingAs($this->admin)
        ->put(route('admin.settings.news.update'), [
            'news' => ['badge_text' => '', 'title' => '', 'sub_title' => ''],
        ])
        ->assertSessionHasErrors(['news.badge_text', 'news.title']);
});

it('shows the news tab in the settings screen', function () {
    actingAs($this->admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('أخبارنا');
});

// ── Media cleanup ───────────────────────────────────────────────

it('purges the cover row and file when an article is permanently deleted', function () {
    Storage::fake('public');

    actingAs($this->admin)
        ->post(route('admin.news.store'), newsPayload([
            'image' => UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg'),
        ]))
        ->assertRedirect();

    $article = News::sole();
    $path = $article->image->path;

    $article->delete();

    // ⚠️ Soft delete leaves the media alone — the article can still come back
    // from the trash, and it must come back with its cover.
    expect(Media::where('mediable_type', News::class)->count())->toBe(1);

    actingAs($this->admin)
        ->delete(route('admin.trash.force-destroy', ['type' => 'news', 'id' => $article->id]))
        ->assertRedirect();

    /*
     * 🔴 mediable_id has NO foreign key — polymorphic columns cannot have
     * one — so nothing cascades. Declaring media() is what opts this model
     * into TrashManager::purgeMedia; without it a permanently deleted article
     * would leave an orphan row and an orphan file behind for ever.
     */
    expect(Media::withTrashed()->where('mediable_type', News::class)->count())->toBe(0);

    Storage::disk('public')->assertMissing(str_replace('storage/', '', $path));
});
