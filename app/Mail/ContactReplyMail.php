<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * An admin's reply, sent to whoever wrote in.
 *
 * 🔴 ShouldQueue, same reason as the confirmation — but here it matters
 * more: the admin is mid-workflow in the panel, and a blocked SMTP call
 * would leave them staring at a spinner unsure whether the reply went out.
 *
 * ⚠️ The reply body is read from the MODEL, not passed in separately. The
 * database is the record of what was said, and a second copy travelling
 * through the queue could disagree with it.
 */
class ContactReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ردّ على رسالتك — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-reply',
            with: ['contactMessage' => $this->contactMessage],
        );
    }
}
