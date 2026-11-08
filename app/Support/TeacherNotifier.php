<?php

namespace App\Support;

use App\Models\Teacher;
use App\Models\User;
use App\Notifications\TeacherNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Who gets a teacher notification, and when one is suppressed.
 *
 * 🔴 One place. Every dispatch site calls this rather than resolving the
 * recipient itself — otherwise "does this teacher even have a login account"
 * gets checked in five places and forgotten in the sixth, and a Teacher row
 * with no account throws when notified.
 *
 * ⚠️ A teacher on the public roster may have NO user account at all
 * (TeacherSeeder). notify() and broadcast() both skip those silently: there is
 * no inbox to deliver to.
 */
final class TeacherNotifier
{
    /**
     * Notifies one teacher, unless they are the person who caused the event.
     *
     * ⚠️ The actor is excluded by default. "You downloaded your own file" one
     * second after downloading it is noise, and the fastest way to teach a
     * teacher to ignore the bell.
     */
    public static function notify(Teacher $teacher, TeacherNotification $notification, ?User $except = null): void
    {
        $account = $teacher->user;

        if ($account === null || ! $account->is_active) {
            return;
        }

        if ($except !== null && $account->is($except)) {
            return;
        }

        $account->notify($notification);
    }

    /**
     * Sends to every active teacher account.
     */
    public static function broadcast(TeacherNotification $notification, ?User $except = null): void
    {
        $recipients = self::recipients($except);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, $notification);
    }

    /**
     * Every active teacher account.
     *
     * @return Collection<int, User>
     */
    public static function recipients(?User $except = null): Collection
    {
        return User::query()
            ->where('userable_type', Teacher::class)
            // ⚠️ Deactivated accounts are skipped: they cannot sign in to read
            // the bell, so the rows would accumulate unread for ever.
            ->where('is_active', true)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->id))
            ->get();
    }
}
