<?php

use App\Models\Branch;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

/*
| الشريط الجانبي للطالب.
|
| 🔴 كل رابط قائمة يقصد مساراً صحيحاً — صفر href="#". وزرّ الطيّ في **أسفل**
| الشريط، مطابقةً لقشرتَي الأدمن والمعلّم.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $branch = Branch::create(['name' => 'العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $branch->id])
        ->user()->create(['email' => 'student@test.local', 'password' => bcrypt('x'), 'email_verified_at' => now()]))
        ->syncRoleToShape();
});

it('لا يحمل الشريط أي رابط ميت href="#"', function () {
    $html = actingAs($this->student)->get(route('student.dashboard'))->assertOk()->getContent();

    // نقتطع نطاق الشريط الجانبي ونفحصه وحده.
    $sidebar = str($html)->between('data-sidebar-drawer', '</aside>')->toString();

    expect($sidebar)->not->toContain('href="#"');
});

it('كل عناصر القائمة تشير لمسارات مسجَّلة', function () {
    foreach ([
        'student.dashboard', 'student.subjects.index', 'student.library',
        'student.progress', 'student.subscription', 'student.profile',
    ] as $name) {
        expect(Route::has($name))->toBeTrue("المسار {$name} غير مسجَّل");
    }
});

it('🔴 زرّ الطيّ بعد نهاية <nav> لا في الترويسة', function () {
    $html = actingAs($this->student)->get(route('student.dashboard'))->assertOk()->getContent();

    $sidebar = str($html)->between('data-sidebar-drawer', '</aside>')->toString();

    $navEnd = strpos($sidebar, '</nav>');
    $togglePos = strpos($sidebar, 'data-sidebar-collapse-toggle');

    expect($navEnd)->not->toBeFalse()
        ->and($togglePos)->not->toBeFalse()
        ->and($togglePos)->toBeGreaterThan($navEnd);
});

it('زرّ إغلاق الدرج (الجوال) يبقى في الترويسة قبل <nav>', function () {
    $html = actingAs($this->student)->get(route('student.dashboard'))->assertOk()->getContent();
    $sidebar = str($html)->between('data-sidebar-drawer', '</aside>')->toString();

    expect(strpos($sidebar, 'data-sidebar-close'))->toBeLessThan(strpos($sidebar, '<nav'));
});

it('يميّز الصفحة الحالية بـaria-current', function () {
    $html = actingAs($this->student)->get(route('student.library'))->assertOk()->getContent();

    expect($html)->toContain('aria-current="page"');
    // الرابط النشط هو المكتبة.
    $active = str($html)->between('data-sidebar-drawer', '</aside>')
        ->betweenFirst('aria-current="page"', '</a>')->toString();
    expect($active)->toContain(__('student.nav_library'));
});
