<?php

use App\Enums\NewsletterStatus;
use App\Jobs\SendNewsletterJob;
use App\Mail\NewsletterCampaignMailable;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\NewsletterSubscriber;
use App\Models\Student;
use App\Support\PermissionCatalog;
use App\Support\TrashableResources;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/*
| The newsletter — footer signup, the admin list, a campaign, and the way out.
|
| 🔴 The one thing this system must never do is mail somebody who opted out.
| Most of what is asserted below is that guarantee seen from a different
| angle: at dispatch, at send, on a tampered request, and on a re-subscribe.
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

// ── Public signup ───────────────────────────────────────────────

it('subscribes an address from the footer form', function () {
    post(route('newsletter.subscribe'), ['email' => 'reader@example.test'])
        ->assertRedirect()
        ->assertSessionHas('newsletter.status', 'subscribed');

    $subscriber = NewsletterSubscriber::sole();

    expect($subscriber->email)->toBe('reader@example.test')
        ->and($subscriber->status)->toBe(NewsletterStatus::Active)
        ->and($subscriber->subscribed_at)->not->toBeNull()
        ->and($subscriber->unsubscribed_at)->toBeNull();
});

it('treats a repeat signup as a no-op rather than an error', function () {
    // 🔴 email is unique. Without the lookup-first path this is a duplicate
    // key error — a 500 on the footer of every page for anyone who forgets
    // whether they already subscribed.
    $first = NewsletterSubscriber::subscribe('reader@example.test');
    $originalDate = $first->subscribed_at;

    $this->travel(2)->days();

    post(route('newsletter.subscribe'), ['email' => 'reader@example.test'])
        ->assertRedirect()
        ->assertSessionHas('newsletter.status', 'already');

    expect(NewsletterSubscriber::count())->toBe(1)
        // ⚠️ The original date survives: re-submitting must not rewrite how
        // long someone has actually been on the list.
        ->and(NewsletterSubscriber::sole()->subscribed_at->eq($originalDate))->toBeTrue();
});

it('brings a soft deleted address back instead of colliding with it', function () {
    /*
     * 🔴 The unique index does not ignore deleted_at, so a trashed row still
     * occupies the address. A plain create() here throws a duplicate key
     * error — a returning subscriber would meet a 500 on the footer form.
     */
    $old = NewsletterSubscriber::subscribe('reader@example.test');
    $old->delete();

    post(route('newsletter.subscribe'), ['email' => 'reader@example.test'])->assertRedirect();

    expect(NewsletterSubscriber::count())->toBe(1)
        ->and(NewsletterSubscriber::withTrashed()->count())->toBe(1)
        ->and(NewsletterSubscriber::sole()->id)->toBe($old->id);
});

it('reactivates someone who had unsubscribed and signed up again', function () {
    $subscriber = NewsletterSubscriber::factory()->unsubscribed()->create([
        'email' => 'reader@example.test',
    ]);

    post(route('newsletter.subscribe'), ['email' => 'reader@example.test'])->assertRedirect();

    $subscriber->refresh();

    expect($subscriber->status)->toBe(NewsletterStatus::Active)
        // Cleared, not left behind: a row that is active AND carries an
        // unsubscribe date makes every retention report wrong.
        ->and($subscriber->unsubscribed_at)->toBeNull();
});

it('rejects an address that is not one', function () {
    post(route('newsletter.subscribe'), ['email' => 'ليس بريداً'])
        ->assertSessionHasErrors('email');

    expect(NewsletterSubscriber::count())->toBe(0);
});

// ── The subscriber list ─────────────────────────────────────────

it('lists subscribers with their status', function () {
    NewsletterSubscriber::factory()->create(['email' => 'active@example.test']);
    NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.test']);

    actingAs($this->admin)
        ->get(route('admin.newsletter.subscribers.index'))
        ->assertOk()
        ->assertSee('active@example.test')
        ->assertSee('gone@example.test')
        ->assertSee('نشط')
        ->assertSee('ملغى الاشتراك');
});

