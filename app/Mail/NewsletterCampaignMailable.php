<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * One campaign email, addressed to one subscriber.
 *
 * ⚠️ Deliberately NOT ShouldQueue. SendNewsletterJob is already the async
 * boundary; a queued mailable sent from inside a queued job would push a
 * SECOND job per recipient, doubling the queue and making a failed send
 * retry through two different backoff policies.
 */
class NewsletterCampaignMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscriber $subscriber,
        public string $subjectLine,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.newsletter-campaign',
            with: [
                // 🔴 Built per recipient. One shared link in a template would
                // unsubscribe whoever happened to be first in the batch.
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }

    /**
     * ⚠️ List-Unsubscribe is what turns a "report spam" click into a quiet
     * unsubscribe in Gmail and Outlook. Without it a campaign teaches the
     * mailbox provider that the whole domain sends spam.
     *
     * The URL form only, without List-Unsubscribe-Post: one-click POST tells
     * providers they may unsubscribe with no confirmation, and this endpoint
     * deliberately asks first.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: ['List-Unsubscribe' => '<'.$this->subscriber->unsubscribeUrl().'>'],
        );
    }
}
