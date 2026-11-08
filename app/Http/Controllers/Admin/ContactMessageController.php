<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContactReplyMail;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * The contact inbox.
 *
 * 🔴 Read and replied are separate states, and the filters treat them that
 * way. A message can be opened and left unanswered — that is the state an
 * inbox exists to surface, and collapsing the two would hide it.
 */
class ContactMessageController extends Controller
{
    /** The states an admin filters by. `all` is the default: an inbox hides nothing. */
    private const STATUSES = ['all', 'unread', 'replied', 'awaiting'];

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), self::STATUSES, true)
            ? $request->query('status')
            : 'all';

        return view('admin.messages.index', [
            'messages' => ContactMessage::query()
                ->when($status === 'unread', fn ($query) => $query->unread())
                ->when($status === 'replied', fn ($query) => $query->replied())
                ->when($status === 'awaiting', fn ($query) => $query->awaitingReply())
                ->latestFirst()
                ->paginate(config('app.paginate_page'))
                // Keeps the filter across pages; without it page 2 reverts
                // silently to "all".
                ->withQueryString(),

            'activeStatus' => $status,

            // Counted unfiltered, so the tab badges show what is waiting even
            // while the admin is looking at another tab.
            'counts' => [
                'all' => ContactMessage::query()->count(),
                'unread' => ContactMessage::query()->unread()->count(),
                'replied' => ContactMessage::query()->replied()->count(),
                'awaiting' => ContactMessage::query()->awaitingReply()->count(),
            ],
        ]);
    }

    /**
     * Opening a message marks it read.
     *
     * ⚠️ A side effect on a GET, deliberately — "read" means "an admin
     * looked at it", and that is exactly this request. markAsRead() only
     * writes when the flag actually changes, so re-opening a read message
     * does not bump `updated_at` and make it useless for telling when the
     * message was last acted on.
     */
    public function show(ContactMessage $message): View
    {
        $message->markAsRead();

        return view('admin.messages.show', [
            'message' => $message->load('admin.userable'),
        ]);
    }

    /**
     * Sends the admin's reply and records it.
     *
     * 🔴 The database write and the mail are ordered on purpose: the reply is
     * SAVED first, then queued. The mailable reads its body from the model,
     * so a job that runs before the row is committed would send an empty
     * reply — and a failed queue push must not lose what was written.
     */
    public function reply(Request $request, ContactMessage $message): RedirectResponse
    {
        $validated = $request->validate([
            'reply_message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'reply_message.min' => 'اكتب رداً أوضح قليلاً — 10 أحرف على الأقل.',
        ]);

        DB::transaction(function () use ($message, $validated, $request): void {
            $message->update([
                'reply_message' => $validated['reply_message'],
                'replied_at' => now(),
                // Replying implies reading: nobody answers a message they
                // never opened, and leaving it unread would keep it in the
                // unread count forever.
                'is_read' => true,
                'admin_id' => $request->user()->id,
            ]);
        });

        Mail::to($message->email)->queue(new ContactReplyMail($message->fresh()));

        flash()->success('أُرسل الردّ إلى '.$message->email.'.');

        return redirect()->route('admin.messages.index');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        // Undo is a real PATCH form with CSRF in the shell, never a link in
        // the notice: a restore mutates server state.
        session()->flash('admin.undo', [
            'url' => route('admin.trash.restore', ['type' => 'messages', 'id' => $message->id]),
            'label' => 'تراجع',
        ]);

        return redirect()->route('admin.messages.index');
    }
}