it('filters the list by status', function () {
    $active = NewsletterSubscriber::factory()->create(['email' => 'active@example.test']);
    $gone = NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.test']);

    $ids = fn (array $query) => actingAs($this->admin)
        ->get(route('admin.newsletter.subscribers.index', $query))
        ->assertOk()
        ->viewData('subscribers')
        ->pluck('id')
        ->all();

    expect($ids(['status' => 'active']))->toBe([$active->id])
        ->and($ids(['status' => 'unsubscribed']))->toBe([$gone->id])
        ->and($ids([]))->toHaveCount(2)
        // An unrecognised status falls back to the whole list, never an
        // empty one — a filter typo must not read as "no subscribers".
        ->and($ids(['status' => 'nonsense']))->toHaveCount(2);
});

it('searches by email, and keeps the status filter while doing it', function () {
    NewsletterSubscriber::factory()->create(['email' => 'layan@example.test']);
    NewsletterSubscriber::factory()->create(['email' => 'omar@example.test']);
    NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'layan.old@example.test']);

    $ids = fn (array $query) => actingAs($this->admin)
        ->get(route('admin.newsletter.subscribers.index', $query))
        ->assertOk()
        ->viewData('subscribers')
        ->pluck('email')
        ->all();

    expect($ids(['q' => 'layan']))->toHaveCount(2)
        ->and($ids(['q' => 'omar']))->toBe(['omar@example.test'])
        // 🔴 Search and status are ANDed. If the two did not combine, an
        // admin narrowing a filtered list would silently get the whole table.
        ->and($ids(['q' => 'layan', 'status' => 'active']))->toBe(['layan@example.test']);
});

it('toggles a subscriber between active and unsubscribed', function () {
    $subscriber = NewsletterSubscriber::factory()->create();

    actingAs($this->admin)
        ->patch(route('admin.newsletter.subscribers.toggle', $subscriber))
        ->assertRedirect(route('admin.newsletter.subscribers.index'));

    $subscriber->refresh();

    expect($subscriber->status)->toBe(NewsletterStatus::Unsubscribed)
        ->and($subscriber->unsubscribed_at)->not->toBeNull();

    actingAs($this->admin)
        ->patch(route('admin.newsletter.subscribers.toggle', $subscriber))
        ->assertRedirect();

    $subscriber->refresh();

    expect($subscriber->status)->toBe(NewsletterStatus::Active)
        ->and($subscriber->unsubscribed_at)->toBeNull();
});

it('sends a deleted subscriber to the trash and restores it', function () {
    $subscriber = NewsletterSubscriber::factory()->create(['email' => 'reader@example.test']);

    actingAs($this->admin)
        ->delete(route('admin.newsletter.subscribers.destroy', $subscriber))
        ->assertRedirect(route('admin.newsletter.subscribers.index'))
        ->assertSessionHas('admin.undo');

    expect(NewsletterSubscriber::count())->toBe(0)
        ->and(NewsletterSubscriber::withTrashed()->count())->toBe(1);

    actingAs($this->admin)
        ->get(route('admin.trash.index', ['type' => 'newsletter']))
        ->assertOk()
        ->assertSee('reader@example.test');

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'newsletter', 'id' => $subscriber->id]))
        ->assertRedirect();

    expect(NewsletterSubscriber::count())->toBe(1);
});

it('bulk deletes only the rows that were selected', function () {
    $doomed = NewsletterSubscriber::factory()->count(3)->create();
    $spared = NewsletterSubscriber::factory()->create();

    actingAs($this->admin)
        ->delete(route('admin.newsletter.subscribers.bulk-destroy'), [
            'ids' => $doomed->pluck('id')->all(),
        ])
        ->assertRedirect(route('admin.newsletter.subscribers.index'));

    expect(NewsletterSubscriber::count())->toBe(1)
        ->and(NewsletterSubscriber::sole()->id)->toBe($spared->id)
        ->and(NewsletterSubscriber::withTrashed()->count())->toBe(4);
});

it('refuses a bulk delete with nothing selected', function () {
    NewsletterSubscriber::factory()->count(2)->create();

    actingAs($this->admin)
        ->delete(route('admin.newsletter.subscribers.bulk-destroy'), ['ids' => []])
        ->assertSessionHasErrors('ids');

    // 🔴 The empty array must not read as "all rows".
    expect(NewsletterSubscriber::count())->toBe(2);
});

