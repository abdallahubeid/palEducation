<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
|--------------------------------------------------------------------------
| ردم أدوار الحسابات القائمة
|--------------------------------------------------------------------------
| هجرة بيانات لا بنية. سببها أن جداول الهوية هبطت قبل جداول الصلاحيات،
| فنشأ ما حذّرت منه وثيقة المعمارية حرفياً: حسابات موجودة بلا أدوار.
| هذه الهجرة تُغلق تلك النافذة مرة واحدة، ولا يفتحها شيء بعدها لأن
| مسارَي إنشاء الحساب صارا يُسنِدان الدور داخل معاملة الإنشاء.
|
| تُشتقّ من userable_type لا من تخمين: الشكل هو مصدر الحقيقة للدور،
| ونفس الدالة (User::roleName) تخدم الردم والإنشاء — فلا تتباعد قاعدتان.
|
| آمنة على قاعدة نظيفة: صفر حسابات = صفر عمل.
*/
return new class extends Migration
{
    public function up(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        // الأدوار قد لا تكون مبذورة بعد على قاعدة جديدة — أنشئها إن غابت.
        foreach (RoleSeeder::ROLES as $name) {
            Role::findOrCreate($name, $guard);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // withTrashed: الحساب المحذوف ناعماً قابل للاسترجاع، ويجب أن
        // يعود بدوره لا بلا صلاحيات.
        User::withTrashed()->cursor()->each(
            fn (User $user) => $user->syncRoleToShape()
        );
    }

    public function down(): void
    {
        // لا تراجُع: نزع الأدوار يترك حسابات معطّلة الصلاحيات بلا فائدة.
        // إسقاط جداول Spatie نفسها هو تراجُع هجرة الصلاحيات لا هذه.
    }
};
