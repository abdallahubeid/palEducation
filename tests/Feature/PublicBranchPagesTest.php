<?php

use App\Models\AcademicTerm;
use App\Models\Branch;
use App\Models\Lecture;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;

/*
| The public branch pages.
|
| 🔴 "Branch" here is an ACADEMIC STREAM — علمي · أدبي · تجاري · صناعي — the
| platform's root content filter, not a physical office. These pages carry
| subjects, teachers and lecture counts; there is no address or opening hours
| anywhere in the schema because a stream does not have one.
|
| Everything public is filtered twice: the branch must be active AND the
| subjects shown must be published.
*/

function makeBranch(array $overrides = []): Branch
{
    return Branch::create(array_merge([
        'name' => 'الفرع العلمي',
        'slug' => 'scientific',
        'description' => 'رياضيات وفيزياء وكيمياء وأحياء.',
        'icon' => 'beaker',
        'accent_token' => 'accent',
        'display_order' => 1,
        'is_active' => true,
    ], $overrides));
}

function makeSubject(Branch $branch, array $overrides = []): Subject
{
    return Subject::create(array_merge([
        'branch_id' => $branch->id,
        'name' => 'الرياضيات',
        'slug' => 'math-'.uniqid(),
        'icon' => 'beaker',
        'accent_token' => 'accent',
        'display_order' => 1,
        'is_published' => true,
    ], $overrides));
}

/**
 * ⚠️ Built by hand rather than by a factory: there is no LectureFactory in
 * this project, and `academic_term_id` is mandatory — no lecture exists
 * without a term, by design.
 */
function makeLectures(Subject $subject, int $count, ?Teacher $teacher = null): void
{
    $term = AcademicTerm::firstOrCreate(
        ['name' => 'الفصل الأول'],
        [
            'academic_year' => '2026/2027',
            // NOT NULL columns — a term without dates is not a term.
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addMonths(4),
            'status' => 'active',
        ]
    );

    // ⚠️ teacher_id is NOT NULL too: every lecture has an author.
    $teacher ??= Teacher::create(['full_name' => 'أ. مؤلّف', 'is_publicly_listed' => false]);

    foreach (range(1, $count) as $i) {
        Lecture::create([
            'subject_id' => $subject->id,
            'academic_term_id' => $term->id,
            'teacher_id' => $teacher->id,
            'title' => "محاضرة {$i}",
            'slug' => 'lecture-'.uniqid(),
            'display_order' => $i,
        ]);
    }
}
// ── Listing ─────────────────────────────────────────────────────

it('lists active branches from the database', function () {
    $branch = makeBranch(['name' => 'الفرع الأدبي', 'slug' => 'literary']);

    get(route('branches.index'))
        ->assertOk()
        ->assertSee('الفرع الأدبي')
        ->assertSee('رياضيات وفيزياء وكيمياء وأحياء.')
        // The card links to the detail page by slug.
        ->assertSee(route('branches.show', $branch), false);
});

it('hides an inactive branch from the listing', function () {
    makeBranch(['name' => 'فرع ظاهر', 'slug' => 'visible']);
    makeBranch(['name' => 'فرع مخفي', 'slug' => 'hidden', 'is_active' => false]);

    $response = get(route('branches.index'))->assertOk();

    expect($response->viewData('branches')->pluck('slug')->all())->toBe(['visible']);

    $response->assertSee('فرع ظاهر')->assertDontSee('فرع مخفي');
});

it('orders branches by display order, not by id', function () {
    makeBranch(['name' => 'ثالث', 'slug' => 'third', 'display_order' => 3]);
    makeBranch(['name' => 'أول', 'slug' => 'first', 'display_order' => 1]);
    makeBranch(['name' => 'ثانٍ', 'slug' => 'second', 'display_order' => 2]);

    // 🔴 The order is editable in the admin panel; falling back to insertion
    // order would silently ignore it.
    expect(get(route('branches.index'))->viewData('branches')->pluck('slug')->all())
        ->toBe(['first', 'second', 'third']);
});

it('counts only published subjects on a card, and counts a teacher once', function () {
    $branch = makeBranch();

    $math = makeSubject($branch, ['name' => 'الرياضيات']);
    $physics = makeSubject($branch, ['name' => 'الفيزياء']);
    makeSubject($branch, ['name' => 'مسودّة', 'is_published' => false]);

    /*
     * 🔴 One teacher on TWO subjects in the same branch is one teacher. A
     * plain withCount over the pivot counts the rows, not the people — the
     * classic many-to-many miscount.
     */
    $teacher = Teacher::create(['full_name' => 'أ. سامر خليل', 'is_publicly_listed' => true]);
    $math->teachers()->attach($teacher);
    $physics->teachers()->attach($teacher);

    $card = get(route('branches.index'))->viewData('branches')->sole();

    expect($card->subjects_count)->toBe(2)
        ->and($card->teachers_count)->toBe(1);
});

