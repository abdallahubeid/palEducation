<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * The shape every admin notification shares.
 *
 * 🔴 One contract, seven subclasses. The bell renders `data` blindly, so if
 * each class invented its own keys the dropdown would silently show blank
 * rows for whichever class forgot one. Subclasses supply the payload; the
 * envelope is fixed here.
 *
 * ⚠️ Database channel only. Mail for these would arrive per event — the
 * volume that trains people to filter the sender into a folder. Anything
 * genuinely worth an email already has its own mailable.
 */
abstract class AdminNotification extends Notification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * The stored payload.
     *
     * ⚠️ A SNAPSHOT, not a reference. `title` keeps the subject line as it
     * was when the event happened — a notification that re-read the row would
     * change wording after an edit, or blank out after a delete.
     *
     * @return array{title: string, message: string, icon: string, url: string, type: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'icon' => $this->icon(),
            'url' => $this->url(),
            'type' => $this->type(),
        ];
    }

    abstract public function title(): string;

    abstract public function message(): string;

    /** An icon name that exists in the x-icon set — an unknown one renders nothing. */
    abstract public function icon(): string;

    /** Where clicking the row goes. Every notification must lead somewhere. */
    abstract public function url(): string;

    /** A short slug used for the row's colour band. */
    abstract public function type(): string;
}
