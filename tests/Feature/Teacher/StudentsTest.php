<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| طلبة فروعي — قائمة لا لوحة أداء.
|
| 🔴 لا بيانات أداء في قاعدة البيانات. النطاق الوحيد الممكن: طلبة **فروع** مواد
| المعلّم المسندة (students.branch_id = subjects.branch_id). لا تسجيل طالب↔مادة.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->sci = Branch::create(['name' => 'العلمي', 'slug' => 'scientific', 'display_order' => 1]);
    $this->lit = Branch::create(['name' => 'الأدبي', 'slug' => 'literary', 'display_order' => 2]);

    $this->teacherRecord = Teacher::create(['full_name' => 'معلّم تجريبي', 'is_publicly_listed' => false]);
    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'teacher@paleducation.test', 'password' => bcrypt('password'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    // مادة في الفرع العلمي مسندة لهذا المعلّم.
    $this->math = Subject::create([
        'branch_id' => $this->sci->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($this->math);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'a@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

function rosterStudent(Branch $branch, string $name, array $overrides = []): Student
{
    return Student::create(array_merge([
        'full_name' => $name,
        'branch_id' => $branch->id,
    ], $overrides));
}

// ── النطاق ─────────────────────────────────────────────────────

it('يعرض طلبة فروع مواد المعلّم فقط', function () {
    rosterStudent($this->sci, 'طالب علمي أ');
    rosterStudent($this->sci, 'طالب علمي ب');
    rosterStudent($this->lit, 'طالب أدبي');   // فرع لا يُدرّس فيه المعلّم

    $names = actingAs($this->teacher)->get(route('teacher.students'))
        ->assertOk()
        ->viewData('students')
        ->pluck('full_name')
        ->all();

    expect($names)->toEqualCanonicalizing(['طالب علمي أ', 'طالب علمي ب']);
});

it('يجمع طلبة كل فروع مواد المعلّم حين تُسند له مواد في أكثر من فرع', function () {
    $arabic = Subject::create([
        'branch_id' => $this->lit->id, 'name' => 'اللغة العربية', 'slug' => 'arabic', 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($arabic);

    rosterStudent($this->sci, 'علمي');
    rosterStudent($this->lit, 'أدبي');

    $names = actingAs($this->teacher)->get(route('teacher.students'))
        ->viewData('students')->pluck('full_name')->all();

    expect($names)->toEqualCanonicalizing(['علمي', 'أدبي']);
});

it('يعرض حالة «لا مواد مسندة» حين لا مواد للمعلّم', function () {
    $this->teacherRecord->subjects()->detach();

    $response = actingAs($this->teacher)->get(route('teacher.students'))->assertOk();

    expect($response->viewData('hasSubjects'))->toBeFalse()
        ->and($response->viewData('students'))->toHaveCount(0);
    $response->assertSee(__('teacher.students_no_subjects_title'));
});

// ── التصفية والبحث ────────────────────────────────────────────

it('يصفّي بالفرع ويُسقط فرعاً لا يملكه المعلّم', function () {
    $arabic = Subject::create([
        'branch_id' => $this->lit->id, 'name' => 'عربي', 'slug' => 'ar2', 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($arabic);

    rosterStudent($this->sci, 'علمي');
    rosterStudent($this->lit, 'أدبي');

    $only = actingAs($this->teacher)
        ->get(route('teacher.students', ['branch_id' => $this->sci->id]))
        ->assertOk();
    expect($only->viewData('students')->pluck('full_name')->all())->toBe(['علمي']);

    // فرع خارج فروع المعلّم — يُسقَط، فتُعرض القائمة كاملة.
    $other = Branch::create(['name' => 'مهني', 'slug' => 'vocational', 'display_order' => 9]);
    $dropped = actingAs($this->teacher)
        ->get(route('teacher.students', ['branch_id' => $other->id]))
        ->assertOk();
    expect($dropped->viewData('filters')['branch_id'])->toBeNull()
        ->and($dropped->viewData('students'))->toHaveCount(2);
});

it('يبحث بالاسم', function () {
    rosterStudent($this->sci, 'أحمد بركات');
    rosterStudent($this->sci, 'ليان عبد الله');

    $names = actingAs($this->teacher)
        ->get(route('teacher.students', ['search' => 'ليان']))
        ->viewData('students')->pluck('full_name')->all();

    expect($names)->toBe(['ليان عبد الله']);
});

it('يرقّم النتائج', function () {
    foreach (range(1, (int) config('app.paginate_page') + 3) as $i) {
        rosterStudent($this->sci, 'طالب '.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
    }

    $page = actingAs($this->teacher)->get(route('teacher.students'))->assertOk()->viewData('students');

    expect($page->hasPages())->toBeTrue()
        ->and($page->count())->toBe((int) config('app.paginate_page'));
});

// ── الحراسة ───────────────────────────────────────────────────

it('يمنع الضيوف والطلبة والأدمن', function () {
    get(route('teacher.students'))->assertRedirect(route('auth.login'));

    $student = tap(Student::create(['full_name' => 'ط', 'branch_id' => $this->sci->id])
        ->user()->create(['email' => 'st@test.local', 'password' => bcrypt('x'), 'email_verified_at' => now()]))
        ->syncRoleToShape();

    actingAs($student)->get(route('teacher.students'))->assertForbidden();
    actingAs($this->admin)->get(route('teacher.students'))->assertForbidden();
});

it('لا يحمل الصفحة أي بيانات وهمية مكتوبة يدوياً', function () {
    // 🔴 حارس: الشاشة القديمة كانت صفوف @php ثابتة. لا أسماء طلبة مخترعة.
    rosterStudent($this->sci, 'اسم حقيقي');

    actingAs($this->teacher)->get(route('teacher.students'))
        ->assertOk()
        ->assertSee('اسم حقيقي')
        ->assertDontSee('أحمد بركات')   // اسم كان في الموك القديم
        ->assertDontSee('الحركة الدائرية');
});
