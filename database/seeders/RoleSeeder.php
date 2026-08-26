<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * الأدوار الثلاثة وصلاحياتها.
 *
 * ثابتة لا تُدار من الواجهة: المنصة لها ثلاثة أدوار بالتعريف، وكل دور
 * يقابل جدول مجال. إضافة دور رابع تعني جدول مجال رابعاً، وهو قرار
 * معماري لا إعداد تشغيلي.
 *
 * 🔴 الصلاحيات تُبذَر هنا فقط حين يفحصها شيء فعلاً. القاعدة المسجّلة:
 * اسم صلاحية لا يحرسه مسار ولا Policy أسوأ من غيابه — يوهم بحماية غير
 * موجودة. أُضيفت manage-sliders و manage-settings مع أول مسار أدمن
 * يفحصهما بـ`can:` middleware.
 */
class RoleSeeder extends Seeder
{
    public const ROLES = ['student', 'teacher', 'admin'];

    /**
     * الصلاحيات المُنفَّذة فعلاً — كل واحدة يحرسها مسار.
     *
     * 🔴 مُقسَّمة لا مجمّعة: «manage-sliders» واحدة كانت تعني أن مَن يعرض
     * القائمة يستطيع الحذف. الفصل يسمح بأدمن محتوى ينشئ ويعدّل ولا يحذف —
     * وهو التمييز الذي يجعل شاشة الأدوار مفيدة أصلاً.
     */
    public const PERMISSIONS = [
        'view-sliders',
        'create-sliders',
        'edit-sliders',
        'delete-sliders',
        'manage-settings',
        'manage-roles',
    ];

    /**
     * 🔴 صلاحيات لا يجوز نزعها عن دور admin من الواجهة.
     *
     * بدون هذا الحارس يستطيع أدمن أن يُطفئ manage-roles عن دوره فيقفل
     * على نفسه شاشة الأدوار نهائياً — ولا سبيل للعودة إلا من قاعدة
     * البيانات مباشرة. القفل الذاتي خطأ لا يُصلَّح بالندم.
     */
    public const LOCKED_ADMIN_PERMISSIONS = ['manage-roles'];

    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        foreach (self::ROLES as $name) {
            Role::findOrCreate($name, $guard);
        }

        // ⚠️ الإنشاء كله أولاً، ثم تفريغ الذاكرة، ثم المنح — بهذا الترتيب.
        // givePermissionTo يحلّ الأسماء من ذاكرة الحزمة المؤقّتة، وهي مُحمّلة
        // منذ بداية الطلب. المنح مباشرةً بعد الإنشاء يرمي PermissionDoesNotExist
        // على صلاحية أُنشئت للتوّ فعلاً — الصفّ موجود والذاكرة لا تعرفه.
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // givePermissionTo تراكمية وآمنة لإعادة التشغيل — لا syncPermissions:
        // المزامنة تنزع أي صلاحية عدّلها الأدمن من شاشة الأدوار.
        Role::findByName('admin', $guard)->givePermissionTo(self::PERMISSIONS);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