it('exports the filtered list as CSV', function () {
    NewsletterSubscriber::factory()->create(['email' => 'active@example.test']);
    NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.test']);

    $response = actingAs($this->admin)
        ->get(route('admin.newsletter.subscribers.export', ['status' => 'active']))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('active@example.test')
        // Exports the FILTER, not everything: an export ignoring the active
        // filter would hand someone a list including opted-out addresses.
        ->and($csv)->not->toContain('gone@example.test')
        // ⚠️ UTF-8 BOM, or Excel on Windows renders the Arabic headers as
        // mojibake.
        ->and(str_starts_with($csv, "\xEF\xBB\xBF"))->toBeTrue();
});

// ── The campaign screen ─────────────────────────────────────────

it('offers every active subscriber, pre-selected, and no unsubscribed one', function () {
    $active = NewsletterSubscriber::factory()->create(['email' => 'active@example.test']);
    NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'gone@example.test']);

    $html = actingAs($this->admin)
        ->get(route('admin.newsletter.send'))
        ->assertOk()
        ->assertSee('active@example.test')
        /*
         * 🔴 An unsubscribed address must not even be offered. Rendering it
         * as an unchecked box would put the one act this system must never
         * commit a single click away.
         */
        ->assertDontSee('gone@example.test')
        ->getContent();

    expect($html)->toContain('value="'.$active->id.'"')
        ->toContain('name="recipients[]"')
        // Checked by default: the ordinary campaign goes to the whole list.
        ->toContain('checked');
});

it('shows an empty state instead of a form when nobody is subscribed', function () {
    NewsletterSubscriber::factory()->unsubscribed()->create();

    actingAs($this->admin)
        ->get(route('admin.newsletter.send'))
        ->assertOk()
        ->assertSee('لا مشتركين نشطين')
        // A compose form whose send can never succeed wastes the writing.
        ->assertDontSee('name="recipients[]"', false);
});

// ── Dispatching ─────────────────────────────────────────────────

it('queues one job per selected subscriber and none for the rest', function () {
    Queue::fake();

    $chosen = NewsletterSubscriber::factory()->count(2)->create();
    $notChosen = NewsletterSubscriber::factory()->create();

    actingAs($this->admin)
        ->post(route('admin.newsletter.send.store'), [
            'subject' => 'مستجدّات الفصل الثاني',
            'body' => 'أضفنا محاضرات جديدة في الرياضيات والفيزياء لهذا الفصل.',
            'recipients' => $chosen->pluck('id')->all(),
        ])
        ->assertRedirect(route('admin.newsletter.subscribers.index'));

    Queue::assertPushed(SendNewsletterJob::class, 2);

    foreach ($chosen as $subscriber) {
        Queue::assertPushed(SendNewsletterJob::class,
            fn (SendNewsletterJob $job): bool => $job->subscriberId === $subscriber->id);
    }

    Queue::assertNotPushed(SendNewsletterJob::class,
        fn (SendNewsletterJob $job): bool => $job->subscriberId === $notChosen->id);
});

it('drops a tampered recipient that is not an active subscriber', function () {
    Queue::fake();

    $active = NewsletterSubscriber::factory()->create();
    $unsubscribed = NewsletterSubscriber::factory()->unsubscribed()->create();
    $trashed = tap(NewsletterSubscriber::factory()->create())->delete();

    /*
     * 🔴 The form posts ids, so the request is a list of integers anyone can
     * edit. The recipient list is rebuilt from the database for exactly this
     * reason: the worst a tampered post can do is mail FEWER people.
     */
    actingAs($this->admin)
        ->post(route('admin.newsletter.send.store'), [
            'subject' => 'عنوان',
            'body' => 'محتوى النشرة البريدية لهذا الأسبوع، بطول كافٍ للقبول.',
            'recipients' => [$active->id, $unsubscribed->id, $trashed->id, 9999],
        ])
        ->assertRedirect();

    Queue::assertPushed(SendNewsletterJob::class, 1);
    Queue::assertPushed(SendNewsletterJob::class,
        fn (SendNewsletterJob $job): bool => $job->subscriberId === $active->id);
});

it('refuses a campaign with no recipients or no content', function () {
    Queue::fake();

    $subscriber = NewsletterSubscriber::factory()->create();

    actingAs($this->admin)
        ->post(route('admin.newsletter.send.store'), [
            'subject' => 'عنوان', 'body' => 'محتوى كافٍ الطول للقبول هنا.', 'recipients' => [],
        ])
        ->assertSessionHasErrors('recipients');

    actingAs($this->admin)
        ->post(route('admin.newsletter.send.store'), [
            'subject' => '', 'body' => '', 'recipients' => [$subscriber->id],
        ])
        ->assertSessionHasErrors(['subject', 'body']);

    Queue::assertNothingPushed();
});