it('builds the search index from subject names, not a hand-written list', function () {
    $branch = makeBranch(['name' => 'الفرع العلمي', 'slug' => 'scientific']);
    makeSubject($branch, ['name' => 'الفيزياء']);
    makeSubject($branch, ['name' => 'مسودّة سرية', 'is_published' => false]);

    $html = get(route('branches.index'))->assertOk()->getContent();

    // A visitor searches for "physics", not for the branch's name.
    expect($html)->toContain('data-search="الفرع العلمي رياضيات وفيزياء وكيمياء وأحياء. الفيزياء"')
        // ⚠️ And an unpublished subject must not leak into the page even as
        // an invisible search keyword.
        ->not->toContain('مسودّة سرية');
});

it('shows an empty state rather than a blank grid when nothing is published', function () {
    makeBranch(['is_active' => false]);

    get(route('branches.index'))
        ->assertOk()
        ->assertSee(__('public.branches_empty_title'))
        // No search box either: filtering nothing is a control that cannot work.
        ->assertDontSee('data-branch-search', false);
});

// ── Detail ──────────────────────────────────────────────────────

it('renders a branch with its own subjects, teachers and counts', function () {
    $branch = makeBranch(['name' => 'الفرع العلمي', 'slug' => 'scientific']);
    $subject = makeSubject($branch, ['name' => 'الرياضيات']);

    $teacher = Teacher::create(['full_name' => 'أ. سامر خليل', 'is_publicly_listed' => true]);
    $subject->teachers()->attach($teacher);

    makeLectures($subject, 3, $teacher);

    $response = get(route('branches.show', $branch))
        ->assertOk()
        ->assertSee('الفرع العلمي')
        ->assertSee('الرياضيات')
        ->assertSee('أ. سامر خليل');

    expect($response->viewData('stats'))->toMatchArray([
        'subjects' => 1,
        'teachers' => 1,
        'lectures' => 3,
        'files' => 0,
    ]);
});

it('shows each branch its OWN content', function () {
    /*
     * 🔴 The bug this page had: it carried a hard-coded catalogue for the
     * scientific stream and rendered it for EVERY slug, so /branches/literary
     * showed the science subjects under the literary branch's name.
     */
    $science = makeBranch(['name' => 'الفرع العلمي', 'slug' => 'scientific']);
    makeSubject($science, ['name' => 'الفيزياء']);

    $literary = makeBranch(['name' => 'الفرع الأدبي', 'slug' => 'literary', 'display_order' => 2]);
    makeSubject($literary, ['name' => 'التاريخ']);

    get(route('branches.show', $literary))
        ->assertOk()
        ->assertSee('التاريخ')
        ->assertDontSee('الفيزياء');
});

it('returns 404 for an inactive branch and for one that does not exist', function () {
    $inactive = makeBranch(['slug' => 'hidden', 'is_active' => false]);

    // 🔴 404, not 403: answering "forbidden" confirms the slug is real.
    get(route('branches.show', 'hidden'))->assertNotFound();
    get(route('branches.show', 'no-such-branch'))->assertNotFound();

    expect($inactive->exists)->toBeTrue();
});

it('returns 404 for a soft deleted branch', function () {
    $branch = makeBranch(['slug' => 'gone']);
    $branch->delete();

    // Soft deletes are excluded by route binding, but this is the assertion
    // that keeps it true if the binding is ever widened.
    get(route('branches.show', 'gone'))->assertNotFound();
});

it('hides unpublished subjects and unlisted teachers from the detail page', function () {
    $branch = makeBranch();

    $published = makeSubject($branch, ['name' => 'مادة منشورة']);
    makeSubject($branch, ['name' => 'مادة مسودّة', 'is_published' => false]);

    $listed = Teacher::create(['full_name' => 'أستاذ معروض', 'is_publicly_listed' => true]);
    $unlisted = Teacher::create(['full_name' => 'أستاذ غير معروض', 'is_publicly_listed' => false]);
    $published->teachers()->attach([$listed->id, $unlisted->id]);

    $response = get(route('branches.show', $branch))
        ->assertOk()
        ->assertSee('مادة منشورة')
        ->assertDontSee('مادة مسودّة')
        ->assertSee('أستاذ معروض')
        ->assertDontSee('أستاذ غير معروض');

    expect($response->viewData('stats'))->toMatchArray(['subjects' => 1, 'teachers' => 1]);
});

