<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Lecture;
use App\Models\LibraryFile;
use App\Models\Media;
use App\Models\News;
use App\Models\NewsletterSubscriber;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\TrashableResources;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The admin dashboard.
 *
 * 🔴 Every figure here is DERIVED at read time. Nothing is stored, so no
 * number on this screen can go stale the way a cached counter column does —
 * the cost is paid in queries, which is why the counts are batched into
 * single aggregate statements rather than one query per card.
 */
class DashboardController extends Controller
{
    /** The trend window. Thirty days compared against the thirty before it. */
    private const TREND_DAYS = 30;

    public function index(): View
    {
        $now = now();
        $from = $now->copy()->subDays(self::TREND_DAYS);
        $previousFrom = $now->copy()->subDays(self::TREND_DAYS * 2);

        return view('admin.dashboard', [
            'kpis' => $this->kpis($from, $previousFrom),
            'pending' => $this->pending(),
            'storage' => $this->storage(),
            'latestNews' => $this->latestNews(),
            'latestStudents' => $this->latestStudents(),
            'health' => $this->health(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | The counter cards
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, array{value: int, trend: ?float, hint: string}>
     */
    private function kpis(Carbon $from, Carbon $previousFrom): array
    {
        return [
            'students' => $this->counted(Student::query(), $from, $previousFrom, 'طالب مسجّل'),
            'teachers' => $this->counted(Teacher::query(), $from, $previousFrom, 'معلّم'),
            'subjects' => $this->counted(Subject::query()->published(), $from, $previousFrom, 'مادة منشورة'),
            'news' => $this->counted(News::query()->published(), $from, $previousFrom, 'خبر منشور'),
            'subscribers' => $this->counted(
                NewsletterSubscriber::query()->active(), $from, $previousFrom, 'مشترك نشط'
            ),
        ];
    }

    /**
     * Total, plus the change between this window and the one before it.
     *
     * 🔴 ONE query per metric, not three. The total and both windows are
     * conditional sums over the same scan — five cards would otherwise be
     * fifteen round trips for numbers nobody reads individually.
     *
     * ⚠️ The trend measures ROWS CREATED, which is not the same as the total
     * changing: a deleted student lowers the total without touching either
     * window. The label says "جديد خلال ٣٠ يوماً" for exactly that reason.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return array{value: int, trend: ?float, recent: int, hint: string}
     */
    private function counted(Builder $query, Carbon $from, Carbon $previousFrom, string $hint): array
    {
        $table = $query->getModel()->getTable();

        $row = $query->clone()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when {$table}.created_at >= ? then 1 else 0 end) as recent", [$from])
            ->selectRaw(
                "sum(case when {$table}.created_at >= ? and {$table}.created_at < ? then 1 else 0 end) as previous",
                [$previousFrom, $from]
            )
            ->first();

        $recent = (int) ($row->recent ?? 0);
        $previous = (int) ($row->previous ?? 0);

        return [
            'value' => (int) ($row->total ?? 0),
            'recent' => $recent,
            /*
             * ⚠️ null, not zero, when the previous window was empty. A jump
             * from 0 to 5 is not "+500%" — it has no percentage, and printing
             * one invents a baseline that never existed.
             */
            'trend' => $previous > 0 ? round((($recent - $previous) / $previous) * 100, 1) : null,
            'hint' => $hint,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | What needs an admin today
    |--------------------------------------------------------------------------
    */

    /**
     * 🔴 The only section that should ever be acted on immediately, so every
     * row links straight to the screen that clears it. A queue you cannot
     * reach in one click is a queue nobody empties.
     *
     * @return array<int, array{label: string, count: int, href: string, permission: string}>
     */
    private function pending(): array
    {
        $items = [
            [
                'label' => 'رسائل لم تُقرأ',
                'count' => ContactMessage::query()->unread()->count(),
                'href' => route('admin.messages.index', ['status' => 'unread']),
                'permission' => 'view-messages',
            ],
            [
                'label' => 'آراء بانتظار الموافقة',
                'count' => Testimonial::query()->where('is_active', false)->count(),
                'href' => route('admin.testimonials.index'),
                'permission' => 'view-testimonials',
            ],
            [
                'label' => 'أخبار مسودّة',
                'count' => News::query()->where('is_published', false)->count(),
                'href' => route('admin.news.index', ['status' => 'draft']),
                'permission' => 'view-news',
            ],
            [
                'label' => 'عناصر في سلّة المحذوفات',
                'count' => $this->trashedCount(),
                'href' => route('admin.trash.index'),
                'permission' => 'view-sliders',
            ],
        ];

        // ⚠️ Rows with nothing pending are dropped, not shown as zero. A list
        // of zeros trains the eye to skip the whole panel.
        return array_values(array_filter($items, fn (array $item): bool => $item['count'] > 0));
    }

    /**
     * Everything currently in the trash, across every registered type.
     *
     * ⚠️ Reads the registry rather than a hard-coded list, so a type added
     * later is counted without touching this method.
     */
    private function trashedCount(): int
    {
        /*
         * ⚠️ Only the types this viewer may actually delete. Each type is a
         * separate table and therefore a separate query, so counting the ones
         * they cannot act on is pure waste — and the number would disagree
         * with what the trash screen shows them anyway.
         */
        $user = auth()->user();

        /*
         * 🔴 CACHED for a minute, and cached because the cost is structural:
         * counting soft-deleted rows across a dozen unrelated tables is one
         * query PER TABLE — there is no join that avoids it. Uncached this
         * single summary number was a third of the whole dashboard's queries,
         * and it grows every time a trashable type is registered.
         *
         * ⚠️ Keyed per user: the figure is permission-filtered, so a shared
         * key would show one admin a count including types another admin
         * cannot even open.
         *
         * A minute is deliberately short — the trash is acted on from its own
         * screen, and this is only the nudge that sends you there.
         */
        return Cache::remember(
            'admin.dashboard.trash-count.'.$user?->id,
            now()->addMinute(),
            function () use ($user): int {
                $allowed = TrashableResources::allowedKeysFor($user);

                return collect(TrashableResources::all())
                    ->only($allowed)
                    ->sum(fn (array $resource): int => $resource['model']::onlyTrashed()->count());
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */

    /**
     * Media rows and what they occupy on disk.
     *
     * 🔴 CACHED for five minutes, because the byte total is a filesystem walk
     * — not a query. Doing it on every dashboard load would make the slowest
     * page in the panel the one people open most.
     *
     * ⚠️ Counts only what the app UPLOADED (the public disk). Repository
     * artwork under public/images is checked into git and is not storage this
     * panel manages.
     *
     * @return array{files: int, bytes: int, images: int}
     */
    private function storage(): array
    {
        return Cache::remember('admin.dashboard.storage', now()->addMinutes(5), function (): array {
            $disk = Storage::disk('public');

            $bytes = 0;
            $files = 0;

            foreach ($disk->allFiles() as $file) {
                $bytes += $disk->size($file);
                $files++;
            }

            return [
                'files' => $files,
                'bytes' => $bytes,
                'images' => Media::query()->count(),
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Feeds
    |--------------------------------------------------------------------------
    */

    /** @return Collection<int, News> */
    private function latestNews()
    {
        return News::query()
            // ⚠️ author.userable because displayName() reads the domain row,
            // and image because cover_url reads the relation. Without both
            // this feed is two N+1s.
            ->with(['author.userable', 'image'])
            ->latestFirst()
            ->limit(5)
            ->get();
    }

    /** @return Collection<int, User> */
    private function latestStudents()
    {
        return User::query()
            ->where('userable_type', Student::class)
            ->with(['userable.branch', 'image'])
            ->latest()
            ->limit(5)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | System status
    |--------------------------------------------------------------------------
    */

    /**
     * A handful of facts an admin would otherwise open four screens to learn.
     *
     * ⚠️ Deliberately NOT a server-health widget. Uptime, queue depth and
     * disk pressure belong to whoever runs the server; what an admin can act
     * on is whether the CONTENT is in a publishable state.
     *
     * @return array<int, array{label: string, value: string, ok: bool}>
     */
    private function health(): array
    {
        $counts = DB::table('subjects')
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when is_published = 1 then 1 else 0 end) as published')
            ->whereNull('deleted_at')
            ->first();

        $lectures = Lecture::query()->published()->count();
        $files = LibraryFile::query()->count();

        return [
            [
                'label' => 'المواد المنشورة',
                'value' => (int) ($counts->published ?? 0).' من '.(int) ($counts->total ?? 0),
                // 🔴 A branch page with no published subject is an empty
                // public page, which is the failure this row exists to catch.
                'ok' => (int) ($counts->published ?? 0) > 0,
            ],
            [
                'label' => 'المحاضرات المنشورة',
                'value' => (string) $lectures,
                'ok' => $lectures > 0,
            ],
            [
                'label' => 'ملفات المكتبة',
                'value' => (string) $files,
                'ok' => true,
            ],
            [
                'label' => 'اللغة الافتراضية',
                'value' => config('app.locale') === 'ar' ? 'العربية' : config('app.locale'),
                'ok' => true,
            ],
        ];
    }
}
