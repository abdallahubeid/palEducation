<?php

use App\Enums\MediaType;
use App\Models\Admin;
use App\Models\Media;
use App\Models\News;
use App\Models\User;
use Database\Seeders\NewsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\get;
use function Pest\Laravel\seed;

/*
| NewsSeeder — the sample articles that make the public page look real.
|
| 🔴 It runs in environments that already have data, so re-running it must
| refresh rather than duplicate. Everything below is that guarantee plus the
| checks that the seeded rows are actually usable by the public page.
*/

beforeEach(function () {
    seed(RoleSeeder::class);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن المنصّة'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

it('seeds published articles with attached covers', function () {
    seed(NewsSeeder::class);

    $articles = News::with('image')->get();

    expect($articles)->toHaveCount(7);

    foreach ($articles as $article) {
        expect($article->title)->not->toBe('')
            ->and($article->summary)->not->toBeNull()
            // Long enough to be a real article rather than a placeholder line.
            ->and(mb_strlen($article->content))->toBeGreaterThan(200)
            ->and($article->is_published)->toBeTrue()
            // 🔴 Published in the PAST: an article dated in the future is
            // seeded invisible, and a seeder whose output does not appear on
            // the page it decorates has failed silently.
            ->and($article->isVisible())->toBeTrue()
            ->and($article->image)->not->toBeNull()
            ->and($article->image->type)->toBe(MediaType::Image);
    }
});

it('attributes the articles to a real admin account', function () {
    seed(NewsSeeder::class);

    $article = News::first();

    expect($article->author_id)->toBe($this->admin->id)
        ->and($article->authorName())->toBe('أدمن المنصّة');
});

it('points every cover at a file that actually exists', function () {
    seed(NewsSeeder::class);

    /*
     * 🔴 A media row pointing at a missing file renders a broken image on the
     * public page — worse than the card's own placeholder. The seeder skips
     * artwork it cannot find, so every row that IS written must resolve.
     */
    foreach (Media::where('mediable_type', News::class)->get() as $media) {
        expect(File::exists(public_path($media->path)))->toBeTrue(
            "Missing seeded artwork: {$media->path}"
        );
    }
});

it('refreshes rather than duplicates when run twice', function () {
    seed(NewsSeeder::class);
    seed(NewsSeeder::class);

    // ⚠️ Both counts matter. Keying only the article would still stack a
    // second media row per run and leave "first by display order" undefined.
    expect(News::count())->toBe(7)
        ->and(Media::where('mediable_type', News::class)->count())->toBe(7);
});

it('gives each article a distinct slug that resolves publicly', function () {
    seed(NewsSeeder::class);

    $slugs = News::pluck('slug');

    expect($slugs->unique())->toHaveCount(7);

    // The seeded slugs are the article's public URL — each must actually
    // load, not just exist as a string.
    foreach (News::all() as $article) {
        get(route('news.show', $article))->assertOk()->assertSee($article->title);
    }
});

it('seeds without an admin account rather than inventing one', function () {
    /*
     * ⚠️ author_id is nullable and the page falls back to the platform's
     * name, so a fresh database with no staff must still seed. Creating an
     * admin here instead would produce a staff account nobody asked for —
     * with no password anyone knows.
     */
    User::query()->where('userable_type', Admin::class)->forceDelete();

    seed(NewsSeeder::class);

    expect(News::count())->toBe(7)
        ->and(News::first()->author_id)->toBeNull();

    get(route('news.index'))->assertOk()->assertSee(__('common.brand'));
});

it('renders the seeded articles on the public page', function () {
    seed(NewsSeeder::class);

    // The end-to-end point of the seeder: the page it decorates looks real.
    get(route('news.index'))
        ->assertOk()
        ->assertSee('إعلان مواعيد امتحانات التوجيهي للدورة الأولى 2026')
        ->assertSee('images/img/blog/4.jpg', false);
});
