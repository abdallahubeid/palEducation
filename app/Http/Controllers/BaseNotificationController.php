<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The bell's JSON feed and the notification actions, shared by every portal.
 *
 * 🔴 Everything here is scoped to $request->user() and nothing else. No route
 * carries an account id, and every single-row action is looked up THROUGH the
 * relation — so a guessed uuid belonging to a colleague resolves to nothing
 * rather than to their row. That guarantee holds identically for an admin and
 * for a teacher, which is why the logic lives here once.
 *
 * ⚠️ feed() is polled every 15 seconds by every open tab. index() is not — it
 * is a page. They are deliberately separate methods with separate budgets,
 * which is also why they no longer share a route name.
 *
 * Subclasses supply only index(): the portal-specific page, its view and its
 * own filter set live there.
 */
abstract class BaseNotificationController extends Controller
{
    /** How many rows the dropdown can show. */
    protected const FEED_LIMIT = 10;

    /** How many rows a page of the full screen shows. */
    protected const PAGE_SIZE = 15;

    /** The filters the full screen offers. */
    protected const FILTERS = ['all', 'unread', 'read'];

    /** The portal's full notifications page. */
    abstract public function index(Request $request): View;

    /*
    |--------------------------------------------------------------------------
    | The bell
    |--------------------------------------------------------------------------
    */

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),

            /*
             * ⚠️ Recent notifications, not just unread ones. A dropdown that
             * empties itself the moment you read it gives no way back to
             * something you glanced at and wanted to reopen.
             */
            'notifications' => $user->notifications()
                ->latest()
                ->limit(static::FEED_LIMIT)
                ->get()
                ->map(fn ($notification): array => $this->payload($notification))
                ->all(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->find($request, $id);

        if (! $notification) {
            return response()->json(['message' => 'غير موجود.'], 404);
        }

        // Idempotent: a double click must not move the timestamp of the first
        // read, which is the only record of when it was actually seen.
        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return $this->state($request, $notification->fresh());
    }

    /**
     * Flips one notification between read and unread.
     *
     * ⚠️ Marking something UNREAD again is deliberate: someone who opens a
     * notification, realises they cannot deal with it now, and wants it back
     * in the queue has no other way to put it there.
     */
    public function toggle(Request $request, string $id): JsonResponse
    {
        $notification = $this->find($request, $id);

        if (! $notification) {
            return response()->json(['message' => 'غير موجود.'], 404);
        }

        $notification->forceFill([
            'read_at' => $notification->read_at === null ? now() : null,
        ])->save();

        return $this->state($request, $notification->fresh());
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        // One UPDATE, not a read-then-loop: the unread set can be large after
        // a quiet week, and a loop would be one query per row.
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->state($request);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $this->find($request, $id);

        if (! $notification) {
            return response()->json(['message' => 'غير موجود.'], 404);
        }

        $notification->delete();

        return $this->state($request);
    }

    /**
     * Clears everything already read.
     *
     * 🔴 READ ones only. "Clear all" would silently destroy notifications the
     * person has never seen — which is the one thing this screen exists to
     * prevent. Unread rows survive on purpose.
     *
     * ⚠️ Permanent. The notifications table has no soft deletes and does not
     * belong in the trash screen: these are a record OF events, not the events
     * themselves, and nothing is lost that the underlying screens do not still
     * hold.
     */
    public function clearRead(Request $request): JsonResponse
    {
        $deleted = $request->user()->readNotifications()->delete();

        return $this->state($request, extra: ['deleted' => $deleted]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * The filter a full-screen request asked for, sanitised.
     */
    protected function requestedFilter(Request $request): string
    {
        return in_array($request->query('filter'), static::FILTERS, true)
            ? $request->query('filter')
            : 'all';
    }

    /**
     * 🔴 Found through the user's OWN relation. A global
     * DatabaseNotification::find($id) would let anyone holding a uuid act on
     * a colleague's row.
     */
    protected function find(Request $request, string $id): ?object
    {
        return $request->user()->notifications()->whereKey($id)->first();
    }

    /**
     * The counts every action returns, so the caller can repaint without a
     * second round trip.
     *
     * @param  array<string, mixed>  $extra
     */
    protected function state(Request $request, ?object $notification = null, array $extra = []): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'read_count' => $user->readNotifications()->count(),
            'total_count' => $user->notifications()->count(),
            'notification' => $notification ? $this->payload($notification) : null,
            ...$extra,
        ]);
    }

    /** @return array<string, mixed> */
    protected function payload(object $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->data['title'] ?? '',
            'message' => $notification->data['message'] ?? '',
            'icon' => $notification->data['icon'] ?? 'bell',
            'url' => $notification->data['url'] ?? null,
            'type' => $notification->data['type'] ?? 'info',
            'read' => $notification->read_at !== null,
            // Arabic relative time is rendered on the SERVER: the browser has
            // no Arabic plural rules for this, and Carbon already does.
            'time' => $notification->created_at->diffForHumans(),
        ];
    }
}
