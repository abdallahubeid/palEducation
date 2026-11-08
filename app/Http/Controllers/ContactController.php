<?php

namespace App\Http\Controllers;

use App\Enums\ContactSubject;
use App\Mail\ContactConfirmationMail;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Notifications\NewContactMessageNotification;
use App\Support\AdminNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * The public contact page.
 *
 * ⚠️ Replaced Route::view once the page gained database-backed content.
 * A controller, not a closure: a route with a closure fails `route:cache`
 * for the WHOLE application, not just itself.
 *
 * 🔴 The channel rows are built here rather than in the template. Each one
 * needs a dial-safe destination derived from a human-readable number, and a
 * channel whose value is empty must not render as a row with no content —
 * neither belongs in a Blade file.
 */
class ContactController extends Controller
{
    public function __invoke(): View
    {
        $contact = Setting::contact();

        return view('pages.contact', [
            'contact' => $contact,
            'channels' => $this->channels($contact),
            'hoursRows' => Setting::workingHoursRows(),
        ]);
    }

    /**
     * Receives a message from the public form.
     *
     * 🔴 The confirmation mail is QUEUED, never sent inline. An SMTP
     * handshake on the request thread turns an unreachable mail host into a
     * contact page that hangs and then 500s — with the message already
     * saved, so the visitor sends it again.
     *
     * ⚠️ Nothing about the sender is trusted beyond validation: `is_read`,
     * `replied_at` and `admin_id` are never taken from the request, so a
     * crafted POST cannot file itself as already answered.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject_type' => ['required', Rule::enum(ContactSubject::class)],
            // min:10 keeps out the one-word submissions that cost a reply
            // cycle to find out they said nothing.
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'message.min' => 'اكتب رسالة أوضح قليلاً — 10 أحرف على الأقل.',
        ]);

        $message = ContactMessage::create($validated);

        Mail::to($message->email)->queue(new ContactConfirmationMail($message));

        // ⚠️ بعد الحفظ لا قبله: الإشعار يحمل الصفّ، وصفّ لم يُكتب بعد بلا
        // مُعرِّف ولا رابط يقود إليه.
        AdminNotifier::send(new NewContactMessageNotification($message));

        /*
         * ⚠️ An explicit redirect, not back(). back() follows the referer
         * header, which a browser may withhold and which is absent
         * entirely on a direct POST — sending someone who just wrote to us
         * to the home page with no confirmation anywhere.
         */
        return redirect()
            ->route('contact')
            ->with('contact.sent', true);
    }

    /**
     * The channel rows, in order, skipping any the admin has cleared.
     *
     * 🔴 `plain` marks Arabic prose, which must NOT be wrapped in
     * `<bdi dir="ltr">`. Everything else here is a latin or numeric string
     * sitting inside Arabic text, and an un-isolated phone number visually
     * reorders in RTL — becoming impossible to read or copy correctly.
     *
     * @param  array<string, string>  $contact
     * @return array<int, array<string, mixed>>
     */
    private function channels(array $contact): array
    {
        $rows = [];

        if (filled($contact['whatsapp'])) {
            $rows[] = [
                'icon' => 'users',
                'label' => __('public.contact_whatsapp'),
                'value' => $contact['whatsapp'],
                // wa.me wants digits only — no plus, no spaces.
                'href' => 'https://wa.me/'.Setting::dialable($contact['whatsapp'], keepPlus: false),
                'external' => true,
            ];
        }

        if (filled($contact['phone'])) {
            $rows[] = [
                'icon' => 'card',
                'label' => __('public.contact_phone_channel'),
                'value' => $contact['phone'],
                // tel: keeps the plus but rejects spaces and dashes.
                'href' => 'tel:'.Setting::dialable($contact['phone']),
                'external' => false,
            ];
        }

        if (filled($contact['email'])) {
            $rows[] = [
                'icon' => 'mail',
                'label' => __('public.contact_email_channel'),
                'value' => $contact['email'],
                'href' => 'mailto:'.$contact['email'],
                'external' => false,
            ];
        }

        if (filled($contact['address'])) {
            $rows[] = [
                'icon' => 'home',
                'label' => __('public.contact_office'),
                'value' => $contact['address'],
                // Static: renders as a div, never a dead link.
                'href' => null,
                'external' => false,
                'plain' => true,
            ];
        }

        return $rows;
    }
}
