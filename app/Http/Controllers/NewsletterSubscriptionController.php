<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The footer signup form.
 *
 * ⚠️ back() is right HERE, unlike the contact form: the newsletter box sits
 * in the footer of every page, so whatever page the visitor is returned to
 * shows the confirmation — including the home page fallback when no referer
 * is sent. The contact form had one screen able to show its result, which is
 * why it needed an explicit redirect.
 */
class NewsletterSubscriptionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            /*
             * ⚠️ No `unique` rule. Submitting an address already on the list
             * is not a mistake to scold someone for — most people cannot
             * remember whether they subscribed. subscribe() makes the repeat
             * a no-op, and a returning unsubscriber is brought back.
             */
            'email' => ['required', 'email', 'max:190'],
        ], [
            'email.email' => 'اكتب بريداً إلكترونياً صحيحاً.',
        ]);

        $subscriber = NewsletterSubscriber::subscribe($validated['email']);

        return back()->with(
            'newsletter.status',
            $subscriber->wasRecentlyCreated ? 'subscribed' : 'already'
        );
    }
}
