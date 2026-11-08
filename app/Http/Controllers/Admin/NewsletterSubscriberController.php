<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The subscriber list.
 *
 * 🔴 Status and deletion are different acts and stay separate. Unsubscribing
 * keeps the address so no later campaign can mail it again; deleting removes
 * it from the list entirely and lets the next footer signup start mailing
 * that person afresh. Collapsing the two would quietly re-add people who
 * opted out.
 */
class NewsletterSubscriberController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['active', 'unsubscribed'], true)
            ? $request->query('status')
            : 'all';

        $search = $request->query('q');

        return view('admin.newsletter.subscribers.index', [
            'subscribers' => NewsletterSubscriber::query()
                ->when($status === 'active', fn ($query) => $query->active())
                ->when($status === 'unsubscribed', fn ($query) => $query->unsubscribed())
                ->search($search)
                ->latestFirst()
                ->paginate(config('app.paginate_page'))
                // Keeps filter AND search across pages; without it page 2
                // silently reverts to the unfiltered list.
                ->withQueryString(),

            'activeStatus' => $status,
            'search' => $search,

            // Counted unfiltered, so the tabs show the whole list's shape
            // while looking at one slice of it.
            'counts' => [
                'all' => NewsletterSubscriber::query()->count(),
                'active' => NewsletterSubscriber::query()->active()->count(),
                'unsubscribed' => NewsletterSubscriber::query()->unsubscribed()->count(),
            ],
        ]);
    }

    /**
     * Flips one subscriber between active and unsubscribed.
     *
     * ⚠️ An admin acting on someone's behalf, which is why it is here at all:
     * people email and phone in asking to be taken off a list, and the
     * alternative is an admin deleting the row — which silently allows the
     * next signup form to start mailing them again.
     */
    public function toggle(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->isActive()
            ? $subscriber->unsubscribe()
            : $subscriber->resubscribe();

        flash()->success($subscriber->isActive()
            ? 'أُعيد تفعيل الاشتراك لـ'.$subscriber->email.'.'
            : 'أُلغي اشتراك '.$subscriber->email.'.');

        return redirect()->route('admin.newsletter.subscribers.index');
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        session()->flash('admin.undo', [
            'url' => route('admin.trash.restore', ['type' => 'newsletter', 'id' => $subscriber->id]),
            'label' => 'تراجع',
        ]);

        return redirect()->route('admin.newsletter.subscribers.index');
    }

    /**
     * Bulk soft delete.
     *
     * 🔴 No undo link. The single delete offers one because it names the row
     * it removed; an undo for forty rows would have to restore exactly the
     * set that was deleted, and a naive "restore the latest" is how an
     * unrelated older row comes back. The trash screen restores them by name.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'لم تُحدَّد أي صفوف.',
        ]);

        $deleted = NewsletterSubscriber::whereIn('id', $validated['ids'])->delete();

        flash()->warning('تم حذف '.$deleted.' مشتركاً إلى سلّة المحذوفات.');

        return redirect()->route('admin.newsletter.subscribers.index');
    }

    /**
     * CSV export of the current filter.
     *
     * ⚠️ Streamed, not built in memory: the whole point of an export is the
     * case where the list is too long to read on screen, which is exactly
     * when building the file as a string exhausts memory.
     *
     * 🔴 Exports what the FILTER selects, not the current page. An export
     * that silently stopped at ten rows would be worse than none.
     */
    public function export(Request $request): StreamedResponse
    {
        $status = in_array($request->query('status'), ['active', 'unsubscribed'], true)
            ? $request->query('status')
            : 'all';

        $query = NewsletterSubscriber::query()
            ->when($status === 'active', fn ($q) => $q->active())
            ->when($status === 'unsubscribed', fn ($q) => $q->unsubscribed())
            ->search($request->query('q'))
            ->latestFirst();

        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');

            /*
             * ⚠️ UTF-8 BOM. Excel on Windows reads a BOM-less UTF-8 CSV as
             * the local codepage and turns every Arabic label into mojibake —
             * and this project's status labels are Arabic.
             */
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['البريد', 'الحالة', 'تاريخ الاشتراك', 'تاريخ الإلغاء']);

            // Chunked so the export never loads the whole list at once.
            $query->chunk(500, function ($chunk) use ($handle): void {
                foreach ($chunk as $subscriber) {
                    fputcsv($handle, [
                        $subscriber->email,
                        $subscriber->status->label(),
                        $subscriber->subscribed_at?->format('Y-m-d H:i'),
                        $subscriber->unsubscribed_at?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
