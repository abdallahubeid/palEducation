<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| Header auth state, and what a database rebuild does to live sessions.
|
| 🔴 The two are one feature. Reseeding reassigns primary keys, so a session
| that survives a rebuild does not merely keep someone logged in — it can
| authenticate them as whichever account now owns that id. The header is
| where that would first become visible, and the listener is what stops it
| happening at all.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $branch = Branch::create(['name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();

    $this->teacher = tap(Teacher::create(['full_name' => 'أ. معلّم'])->user()->create([
        'email' => 'teacher@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

// ── Guest ───────────────────────────────────────────────────────

it('shows a guest the login and register buttons', function () {
    get(route('home'))
        ->assertOk()
        ->assertSee(__('nav.login'))
        ->assertSee(__('nav.register'))
        ->assertSee(route('auth.login'), escape: false)
        ->assertSee(route('auth.register'), escape: false)
        ->assertDontSee(__('nav.dashboard'));
});

// ── Signed in ───────────────────────────────────────────────────

it('replaces both buttons with one dashboard link when signed in', function () {
    /*
     * 🔴 Not layered — replaced. An authenticated visitor being invited to
     * "log in" beside their own dashboard link reads as a broken session.
     */
    actingAs($this->student)->get(route('home'))
        ->assertOk()
        ->assertSee(__('nav.dashboard'))
        ->assertDontSee(__('nav.login'))
        ->assertDontSee(__('nav.register'))
        ->assertDontSee(route('auth.register'), escape: false);
});

it('points each role at its own dashboard', function () {
    // The link comes from User::homeRoute(), the same method the three login
    // paths redirect through — so the two can never disagree.
    actingAs($this->student)->get(route('home'))
        ->assertOk()->assertSee(route('student.dashboard'), escape: false);

    actingAs($this->teacher)->get(route('home'))
        ->assertOk()->assertSee(route('teacher.dashboard'), escape: false);

    actingAs($this->admin)->get(route('home'))
        ->assertOk()->assertSee(route('admin.dashboard'), escape: false);
});

it('guards the mobile drawer as well as the desktop bar', function () {
    // ⚠️ The drawer is a second copy of these controls. Missing the guard
    // there would hide the buttons on desktop and leave them on mobile.
    /*
     * ⚠️ Scoped to the <header> element, not the whole page. The
     * testimonial modal offers a guest its own login and register buttons,
     * so counting page-wide reported four of each and said nothing about
     * whether the header itself was guarded.
     */
    $header = function (?object $as = null): string {
        $html = ($as ? actingAs($as) : test())->get(route('home'))->assertOk()->getContent();
        $start = strpos($html, '<header');
        $end = strpos($html, '</header>', $start);

        return substr($html, $start, $end - $start);
    };

    $guest = $header();
    $authed = $header($this->admin);

    // Desktop bar + drawer = two of each for a guest, none once signed in.
    expect(substr_count($guest, __('nav.login')))->toBe(2)
        ->and(substr_count($guest, __('nav.register')))->toBe(2)
        ->and(substr_count($guest, __('nav.dashboard')))->toBe(0)
        ->and(substr_count($authed, __('nav.dashboard')))->toBe(2)
        ->and(substr_count($authed, __('nav.login')))->toBe(0)
        ->and(substr_count($authed, __('nav.register')))->toBe(0);
});

// ── Session invalidation on a database rebuild ──────────────────

it('drops every session when the database is refreshed', function () {
    /*
     * 🔴 The real hazard is an identity SWAP, not an inconvenience.
     * Reseeding reassigns primary keys, so a surviving cookie authenticates
     * its holder as whichever account now owns that id — an admin can come
     * back as a student, or a student as an admin.
     */
    /*
     * ⚠️ phpunit.xml runs with SESSION_DRIVER=array, where sessions live
     * for one request and there is nothing to flush. Production uses the
     * database driver, so the test points at that — otherwise this would
     * assert against a code path nobody runs.
     */
    config(['session.driver' => 'database']);

    DB::table(config('session.table', 'sessions'))->insert([
        'id' => 'stale-session-id',
        'user_id' => $this->admin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest',
        'payload' => base64_encode('payload'),
        'last_activity' => now()->timestamp,
    ]);

    expect(DB::table('sessions')->count())->toBe(1);

    // The event `migrate:fresh` fires once the schema is rebuilt.
    event(new DatabaseRefreshed);

    expect(DB::table('sessions')->count())->toBe(0);
});

it('flushes the cache alongside the sessions', function () {
    // Rebuilt data behind a stale cache is its own class of ghost.
    Cache::put('leftover', 'from before the rebuild', 600);

    expect(Cache::get('leftover'))->toBe('from before the rebuild');

    event(new DatabaseRefreshed);

    expect(Cache::get('leftover'))->toBeNull();
});

it('does not fire on a plain seed or migrate', function () {
    /*
     * ⚠️ The listener is bound to DatabaseRefreshed specifically, which only
     * `migrate:fresh` dispatches. Putting this in DatabaseSeeder instead
     * would sign every user out on a routine production reseed.
     */
    config(['session.driver' => 'database']);

    DB::table(config('session.table', 'sessions'))->insert([
        'id' => 'survives-a-seed',
        'user_id' => $this->admin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest',
        'payload' => base64_encode('payload'),
        'last_activity' => now()->timestamp,
    ]);

    $this->seed(RoleSeeder::class);

    expect(DB::table('sessions')->where('id', 'survives-a-seed')->exists())->toBeTrue();
});
