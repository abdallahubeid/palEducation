<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\ContactMessage;
use App\Models\News;
use App\Models\NewsletterSubscriber;
use App\Models\Student;
use App\Models\User;
use App\Notifications\CriticalSettingChangedNotification;
use App\Notifications\NewAdminRegisteredNotification;
use App\Notifications\NewContactMessageNotification;
use App\Notifications\NewsletterCampaignFinishedNotification;
use App\Notifications\SystemErrorLoggedNotification;
use App\Notifications\TestimonialPendingNotification;
use App\Notifications\TrashAutoPurgeWarningNotification;
use App\Support\AdminNotifier;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/*
| The admin notification bell.
|
| 🔴 Two risks run through everything below. First, SCOPE: the endpoints carry
| no account id, so one admin must never be able to read or clear another's
| notifications. Second, VOLUME: the bell is polled every fifteen seconds by
| every open tab, and a system that notifies per-event without restraint fills
| the table faster than anyone reads it.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Mail::fake();
    Cache::flush();

    $this->admin = tap(Admin::create(['full_name' => 'سارة الأدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->otherAdmin = tap(Admin::create(['full_name' => 'أدمن آخر'])->user()->create([
        'email' => 'admin2@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $branch = Branch::create(['name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

// ── Recipients ──────────────────────────────────────────────────

it('notifies every active admin and nobody else', function () {
    Notification::fake();

    AdminNotifier::send(new TrashAutoPurgeWarningNotification(3, 30));

    Notification::assertSentTo([$this->admin, $this->otherAdmin], TrashAutoPurgeWarningNotification::class);

    // 🔴 A student must never receive an admin system alert.
    Notification::assertNotSentTo($this->student, TrashAutoPurgeWarningNotification::class);
});

it('skips deactivated admins', function () {
    Notification::fake();

    $this->otherAdmin->update(['is_active' => false]);

    AdminNotifier::send(new TrashAutoPurgeWarningNotification(1, 30));

    // ⚠️ They cannot sign in to read the bell, so the rows would sit unread
    // for ever.
    Notification::assertSentTo($this->admin, TrashAutoPurgeWarningNotification::class);
    Notification::assertNotSentTo($this->otherAdmin, TrashAutoPurgeWarningNotification::class);
});

it('excludes the admin who caused the event', function () {
    Notification::fake();

    AdminNotifier::send(new TrashAutoPurgeWarningNotification(1, 30), except: $this->admin);

    /*
     * ⚠️ Telling somebody "you changed the settings" one second after they
     * changed the settings is the fastest way to teach them to ignore the bell.
     */
    Notification::assertNotSentTo($this->admin, TrashAutoPurgeWarningNotification::class);
    Notification::assertSentTo($this->otherAdmin, TrashAutoPurgeWarningNotification::class);
});

// ── Triggers ────────────────────────────────────────────────────

it('notifies on a new contact message', function () {
    Notification::fake();

    post(route('contact.store'), [
        'name' => 'ليان', 'email' => 'layan@example.test',
        'subject_type' => 'support', 'message' => 'عندي سؤال عن الاشتراك في المنصّة.',
    ])->assertRedirect();

    Notification::assertSentTo($this->admin, NewContactMessageNotification::class);
});

it('notifies on a testimonial awaiting approval', function () {
    Notification::fake();

    actingAs($this->student)
        ->post(route('testimonials.store'), [
            'rate' => 5, 'description' => 'المنصّة ساعدتني كثيراً في مراجعة الرياضيات.',
        ])
        ->assertRedirect();

    Notification::assertSentTo($this->admin, TestimonialPendingNotification::class);
});

it('notifies when a newsletter campaign finishes', function () {
    Notification::fake();

    NewsletterSubscriber::factory()->count(2)->create();

    actingAs($this->admin)
        ->post(route('admin.newsletter.send.store'), [
            'subject' => 'نشرة الأسبوع',
            'body' => 'محتوى النشرة البريدية لهذا الأسبوع، بطول كافٍ للقبول.',
            'recipients' => NewsletterSubscriber::pluck('id')->all(),
        ])
        ->assertRedirect();

    /*
     * 🔴 The queue is `sync` in tests, so the batch runs inline and its
     * finally() callback fires — this asserts the real completion path, not
     * a stub.
     */
    Notification::assertSentTo($this->admin, NewsletterCampaignFinishedNotification::class);
});

