<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\ContactMessage;
use App\Models\News;
use App\Models\NewsletterSubscriber;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| The admin dashboard.
|
| 🔴 Every figure is derived at read time, so the risk here is not staleness —
| it is cost. A dashboard that runs a query per card is the slowest page in
| the panel and the one people open most, which is why there is a query budget
| below alongside the correctness assertions.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Cache::flush();

    $this->admin = tap(Admin::create(['full_name' => 'سارة الأدمن'])->user()->create([
        'email' => 'admin@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
        ]))->syncRoleToShape();
});

/**
 * Creates a student dated in the past.
 *
 * 🔴 forceFill + saveQuietly, NOT create(['created_at' => …]). `created_at`
 * is not in Student::$fillable, so Eloquent drops it in SILENCE — no error,
 * no warning — and every student lands in the current window instead. That
 * is exactly the silent mass-assignment trap this project has been bitten by
 * before, and it made this test read as a broken trend calculation.
 */
function studentDated(int $branchId, string $name, ?Carbon\Carbon $at = null): Student
{
    $student = Student::create(['full_name' => $name, 'branch_id' => $branchId]);

    if ($at) {
        $student->forceFill(['created_at' => $at])->saveQuietly();
    }

    return $student;
}
// ── Access ──────────────────────────────────────────────────────

it('keeps the dashboard away from guests and students', function () {
    get(route('admin.dashboard'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->get(route('admin.dashboard'))->assertForbidden();
});

it('opens for an admin', function () {
    actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('سارة الأدمن')
        ->assertSee('إجراءات سريعة');
});

// ── The counters ────────────────────────────────────────────────

it('counts what each card claims to count', function () {
    Student::create(['full_name' => 'طالب ثانٍ', 'branch_id' => $this->branch->id]);
    Teacher::create(['full_name' => 'أ. سامر']);

    Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الرياضيات',
        'slug' => 'math', 'is_published' => true,
    ]);
    Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'مسودّة',
        'slug' => 'draft-subject', 'is_published' => false,
    ]);

    News::factory()->create();
    News::factory()->draft()->create();

    NewsletterSubscriber::factory()->create();
    NewsletterSubscriber::factory()->unsubscribed()->create();

    $kpis = actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->viewData('kpis');

    expect($kpis['students']['value'])->toBe(2)
        ->and($kpis['teachers']['value'])->toBe(1)
        /*
         * 🔴 Published only, on all three. A dashboard that counted drafts
         * would report content the public cannot see as if it were live.
         */
        ->and($kpis['subjects']['value'])->toBe(1)
        ->and($kpis['news']['value'])->toBe(1)
        ->and($kpis['subscribers']['value'])->toBe(1);
});

it('reports the trend against the previous window', function () {
    // Two in the current 30 days, one in the 30 before it → +100%.
    studentDated($this->branch->id, 'أ', now()->subDays(3));
    studentDated($this->branch->id, 'ب', now()->subDays(10));
    studentDated($this->branch->id, 'ج', now()->subDays(45));

    $students = actingAs($this->admin)->get(route('admin.dashboard'))->viewData('kpis')['students'];

    // beforeEach already created one student, dated now.
    expect($students['recent'])->toBe(3)
        ->and($students['trend'])->toBe(200.0);
});

it('reports no trend rather than a fake percentage from zero', function () {
    Student::create(['full_name' => 'جديد', 'branch_id' => $this->branch->id]);

    $students = actingAs($this->admin)->get(route('admin.dashboard'))->viewData('kpis')['students'];

    /*
     * 🔴 null, not 0 and not "+200%". Growth from an empty previous window
     * has no percentage — printing one invents a baseline that never existed.
     */
    expect($students['recent'])->toBe(2)
        ->and($students['trend'])->toBeNull();
});

it('counts a total that the trend window does not', function () {
    // ⚠️ The trend measures rows CREATED; an old row still counts toward the
    // total. The two numbers are allowed to disagree, and the card labels
    // them differently for that reason.
    studentDated($this->branch->id, 'قديم', now()->subYear());

    $students = actingAs($this->admin)->get(route('admin.dashboard'))->viewData('kpis')['students'];

    expect($students['value'])->toBe(2)
        ->and($students['recent'])->toBe(1);
});

// ── The pending queue ───────────────────────────────────────────

