<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The public news pages.
 *
 * 🔴 Every query here goes through scopePublished, which checks BOTH the
 * editor's switch and the clock. A draft or a scheduled article must be
 * unreachable even by someone holding its slug.
 */
class NewsController extends Controller
{
    public function index(): View
    {
        return view('pages.news', [
            // Header copy is admin-editable; the articles are rows.
            'header' => Setting::news(),

            'articles' => News::query()
                ->published()
                // ⚠️ `image` too: cover_url reads the relation, so without
                // this the accessor is an N+1 hiding in a property access.
                ->with(['author.userable', 'image'])
                ->latestFirst()
                ->paginate(config('app.paginate_page')),
        ]);
    }

    public function show(News $news): View
    {
        /*
         * 🔴 404, not 403. A draft is indistinguishable from an article that
         * never existed as far as a visitor is concerned — answering
         * "forbidden" would confirm the slug is real and that something is
         * being withheld.
         *
         * ⚠️ Checked here rather than by scoping the binding, so the same
         * model can still be bound by the admin routes.
         */
        if (! $news->isVisible()) {
            throw new NotFoundHttpException;
        }

        $news->load(['author.userable', 'image']);

        return view('pages.news-detail', [
            'article' => $news,

            'related' => News::query()
                ->published()
                // 🔴 Excludes the article being read: a "related" grid that
                // links back to the current page is a dead end.
                ->whereKeyNot($news->id)
                ->with(['author.userable', 'image'])
                ->latestFirst()
                ->limit(3)
                ->get(),
        ]);
    }
}
