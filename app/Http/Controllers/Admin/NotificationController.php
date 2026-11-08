<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseNotificationController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The admin bell and its full screen.
 *
 * 🔴 Every JSON action (feed, read, read-all, toggle, destroy, clear-read) is
 * inherited from BaseNotificationController and scoped to $request->user().
 * Only index() — the page, its view and its filters — is admin-specific.
 */
class NotificationController extends BaseNotificationController
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $filter = $this->requestedFilter($request);

        return view('admin.notifications.index', [
            'notifications' => $user->notifications()
                ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
                ->when($filter === 'read', fn ($query) => $query->whereNotNull('read_at'))
                ->latest()
                ->paginate(self::PAGE_SIZE)
                // Keeps the tab across pages; without it page 2 silently
                // reverts to "all".
                ->withQueryString(),

            'activeFilter' => $filter,

            // Counted unfiltered, so the tabs describe the whole history while
            // looking at one slice of it.
            'counts' => [
                'all' => $user->notifications()->count(),
                'unread' => $user->unreadNotifications()->count(),
                'read' => $user->readNotifications()->count(),
            ],
        ]);
    }
}
