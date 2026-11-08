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
 * "We got your message" — sent to the visitor the moment they submit.
 *
 * 🔴 ShouldQueue. The SMTP handshake happens on the request thread
 * otherwise, so a slow or unreachable mail host turns a contact form into a
 * page that hangs and then 500s — with the message already saved. Queuing it
 * means the visitor's confirmation screen never waits on mail.
 *
 * ⚠️ SerializesModels stores only the model's KEY on the queue and reloads
 * it when the job runs. A message soft-deleted in between makes the job fail
 * loudly rather than send a confirmation for something no longer there.
 */
class ContactConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'وصلتنا رسالتك — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-confirmation',
            with: ['contactMessage' => $this->contactMessage],
        );
    }
}
