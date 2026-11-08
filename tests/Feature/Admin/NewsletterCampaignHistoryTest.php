<?php

use App\Enums\CampaignStatus;
use App\Jobs\SendNewsletterJob;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\Student;
use App\Support\PermissionCatalog;
use App\Support\TrashableResources;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| The campaign archive — what was sent, by whom, to how many, and when.
|
| 🔴 The record is written BEFORE the queue runs, so it starts life as
| `processing` with no sent_at. Anything that claimed "completed" at dispatch
| time would be asserting a delivery that has not happened yet.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Mail::fake();

    $this->admin = tap(Admin::create(['full_name' => 'سارة الأدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $branch = Branch::create(['name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

/** Sends a campaign the way the screen does, returning the response. */
function sendCampaign(array $overrides = [])
{
    $recipients = $overrides['recipients']
        ?? NewsletterSubscriber::query()->active()->pluck('id')->all();

    return actingAs(test()->admin)->post(route('admin.newsletter.send.store'), [
        'subject' => $overrides['subject'] ?? 'مستجدّات الفصل الثاني',
        'body' => $overrides['body'] ?? 'أضفنا محاضرات جديدة في الرياضيات والفيزياء لهذا الفصل.',
        'recipients' => $recipients,
    ]);
}

// ── Recording a send ────────────────────────────────────────────

it('records a campaign when a newsletter is sent', function () {
    Bus::fake();

    NewsletterSubscriber::factory()->count(3)->create();

    sendCampaign()->assertRedirect();

    $campaign = NewsletterCampaign::sole();

    expect($campaign->subject)->toBe('مستجدّات الفصل الثاني')
        ->and($campaign->body)->toContain('محاضرات جديدة')
        ->and($campaign->recipients_count)->toBe(3)
        // 🔴 processing, not completed: at this moment the jobs are queued
        // and nothing has been delivered.
        ->and($campaign->status)->toBe(CampaignStatus::Processing)
        ->and($campaign->sent_at)->toBeNull()
        // Attributed to whoever pressed the button.
        ->and($campaign->admin_id)->toBe($this->admin->id);
});

it('counts only the recipients that were actually queued', function () {
    Bus::fake();

    $active = NewsletterSubscriber::factory()->count(2)->create();
    $unsubscribed = NewsletterSubscriber::factory()->unsubscribed()->create();

    /*
     * 🔴 recipients_count must match the jobs queued, not the ids posted. The
     * form is editable, and a record claiming three recipients when two were
     * mailed is an archive that lies about what happened.
     */
    sendCampaign(['recipients' => [...$active->pluck('id')->all(), $unsubscribed->id, 9999]])
        ->assertRedirect();

    expect(NewsletterCampaign::sole()->recipients_count)->toBe(2);
});

it('carries the campaign id into every queued job', function () {
    Queue::fake();

    NewsletterSubscriber::factory()->count(2)->create();

    sendCampaign()->assertRedirect();

    $campaign = NewsletterCampaign::sole();

    Queue::assertPushed(SendNewsletterJob::class, 2);
    Queue::assertPushed(SendNewsletterJob::class,
        fn (SendNewsletterJob $job): bool => $job->campaignId === $campaign->id);
});

it('records no campaign when the send is refused', function () {
    Bus::fake();

    NewsletterSubscriber::factory()->create();

    // Validation failure, and a recipient list that survives validation but
    // contains nobody actually mailable.
    sendCampaign(['subject' => '', 'body' => ''])->assertSessionHasErrors();
    sendCampaign(['recipients' => [9999]])->assertSessionHasErrors('recipients');

    // ⚠️ A row here would be a campaign in the history that never went out.
    expect(NewsletterCampaign::count())->toBe(0);
});

// ── Closing the campaign ────────────────────────────────────────

it('completes the campaign once the whole batch has run', function () {
    /*
     * 🔴 The real thing, not a fake: the queue is `sync` in tests, so the
     * batch runs inline and its finally() callback fires. This is the only
     * assertion that proves the record ever leaves `processing` — with one
     * job per recipient nothing else knows the send is over.
     */
    NewsletterSubscriber::factory()->count(2)->create();

    sendCampaign()->assertRedirect();

    $campaign = NewsletterCampaign::sole();

    expect($campaign->status)->toBe(CampaignStatus::Completed)
        ->and($campaign->sent_at)->not->toBeNull();

    Mail::assertSentCount(2);
});

it('keeps the first finish time when the batch closes twice', function () {
    $campaign = NewsletterCampaign::factory()->processing()->create();

    $campaign->markFinished(hasFailures: false);
    $firstDate = $campaign->fresh()->sent_at;

    $this->travel(3)->days();

    $campaign->markFinished(hasFailures: true);

    // Idempotent: the callback can fire more than once, and a second close
    // must not move the timestamp or rewrite the outcome.
    expect($campaign->fresh()->sent_at->eq($firstDate))->toBeTrue()
        ->and($campaign->fresh()->status)->toBe(CampaignStatus::Completed);
});

it('marks the campaign failed when a job gives up, without closing it', function () {
    $campaign = NewsletterCampaign::factory()->processing()->create();
    $subscriber = NewsletterSubscriber::factory()->create();

    (new SendNewsletterJob($subscriber->id, 'عنوان', 'محتوى.', $campaign->id))
        ->failed(new RuntimeException('SMTP refused'));

    $campaign->refresh();

    expect($campaign->status)->toBe(CampaignStatus::Failed)
        /*
         * ⚠️ Still open. Failures are allowed so one dead address cannot stop
         * the send, so the rest of the batch is still running — the campaign
         * is doomed to be recorded as failed, but it has not finished.
         */
        ->and($campaign->sent_at)->toBeNull();
});

it('survives a job that carries no campaign at all', function () {
    // The archive is bookkeeping: losing it must never cost a recipient
    // their email, so a campaign-less job still sends and still fails safely.
    $subscriber = NewsletterSubscriber::factory()->create();

    $job = new SendNewsletterJob($subscriber->id, 'عنوان', 'محتوى النشرة.');

    $job->failed(new RuntimeException('SMTP refused'));
    $job->handle();

    Mail::assertSentCount(1);
});

// ── The history screen ──────────────────────────────────────────

it('lists campaigns with sender, recipient count and status', function () {
    NewsletterCampaign::factory()->create([
        'admin_id' => $this->admin->id,
        'subject' => 'نشرة الأسبوع',
        'recipients_count' => 42,
    ]);

    actingAs($this->admin)
        ->get(route('admin.newsletter.campaigns.index'))
        ->assertOk()
        ->assertSee('نشرة الأسبوع')
        ->assertSee('سارة الأدمن')
        ->assertSee('42')
        ->assertSee('مكتملة');
});

it('names a departed sender rather than leaving the cell blank', function () {
    $campaign = NewsletterCampaign::factory()->create(['admin_id' => $this->admin->id]);

    // ⚠️ admin_id is nullOnDelete: removing a staff account must not delete
    // the record of a newsletter that already reached real inboxes.
    $this->admin->forceDelete();

    expect($campaign->fresh()->admin_id)->toBeNull();

    actingAs($this->student)->assertAuthenticated();

    // An empty cell reads as a broken screen rather than a departed colleague.
    expect($campaign->fresh()->senderName())->toBe('حساب محذوف');
});

it('shows the newest campaign first', function () {
    $older = NewsletterCampaign::factory()->create(['created_at' => now()->subWeek()]);
    $newer = NewsletterCampaign::factory()->create(['created_at' => now()]);

    $ids = actingAs($this->admin)
        ->get(route('admin.newsletter.campaigns.index'))
        ->assertOk()
        ->viewData('campaigns')
        ->pluck('id')
        ->all();

    expect($ids)->toBe([$newer->id, $older->id]);
});

it('filters the history by status', function () {
    $done = NewsletterCampaign::factory()->create();
    $running = NewsletterCampaign::factory()->processing()->create();
    $broken = NewsletterCampaign::factory()->failed()->create();

    $ids = fn (array $query) => actingAs($this->admin)
        ->get(route('admin.newsletter.campaigns.index', $query))
        ->assertOk()
        ->viewData('campaigns')
        ->pluck('id')
        ->all();

    expect($ids(['status' => 'completed']))->toBe([$done->id])
        ->and($ids(['status' => 'processing']))->toBe([$running->id])
        ->and($ids(['status' => 'failed']))->toBe([$broken->id])
        ->and($ids([]))->toHaveCount(3)
        // An unrecognised status shows everything, never nothing.
        ->and($ids(['status' => 'nonsense']))->toHaveCount(3);
});

it('shows no send date for a campaign still in the queue', function () {
    NewsletterCampaign::factory()->processing()->create(['subject' => 'قيد الإرسال الآن']);

    actingAs($this->admin)
        ->get(route('admin.newsletter.campaigns.index'))
        ->assertOk()
        ->assertSee('قيد الإرسال')
        // An unfinished campaign has no honest date to show.
        ->assertSee('—');
});

it('carries the full body to the preview, with the exact timestamp', function () {
    $campaign = NewsletterCampaign::factory()->create([
        'admin_id' => $this->admin->id,
        'body' => 'نصّ النشرة الكامل الذي يجب أن تعرضه نافذة المعاينة كاملاً.',
        'sent_at' => now()->subDays(3),
    ]);

    $html = actingAs($this->admin)
        ->get(route('admin.newsletter.campaigns.index'))
        ->assertOk()
        ->getContent();

    /*
     * 🔴 The modal is fed from data attributes on the row's button, so the
     * whole body must be in the document — the table cell only shows a
     * truncated line, and a preview built from that would show the same
     * truncation it exists to escape.
     */
    expect($html)->toContain('data-campaign-body="'.e($campaign->body).'"')
        ->toContain('data-campaign-sender="سارة الأدمن"')
        // The relative date is what an admin scans; the absolute one is what
        // they need when reconciling a complaint, so both are present.
        ->toContain($campaign->sent_at->format('Y-m-d H:i'));
});

// ── Duplicating ─────────────────────────────────────────────────

it('prefills the send form from a past campaign without sending anything', function () {
    Bus::fake();

    NewsletterSubscriber::factory()->create();

    $campaign = NewsletterCampaign::factory()->create([
        'subject' => 'عنوان أصلي',
        'body' => 'نصّ الحملة الأصلية الذي سيُنسَخ إلى النموذج الجديد.',
    ]);

    $html = actingAs($this->admin)
        ->get(route('admin.newsletter.send', ['from' => $campaign->id]))
        ->assertOk()
        ->assertSee('نسخة من حملة «عنوان أصلي»')
        ->getContent();

    expect($html)->toContain('value="عنوان أصلي"')
        ->toContain('نصّ الحملة الأصلية الذي سيُنسَخ إلى النموذج الجديد.');

    /*
     * 🔴 A GET that dispatched a campaign would fire on a browser prefetch or
     * a passing crawler and mail the whole list. Duplicating fills a form.
     */
    Bus::assertNothingBatched();
    expect(NewsletterCampaign::count())->toBe(1);
});

it('creates a separate record when a duplicate is actually sent', function () {
    Bus::fake();

    NewsletterSubscriber::factory()->count(2)->create();

    $original = NewsletterCampaign::factory()->create(['subject' => 'عنوان أصلي']);

    sendCampaign(['subject' => 'عنوان أصلي'])->assertRedirect();

    // A resend is a NEW row: the archive must keep saying what the first
    // send was, including its own recipient count.
    expect(NewsletterCampaign::count())->toBe(2)
        ->and($original->fresh()->recipients_count)->not->toBe(2);
});

it('ignores a duplicate link pointing at a campaign that is gone', function () {
    NewsletterSubscriber::factory()->create();

    // A stale bookmark must render the ordinary empty form, not a 500.
    actingAs($this->admin)
        ->get(route('admin.newsletter.send', ['from' => 9999]))
        ->assertOk()
        ->assertDontSee('نسخة من حملة');
});

// ── Trash ───────────────────────────────────────────────────────

it('sends a deleted campaign to the trash and restores it', function () {
    $campaign = NewsletterCampaign::factory()->create(['subject' => 'نشرة للحذف']);

    actingAs($this->admin)
        ->delete(route('admin.newsletter.campaigns.destroy', $campaign))
        ->assertRedirect(route('admin.newsletter.campaigns.index'))
        ->assertSessionHas('admin.undo');

    expect(NewsletterCampaign::count())->toBe(0)
        ->and(NewsletterCampaign::withTrashed()->count())->toBe(1);

    actingAs($this->admin)
        ->get(route('admin.trash.index', ['type' => 'campaigns']))
        ->assertOk()
        ->assertSee('نشرة للحذف');

    actingAs($this->admin)
        ->patch(route('admin.trash.restore', ['type' => 'campaigns', 'id' => $campaign->id]))
        ->assertRedirect();

    expect(NewsletterCampaign::count())->toBe(1);
});

it('does not unsend anything when a campaign record is deleted', function () {
    NewsletterSubscriber::factory()->count(2)->create();

    sendCampaign()->assertRedirect();

    $campaign = NewsletterCampaign::sole();

    actingAs($this->admin)
        ->delete(route('admin.newsletter.campaigns.destroy', $campaign))
        ->assertRedirect();

    /*
     * ⚠️ Worth stating outright: deleting the record removes the ARCHIVE, not
     * the emails. They are in other people's inboxes and nothing here can
     * recall them — which is why the delete dialog says so.
     */
    Mail::assertSentCount(2);
});

// ── Permissions ─────────────────────────────────────────────────

it('separates reading the archive from managing subscribers', function () {
    /*
     * 🔴 The archive holds the full TEXT of every newsletter sent. That can
     * be internal content, and it does not follow that everyone who may see
     * a list of addresses may read it.
     */
    Role::findByName('admin', 'web')->revokePermissionTo('view-campaigns');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($this->admin)->get(route('admin.newsletter.campaigns.index'))->assertForbidden();

    actingAs($this->admin)->get(route('admin.newsletter.subscribers.index'))->assertOk();
    actingAs($this->admin)->get(route('admin.newsletter.send'))->assertOk();
});

it('separates deleting from reading the archive', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('delete-campaigns');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $campaign = NewsletterCampaign::factory()->create();

    // The direct POST is what matters — hiding a button is not a guard.
    actingAs($this->admin)
        ->delete(route('admin.newsletter.campaigns.destroy', $campaign))
        ->assertForbidden();

    expect(NewsletterCampaign::count())->toBe(1);

    actingAs($this->admin)->get(route('admin.newsletter.campaigns.index'))->assertOk();
});

it('keeps the archive away from guests and students', function () {
    $campaign = NewsletterCampaign::factory()->create();

    get(route('admin.newsletter.campaigns.index'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.newsletter.campaigns.index'))->assertForbidden();
    actingAs($this->student)
        ->delete(route('admin.newsletter.campaigns.destroy', $campaign))
        ->assertForbidden();

    expect(NewsletterCampaign::count())->toBe(1);
});

it('registers the campaign permissions and its trash type', function () {
    expect(PermissionCatalog::names())
        ->toContain('view-campaigns')
        ->toContain('delete-campaigns')
        ->and(array_keys(TrashableResources::all()))->toContain('campaigns');
});