it('notifies when a staff account is created, but not for a student', function () {
    Notification::fake();

    $branch = Branch::first();

    actingAs($this->admin)->post(route('admin.users.store'), [
        'type' => 'teacher', 'full_name' => 'أ. سامر',
        'email' => 'samer@test.local', 'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
    ])->assertRedirect();

    Notification::assertSentTo($this->otherAdmin, NewAdminRegisteredNotification::class);

    Notification::fake();

    actingAs($this->admin)->post(route('admin.users.store'), [
        'type' => 'student', 'full_name' => 'طالب جديد',
        'email' => 'newstudent@test.local', 'password' => 'Password!234',
        'password_confirmation' => 'Password!234', 'branch_id' => $branch->id,
    ])->assertRedirect();

    /*
     * 🔴 Student registrations are routine. One notification each would bury
     * everything else in the bell within a day of launch.
     */
    Notification::assertNothingSent();
});

it('notifies on a settings change, once per tab and not to the editor', function () {
    Notification::fake();

    actingAs($this->admin)
        ->put(route('admin.settings.news.update'), [
            'news' => ['badge_text' => 'شارة', 'title' => 'عنوان', 'sub_title' => 'نصّ'],
        ])
        ->assertRedirect();

    // ⚠️ One notification for the whole tab, not one per key — the save
    // writes several rows.
    Notification::assertSentToTimes($this->otherAdmin, CriticalSettingChangedNotification::class, 1);
    Notification::assertNotSentTo($this->admin, CriticalSettingChangedNotification::class);
});

it('warns about old trash without deleting anything', function () {
    Notification::fake();

    $news = News::factory()->create();
    $news->delete();
    $news->forceFill(['deleted_at' => now()->subDays(45)])->saveQuietly();

    $this->artisan('notifications:trash-warning')->assertSuccessful();

    Notification::assertSentTo($this->admin, TrashAutoPurgeWarningNotification::class);

    /*
     * 🔴 It WARNS and deletes nothing. The 30-day auto-purge is still an open
     * decision, and a scheduled command that quietly destroyed content would
     * be the worst way to settle it.
     */
    expect(News::withTrashed()->count())->toBe(1);
});

it('sends no trash warning when nothing is old enough', function () {
    Notification::fake();

    News::factory()->create()->delete();

    $this->artisan('notifications:trash-warning')->assertSuccessful();

    Notification::assertNothingSent();
});

it('reports a system error at most once an hour per distinct error', function () {
    Notification::fake();

    $error = new RuntimeException('قاعدة البيانات غير متاحة');

    AdminNotifier::reportError($error, '/admin/dashboard');
    AdminNotifier::reportError($error, '/admin/dashboard');
    AdminNotifier::reportError($error, '/admin/news');

    /*
     * 🔴 The dedupe is the whole point: a worker retrying a broken job
     * produces thousands of identical failures a minute, and without a guard
     * the bell becomes useless exactly when something is actually wrong.
     */
    Notification::assertSentToTimes($this->admin, SystemErrorLoggedNotification::class, 1);
});

it('still reports a different error while one is suppressed', function () {
    Notification::fake();

    AdminNotifier::reportError(new RuntimeException('الأول'), '');
    AdminNotifier::reportError(new LogicException('الثاني'), '');

    // ⚠️ Keyed on class + file + line, so a genuinely different failure is
    // never swallowed by an unrelated one.
    Notification::assertSentToTimes($this->admin, SystemErrorLoggedNotification::class, 2);
});

// ── Payload ─────────────────────────────────────────────────────

it('stores a complete payload the bell can render', function () {
    $message = ContactMessage::create([
        'name' => 'ليان', 'email' => 'layan@example.test',
        'subject_type' => 'support', 'message' => 'سؤال عن الاشتراك في المنصّة.',
    ]);

    AdminNotifier::send(new NewContactMessageNotification($message));

    $data = $this->admin->notifications()->sole()->data;

    /*
     * 🔴 The dropdown renders `data` blindly. A class that forgot a key would
     * show a blank row rather than an error, which is why the envelope is
     * fixed on the base class and asserted here.
     */
    expect($data)->toHaveKeys(['title', 'message', 'icon', 'url', 'type'])
        ->and($data['title'])->toBe('رسالة تواصل جديدة')
        ->and($data['message'])->toContain('ليان')
        ->and($data['url'])->toContain('/admin/messages/'.$message->id);
});

// ── The API ─────────────────────────────────────────────────────

it('returns unread count and recent items', function () {
    AdminNotifier::send(new TrashAutoPurgeWarningNotification(2, 30));

    $response = actingAs($this->admin)
        ->getJson(route('admin.notifications.feed'))
        ->assertOk()
        ->assertJsonStructure([
            'unread_count',
            'notifications' => [['id', 'title', 'message', 'icon', 'url', 'type', 'read', 'time']],
        ]);

    expect($response->json('unread_count'))->toBe(1)
        ->and($response->json('notifications.0.read'))->toBeFalse()
        // Arabic relative time is rendered server-side: the browser has no
        // Arabic plural rules for it and Carbon does.
        ->and($response->json('notifications.0.time'))->not->toBe('');
});