it('lists a teacher once even when they carry two subjects in the branch', function () {
    $branch = makeBranch();
    $math = makeSubject($branch, ['name' => 'الرياضيات']);
    $physics = makeSubject($branch, ['name' => 'الفيزياء', 'display_order' => 2]);

    $teacher = Teacher::create(['full_name' => 'أ. سامر خليل', 'is_publicly_listed' => true]);
    $math->teachers()->attach($teacher);
    $physics->teachers()->attach($teacher);

    $response = get(route('branches.show', $branch))->assertOk();

    expect($response->viewData('teachers'))->toHaveCount(1)
        // ⚠️ And the subject shown is one from THIS branch — captured while
        // flattening, so it can never name a subject from another branch.
        ->and($response->viewData('teachers')->first()->branch_subject_name)->toBe('الرياضيات');
});

it('renders a branch with no content at all without breaking', function () {
    $branch = makeBranch(['description' => null]);

    // A newly created branch has no subjects and no teachers. Both sections
    // must say so rather than rendering an empty grid.
    get(route('branches.show', $branch))
        ->assertOk()
        ->assertSee(__('public.branch_subjects_empty_title'))
        ->assertSee(__('public.branch_teachers_empty_title'));
});

it('does not query once per teacher for portraits or lecture counts', function () {
    $branch = makeBranch();

    foreach (range(1, 6) as $i) {
        $subject = makeSubject($branch, ['name' => "مادة {$i}", 'display_order' => $i]);
        $teacher = Teacher::create(['full_name' => "أستاذ {$i}", 'is_publicly_listed' => true]);
        $subject->teachers()->attach($teacher);
    }

    // Warm up: the settings the CTA reads are queried on first render.
    get(route('branches.show', $branch))->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();

    get(route('branches.show', $branch))->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    /*
     * 🔴 A fixed budget, not a ratio. avatarUrl() and the lecture count are
     * both per-teacher lookups unless eager loaded, so an N+1 here grows with
     * every teacher added — six teachers would add twelve queries.
     */
    expect($count)->toBeLessThan(15);
});

// ── The shared CTA ──────────────────────────────────────────────

it('renders the shared CTA on both branch pages, from admin settings', function () {
    Setting::query()->updateOrCreate(['key' => 'final_cta.title'], ['value' => 'عنوان الدعوة المخصّص']);
    Setting::query()->updateOrCreate(['key' => 'final_cta.btn_text'], ['value' => 'زر الدعوة المخصّص']);

    $branch = makeBranch();

    foreach ([route('branches.index'), route('branches.show', $branch)] as $url) {
        get($url)
            ->assertOk()
            ->assertSee('عنوان الدعوة المخصّص')
            ->assertSee('زر الدعوة المخصّص');
    }
});

it('feeds the home page and the branch pages from the same settings', function () {
    /*
     * 🔴 The point of extracting the component. The same block used to be
     * written out three times, so editing the CTA in the admin panel changed
     * the home page and left the branch pages saying something else.
     */
    Setting::query()->updateOrCreate(['key' => 'final_cta.title'], ['value' => 'نصّ موحّد للجميع']);

    $branch = makeBranch();

    foreach ([route('home'), route('branches.index'), route('branches.show', $branch)] as $url) {
        get($url)->assertOk()->assertSee('نصّ موحّد للجميع');
    }
});

it('falls back to the default CTA copy when nothing is configured', function () {
    // No settings rows at all: the component must still render real copy
    // rather than empty tags.
    expect(Setting::query()->where('key', 'like', 'final_cta.%')->count())->toBe(0);

    $branch = makeBranch();

    get(route('branches.show', $branch))
        ->assertOk()
        ->assertSee(Setting::FINAL_CTA_DEFAULTS['final_cta.title']);
});

it('neutralises a CTA link that is not a real destination', function () {
    // ⚠️ Same guard the footer uses: a value that is neither a path, an
    // anchor, nor an absolute URL becomes '#'.
    Setting::query()->updateOrCreate(['key' => 'final_cta.btn_link'], ['value' => 'javascript:alert(1)']);

    $html = get(route('branches.index'))->assertOk()->getContent();

    expect($html)->not->toContain('javascript:alert(1)');
});

it('keeps the page-specific secondary link on each branch page', function () {
    $branch = makeBranch();

    // The secondary link has no global setting — it is page context, so each
    // page passes its own and the component renders nothing without one.
    get(route('branches.index'))->assertOk()->assertSee(__('public.branches_cta_secondary'));
    get(route('branches.show', $branch))->assertOk()->assertSee(__('public.branch_all_branches'));
});
