<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Notifications\TrashAutoPurgeWarningNotification;
use App\Support\AdminNotifier;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| The full notifications screen at /admin/notifications.
|
| 🔴 The bell shows ten. Everything older is reachable only from here, which
| is why this page exists at all — and why "clear read" must never touch
| anything unread.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

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

/** Sends n notifications to every admin. */
function seedNotifications(int $count): void
{
    foreach (range(1, $count) as $i) {
        AdminNotifier::send(new TrashAutoPurgeWarningNotification($i, 30));
    }
}

// ── The page ────────────────────────────────────────────────────

it('opens for an admin with its heading and stats', function () {
    seedNotifications(2);

    actingAs($this->admin)
        ->get(route('admin.notifications.index'))
        ->assertOk()
        ->assertSee('الإشعارات والتنبيهات')
        ->assertSee('عناصر قديمة في سلّة المحذوفات')
        ->assertViewHas('counts', fn (array $counts): bool => $counts['all'] === 2
            && $counts['unread'] === 2
            && $counts['read'] === 0);
});

it('paginates fifteen to a page', function () {
    seedNotifications(18);

    $response = actingAs($this->admin)->get(route('admin.notifications.index'))->assertOk();

    expect($response->viewData('notifications'))->toHaveCount(15)
        ->and($response->viewData('notifications')->total())->toBe(18);

    // ⚠️ The bell only ever shows ten — page two is the whole reason this
    // screen exists.
    expect(actingAs($this->admin)
        ->get(route('admin.notifications.index', ['page' => 2]))
        ->viewData('notifications'))->toHaveCount(3);
});

it('shows an empty state rather than a bare list', function () {
    actingAs($this->admin)
        ->get(route('admin.notifications.index'))
        ->assertOk()
        ->assertSee('لا إشعارات بعد');
});

it('shows only this admin notifications', function () {
    seedNotifications(1);

    // Each admin holds their own copy; the page must never show a colleague's.
    expect(actingAs($this->admin)->get(route('admin.notifications.index'))->viewData('counts')['all'])
        ->toBe(1)
        ->and(actingAs($this->otherAdmin)->get(route('admin.notifications.index'))->viewData('counts')['all'])
        ->toBe(1);
});

// ── Filters ─────────────────────────────────────────────────────

it('filters by read state and keeps the counts unfiltered', function () {
    seedNotifications(3);

    // Mark one read.
    $this->admin->notifications()->latest()->first()->markAsRead();

    $ids = fn (array $query) => actingAs($this->admin)
        ->get(route('admin.notifications.index', $query))
        ->assertOk()
        ->viewData('notifications')
        ->pluck('id')
        ->all();

    expect($ids([]))->toHaveCount(3)
        ->and($ids(['filter' => 'unread']))->toHaveCount(2)
        ->and($ids(['filter' => 'read']))->toHaveCount(1)
        /*
         * ⚠️ An unrecognised filter shows everything, never nothing — a typo
         * in the query string must not read as "no notifications".
         */
        ->and($ids(['filter' => 'nonsense']))->toHaveCount(3);

    // The tab badges describe the whole history while showing one slice.
    $counts = actingAs($this->admin)
        ->get(route('admin.notifications.index', ['filter' => 'unread']))
        ->viewData('counts');

    expect($counts)->toMatchArray(['all' => 3, 'unread' => 2, 'read' => 1]);
});

it('keeps the filter across pages', function () {
    seedNotifications(20);

    $response = actingAs($this->admin)
        ->get(route('admin.notifications.index', ['filter' => 'unread', 'page' => 2]))
        ->assertOk();

    // 🔴 withQueryString: without it page 2 silently reverts to "all".
    expect($response->viewData('activeFilter'))->toBe('unread')
        ->and($response->viewData('notifications')->currentPage())->toBe(2);
});

// ── Row actions ─────────────────────────────────────────────────

it('toggles a notification both ways', function () {
    seedNotifications(1);

    $notification = $this->admin->notifications()->sole();

    actingAs($this->admin)
        ->postJson(route('admin.notifications.toggle', ['id' => $notification->id]))
        ->assertOk()
        ->assertJson(['unread_count' => 0, 'read_count' => 1]);

    expect($notification->fresh()->read_at)->not->toBeNull();

    /*
     * 🔴 Back to unread. An admin who opens something, realises they cannot
     * deal with it now, and wants it back in the queue has no other way.
     */
    actingAs($this->admin)
        ->postJson(route('admin.notifications.toggle', ['id' => $notification->id]))
        ->assertOk()
        ->assertJson(['unread_count' => 1, 'read_count' => 0]);

    expect($notification->fresh()->read_at)->toBeNull();
});