it('keeps read notifications in the list', function () {
    AdminNotifier::send(new TrashAutoPurgeWarningNotification(1, 30));

    $this->admin->unreadNotifications()->update(['read_at' => now()]);

    $response = actingAs($this->admin)->getJson(route('admin.notifications.feed'))->assertOk();

    // ⚠️ A dropdown that empties itself the moment you read it gives no way
    // back to something you glanced at.
    expect($response->json('unread_count'))->toBe(0)
        ->and($response->json('notifications'))->toHaveCount(1)
        ->and($response->json('notifications.0.read'))->toBeTrue();
});

it('caps the list at ten', function () {
    foreach (range(1, 14) as $i) {
        AdminNotifier::send(new TrashAutoPurgeWarningNotification($i, 30));
    }

    $response = actingAs($this->admin)->getJson(route('admin.notifications.feed'))->assertOk();

    // ⚠️ Polled every fifteen seconds by every open tab — the payload is
    // capped for that reason.
    expect($response->json('notifications'))->toHaveCount(10)
        ->and($response->json('unread_count'))->toBe(14);
});

it('marks one notification as read', function () {
    AdminNotifier::send(new TrashAutoPurgeWarningNotification(1, 30));

    $notification = $this->admin->notifications()->sole();

    actingAs($this->admin)
        ->postJson(route('admin.notifications.read', ['id' => $notification->id]))
        ->assertOk()
        ->assertJson(['unread_count' => 0]);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('keeps the first read time when marked twice', function () {
    AdminNotifier::send(new TrashAutoPurgeWarningNotification(1, 30));

    $notification = $this->admin->notifications()->sole();

    actingAs($this->admin)->postJson(route('admin.notifications.read', ['id' => $notification->id]))->assertOk();

    $firstRead = $notification->fresh()->read_at;

    $this->travel(2)->minutes();

    actingAs($this->admin)->postJson(route('admin.notifications.read', ['id' => $notification->id]))->assertOk();

    // Idempotent: read_at is the only record of when it was actually seen.
    expect($notification->fresh()->read_at->eq($firstRead))->toBeTrue();
});

it('marks all as read in one go', function () {
    foreach (range(1, 4) as $i) {
        AdminNotifier::send(new TrashAutoPurgeWarningNotification($i, 30));
    }

    actingAs($this->admin)
        ->postJson(route('admin.notifications.read-all'))
        ->assertOk()
        ->assertJson(['unread_count' => 0]);

    expect($this->admin->unreadNotifications()->count())->toBe(0)
        // The other admin's copies are untouched.
        ->and($this->otherAdmin->unreadNotifications()->count())->toBe(4);
});

// ── Authorisation ───────────────────────────────────────────────

it('refuses guests and students', function () {
    AdminNotifier::send(new TrashAutoPurgeWarningNotification(1, 30));

    get(route('admin.notifications.feed'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->getJson(route('admin.notifications.feed'))->assertForbidden();
    actingAs($this->student)->postJson(route('admin.notifications.read-all'))->assertForbidden();
});

it('cannot read or clear another admin notification', function () {
    AdminNotifier::send(new TrashAutoPurgeWarningNotification(1, 30));

    $theirs = $this->otherAdmin->notifications()->sole();

    /*
     * 🔴 Looked up THROUGH the signed-in user's relation. A global find()
     * would let anyone holding a uuid clear a colleague's notification.
     */
    actingAs($this->admin)
        ->postJson(route('admin.notifications.read', ['id' => $theirs->id]))
        ->assertNotFound();

    expect($theirs->fresh()->read_at)->toBeNull();
});

it('leaves each admin their own copy', function () {
    AdminNotifier::send(new TrashAutoPurgeWarningNotification(1, 30));

    actingAs($this->admin)->postJson(route('admin.notifications.read-all'))->assertOk();

    expect(actingAs($this->otherAdmin)
        ->getJson(route('admin.notifications.feed'))
        ->json('unread_count'))->toBe(1);
});

// ── The bell in the shell ───────────────────────────────────────

it('renders the bell wired to the real routes', function () {
    $html = actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->getContent();

    expect($html)->toContain('bellFeed')
        ->toContain(route('admin.notifications.feed'))
        // «عرض كل الإشعارات» — القائمة تعرض عشرة، وما بعدها لا يُوصَل إليه إلا من هنا.
        ->toContain(route('admin.notifications.index'))
        ->toContain('عرض كل الإشعارات')
        ->toContain(route('admin.notifications.read-all'))
        // ⚠️ The id placeholder is substituted in JS; a literal route with a
        // baked id would mark the same row read for ever.
        ->toContain('__ID__');
});
