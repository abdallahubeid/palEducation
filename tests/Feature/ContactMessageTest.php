<?php

use App\Enums\ContactSubject;
use App\Mail\ContactConfirmationMail;
use App\Mail\ContactReplyMail;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\ContactMessage;
use App\Models\Student;
use App\Support\PermissionCatalog;
use App\Support\TrashableResources;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/*
| The contact inbox — public submission through to an emailed reply.
|
| 🔴 Both mailables are ShouldQueue. An SMTP handshake on the request thread
| turns an unreachable mail host into a contact page that hangs and then
| 500s, with the message already saved — so the visitor sends it twice.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Mail::fake();

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $branch = Branch::create(['name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

function messagePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'ليان أبو ديّة',
        'email' => 'layan@example.test',
        'phone' => '+970 599 000 111',
        'subject_type' => ContactSubject::Support->value,
        'message' => 'عندي سؤال عن الاشتراك في الفرع العلمي، هل يشمل كل المواد؟',
    ], $overrides);
}

// ── Public submission ───────────────────────────────────────────

it('stores a message and queues the confirmation to the sender', function () {
    post(route('contact.store'), messagePayload())
        ->assertRedirect()
        ->assertSessionHas('contact.sent');

    $message = ContactMessage::sole();

    expect($message->name)->toBe('ليان أبو ديّة')
        ->and($message->email)->toBe('layan@example.test')
        ->and($message->subject_type)->toBe(ContactSubject::Support)
        // 🔴 Arrives unread and unanswered — never taken from the request.
        ->and($message->is_read)->toBeFalse()
        ->and($message->replied_at)->toBeNull()
        ->and($message->admin_id)->toBeNull();

    Mail::assertQueued(ContactConfirmationMail::class, function ($mail) use ($message) {
        return $mail->hasTo($message->email)
            && $mail->contactMessage->is($message);
    });

    // ⚠️ Queued, NOT sent inline.
    Mail::assertNotSent(ContactConfirmationMail::class);
});

it('shows the sender a confirmation without JavaScript', function () {
    // The success panel is rendered by the SERVER on the redirect, so the
    // page works with scripting disabled.
    $this->followingRedirects()
        ->post(route('contact.store'), messagePayload())
        ->assertOk()
        ->assertSee(__('public.contact_success_title'));
});

it('rejects an incomplete or implausible submission', function () {
    /*
     * ⚠️ Throttling is disabled for THIS test only. The route allows five
     * posts a minute and this makes seven — without lifting it, the last two
     * come back as 429 with no validation errors at all, and the test fails
     * for a reason that has nothing to do with validation. The limit itself
     * is asserted separately below.
     */
    $this->withoutMiddleware(ThrottleRequests::class);

    foreach (['name', 'email', 'subject_type', 'message'] as $field) {
        post(route('contact.store'), messagePayload([$field => '']))
            ->assertSessionHasErrors($field);
    }

    post(route('contact.store'), messagePayload(['email' => 'ليس بريداً']))
        ->assertSessionHasErrors('email');

    // A subject the form cannot produce is refused rather than stored.
    post(route('contact.store'), messagePayload(['subject_type' => 'nonsense']))
        ->assertSessionHasErrors('subject_type');

    // One word costs a whole reply cycle to discover it said nothing.
    post(route('contact.store'), messagePayload(['message' => 'مرحبا']))
        ->assertSessionHasErrors('message');

    expect(ContactMessage::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('offers the same subjects the server will accept', function () {
    // 🔴 The select is built from the enum. A second hardcoded list could
    // offer an option the controller rejects.
    $html = get(route('contact'))->assertOk()->getContent();

    foreach (ContactSubject::cases() as $case) {
        expect($html)->toContain('value="'.$case->value.'"');
    }

    expect($html)->toContain('name="subject_type"');
});

it('throttles the public form', function () {
    /*
     * 🔴 Every hit writes a row AND queues an email. Unthrottled, the form
     * is a free way to fill both the inbox and the mail queue.
     */
    for ($i = 1; $i <= 5; $i++) {
        post(route('contact.store'), messagePayload(['email' => "sender{$i}@example.test"]))
            ->assertRedirect();
    }

    post(route('contact.store'), messagePayload(['email' => 'sixth@example.test']))
        ->assertStatus(429);

    expect(ContactMessage::count())->toBe(5);
});

// ── The inbox ───────────────────────────────────────────────────

it('lists an arriving message in the admin inbox', function () {
    post(route('contact.store'), messagePayload());

    actingAs($this->admin)
        ->get(route('admin.messages.index'))
        ->assertOk()
        ->assertSee('ليان أبو ديّة')
        ->assertSee('layan@example.test')
        // The subject renders as its Arabic label, not the raw enum value.
        ->assertSee(ContactSubject::Support->label())
        ->assertSee('غير مقروءة');
});

it('separates unread from awaiting a reply', function () {
    // 🔴 The two are not the same state. A message can be opened and left
    // unanswered, and that queue is the whole reason the inbox exists.
    $unread = ContactMessage::create(messagePayload(['email' => 'a@example.test']));
    $opened = ContactMessage::create(messagePayload(['email' => 'b@example.test', 'is_read' => true]));
    $answered = ContactMessage::create(messagePayload([
        'email' => 'c@example.test',
        'is_read' => true,
        'reply_message' => 'تفضّلي الإجابة.',
        'replied_at' => now(),
    ]));

    $ids = fn (string $status) => actingAs($this->admin)
        ->get(route('admin.messages.index', ['status' => $status]))
        ->assertOk()
        ->viewData('messages')
        ->pluck('id')
        ->all();

    expect($ids('unread'))->toBe([$unread->id])
        /*
         * ⚠️ «بانتظار الردّ» is every UNANSWERED message, read or not — so
         * it overlaps the unread tab on purpose. A reply queue that left
         * out unread messages would hide the newest and most urgent ones,
         * the opposite of what the tab is for. The row badge carries the
         * finer distinction: a row here can still be badged «غير مقروءة».
         */
        ->and($ids('awaiting'))->toBe([$opened->id, $unread->id])
        ->and($ids('replied'))->toBe([$answered->id])
        ->and($ids('all'))->toHaveCount(3)
        // An unrecognised status falls back to all rather than an empty list.
        ->and($ids('nonsense'))->toHaveCount(3);
});

it('marks a message read when it is opened, and only then', function () {
    $message = ContactMessage::create(messagePayload());

    // ⚠️ Read back from the database, not from the instance just created:
    // `is_read` is a column default, so the in-memory model carries null
    // for it until it is refreshed.
    expect($message->fresh()->is_read)->toBeFalse();

    actingAs($this->admin)->get(route('admin.messages.show', $message))->assertOk();

    expect($message->fresh()->is_read)->toBeTrue();

    // ⚠️ Re-opening must not touch the row. markAsRead() writes only on a
    // real change — otherwise `updated_at` tracks page views, not handling.
    $touchedAt = $message->fresh()->updated_at;

    actingAs($this->admin)->get(route('admin.messages.show', $message))->assertOk();

    expect($message->fresh()->updated_at->eq($touchedAt))->toBeTrue();
});

// ── Replying ────────────────────────────────────────────────────

it('records the reply and queues it to the sender', function () {
    $message = ContactMessage::create(messagePayload());

    actingAs($this->admin)
        ->post(route('admin.messages.reply', $message), [
            'reply_message' => 'نعم، الاشتراك الشامل يغطي كل مواد الفرع العلمي.',
        ])
        ->assertRedirect(route('admin.messages.index'));

    $message->refresh();

    expect($message->reply_message)->toBe('نعم، الاشتراك الشامل يغطي كل مواد الفرع العلمي.')
        ->and($message->replied_at)->not->toBeNull()
        // Replying implies reading — otherwise an answered message would sit
        // in the unread count forever.
        ->and($message->is_read)->toBeTrue()
        // Who answered is recorded, so a second admin is not left guessing.
        ->and($message->admin_id)->toBe($this->admin->id);

    Mail::assertQueued(ContactReplyMail::class, function ($mail) use ($message) {
        return $mail->hasTo($message->email)
            // 🔴 The mailable reads the body from the model, so the row must
            // already be written when the job is pushed.
            && $mail->contactMessage->reply_message === $message->reply_message;
    });

    Mail::assertNotSent(ContactReplyMail::class);
});

it('refuses an empty or one-word reply', function () {
    $message = ContactMessage::create(messagePayload());

    foreach (['', 'حسناً'] as $attempt) {
        actingAs($this->admin)
            ->post(route('admin.messages.reply', $message), ['reply_message' => $attempt])
            ->assertSessionHasErrors('reply_message');
    }

    // Nothing recorded and nothing sent: a rejected reply leaves the message
    // exactly as it was, still awaiting an answer.
    expect($message->fresh()->replied_at)->toBeNull()
        ->and($message->fresh()->reply_message)->toBeNull();

    Mail::assertNothingQueued();
});

// ── Trash ───────────────────────────────────────────────────────

it('sends a deleted message to the trash and restores it', function () {
    $message = ContactMessage::create(messagePayload());

    actingAs($this->admin)
        ->delete(route('admin.messages.destroy', $message))
        ->assertRedirect(route('admin.messages.index'))
        // The undo is a real PATCH form in the shell, so the URL is handed
        // to the view rather than embedded in a notice as a link.
        ->assertSessionHas('admin.undo');

    expect(ContactMessage::count())->toBe(0)
        ->and(ContactMessage::withTrashed()->count())->toBe(1);

    actingAs($this->admin)
        ->get(route('admin.trash.index', ['type' => 'messages']))
        ->assertOk()
        ->assertSee('layan@example.test');

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'messages', 'id' => $message->id]))
        ->assertRedirect();

    expect(ContactMessage::count())->toBe(1);
});