it('sends the campaign with a per-recipient unsubscribe link', function () {
    $subscriber = NewsletterSubscriber::factory()->create(['email' => 'reader@example.test']);

    (new SendNewsletterJob($subscriber->id, 'عنوان النشرة', 'محتوى النشرة لهذا الأسبوع.'))->handle();

    Mail::assertSent(NewsletterCampaignMailable::class, function ($mail) use ($subscriber) {
        $rendered = $mail->render();

        return $mail->hasTo('reader@example.test')
            && $mail->subjectLine === 'عنوان النشرة'
            && str_contains($rendered, 'محتوى النشرة لهذا الأسبوع.')
            /*
             * 🔴 Every campaign carries a way out, and it is signed and
             * specific to this recipient — a shared link would unsubscribe
             * whoever happened to be first in the batch.
             */
            && str_contains($rendered, 'إلغاء الاشتراك')
            && str_contains($rendered, 'signature=')
            && str_contains($rendered, '/newsletter/unsubscribe/'.$subscriber->id);
    });
});

it('does not send to someone who unsubscribed after the job was queued', function () {
    /*
     * 🔴 The window that matters: a queue running behind means the list at
     * dispatch time is not the list at send time. Mailing someone who opted
     * out in that gap is the complaint that gets a domain blocklisted.
     */
    $subscriber = NewsletterSubscriber::factory()->create();

    $job = new SendNewsletterJob($subscriber->id, 'عنوان', 'محتوى.');

    $subscriber->unsubscribe();

    $job->handle();

    Mail::assertNothingSent();
});

// ── Unsubscribing ───────────────────────────────────────────────

it('unsubscribes through the signed link', function () {
    $subscriber = NewsletterSubscriber::factory()->create();

    get($subscriber->unsubscribeUrl())
        ->assertOk()
        ->assertSee('إلغاء الاشتراك في النشرة');

    /*
     * 🔴 The GET changed nothing. Mail clients and security scanners fetch
     * every link in a message before a human sees it, so an opt-out firing
     * on GET would silently remove people who never clicked.
     */
    expect($subscriber->fresh()->status)->toBe(NewsletterStatus::Active);

    post(URL::signedRoute('newsletter.unsubscribe.confirm', ['subscriber' => $subscriber->id]))
        ->assertRedirect(route('newsletter.unsubscribed'));

    $subscriber->refresh();

    expect($subscriber->status)->toBe(NewsletterStatus::Unsubscribed)
        ->and($subscriber->unsubscribed_at)->not->toBeNull();
});

it('refuses an unsigned or altered unsubscribe link', function () {
    $mine = NewsletterSubscriber::factory()->create();
    $someoneElse = NewsletterSubscriber::factory()->create();

    // 🔴 Without a signature anyone could walk the integers and unsubscribe
    // the entire list.
    get(route('newsletter.unsubscribe', ['subscriber' => $mine->id]))
        ->assertForbidden();

    /*
     * Retargeting a VALID signature at another subscriber — the attack the
     * signature exists to stop, since the id it covers is part of what was
     * signed.
     *
     * ⚠️ Aimed at a row that EXISTS on purpose. Pointing it at a missing id
     * returns 404 instead, because SubstituteBindings (web group) resolves
     * before the route's own `signed` middleware — refused either way, but
     * it would not be the signature doing the refusing, and the test would
     * pass without proving anything.
     */
    $tampered = str_replace(
        '/unsubscribe/'.$mine->id,
        '/unsubscribe/'.$someoneElse->id,
        $mine->unsubscribeUrl()
    );

    expect($tampered)->not->toBe($mine->unsubscribeUrl());

    get($tampered)->assertForbidden();

    expect($mine->fresh()->status)->toBe(NewsletterStatus::Active)
        ->and($someoneElse->fresh()->status)->toBe(NewsletterStatus::Active);
});
it('keeps the first unsubscribe date when the link is clicked twice', function () {
    $subscriber = NewsletterSubscriber::factory()->create();

    post(URL::signedRoute('newsletter.unsubscribe.confirm', ['subscriber' => $subscriber->id]))
        ->assertRedirect();

    $firstDate = $subscriber->fresh()->unsubscribed_at;

    $this->travel(3)->days();

    post(URL::signedRoute('newsletter.unsubscribe.confirm', ['subscriber' => $subscriber->id]))
        ->assertRedirect();

    // Idempotent: the second click must not rewrite the date of the first.
    expect($subscriber->fresh()->unsubscribed_at->eq($firstDate))->toBeTrue();

    // And the page says so plainly rather than offering a button that would
    // do nothing.
    get($subscriber->unsubscribeUrl())->assertOk()->assertSee('اشتراكك ملغى بالفعل');
});

