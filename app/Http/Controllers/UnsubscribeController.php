<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The unsubscribe link carried by every campaign.
 *
 * 🔴 Two steps, GET then POST, and the GET changes nothing. Mail clients and
 * security scanners FETCH every link in a message before a human sees it —
 * Outlook's Safe Links does exactly this — so an unsubscribe that fired on
 * GET would silently remove people who never clicked anything. The same
 * reasoning already applies to every destructive route in the admin panel.
 *
 * ⚠️ Both steps are signed and neither requires a login: the recipient is
 * usually not a user of the platform at all, and forcing a login to stop
 * mail is what turns an unsubscribe into a spam report.
 */
class UnsubscribeController extends Controller
{
    /** The confirmation page. Reads only. */
    public function show(NewsletterSubscriber $subscriber): View
    {
        return view('pages.newsletter.unsubscribe', [
            'subscriber' => $subscriber,
            'alreadyDone' => ! $subscriber->isActive(),
        ]);
    }

    /** The actual opt-out. */
    public function update(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->unsubscribe();

        return redirect()->route('newsletter.unsubscribed');
    }

    /**
     * The result page.
     *
     * ⚠️ A separate route rather than a flash on the confirmation page: the
     * signed URL is not reusable after the fact, and refreshing a page that
     * still carried a POST form would ask someone to unsubscribe twice.
     */
    public function done(): View
    {
        return view('pages.newsletter.unsubscribed');
    }
}
