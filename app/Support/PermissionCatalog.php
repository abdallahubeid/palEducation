<?php

namespace App\Support;

/**
 * فهرس صلاحيات المنصّة، مجمّعاً بالوحدات.
 *
 * 🔴 مصدر واحد: البذرة تُنشئ منه، وشاشة الأدوار تعرض منه، والمسارات
 * تحرس بأسمائه. ثلاث نسخ من نفس القائمة تتباعد؛ نسخة واحدة لا تفعل.
 *
 * ⚠️ الصلاحية تدخل هنا فقط حين يحرسها مسار فعلاً. اسم صلاحية لا يفحصه
 * شيء أسوأ من غيابه — يوهم بحماية غير موجودة.
 */
final class PermissionCatalog
{
    /** الأدوار الثلاثة الثابتة — تقابل جداول المجال الثلاثة. */
    public const SYSTEM_ROLES = ['student', 'teacher', 'admin'];

    /**
     * 🔴 صلاحيات لا تُنزع عن دور admin من الواجهة.
     *
     * بدونها يستطيع أدمن أن يُطفئ manage-roles عن دوره فيقفل على نفسه
     * شاشة الأدوار نهائياً — ولا عودة إلا من قاعدة البيانات.
     */
    public const LOCKED_ADMIN_PERMISSIONS = ['manage-roles'];

    /**
     * @return array<string, array{label: string, icon: string, permissions: array<string, string>}>
     */
    public static function groups(): array
    {
        return [
            'sliders' => [
                'label' => 'الهيرو',
                'icon' => 'sparkle',
                'permissions' => [
                    'view-sliders' => 'عرض الشرائح',
                    'create-sliders' => 'إنشاء شريحة',
                    'edit-sliders' => 'تعديل شريحة',
                    'delete-sliders' => 'حذف شريحة',
                ],
            ],
            'settings' => [
                'label' => 'الإعدادات',
                'icon' => 'settings',
                'permissions' => [
                    'manage-settings' => 'إدارة الإعدادات',
                ],
            ],
            'roles' => [
                'label' => 'الأدوار والصلاحيات',
                'icon' => 'shield',
                'permissions' => [
                    /*
                     * مقسّمة لا مجمّعة: «إدارة الأدوار» واحدة كانت تعني أن
                     * مَن يعرض القائمة يستطيع حذف دور بكل مَن يحمله. الفصل
                     * يسمح بأدمن يراجع الصلاحيات ولا يعيد تشكيلها.
                     */
                    'view-roles' => 'عرض الأدوار',
                    'create-roles' => 'إنشاء دور',
                    'manage-roles' => 'تعديل صلاحيات دور',
                    'delete-roles' => 'حذف دور',
                ],
            ],
            'users' => [
                'label' => 'المستخدمون',
                'icon' => 'users',
                'permissions' => [
                    'view-users' => 'عرض المستخدمين',
                    'create-users' => 'إنشاء مستخدم',
                    'edit-users' => 'تعديل مستخدم',
                    'delete-users' => 'حذف مستخدم',
                ],
            ],
        ];
    }

    /** كل أسماء الصلاحيات مسطّحةً. @return array<int, string> */
    public static function names(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group): array => array_keys($group['permissions']))
            ->all();
    }

    /** التسمية العربية لصلاحية، أو اسمها إن لم تكن في الفهرس. */
    public static function label(string $permission): string
    {
        return collect(self::groups())
            ->flatMap(fn (array $group): array => $group['permissions'])
            ->get($permission, $permission);
    }

    public static function isSystemRole(string $role): bool
    {
        return in_array($role, self::SYSTEM_ROLES, true);
    }

    /** الصلاحيات المقفلة على دور بعينه. @return array<int, string> */
    public static function lockedFor(string $role): array
    {
        return $role === 'admin' ? self::LOCKED_ADMIN_PERMISSIONS : [];
    }
}