// ── Permissions ─────────────────────────────────────────────────

it('separates reading the list from mailing it', function () {
    /*
     * 🔴 The whole reason these are two permissions. Reading the list is an
     * act with no trace; sending a campaign leaves the server for inboxes it
     * cannot be recalled from.
     */
    Role::findByName('admin', 'web')->revokePermissionTo('send-newsletter');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Queue::fake();
    $subscriber = NewsletterSubscriber::factory()->create();

    actingAs($this->admin)->get(route('admin.newsletter.send'))->assertForbidden();

    // The direct POST is the one that matters — hiding a link is not a guard.
    actingAs($this->admin)
        ->post(route('admin.newsletter.send.store'), [
            'subject' => 'عنوان', 'body' => 'محتوى كافٍ الطول للقبول.', 'recipients' => [$subscriber->id],
        ])
        ->assertForbidden();

    Queue::assertNothingPushed();

    actingAs($this->admin)->get(route('admin.newsletter.subscribers.index'))->assertOk();
});

it('separates deleting a subscriber from reading the list', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('delete-newsletter');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $subscriber = NewsletterSubscriber::factory()->create();

    actingAs($this->admin)
        ->delete(route('admin.newsletter.subscribers.destroy', $subscriber))
        ->assertForbidden();

    actingAs($this->admin)
        ->delete(route('admin.newsletter.subscribers.bulk-destroy'), ['ids' => [$subscriber->id]])
        ->assertForbidden();

    expect(NewsletterSubscriber::count())->toBe(1);

    actingAs($this->admin)->get(route('admin.newsletter.subscribers.index'))->assertOk();
});

it('keeps the whole module away from guests and students', function () {
    $subscriber = NewsletterSubscriber::factory()->create();

    get(route('admin.newsletter.subscribers.index'))->assertRedirect(route('auth.login'));
    get(route('admin.newsletter.send'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.newsletter.subscribers.index'))->assertForbidden();
    actingAs($this->student)->get(route('admin.newsletter.send'))->assertForbidden();
    actingAs($this->student)
        ->delete(route('admin.newsletter.subscribers.destroy', $subscriber))
        ->assertForbidden();

    expect(NewsletterSubscriber::count())->toBe(1);
});

it('registers the newsletter permissions and its trash type', function () {
    expect(PermissionCatalog::names())
        ->toContain('view-newsletter')
        ->toContain('send-newsletter')
        ->toContain('delete-newsletter')
        ->and(array_keys(TrashableResources::all()))->toContain('newsletter');
});

it('shows both newsletter links in the sidebar, and neither without permission', function () {
    $sidebar = fn () => actingAs($this->admin)
        ->get(route('admin.newsletter.subscribers.index'))
        ->assertOk()
        ->getContent();

    expect($sidebar())->toContain(route('admin.newsletter.subscribers.index'))
        ->toContain(route('admin.newsletter.send'))
        ->toContain(route('admin.newsletter.campaigns.index'));

    /*
     * ⚠️ The parent is a disclosure toggle with no permission of its own, so
     * its visibility is derived from its children. With both revoked it must
     * disappear entirely — a dropdown that opens onto nothing is worse than
     * no dropdown.
     */
    /*
     * ⚠️ EVERY child, not just two. The parent survives on any one surviving
     * child, so a list that misses a newly added sub-item would leave this
     * test asserting nothing — which is exactly what happened when
     * "سجل الحملات" was added.
     */
    $role = Role::findByName('admin', 'web');
    $role->revokePermissionTo('view-newsletter');
    $role->revokePermissionTo('send-newsletter');
    $role->revokePermissionTo('view-campaigns');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $html = actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)->not->toContain('النشرة البريدية');
});