it('enforces each inbox permission independently', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('reply-messages');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $message = ContactMessage::create(messagePayload());

    // 🔴 The direct POST is the test that matters. The reply form is hidden
    // behind @can, but hiding a form is not a guard.
    actingAs($this->admin)
        ->post(route('admin.messages.reply', $message), ['reply_message' => 'ردّ لن يُقبل.'])
        ->assertForbidden();

    expect($message->fresh()->replied_at)->toBeNull();
    Mail::assertNothingQueued();

    // Reading and deleting are untouched by that revocation.
    actingAs($this->admin)->get(route('admin.messages.index'))->assertOk();
    actingAs($this->admin)->delete(route('admin.messages.destroy', $message))->assertRedirect();
});

it('keeps the inbox away from guests and students', function () {
    $message = ContactMessage::create(messagePayload());

    get(route('admin.messages.index'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.messages.index'))->assertForbidden();
    actingAs($this->student)->get(route('admin.messages.show', $message))->assertForbidden();

    // A student must not be able to read strangers' messages by opening one
    // directly — nor mark it read as a side effect of trying.
    expect($message->fresh()->is_read)->toBeFalse();
});

it('registers the inbox permissions and its trash type', function () {
    expect(PermissionCatalog::names())
        ->toContain('view-messages')
        ->toContain('reply-messages')
        ->toContain('delete-messages')
        ->and(array_keys(TrashableResources::all()))->toContain('messages');
});

// ── Sidebar counter ─────────────────────────────────────────────

/**
 * The messages link markup alone, from its href to the end of the anchor.
 *
 * ⚠️ Scoped deliberately. A page-wide assertion on a number would match the
 * tab counts, the pagination, or any other digit on the screen, and would
 * pass while the badge itself was missing.
 */
function sidebarMessagesLink(string $html): string
{
    preg_match('#<a[^>]+href="[^"]*/admin/messages"[^>]*>.*?</a>#s', $html, $m);

    return $m[0] ?? '';
}

it('counts unread messages on the sidebar link', function () {
    ContactMessage::create(messagePayload(['email' => 'a@example.test']));
    ContactMessage::create(messagePayload(['email' => 'b@example.test']));
    ContactMessage::create(messagePayload(['email' => 'c@example.test', 'is_read' => true]));

    $link = sidebarMessagesLink(actingAs($this->admin)->get(route('admin.messages.index'))->getContent());

    expect($link)->not->toBe('')
        ->and($link)->toContain('bg-warn')
        // Two unread, not three: the read one is handled.
        ->and(preg_replace('/\s+/', '', strip_tags($link)))->toContain('2');
});

it('drops the counter entirely when nothing is unread', function () {
    // A badge reading "0" trains the eye to ignore the badge.
    ContactMessage::create(messagePayload(['is_read' => true]));

    $link = sidebarMessagesLink(actingAs($this->admin)->get(route('admin.messages.index'))->getContent());

    expect($link)->not->toBe('')
        ->and($link)->not->toContain('bg-warn');
});
