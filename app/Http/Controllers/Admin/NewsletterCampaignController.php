<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendNewsletterJob;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Notifications\NewsletterCampaignFinishedNotification;
use App\Support\AdminNotifier;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

/**
 * Composing and dispatching a campaign.
 *
 * 🔴 There is no draft and no edit-after-send, by design: once dispatched the
 * message is in other people's inboxes, and a screen that let it be "edited"
 * afterwards would show an archive that differs from what was received.
 */
class NewsletterCampaignController extends Controller
{
    public function create(Request $request): View
    {
        /*
         * "نسخ كحملة جديدة" — the history screen links here with ?from={id}.
         *
         * 🔴 It PREFILLS a new form; it does not resend anything. A duplicate
         * link that dispatched on its own would be a GET that mails hundreds
         * of people, firing on a browser prefetch or a passing crawler.
         *
         * ⚠️ old() wins over the copied values, so a failed validation on a
         * duplicated campaign redisplays what the admin typed rather than
         * silently reverting to the original text.
         */
        $source = $request->filled('from')
            ? NewsletterCampaign::find($request->query('from'))
            : null;

        return view('admin.newsletter.campaigns.create', [
            'source' => $source,
            /*
             * ⚠️ Active only. Listing unsubscribed addresses as selectable
             * would put the one act this system must never commit — mailing
             * someone who opted out — a single click away.
             */
            'subscribers' => NewsletterSubscriber::query()
                ->active()
                ->latestFirst()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'min:20', 'max:20000'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['integer'],
        ], [
            'recipients.required' => 'اختر مستلماً واحداً على الأقل.',
            'recipients.min' => 'اختر مستلماً واحداً على الأقل.',
            'body.min' => 'اكتب نصّاً أطول قليلاً — 20 حرفاً على الأقل.',
        ]);

        /*
         * 🔴 The recipient list is REBUILT from the database, never trusted
         * from the request. The form posts ids, and a tampered post could
         * carry the id of an unsubscribed address, a soft-deleted one, or an
         * id that is not on the list at all. Filtering here means the worst a
         * tampered request can do is mail fewer people, never more.
         */
        $recipients = NewsletterSubscriber::query()
            ->active()
            ->whereIn('id', $validated['recipients'])
            ->pluck('id');

        if ($recipients->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['recipients' => 'لم يعد أي من المستلمين المحدَّدين مشتركاً نشطاً.']);
        }

        /*
         * 🔴 The record is written BEFORE the jobs are queued, and carries
         * its own copy of the subject and body. The jobs need its id, and the
         * archive has to say what was actually sent even after the same admin
         * sends something different tomorrow.
         */
        $campaign = NewsletterCampaign::create([
            'admin_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'recipients_count' => $recipients->count(),
            'status' => CampaignStatus::Processing,
        ]);

        $jobs = $recipients
            ->map(fn (int $id): SendNewsletterJob => new SendNewsletterJob(
                $id, $validated['subject'], $validated['body'], $campaign->id
            ))
            ->all();

        /*
         * A BATCH, not loose dispatches. With one job per recipient there is
         * otherwise nothing that knows when the send is over, and the record
         * would sit at "قيد الإرسال" for ever.
         *
         * 🔴 allowFailures(): by default the first failed job CANCELS the
         * batch, so one dead address would stop everyone after it from being
         * mailed at all. The campaign is still recorded as failed — the
         * failure is reported, not obeyed.
         */
        Bus::batch($jobs)
            ->name('newsletter-campaign-'.$campaign->id)
            ->allowFailures()
            ->finally(function (Batch $batch) use ($campaign): void {
                // ⚠️ Re-fetched inside the callback: it is serialised and runs
                // later on a worker, where the model captured here is stale.
                $finished = NewsletterCampaign::find($campaign->id);

                if (! $finished) {
                    return;
                }

                $finished->markFinished($batch->hasFailures());

                // ⚠️ بعد markFinished لا قبله: الإشعار يقرأ الحالة النهائية
                // ليقول «اكتمل» أو «فشل»، وقبلها كانت ستبقى «قيد الإرسال».
                AdminNotifier::send(new NewsletterCampaignFinishedNotification($finished->fresh()));
            })
            ->dispatch();

        flash()->success('أُدرجت النشرة للإرسال إلى '.$recipients->count().' مشترك.');

        return redirect()->route('admin.newsletter.subscribers.index');
    }
}
