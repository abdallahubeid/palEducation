<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\BaseNotificationController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The teacher bell and its full screen.
 *
 * 🔴 Every JSON action is inherited from BaseNotificationController and scoped
 * to $request->user() — a teacher can never touch a colleague's row. Only
 * index() is teacher-specific.
 */
class NotificationController extends BaseNotificationController
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $filter = $this->requestedFilter($request);

        return view('teacher.notifications.index', [
            'notifications' => $user->notifications()
                ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
                ->when($filter === 'read', fn ($query) => $query->whereNotNull('read_at'))
                ->latest()
                ->paginate(self::PAGE_SIZE)
                ->withQueryString(),

            'activeFilter' => $filter,

            'counts' => [
                'all' => $user->notifications()->count(),
                'unread' => $user->unreadNotifications()->count(),
                'read' => $user->readNotifications()->count(),
            ],
        ]);
    }
}
