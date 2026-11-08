<?php

namespace App\Jobs;

use App\Mail\NewsletterCampaignMailable;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one campaign email to one subscriber.
 *
 * 🔴 One job PER RECIPIENT, not one job looping the list. A single job that
 * mails two thousand people fails as a unit: a bad address at number 300
 * retries the whole batch and the first 299 receive the campaign twice.
 *
 * Batchable so the campaign record can be closed when the LAST recipient has
 * been processed — with one job per recipient there is otherwise nothing that
 * knows the send is over.
 */
class SendNewsletterJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    /** Backoff in seconds — a refused connection is usually momentary. */
    public array $backoff = [60, 300];

    public function __construct(
        public int $subscriberId,
        public string $subjectLine,
        public string $body,
        /*
         * ⚠️ Nullable. The campaign record is the normal case, but a job
         * dispatched without one still has to send — the archive is
         * bookkeeping, and losing it must never cost a recipient their email.
         */
        public ?int $campaignId = null,
    ) {}

    public function handle(): void
    {
        // A cancelled batch stops here: the admin (or a failure policy) ended
        // the send, and remaining jobs must not keep mailing after that.
        if ($this->batch()?->cancelled()) {
            return;
        }

        $subscriber = NewsletterSubscriber::find($this->subscriberId);

        /*
         * 🔴 Re-checked at SEND time, not only at dispatch time. A queue can
         * run minutes or hours behind, and someone who unsubscribed in that
         * window must not receive the campaign already queued for them —
         * that is the complaint that gets a sending domain blocklisted.
         */
        if (! $subscriber || ! $subscriber->isActive()) {
            return;
        }

        Mail::to($subscriber->email)->send(
            new NewsletterCampaignMailable($subscriber, $this->subjectLine, $this->body)
        );
    }

    /**
     * Marks the campaign failed as soon as one recipient is given up on.
     *
     * ⚠️ Does NOT close the campaign. Failures are allowed so one bad address
     * cannot stop the send, so the rest of the batch is still running; the
     * batch's own callback is what records when it actually ended.
     */
    public function failed(?Throwable $exception): void
    {
        if ($this->campaignId === null) {
            return;
        }

        NewsletterCampaign::find($this->campaignId)?->markFailed();
    }
}
