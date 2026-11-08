<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\User;
use App\Notifications\AdminNotification;
use App\Notifications\SystemErrorLoggedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Who gets an admin notification, and when one is suppressed.
 *
 * 🔴 One place. Every dispatch site in the app calls this rather than
 * resolving recipients itself — otherwise "who is an admin" drifts between
 * seven call sites and one of them quietly starts notifying students.
 *
 * ⚠️ There is no "super admin" tier in this project. The roles are exactly
 * three (student · teacher · admin), so "super admin" here means every
 * account whose SHAPE is Admin. Introducing a tier is a permissions decision,
 * not something to invent inside a notifier.
 */
final class AdminNotifier
{
    /** How long an identical error stays suppressed. */
    private const ERROR_DEDUPE_MINUTES = 60;

    /**
     * Sends to every admin, optionally excluding the person who caused it.
     *
     * ⚠️ The actor is excluded by default. Telling someone "you changed the
     * settings" one second after they changed the settings is noise, and it
     * is the fastest way to teach an admin to ignore the bell.
     */
    public static function send(AdminNotification $notification, ?User $except = null): void
    {
        $recipients = self::recipients($except);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, $notification);
    }

    /**
     * Every active admin account.
     *
     * @return Collection<int, User>
     */
    public static function recipients(?User $except = null): Collection
    {
        return User::query()
            ->where('userable_type', Admin::class)
            // ⚠️ Deactivated accounts are skipped: they cannot sign in to read
            // the bell, so the rows would accumulate unread for ever.
            ->where('is_active', true)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->id))
            ->get();
    }

    /**
     * Reports a system error, at most once an hour per distinct error.
     *
     * 🔴 The dedupe is the whole point. An exception inside a loop, or a queue
     * worker retrying a broken job, produces thousands of identical failures a
     * minute — without a guard the notifications table fills faster than
     * anyone can read it, and the bell becomes useless precisely when
     * something is actually wrong.
     *
     * ⚠️ Keyed on class + file + line, NOT on the message: messages usually
     * embed an id or a value, so keying on them would defeat the dedupe.
     */
    public static function reportError(Throwable $e, string $context = ''): void
    {
        $key = 'admin.error-notice:'.md5($e::class.'|'.$e->getFile().'|'.$e->getLine());

        // add() is atomic: two workers failing at the same instant cannot both
        // win the race and send two notifications.
        if (! Cache::add($key, true, now()->addMinutes(self::ERROR_DEDUPE_MINUTES))) {
            return;
        }

        self::send(new SystemErrorLoggedNotification(
            summary: class_basename($e).': '.Str::limit($e->getMessage(), 140),
            context: $context,
        ));
    }
}
