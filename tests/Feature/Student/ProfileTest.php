<?php

use App\Models\Branch;
use App\Models\Media;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| ملف الطالب — تحديث البيانات، والأفاتار عبر علاقة media متعدّدة الأشكال.
|
| 🔴 الفرع للقراءة فقط: لا حقل branch_id في الطلب، ومحاولة تمريره تُتجاهَل.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');

    $this->sci = Branch::create(['name' => 'العلمي', 'slug' => 'scientific', 'display_order' => 1, 'is_active' => true]);
    $this->lit = Branch::create(['name' => 'الأدبي', 'slug' => 'literary', 'display_order' => 2, 'is_active' => true]);

    $this->studentRecord = Student::create([
        'full_name' => 'طالب', 'branch_id' => $this->sci->id,
        'school_name' => 'مدرسة أ', 'governorate' => 'نابلس', 'phone' => '0599',
    ]);
    $this->student = tap($this->studentRecord->user()->create([
        'email' => 'student@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();
});

// ── تحديث البيانات ───────────────────────────────────────────

it('يحدّث بيانات الطالب في الجدولين', function () {
    actingAs($this->student)->put(route('student.profile.update'), [
        'full_name' => 'اسم جديد',
        'email' => 'new@test.local',
        'phone' => '0598111',
        'guardian_phone' => '0597222',
        'school_name' => 'مدرسة ب',
        'governorate' => 'رام الله',
        'locale' => 'en',
    ])->assertRedirect(route('student.profile'));

    $this->studentRecord->refresh();
    $this->student->refresh();

    expect($this->studentRecord->full_name)->toBe('اسم جديد')
        ->and($this->studentRecord->phone)->toBe('0598111')
        ->and($this->studentRecord->guardian_phone)->toBe('0597222')
        ->and($this->studentRecord->school_name)->toBe('مدرسة ب')
        ->and($this->studentRecord->governorate)->toBe('رام الله')
        ->and($this->student->email)->toBe('new@test.local')
        ->and($this->student->locale)->toBe('en');
});

it('🔴 يتجاهل محاولة تغيير الفرع', function () {
    actingAs($this->student)->put(route('student.profile.update'), [
        'full_name' => 'طالب', 'email' => 'student@test.local', 'locale' => 'ar',
        'branch_id' => $this->lit->id,   // تُتجاهَل — ليست في التحقّق
    ])->assertRedirect();

    expect($this->studentRecord->fresh()->branch_id)->toBe($this->sci->id);
});

it('تعرض الصفحة الفرع للقراءة فقط', function () {
    actingAs($this->student)->get(route('student.profile'))
        ->assertOk()
        ->assertSee('العلمي')
        ->assertSee('لتغيير الفرع تواصل مع إدارة المنصّة');
});

it('يرفض بريداً مستعملاً من حساب آخر', function () {
    Student::create(['full_name' => 'ثانٍ', 'branch_id' => $this->sci->id])
        ->user()->create(['email' => 'taken@test.local', 'password' => bcrypt('x')]);

    actingAs($this->student)->put(route('student.profile.update'), [
        'full_name' => 'طالب', 'email' => 'taken@test.local', 'locale' => 'ar',
    ])->assertSessionHasErrors('email');
});

// ── الأفاتار عبر media morph ─────────────────────────────────

it('يرفع أفاتاراً ويربطه بعلاقة media متعدّدة الأشكال', function () {
    actingAs($this->student)->post(route('student.profile.avatar'), [
        'avatar' => UploadedFile::fake()->create('me.jpg', 200, 'image/jpeg'),
    ])->assertRedirect(route('student.profile'));

    $media = Media::sole();
    expect($media->mediable_type)->toBe(User::class)
        ->and($media->mediable_id)->toBe($this->student->id)
        ->and($media->type->value)->toBe('image')
        ->and($media->path)->toStartWith('storage/avatars/');

    Storage::disk('public')->assertExists(str_replace('storage/', '', $media->path));
    expect($this->student->fresh()->avatar_url)->not->toBeNull();
});

it('يستبدل الأفاتار السابق ولا يكدّس صفوف media', function () {
    actingAs($this->student)->post(route('student.profile.avatar'), [
        'avatar' => UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'),
    ]);
    $firstPath = str_replace('storage/', '', Media::sole()->path);

    actingAs($this->student)->post(route('student.profile.avatar'), [
        'avatar' => UploadedFile::fake()->create('b.jpg', 100, 'image/jpeg'),
    ]);

    expect(Media::count())->toBe(1);
    Storage::disk('public')->assertMissing($firstPath);
    Storage::disk('public')->assertExists(str_replace('storage/', '', Media::sole()->path));
});

it('يحذف الأفاتار ويمحو صفّ media وملفه', function () {
    actingAs($this->student)->post(route('student.profile.avatar'), [
        'avatar' => UploadedFile::fake()->create('a.jpg', 100, 'image/jpeg'),
    ]);
    $path = str_replace('storage/', '', Media::sole()->path);

    actingAs($this->student)->delete(route('student.profile.avatar.destroy'))->assertRedirect();

    expect(Media::count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});

it('يرفض صيغة صورة غير مسموحة', function () {
    actingAs($this->student)->post(route('student.profile.avatar'), [
        'avatar' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('avatar');

    expect(Media::count())->toBe(0);
});

// ── كلمة المرور ─────────────────────────────────────────────

it('يغيّر كلمة المرور بالحالية', function () {
    actingAs($this->student)->put(route('student.profile.password'), [
        'current_password' => 'secret',
        'password' => 'new-Str0ng-pass!',
        'password_confirmation' => 'new-Str0ng-pass!',
    ])->assertRedirect();

    expect(Hash::check('new-Str0ng-pass!', $this->student->fresh()->password))->toBeTrue();
});

it('يرفض تغيير كلمة المرور بحالية خاطئة', function () {
    actingAs($this->student)->put(route('student.profile.password'), [
        'current_password' => 'wrong',
        'password' => 'new-Str0ng-pass!',
        'password_confirmation' => 'new-Str0ng-pass!',
    ])->assertSessionHasErrors('current_password');
});

// ── الحراسة ───────────────────────────────────────────────────

it('يمنع الضيوف من ملف الطالب', function () {
    get(route('student.profile'))->assertRedirect(route('auth.login'));
});
