<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * الأدوار الثلاثة الثابتة وصلاحياتها.
 *
 * الأدوار الثلاثة تقابل جداول المجال الثلاثة (Student · Teacher · Admin)
 * ولا تُحذف من الواجهة. أدوار إضافية يُنشئها الأدمن من شاشة الأدوار.
 *
 * ⚠️ تُبقي القوائم في PermissionCatalog لا هنا: البذرة مستهلِك للفهرس
 * لا مالك له، وإلّا صار عندنا مصدران للحقيقة.
 */
class RoleSeeder extends Seeder
{
    /** @deprecated يُقرأ من PermissionCatalog — مُبقى لتوافق الاستدعاءات القائمة. */
    public const ROLES = PermissionCatalog::SYSTEM_ROLES;

    /** @deprecated يُقرأ من PermissionCatalog. */
    public const LOCKED_ADMIN_PERMISSIONS = PermissionCatalog::LOCKED_ADMIN_PERMISSIONS;

    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        foreach (PermissionCatalog::SYSTEM_ROLES as $name) {
            Role::findOrCreate($name, $guard);
        }

        // ⚠️ الإنشاء كله أولاً، ثم تفريغ الذاكرة، ثم المنح — بهذا الترتيب.
        // givePermissionTo يحلّ الأسماء من ذاكرة الحزمة المؤقّتة المحمّلة منذ
        // بداية الطلب، فالمنح مباشرةً بعد الإنشاء يرمي PermissionDoesNotExist
        // على صلاحية أُنشئت للتوّ — الصفّ موجود والذاكرة لا تعرفه.
        foreach (PermissionCatalog::names() as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // givePermissionTo تراكمية وآمنة لإعادة التشغيل — لا syncPermissions:
        // المزامنة تنزع أي صلاحية عدّلها الأدمن من شاشة الأدوار.
        Role::findByName('admin', $guard)->givePermissionTo(PermissionCatalog::names());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