it('lists only queues that actually have something waiting', function () {
    ContactMessage::create([
        'name' => 'زائر', 'email' => 'v@example.test',
        'subject_type' => 'support', 'message' => 'سؤال عن الاشتراك في المنصّة.',
    ]);

    $pending = actingAs($this->admin)->get(route('admin.dashboard'))->viewData('pending');

    $labels = collect($pending)->pluck('label');

    expect($labels)->toContain('رسائل لم تُقرأ')
        /*
         * ⚠️ Empty queues are dropped, not rendered as zero. A panel of zeros
         * trains the eye to skip the whole section.
         */
        ->not->toContain('آراء بانتظار الموافقة');

    expect(collect($pending)->firstWhere('label', 'رسائل لم تُقرأ')['count'])->toBe(1);
});

it('hides the whole pending panel when nothing is waiting', function () {
    actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('بانتظار إجراء');
});

it('hides a pending row the viewer has no permission to open', function () {
    Role::findByName('admin', 'web')->revokePermissionTo('view-messages');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    ContactMessage::create([
        'name' => 'زائر', 'email' => 'v@example.test',
        'subject_type' => 'support', 'message' => 'سؤال عن الاشتراك في المنصّة.',
    ]);

    // 🔴 A row linking to a screen that answers 403 is worse than no row.
    actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('رسائل لم تُقرأ');
});

it('counts the trash across every registered type', function () {
    $news = News::factory()->create();
    $news->delete();

    $subject = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'مادة', 'slug' => 'subj', 'is_published' => true,
    ]);
    $this->branch->delete();

    $pending = actingAs($this->admin)->get(route('admin.dashboard'))->viewData('pending');

    // ⚠️ Read from the registry, so a type added later is counted without
    // touching the dashboard.
    expect(collect($pending)->firstWhere('label', 'عناصر في سلّة المحذوفات')['count'])
        ->toBeGreaterThanOrEqual(2);
});

// ── Feeds ───────────────────────────────────────────────────────

it('shows the latest news with author and status', function () {
    News::factory()->create([
        'title' => 'أحدث خبر', 'author_id' => $this->admin->id, 'published_at' => now(),
    ]);
    News::factory()->scheduled()->create(['title' => 'خبر مجدول']);

    actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('أحدث خبر')
        ->assertSee('سارة الأدمن')
        // The feed shows drafts and scheduled items too — it is an editor's
        // view of the pipeline, not the public list.
        ->assertSee('خبر مجدول')
        ->assertSee('مجدول');
});

it('shows the latest students with their branch', function () {
    actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('student@test.local')
        ->assertSee('الفرع العلمي');
});

it('caps each feed at five rows', function () {
    News::factory()->count(9)->create();

    foreach (range(1, 9) as $i) {
        Student::create(['full_name' => "طالب {$i}", 'branch_id' => $this->branch->id])
            ->user()->create([
                'email' => "s{$i}@test.local", 'password' => bcrypt('secret'),
            ]);
    }

    $response = actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

    expect($response->viewData('latestNews'))->toHaveCount(5)
        ->and($response->viewData('latestStudents'))->toHaveCount(5);
});

// ── Storage ─────────────────────────────────────────────────────

it('reports storage in raw bytes and caches the walk', function () {
    $storage = actingAs($this->admin)->get(route('admin.dashboard'))->viewData('storage');

    /*
     * ⚠️ Raw bytes from the controller, formatted in the view: the number has
     * to stay comparable and testable, and formatting is a display concern.
     */
    expect($storage)->toHaveKeys(['files', 'bytes', 'images'])
        ->and($storage['bytes'])->toBeInt();

    // 🔴 The byte total is a FILESYSTEM WALK, not a query. Without the cache
    // the most-opened page in the panel would be its slowest.
    expect(Cache::has('admin.dashboard.storage'))->toBeTrue();
});

// ── Cost ────────────────────────────────────────────────────────

it('renders on a fixed query budget rather than one per card', function () {
    // Enough content that an N+1 would be unmistakable.
    News::factory()->count(6)->create(['author_id' => $this->admin->id]);

    foreach (range(1, 6) as $i) {
        Student::create(['full_name' => "طالب {$i}", 'branch_id' => $this->branch->id])
            ->user()->create(['email' => "s{$i}@test.local", 'password' => bcrypt('secret')]);
    }

    // Warm-up: permissions and the storage walk are cached on first render.
    actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();

    actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    /*
     * 🔴 A fixed budget, not a ratio. The feeds eager-load their authors,
     * covers and branches, and each KPI is a single conditional-sum query —
     * so the cost must not grow with the amount of content.
     */
    expect($count)->toBeLessThan(35);
});
