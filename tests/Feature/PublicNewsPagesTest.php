<?php

use App\Models\Admin;
use App\Models\News;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;

/*
| The public news pages.
|
| 🔴 Visibility needs BOTH gates: is_published AND published_at <= now. A
| draft and a scheduled article are equally invisible, and neither may be
| reached by someone holding its slug.
*/

beforeEach(function () {
    $this->author = tap(Admin::create(['full_name' => 'سارة المحرّرة'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

// ── Listing ─────────────────────────────────────────────────────

it('lists published articles with their by-line and date', function () {
    News::factory()->create([
        'title' => 'إعلان مواعيد الامتحانات',
        'summary' => 'أعلنت الوزارة الجدول الرسمي.',
        'author_id' => $this->author->id,
        'published_at' => now()->subDay(),
    ]);

    get(route('news.index'))
        ->assertOk()
        ->assertSee('إعلان مواعيد الامتحانات')
        ->assertSee('أعلنت الوزارة الجدول الرسمي.')
        ->assertSee('سارة المحرّرة');
});

it('hides drafts and articles scheduled for the future', function () {
    News::factory()->create(['title' => 'خبر منشور']);
    News::factory()->draft()->create(['title' => 'خبر مسودّة']);
    News::factory()->scheduled()->create(['title' => 'خبر مجدول']);

    $response = get(route('news.index'))->assertOk();

    expect($response->viewData('articles')->pluck('title')->all())->toBe(['خبر منشور']);

    $response->assertSee('خبر منشور')
        ->assertDontSee('خبر مسودّة')
        // 🔴 The scheduling case: switched ON but not due. Checking only
        // is_published would publish it a week early.
        ->assertDontSee('خبر مجدول');
});

it('shows the newest article first', function () {
    $older = News::factory()->create(['title' => 'أقدم', 'published_at' => now()->subWeek()]);
    $newer = News::factory()->create(['title' => 'أحدث', 'published_at' => now()->subHour()]);

    expect(get(route('news.index'))->viewData('articles')->pluck('id')->all())
        ->toBe([$newer->id, $older->id]);
});

it('names the platform when the author account is gone', function () {
    $article = News::factory()->create(['author_id' => $this->author->id]);

    // ⚠️ author_id is nullOnDelete: removing a staff account must not delete
    // the articles they wrote.
    $this->author->forceDelete();

    expect($article->fresh()->author_id)->toBeNull();

    // An empty by-line reads as a broken page, not as a departed colleague.
    get(route('news.index'))->assertOk()->assertSee(__('common.brand'));
});

it('shows an empty state rather than headings over nothing', function () {
    News::factory()->draft()->create();

    get(route('news.index'))
        ->assertOk()
        ->assertSee(__('public.news_empty_title'))
        // No search box either: filtering nothing is a control that cannot work.
        ->assertDontSee('data-news-search', false);
});

it('paginates rather than printing the whole archive', function () {
    News::factory()->count(config('app.paginate_page') + 3)->create();

    $response = get(route('news.index'))->assertOk();

    expect($response->viewData('articles')->count())->toBe(config('app.paginate_page'));

    $response->assertSee('?page=2', false);
});

// ── Detail ──────────────────────────────────────────────────────

it('renders an article with its content, author and date', function () {
    $article = News::factory()->create([
        'title' => 'إعلان مواعيد الامتحانات',
        'content' => 'نصّ الخبر الكامل الذي يجب أن يظهر في صفحة التفاصيل.',
        'author_id' => $this->author->id,
        'published_at' => now()->subDays(2),
    ]);

    get(route('news.show', $article))
        ->assertOk()
        ->assertSee('إعلان مواعيد الامتحانات')
        ->assertSee('نصّ الخبر الكامل الذي يجب أن يظهر في صفحة التفاصيل.')
        ->assertSee('سارة المحرّرة')
        ->assertSee($article->published_at->translatedFormat('j F Y'));
});

it('shows each article its OWN content', function () {
    /*
     * 🔴 The bug this page had: it carried one hard-coded article and
     * rendered it for EVERY slug, so every news URL showed the same story
     * under a different address.
     */
    News::factory()->create(['title' => 'الخبر الأول', 'content' => 'محتوى الخبر الأول تماماً.']);
    $second = News::factory()->create(['title' => 'الخبر الثاني', 'content' => 'محتوى الخبر الثاني تماماً.']);

    get(route('news.show', $second))
        ->assertOk()
        ->assertSee('محتوى الخبر الثاني تماماً.')
        ->assertDontSee('محتوى الخبر الأول تماماً.');
});

it('returns 404 for a draft, a scheduled article and a missing slug', function () {
    $draft = News::factory()->draft()->create(['slug' => 'draft-item']);
    $scheduled = News::factory()->scheduled()->create(['slug' => 'scheduled-item']);

    // 🔴 404, not 403: answering "forbidden" confirms the slug is real and
    // that something is being withheld.
    get(route('news.show', 'draft-item'))->assertNotFound();
    get(route('news.show', 'scheduled-item'))->assertNotFound();
    get(route('news.show', 'no-such-article'))->assertNotFound();

    expect($draft->exists)->toBeTrue()
        ->and($scheduled->exists)->toBeTrue();
});

it('returns 404 for a soft deleted article', function () {
    $article = News::factory()->create(['slug' => 'gone']);
    $article->delete();

    get(route('news.show', 'gone'))->assertNotFound();
});

it('escapes markup pasted into an article body', function () {
    // ⚠️ Admin-authored, but an admin pasting from a web page brings markup
    // with them, and this is a public page.
    $article = News::factory()->create([
        'content' => 'نصّ عادي <script>alert(1)</script> يتبعه المزيد.',
    ]);

    $html = get(route('news.show', $article))->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->toContain('&lt;script&gt;');
});

// ── Related ─────────────────────────────────────────────────────

it('shows related articles and never the one being read', function () {
    $current = News::factory()->create(['title' => 'الخبر المعروض', 'published_at' => now()->subDay()]);
    News::factory()->create(['title' => 'خبر ذو صلة أول', 'published_at' => now()->subDays(2)]);
    News::factory()->create(['title' => 'خبر ذو صلة ثانٍ', 'published_at' => now()->subDays(3)]);

    $response = get(route('news.show', $current))
        ->assertOk()
        ->assertSee(__('public.news_related_title'))
        ->assertSee('خبر ذو صلة أول')
        ->assertSee('خبر ذو صلة ثانٍ');

    // 🔴 A "related" grid linking back to the current page is a dead end.
    expect($response->viewData('related')->pluck('id'))->not->toContain($current->id);
});

it('keeps drafts out of the related grid too', function () {
    $current = News::factory()->create();
    News::factory()->draft()->create(['title' => 'مسودّة ذات صلة']);
    News::factory()->scheduled()->create(['title' => 'مجدول ذو صلة']);

    get(route('news.show', $current))
        ->assertOk()
        ->assertDontSee('مسودّة ذات صلة')
        ->assertDontSee('مجدول ذو صلة');
});

it('drops the related section entirely when there is nothing to relate', function () {
    $only = News::factory()->create();

    $response = get(route('news.show', $only))->assertOk();

    expect($response->viewData('related'))->toBeEmpty();

    // A heading over an empty grid is worse than no section.
    $response->assertDontSee(__('public.news_related_title'));
});

it('caps the related grid at three', function () {
    $current = News::factory()->create();
    News::factory()->count(6)->create();

    expect(get(route('news.show', $current))->viewData('related'))->toHaveCount(3);
});

// ── The shared CTA ──────────────────────────────────────────────

it('renders the shared CTA on both news pages', function () {
    Setting::query()->updateOrCreate(['key' => 'final_cta.title'], ['value' => 'عنوان الدعوة المخصّص']);

    $article = News::factory()->create();

    foreach ([route('news.index'), route('news.show', $article)] as $url) {
        get($url)->assertOk()->assertSee('عنوان الدعوة المخصّص');
    }
});

// ── Query budget ────────────────────────────────────────────────

it('does not query once per article for its by-line', function () {
    foreach (range(1, 8) as $i) {
        $author = tap(Admin::create(['full_name' => "كاتب {$i}"])->user()->create([
            'email' => "author{$i}@test.local", 'password' => bcrypt('secret'),
        ]))->syncRoleToShape();

        News::factory()->create(['author_id' => $author->id]);
    }

    // Warm up: the settings the CTA reads are queried on first render.
    get(route('news.index'))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();

    get(route('news.index'))->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    /*
     * 🔴 authorName() reads the DOMAIN row behind the account, so eager
     * loading only `author` still leaves one query per article. A fixed
     * budget, because the failure grows with every article added.
     */
    expect($count)->toBeLessThan(12);
});

// ── Cover images from the media relation ────────────────────────

it('renders the cover from the media relation on both pages', function () {
    $article = News::factory()->create(['title' => 'خبر بصورة']);
    $article->media()->create([
        'path' => 'images/img/blog/4.jpg',
        'type' => 'image',
        'alt_text' => 'صورة الغلاف',
        'display_order' => 1,
    ]);

    // 🔴 The path is no longer a column — the page can only find it through
    // the morph, which is what this asserts on both views.
    foreach ([route('news.index'), route('news.show', $article)] as $url) {
        get($url)->assertOk()->assertSee('images/img/blog/4.jpg', false);
    }
});

it('renders a card without a cover rather than a broken image', function () {
    // An article with no media row at all: cover_url is null and the card
    // falls back to its own placeholder.
    $article = News::factory()->create(['title' => 'خبر بلا صورة']);

    expect($article->cover_url)->toBeNull();

    get(route('news.index'))->assertOk()->assertSee('خبر بلا صورة');
    get(route('news.show', $article))->assertOk();
});

it('shows the first cover by display order when several are attached', function () {
    $article = News::factory()->create();

    // ⚠️ Inserted out of order on purpose: "first" must mean display_order,
    // not insertion order or id.
    $article->media()->create(['path' => 'images/img/blog/2.jpg', 'type' => 'image', 'display_order' => 5]);
    $article->media()->create(['path' => 'images/img/blog/1.jpg', 'type' => 'image', 'display_order' => 1]);

    expect($article->fresh()->cover_url)->toContain('images/img/blog/1.jpg');
});

it('does not query once per article for its cover', function () {
    foreach (range(1, 8) as $i) {
        $article = News::factory()->create();
        $article->media()->create([
            'path' => "images/img/blog/{$i}.jpg", 'type' => 'image', 'display_order' => 1,
        ]);
    }

    get(route('news.index'))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();

    get(route('news.index'))->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    /*
     * 🔴 cover_url reads the relation, so without eager loading `image` the
     * accessor is an N+1 hiding inside a property access — the hardest kind
     * to notice, because nothing in the template looks like a query.
     */
    expect($count)->toBeLessThan(12);
});
