<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * The shape every teacher notification shares.
 *
 * 🔴 One contract, every subclass. The bell renders `data` blindly, so if each
 * class invented its own keys the dropdown would silently show blank rows for
 * whichever class forgot one. Subclasses supply the payload; the envelope is
 * fixed here — identical to AdminNotification because the same bell renders
 * both.
 *
 * ⚠️ Database channel only. Mail for these would arrive per event — the volume
 * that trains people to filter the sender into a folder.
 */
abstract class TeacherNotification extends Notification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * The stored payload — a SNAPSHOT, not a reference. `title` keeps the
     * wording as it was when the event happened; a notification that re-read
     * the row would change after an edit, or blank out after a delete.
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

    /** An icon name that exists in the x-icon set — an unknown one renders a bell. */
    abstract public function icon(): string;

    /** Where clicking the row goes. Every notification must lead somewhere. */
    abstract public function url(): string;

    /** A short slug used for the row's colour band. */
    abstract public function type(): string;
}
