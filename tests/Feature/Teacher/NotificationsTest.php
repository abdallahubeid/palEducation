<?php

use App\Enums\LibraryCategory;
use App\Models\Admin;
use App\Models\Branch;
use App\Models\LibraryFile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\SubjectAssignedToTeacherNotification;
use App\Support\TeacherNotifier;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
| نظام إشعارات المعلّم — التسليم، حالة القراءة، والأحداث الجاهزة الموصولة.
|
| 🔴 كل مسار مقيَّد بـ$request->user(). uuid مُخمَّن لزميل يُحَلّ إلى لا شيء.
| 🔴 استثناء الفاعل: من يسبّب الحدث لا يُخطَر به.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('private');

    $this->branch = Branch::create(['name' => 'العلمي', 'slug' => 'scientific', 'display_order' => 1]);

    $this->teacherRecord = Teacher::create(['full_name' => 'معلّم تجريبي', 'is_publicly_listed' => false]);
    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'teacher@paleducation.test', 'password' => bcrypt('password'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->colleagueRecord = Teacher::create(['full_name' => 'زميل', 'is_publicly_listed' => false]);
    $this->colleague = tap($this->colleagueRecord->user()->create([
        'email' => 'colleague@paleducation.test', 'password' => bcrypt('password'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->math = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($this->math);

    $this->admin = tap(Admin::create(['full_name' => 'أدمن'])->user()->create([
        'email' => 'a@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->student = tap(Student::create(['full_name' => 'طالب', 'branch_id' => $this->branch->id])
        ->user()->create(['email' => 's@test.local', 'password' => bcrypt('secret'), 'email_verified_at' => now()]))
        ->syncRoleToShape();
});

function teacherPush(User $user, string $title = 'إشعار'): void
{
    TeacherNotifier::notify(
        $user->userable,
        new SubjectAssignedToTeacherNotification($title),
    );
}

// ── جرس الاستطلاع (feed) ───────────────────────────────────────

it('يسلّم الجرس عدّاد غير المقروء وآخر الإشعارات', function () {
    teacherPush($this->teacher, 'الرياضيات');
    teacherPush($this->teacher, 'الفيزياء');

    $json = actingAs($this->teacher)->getJson(route('teacher.notifications.feed'))->assertOk();

    expect($json->json('unread_count'))->toBe(2)
        ->and($json->json('notifications'))->toHaveCount(2)
        ->and($json->json('notifications.0.title'))->toBe('مادة جديدة أُسندت إليك')
        ->and($json->json('notifications.0.read'))->toBeFalse()
        ->and($json->json('notifications.0.time'))->toBeString();
});

it('لا يخلط إشعارات معلّمَين — كل واحد يرى نسخته', function () {
    teacherPush($this->teacher);
    teacherPush($this->colleague);
    teacherPush($this->colleague);

    expect(actingAs($this->teacher)->getJson(route('teacher.notifications.feed'))->json('unread_count'))->toBe(1)
        ->and(actingAs($this->colleague)->getJson(route('teacher.notifications.feed'))->json('unread_count'))->toBe(2);
});

// ── حالة القراءة ──────────────────────────────────────────────

it('يعلّم إشعاراً واحداً مقروءاً ويرجع العدّادات', function () {
    teacherPush($this->teacher);
    $id = $this->teacher->notifications()->first()->id;

    $json = actingAs($this->teacher)
        ->postJson(route('teacher.notifications.read', ['id' => $id]))
        ->assertOk();

    expect($json->json('unread_count'))->toBe(0)
        ->and($this->teacher->fresh()->unreadNotifications()->count())->toBe(0);
});

it('يعلّم الكل مقروءاً بتحديث واحد', function () {
    teacherPush($this->teacher);
    teacherPush($this->teacher);
    teacherPush($this->teacher);

    actingAs($this->teacher)->postJson(route('teacher.notifications.read-all'))
        ->assertOk()
        ->assertJson(['unread_count' => 0]);

    expect($this->teacher->fresh()->unreadNotifications()->count())->toBe(0);
});

it('يقلب إشعاراً بين مقروء وغير مقروء', function () {
    teacherPush($this->teacher);
    $id = $this->teacher->notifications()->first()->id;

    actingAs($this->teacher)->postJson(route('teacher.notifications.toggle', ['id' => $id]))
        ->assertOk()->assertJson(['unread_count' => 0]);

    actingAs($this->teacher)->postJson(route('teacher.notifications.toggle', ['id' => $id]))
        ->assertOk()->assertJson(['unread_count' => 1]);
});

it('يرفض التصرّف في إشعار زميل بـ404', function () {
    teacherPush($this->colleague);
    $foreignId = $this->colleague->notifications()->first()->id;

    // 🔴 يُجلب عبر علاقة المستخدم نفسه — uuid صحيح لصفّ زميل لا يُحَلّ.
    actingAs($this->teacher)->postJson(route('teacher.notifications.read', ['id' => $foreignId]))->assertNotFound();
    actingAs($this->teacher)->postJson(route('teacher.notifications.toggle', ['id' => $foreignId]))->assertNotFound();
    actingAs($this->teacher)->deleteJson(route('teacher.notifications.destroy', ['id' => $foreignId]))->assertNotFound();

    expect($this->colleague->fresh()->unreadNotifications()->count())->toBe(1);
});

// ── الصفحة الكاملة ────────────────────────────────────────────

it('تعرض الصفحة الكاملة إشعارات المعلّم مع تبويبات وعدّادات', function () {
    teacherPush($this->teacher);
    tap($this->teacher->notifications()->first())->markAsRead();
    teacherPush($this->teacher);

    $response = actingAs($this->teacher)->get(route('teacher.notifications'))->assertOk();

    expect($response->viewData('counts'))->toMatchArray(['all' => 2, 'unread' => 1, 'read' => 1]);

    expect(actingAs($this->teacher)->get(route('teacher.notifications', ['filter' => 'unread']))
        ->viewData('notifications'))->toHaveCount(1);
});

// ── الحدث: إسناد/إلغاء مادة (Admin\UserController) ────────────

it('يُخطر المعلّم حين تُسند إليه مادة أو يُلغى إسنادها', function () {
    $physics = Subject::create([
        'branch_id' => $this->branch->id, 'name' => 'الفيزياء', 'slug' => 'physics',
        'display_order' => 2, 'is_published' => true,
    ]);

    // إسناد الفيزياء (تُضاف) مع إبقاء الرياضيات.
    actingAs($this->admin)->put(route('admin.users.update', $this->teacher), [
        'full_name' => 'معلّم تجريبي', 'email' => 'teacher@paleducation.test',
        'is_active' => '1', 'is_publicly_listed' => '0',
        'subject_ids' => [$this->math->id, $physics->id],
    ])->assertRedirect();

    $assigned = $this->teacher->fresh()->notifications
        ->firstWhere('data.title', 'مادة جديدة أُسندت إليك');
    expect($assigned)->not->toBeNull()
        ->and($assigned->data['message'])->toContain('الفيزياء');

    // إلغاء إسناد الرياضيات.
    actingAs($this->admin)->put(route('admin.users.update', $this->teacher), [
        'full_name' => 'معلّم تجريبي', 'email' => 'teacher@paleducation.test',
        'is_active' => '1', 'is_publicly_listed' => '0',
        'subject_ids' => [$physics->id],
    ])->assertRedirect();

    $unassigned = $this->teacher->fresh()->notifications
        ->firstWhere('data.title', 'أُلغي إسناد مادة');
    expect($unassigned)->not->toBeNull()
        ->and($unassigned->data['message'])->toContain('الرياضيات');
});

// ── الحدث: نشر/إخفاء مادة (Admin\SubjectController) ──────────

it('يُخطر معلّمي المادة حين ينشرها الأدمن أو يخفيها', function () {
    $this->math->update(['is_published' => false]);

    actingAs($this->admin)->patch(route('admin.subjects.toggle', $this->math))->assertRedirect();

    $latest = $this->teacher->fresh()->notifications()->first();
    expect($latest->data['title'])->toBe('نُشرت مادتك')
        ->and($latest->data['message'])->toContain('الرياضيات');
});

// ── الحدث: تنزيل ملف مكتبة (استثناء الفاعل) ──────────────────

it('يُخطر صاحب الملف حين ينزّله شخص آخر — لا حين ينزّله هو', function () {
    // ملف يملكه هذا المعلّم على مادته.
    $this->colleagueRecord->subjects()->attach($this->math);   // ليصل الزميل للتنزيل

    $file = LibraryFile::create([
        'subject_id' => $this->math->id, 'teacher_id' => $this->teacherRecord->id,
        'title' => 'ورقة عمل', 'category' => LibraryCategory::Worksheet->value,
    ]);
    $file->media()->create([
        'path' => 'library/'.$this->math->id.'/x.pdf', 'disk' => 'private',
        'type' => 'document', 'original_filename' => 'x.pdf', 'mime_type' => 'application/pdf',
        'size_bytes' => 10, 'display_order' => 1,
    ]);
    Storage::disk('private')->put('library/'.$this->math->id.'/x.pdf', 'PDF');

    // صاحب الملف ينزّله بنفسه — لا إشعار.
    actingAs($this->teacher)->get(route('teacher.library.download', $file))->assertOk();
    expect($this->teacher->fresh()->notifications()->count())->toBe(0);

    // زميل على نفس المادة ينزّله — إشعار لصاحبه.
    actingAs($this->colleague)->get(route('teacher.library.download', $file))->assertOk();

    $latest = $this->teacher->fresh()->notifications()->first();
    expect($latest)->not->toBeNull()
        ->and($latest->data['title'])->toBe('تنزيل ملف من مكتبتك')
        ->and($latest->data['message'])->toContain('ورقة عمل');
});

// ── الحدث: إعلان عام (teachers:announce) ─────────────────────

it('أمر teachers:announce يبثّ لكل المعلّمين الفاعلين', function () {
    $this->artisan('teachers:announce', [
        'heading' => 'صيانة الليلة',
        'body' => 'المنصّة غير متاحة 2–3 صباحاً.',
    ])->assertSuccessful();

    foreach ([$this->teacher, $this->colleague] as $t) {
        $n = $t->fresh()->notifications()->first();
        expect($n->data['title'])->toBe('صيانة الليلة')
            ->and($n->data['type'])->toBe('info');
    }

    // الأدمن ليس معلّماً — لا يصله.
    expect($this->admin->fresh()->notifications()->count())->toBe(0);
});

it('teachers:announce يتخطّى المعلّم المعطّل', function () {
    $this->colleague->update(['is_active' => false]);

    $this->artisan('teachers:announce', ['heading' => 'خبر', 'body' => 'نصّ'])->assertSuccessful();

    expect($this->teacher->fresh()->notifications()->count())->toBe(1)
        ->and($this->colleague->fresh()->notifications()->count())->toBe(0);
});

// ── الحراسة ───────────────────────────────────────────────────

it('يمنع الضيوف والطلبة من مسارات إشعارات المعلّم', function () {
    get(route('teacher.notifications'))->assertRedirect(route('auth.login'));
    get(route('teacher.notifications.feed'))->assertRedirect(route('auth.login'));

    actingAs($this->student)->getJson(route('teacher.notifications.feed'))->assertForbidden();
    actingAs($this->admin)->getJson(route('teacher.notifications.feed'))->assertForbidden();
});

it('يرسم الجرس موصولاً بمسارات المعلّم الحقيقية في القشرة', function () {
    $html = actingAs($this->teacher)->get(route('teacher.students'))->assertOk()->getContent();

    expect($html)->toContain('bellFeed')
        ->toContain(route('teacher.notifications.feed'))
        ->toContain(route('teacher.notifications'))
        ->toContain('name="csrf-token"');
});
