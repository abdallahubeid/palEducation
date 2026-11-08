<?php

use App\Models\Branch;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;

/*
| شريط المعلّم الجانبي.
|
| 🔴 حارس Route::has في الشريط يمنع الانهيار **ويُخفي العطل معاً**: مسار
| محذوف يصير href="#" بلا خطأ ولا تحذير. ثلاثة روابط عاشت ميّتة هكذا، ولم
| يكتشفها أي اختبار لأن الصفحة كانت تُرسَل 200 في كل مرة.
|
| هذه الاختبارات تؤكّد على **الوجهة** لا على وجود النصّ.
*/

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $branch = Branch::create([
        'name' => 'الفرع العلمي', 'slug' => 'scientific', 'display_order' => 1,
    ]);

    $this->teacherRecord = Teacher::create([
        'full_name' => 'معلّم تجريبي', 'is_publicly_listed' => false,
    ]);

    $this->teacher = tap($this->teacherRecord->user()->create([
        'email' => 'teacher@paleducation.test', 'password' => bcrypt('password'), 'email_verified_at' => now(),
    ]))->syncRoleToShape();

    $this->subject = Subject::create([
        'branch_id' => $branch->id, 'name' => 'الرياضيات', 'slug' => 'math', 'is_published' => true,
    ]);
    $this->teacherRecord->subjects()->attach($this->subject);
});

it('يعرض رابط المكتبة التعليمية موجّهاً إلى وجهته الحقيقية', function () {
    $html = actingAs($this->teacher)->get(route('teacher.subjects'))->assertOk()->getContent();

    expect($html)->toContain('المكتبة التعليمية')
        ->toContain('href="'.route('teacher.library').'"');
});

it('لا يترك أي رابط ميّت في الشريط', function () {
    $html = actingAs($this->teacher)->get(route('teacher.subjects'))->assertOk()->getContent();

    /*
     * 🔴 لبّ الاختبار. `href="#"` هو ما يُرسَم لمسار غير موجود، وهو الشكل
     * الذي عاشت به ثلاثة روابط: المكتبة (مسار محذوف)، والملف الشخصي (أُعيدت
     * تسميته)، وزرّ «رفع محاضرة» البارز — أبرز عنصر في الشريط وهو لا يفعل
     * شيئاً.
     *
     * ⚠️ الفحص على القشرة كاملةً: أي إعادة تسمية مسار لاحقاً تُسقط هذا
     * الاختبار بدل أن تمرّ بصمت.
     */
    expect(substr_count($html, 'href="#"'))->toBe(0);
});

it('يفعّل رابط المكتبة على مسارات المكتبة كلها', function () {
    $html = actingAs($this->teacher)->get(route('teacher.library'))->assertOk()->getContent();

    // ⚠️ pattern منفصل عن route: التفعيل يشمل teacher.library.* بينما
    // الرابط يقصد صفحة واحدة.
    expect($html)->toContain('aria-current="page"');
});

it('يضع زرّ الطيّ أسفل الشريط لا في ترويسته', function () {
    $html = actingAs($this->teacher)->get(route('teacher.subjects'))->assertOk()->getContent();

    /*
     * 🔴 الموضع يُقاس بالترتيب لا بالوجود: الزرّ كان موجوداً قبل التعديل
     * أيضاً — في الأعلى. فحص «هل هو موجود؟» كان سيمرّ قبل النقل وبعده.
     */
    $togglePos = strpos($html, 'data-sidebar-collapse-toggle');
    $navEndPos = strrpos($html, '</nav>');

    expect($togglePos)->toBeGreaterThan($navEndPos);
});

it('يبقي زرّ إغلاق الدرج في الترويسة', function () {
    $html = actingAs($this->teacher)->get(route('teacher.subjects'))->assertOk()->getContent();

    /*
     * ⚠️ زرّان مختلفان لغرضين مختلفين: الطيّ لسطح المكتب (شريط أيقونات)،
     * والإغلاق للجوال (درج). نقل الأول يجب ألّا يأخذ الثاني معه — الدرج
     * بلا زرّ إغلاق يُغلق بالنقر خارجه فقط.
     */
    $closePos = strpos($html, 'data-sidebar-close');
    $navStartPos = strpos($html, '<nav');

    expect($closePos)->toBeLessThan($navStartPos);
});
