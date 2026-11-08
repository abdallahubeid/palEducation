<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * News CRUD.
 *
 * 🔴 Two gates decide public visibility and both are editable here:
 * `is_published` is the switch, `published_at` is the clock. An article can
 * be switched on and dated next week — published to the editor, invisible to
 * the visitor. That is scheduling, and collapsing the pair would remove it.
 */
class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['published', 'draft', 'scheduled'], true)
            ? $request->query('status')
            : 'all';

        return view('admin.news.index', [
            'news' => News::query()
                // ⚠️ author.userable, not author: displayName() reads the
                // domain row behind the account, so loading only the user
                // still leaves one query per row.
                ->with(['author.userable', 'image'])
                ->when($status === 'published', fn ($query) => $query->published())
                ->when($status === 'draft', fn ($query) => $query->where('is_published', false))
                ->when($status === 'scheduled', fn ($query) => $query
                    ->where('is_published', true)
                    ->where('published_at', '>', now()))
                ->latestFirst()
                ->paginate(config('app.paginate_page'))
                ->withQueryString(),

            'activeStatus' => $status,

            'counts' => [
                'all' => News::query()->count(),
                'published' => News::query()->published()->count(),
                'draft' => News::query()->where('is_published', false)->count(),
                'scheduled' => News::query()
                    ->where('is_published', true)
                    ->where('published_at', '>', now())
                    ->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.news.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $article = News::create([
            ...$data,
            /*
             * 🔴 The author is taken from the SESSION, never from the form.
             * A posted author_id would let anyone attribute an article to a
             * colleague — and there is no reason for the field to exist.
             */
            'author_id' => $request->user()->id,
            'slug' => News::uniqueSlug($data['title']),
        ]);

        // ⚠️ After create(), never before: a morph row needs its parent's id.
        $this->syncImage($request, $article);

        flash()->success('تمت إضافة الخبر بنجاح.');

        return redirect()->route('admin.news.index');
    }

    public function edit(News $news): View
    {
        return view('admin.news.edit', ['news' => $news]);
    }

    public function update(Request $request, News $news): RedirectResponse
    {
        $data = $this->validated($request);

        $news->update([
            ...$data,
            /*
             * ⚠️ The slug follows the title, but only when the title actually
             * changed. Regenerating it on every save would break the links of
             * an article already shared, for an edit that fixed a typo in the
             * body.
             */
            'slug' => $news->title === $data['title']
                ? $news->slug
                : News::uniqueSlug($data['title'], $news->id),
        ]);

        $this->syncImage($request, $news);

        flash()->success('تم تعديل الخبر بنجاح.');

        return redirect()->route('admin.news.index');
    }

    /**
     * Flips the published switch.
     *
     * ⚠️ Touches `is_published` alone. Publishing an article that was dated
     * last month must keep that date — moving it to now would reorder the
     * whole list every time someone unhid an old notice.
     */
    public function toggle(News $news): RedirectResponse
    {
        $news->update(['is_published' => ! $news->is_published]);

        flash()->success($news->is_published ? 'الخبر منشور الآن.' : 'أُخفي الخبر.');

        return redirect()->route('admin.news.index');
    }

    public function destroy(News $news): RedirectResponse
    {
        $news->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        session()->flash('admin.undo', [
            'url' => route('admin.trash.restore', ['type' => 'news', 'id' => $news->id]),
            'label' => 'تراجع',
        ]);

        return redirect()->route('admin.news.index');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'min:20'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:4096'],
            'published_at' => ['nullable', 'date'],
        ], [
            'content.min' => 'اكتب نصّاً أطول قليلاً — 20 حرفاً على الأقل.',
        ]);

        return [
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'],

            /*
             * 🔴 A checkbox absent from the request means UNCHECKED, not
             * "leave as is". Reading it with ?? would make the toggle
             * one-way: switchable on, never off.
             */
            'is_published' => $request->boolean('is_published'),

            // Defaults to now, so an article saved without a date is
            // published immediately rather than invisible for ever.
            'published_at' => $data['published_at'] ?? now(),
        ];
    }

    /**
     * Attaches or replaces the cover through the polymorphic media relation.
     *
     * 🔴 updateOrCreate on the relation, not create: an article has ONE cover,
     * and a plain create on every edit would stack a new media row per save
     * until the "first by display order" was whichever the query happened to
     * return.
     *
     * ⚠️ A missing file means "leave it alone", never "remove it". A blank
     * file input is what a browser sends for every unchanged edit.
     */
    private function syncImage(Request $request, News $news): void
    {
        $file = $request->file('image');

        if ($file === null) {
            return;
        }

        $previous = $news->image?->path;

        // 'storage/' prefix so Media::url()'s asset() call resolves through
        // the public symlink — the same convention the sliders use.
        $path = $file->store('news', 'public');

        $news->media()->updateOrCreate([], [
            'path' => 'storage/'.$path,
            'type' => 'image',
            'display_order' => 1,
        ]);

        $this->deleteFile($previous);
    }

    /**
     * Removes a replaced file from disk.
     *
     * 🔴 The prefix check is a guard, not a formality: seeded artwork lives
     * under public/images as REPOSITORY files, and deleting one would remove
     * it from the working tree of every environment and never come back from
     * the trash screen.
     */
    private function deleteFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/')) {
            Storage::disk('public')->delete(str_replace('storage/', '', $path));
        }
    }
}