it('deletes a single notification', function () {
    seedNotifications(2);

    $notification = $this->admin->notifications()->first();

    actingAs($this->admin)
        ->deleteJson(route('admin.notifications.destroy', ['id' => $notification->id]))
        ->assertOk()
        ->assertJson(['total_count' => 1]);

    expect($this->admin->notifications()->count())->toBe(1)
        // The other admin's copy is untouched.
        ->and($this->otherAdmin->notifications()->count())->toBe(2);
});

it('returns the updated counts with every action', function () {
    seedNotifications(3);

    $response = actingAs($this->admin)
        ->postJson(route('admin.notifications.toggle', ['id' => $this->admin->notifications()->first()->id]))
        ->assertOk();

    // ⚠️ So the page can repaint its stat cards without a second round trip.
    expect($response->json())->toHaveKeys(['unread_count', 'read_count', 'total_count', 'notification'])
        ->and($response->json('notification.read'))->toBeTrue();
});

// ── Batch actions ───────────────────────────────────────────────

it('marks everything read', function () {
    seedNotifications(4);

    actingAs($this->admin)
        ->postJson(route('admin.notifications.read-all'))
        ->assertOk()
        ->assertJson(['unread_count' => 0, 'read_count' => 4]);

    expect($this->admin->unreadNotifications()->count())->toBe(0)
        ->and($this->otherAdmin->unreadNotifications()->count())->toBe(4);
});

it('clears read notifications and keeps unread ones', function () {
    seedNotifications(5);

    // Mark three read.
    $this->admin->notifications()->limit(3)->get()->each->markAsRead();

    actingAs($this->admin)
        ->deleteJson(route('admin.notifications.clear-read'))
        ->assertOk()
        ->assertJson(['deleted' => 3, 'unread_count' => 2, 'total_count' => 2]);

    /*
     * 🔴 The unread ones survive. "Clear all" would silently destroy
     * notifications the admin has never seen — the one thing this screen
     * exists to prevent.
     */
    expect($this->admin->notifications()->count())->toBe(2)
        ->and($this->admin->unreadNotifications()->count())->toBe(2)
        ->and($this->otherAdmin->notifications()->count())->toBe(5);
});

it('clears nothing when nothing has been read', function () {
    seedNotifications(3);

    actingAs($this->admin)
        ->deleteJson(route('admin.notifications.clear-read'))
        ->assertOk()
        ->assertJson(['deleted' => 0]);

    expect($this->admin->notifications()->count())->toBe(3);
});

// ── Authorisation ───────────────────────────────────────────────

it('keeps the page away from guests and students', function () {
    seedNotifications(1);

    get(route('admin.notifications.index'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.notifications.index'))->assertForbidden();
    actingAs($this->student)->deleteJson(route('admin.notifications.clear-read'))->assertForbidden();

    expect($this->admin->notifications()->count())->toBe(1);
});

it('cannot toggle or delete another admin notification', function () {
    seedNotifications(1);

    $theirs = $this->otherAdmin->notifications()->sole();

    /*
     * 🔴 Looked up THROUGH the signed-in user's relation, so a guessed uuid
     * resolves to nothing rather than to a colleague's row.
     */
    actingAs($this->admin)
        ->postJson(route('admin.notifications.toggle', ['id' => $theirs->id]))
        ->assertNotFound();

    actingAs($this->admin)
        ->deleteJson(route('admin.notifications.destroy', ['id' => $theirs->id]))
        ->assertNotFound();

    expect($theirs->fresh()->read_at)->toBeNull()
        ->and($this->otherAdmin->notifications()->count())->toBe(1);
});

it('wires the page to the real action routes', function () {
    seedNotifications(1);

    $html = actingAs($this->admin)->get(route('admin.notifications.index'))->assertOk()->getContent();

    expect($html)->toContain('notificationsPage')
        ->toContain(route('admin.notifications.read-all'))
        ->toContain(route('admin.notifications.clear-read'))
        // ⚠️ The id placeholder is substituted in JS; a baked id would act on
        // the same row for ever.
        ->toContain('__ID__');
});
