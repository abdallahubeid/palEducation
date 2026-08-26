<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

function makeStudent(array $userAttributes = []): User
{
    $branch = Branch::firstOrCreate(
        ['slug' => 'scientific'],
        ['name' => 'علمي', 'display_order' => 1],
    );

    $student = Student::create(['full_name' => 'طالب', 'branch_id' => $branch->id]);

    return $student->user()->create(array_merge([
        'email' => 'student@example.test',
        'password' => Hash::make('Password123'),
        'email_verified_at' => now(),
    ], $userAttributes));
}

it('يعرض صفحة الدخول ومعها زر جوجل', function () {
    get(route('auth.login'))
        ->assertOk()
        ->assertSee(__('auth.google_continue'))
        ->assertSee(route('auth.google.redirect'));
});

it('يدخل ببيانات صحيحة', function () {
    makeStudent();

    post(route('auth.login.store'), [
        'email' => 'student@example.test',
        'password' => 'Password123',
    ])->assertRedirect(route('student.dashboard'));

    assertAuthenticated();
});

it('يرفض كلمة مرور خاطئة ولا يكشف أي الحقلين كان الخطأ', function () {
    makeStudent();

    post(route('auth.login.store'), [
        'email' => 'student@example.test',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email'); // على مفتاح email عمداً لا password

    assertGuest();
});

it('يمنع الحساب الموقوف من الدخول برسالة مختلفة', function () {
    makeStudent(['is_active' => false]);

    post(route('auth.login.store'), [
        'email' => 'student@example.test',
        'password' => 'Password123',
    ])->assertSessionHasErrors(['email' => __('auth.blocked')]);

    assertGuest();
});

it('يسجّل آخر دخول', function () {
    $user = makeStudent();

    expect($user->last_login_at)->toBeNull();

    post(route('auth.login.store'), [
        'email' => 'student@example.test',
        'password' => 'Password123',
    ]);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('يوجّه كل نوع حساب إلى لوحته', function () {
    $teacher = Teacher::create(['full_name' => 'معلّم']);
    $teacherUser = $teacher->user()->create([
        'email' => 'teacher@example.test',
        'password' => Hash::make('Password123'),
        'email_verified_at' => now(),
    ]);

    expect($teacherUser->isTeacher())->toBeTrue()
        ->and($teacherUser->homeRoute())->toBe(route('teacher.dashboard'));

    $admin = Admin::create(['full_name' => 'أدمن']);
    $adminUser = $admin->user()->create([
        'email' => 'admin@example.test',
        'password' => Hash::make('Password123'),
        'email_verified_at' => now(),
    ]);

    expect($adminUser->isAdmin())->toBeTrue();
});

it('يخرج ويبطل الجلسة', function () {
    $user = makeStudent();

    actingAs($user)->post(route('auth.logout'))->assertRedirect(route('home'));

    assertGuest();
});

it('يعيد الضيف إلى صفحة الدخول عند طلب مسار محمي', function () {
    get(route('verification.notice'))->assertRedirect(route('auth.login'));
});

it('يُظهر شاشة توثيق البريد لمن لم يوثّق', function () {
    $user = makeStudent(['email_verified_at' => null]);

    actingAs($user)->get(route('verification.notice'))
        ->assertOk()
        ->assertSee(__('auth.verify_title'));
});

it('يتخطّى شاشة التوثيق لمن وثّق بريده', function () {
    actingAs(makeStudent())->get(route('verification.notice'))
        ->assertRedirect(route('student.dashboard'));
});
