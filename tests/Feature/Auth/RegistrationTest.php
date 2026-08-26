<?php

use App\Models\Branch;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->branch = Branch::create([
        'name' => 'علمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);
});

function validRegistration(array $overrides = []): array
{
    return array_merge([
        'name' => 'محمد أحمد',
        'email' => 'student@example.test',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'terms' => '1',
        'branch_id' => Branch::first()->id,
        'school' => 'مدرسة النجاح',
        'governorate' => 'nablus',
        'country' => 'ps',
        'phone' => '0599000000',
    ], $overrides);
}

it('يعرض صفحة التسجيل مع الفروع من قاعدة البيانات', function () {
    get(route('auth.register'))
        ->assertOk()
        ->assertSee('علمي');
});

it('ينشئ سجلّ الطالب وحساب الدخول معاً', function () {
    post(route('auth.register.store'), validRegistration())
        ->assertRedirect(route('verification.notice'));

    assertDatabaseCount('students', 1);
    assertDatabaseCount('users', 1);

    $user = User::first();

    expect($user->email)->toBe('student@example.test')
        ->and($user->userable_type)->toBe(Student::class)
        ->and($user->isStudent())->toBeTrue()
        ->and($user->displayName())->toBe('محمد أحمد')
        ->and($user->userable->branch_id)->toBe($this->branch->id)
        ->and($user->userable->school_name)->toBe('مدرسة النجاح');
});

// كل حقل يجمعه النموذج يُختبر وصوله إلى العمود. الاكتفاء بإرساله في
// الطلب هو ما جعل phone يُسقَط بصمت طويلاً: $fillable لم يحتوه، فمرّ
// الاختبار وضاعت القيمة.
it('يحفظ كل حقول ملف الطالب لا الحقول المطلوبة وحدها', function () {
    post(route('auth.register.store'), validRegistration());

    expect(Student::first())
        ->phone->toBe('0599000000')
        ->governorate->toBe('nablus')
        ->country_code->toBe('ps')
        ->school_name->toBe('مدرسة النجاح');
});

it('يسند الدور المطابق لشكل الحساب داخل معاملة الإنشاء', function () {
    post(route('auth.register.store'), validRegistration());

    $user = User::first();

    // الثابت 4: مميّز الشكل (userable_type) ومميّز الصلاحية (الدور) يتفقان.
    expect($user->hasRole('student'))->toBeTrue()
        ->and($user->getRoleNames()->all())->toBe(['student'])
        ->and($user->roleName())->toBe('student');
});

it('يطلق حدث Registered فيُرسَل بريد التوثيق', function () {
    Event::fake();

    post(route('auth.register.store'), validRegistration());

    Event::assertDispatched(Registered::class);
});

it('لا يُنشئ نصف تسجيل عند فشل التحقّق', function () {
    post(route('auth.register.store'), validRegistration(['email' => 'not-an-email']))
        ->assertSessionHasErrors('email');

    assertDatabaseCount('students', 0);
    assertDatabaseCount('users', 0);
});

it('يرفض فرعاً غير موجود', function () {
    post(route('auth.register.store'), validRegistration(['branch_id' => 999]))
        ->assertSessionHasErrors('branch_id');

    assertDatabaseCount('students', 0);
});

it('يرفض التسجيل بلا فرع — الفرع فلتر جذري لا حقل اختياري', function () {
    $data = validRegistration();
    unset($data['branch_id']);

    post(route('auth.register.store'), $data)
        ->assertSessionHasErrors('branch_id');
});

it('يرفض بريداً مكرّراً', function () {
    // الحساب الأول يُنشأ مباشرة لا عبر المسار: التسجيل يُدخل المستخدم
    // فوراً، فطلب تسجيل ثانٍ في نفس الجلسة يصطدم بحارس guest ويُعاد
    // توجيهه قبل أن يصل إلى التحقّق أصلاً.
    Student::create(['full_name' => 'الأول', 'branch_id' => $this->branch->id])
        ->user()->create([
            'email' => 'student@example.test',
            'password' => bcrypt('Password123'),
        ]);

    post(route('auth.register.store'), validRegistration(['name' => 'شخص آخر']))
        ->assertSessionHasErrors('email');

    assertDatabaseCount('users', 1);
    assertDatabaseCount('students', 1);
});

it('يرفض عدم الموافقة على الشروط', function () {
    $data = validRegistration();
    unset($data['terms']);

    post(route('auth.register.store'), $data)->assertSessionHasErrors('terms');
});
