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
            'branches' => [
                'label' => 'الفروع',
                'icon' => 'book',
                'permissions' => [
                    /*
                     * Split, not lumped — same reasoning as the roles group.
                     * A branch is the platform's root filter: deleting one
                     * hides every subject, lecture and student under it, so
                     * that must be grantable apart from editing a label.
                     */
                    'view-branches' => 'عرض الفروع',
                    'create-branches' => 'إنشاء فرع',
                    'edit-branches' => 'تعديل فرع',
                    'delete-branches' => 'حذف فرع',
                ],
            ],
            'features' => [
                'label' => 'ما يميّزنا',
                'icon' => 'sparkle',
                'permissions' => [
                    'view-features' => 'عرض المميزات',
                    'create-features' => 'إنشاء ميزة',
                    'edit-features' => 'تعديل ميزة',
                    'delete-features' => 'حذف ميزة',
                ],
            ],
            'pricings' => [
                'label' => 'الأسعار',
                'icon' => 'wallet',
                'permissions' => [
                    /*
                     * Split rather than lumped. Editing the copy on a plan
                     * and withdrawing a plan from sale are different levels
                     * of authority, and this is the section where a mistake
                     * is visible to every visitor and costs money.
                     */
                    'view-pricings' => 'عرض خطط الأسعار',
                    'create-pricings' => 'إنشاء خطة',
                    'edit-pricings' => 'تعديل خطة',
                    'delete-pricings' => 'حذف خطة',
                ],
            ],
            'teachers' => [
                'label' => 'المعلّمون',
                'icon' => 'users',
                'permissions' => [
                    /*
                     * 🔴 One permission, not four. There is no teachers CRUD
                     * module yet — teacher records are created and removed
                     * through the Users screen — so `delete-teachers` is the
                     * only one an actual route checks: the trash.
                     *
                     * The catalog's own rule is that a permission nothing
                     * checks is worse than its absence, because it shows in
                     * the roles screen as a switch that does nothing. The
                     * other three arrive with the module that needs them.
                     */
                    'delete-teachers' => 'استعادة وحذف المعلّمين نهائياً',
                ],
            ],
            'testimonials' => [
                'label' => 'آراء الطلبة',
                'icon' => 'users',
                'permissions' => [
                    'view-testimonials' => 'عرض الآراء',
                    'create-testimonials' => 'إنشاء رأي',
                    'edit-testimonials' => 'تعديل رأي',
                    'delete-testimonials' => 'حذف رأي',
                ],
            ],
            'faqs' => [
                'label' => 'الأسئلة الشائعة',
                'icon' => 'alert',
                'permissions' => [
                    'view-faqs' => 'عرض الأسئلة',
                    'create-faqs' => 'إنشاء سؤال',
                    'edit-faqs' => 'تعديل سؤال',
                    'delete-faqs' => 'حذف سؤال',
                ],
            ],
            'subjects' => [
                'label' => 'المواد الدراسية',
                'icon' => 'book',
                'permissions' => [
                    /*
                     * 🔴 مقسّمة كالفروع: المادة هي ما يعلّق به المعلّم
                     * والمحاضرة والملف، وحذف مادة يخفي كل ما تحتها عن
                     * الطالب. فصل الحذف عن التعديل ليس ترفاً.
                     */
                    'view-subjects' => 'عرض المواد',
                    'create-subjects' => 'إنشاء مادة',
                    'edit-subjects' => 'تعديل مادة',
                    'delete-subjects' => 'حذف مادة',
                ],
            ],

            'news' => [
                'label' => 'الأخبار',
                'icon' => 'clipboard',
                'permissions' => [
                    'view-news' => 'عرض الأخبار',
                    'create-news' => 'إنشاء خبر',
                    'edit-news' => 'تعديل خبر',
                    'delete-news' => 'حذف خبر',
                ],
            ],

            'newsletter' => [
                'label' => 'النشرة البريدية',
                'icon' => 'mail',
                'permissions' => [
                    /*
                     * 🔴 الإرسال منفصل عن العرض عمداً. قراءة قائمة المشتركين
                     * فعل بلا أثر يمكن التراجع عنه؛ إرسال حملة يغادر الخادم
                     * إلى صناديق بريد لا يمكن سحبه منها. دمجهما في صلاحية
                     * واحدة يعني أن كل من يفتح القائمة يستطيع مراسلتها.
                     *
                     * ولا صلاحية «إنشاء»: المشتركون يسجّلون أنفسهم من الفوتر،
                     * والأدمن لا يضيف عناوين يدوياً — إضافة عنوان لم يطلب
                     * صاحبه الاشتراك هي تعريف البريد المزعج.
                     */
                    'view-newsletter' => 'عرض المشتركين',
                    'send-newsletter' => 'إرسال نشرة بريدية',
                    'delete-newsletter' => 'حذف مشترك',

                    /*
                     * سجلّ الحملات صلاحيتان منفصلتان عن المشتركين: السجلّ
                     * يحمل **نصّ** كل نشرة أُرسلت، وهو محتوى قد يكون داخلياً
                     * لا يلزم أن يقرأه كل من يرى قائمة العناوين.
                     */
                    'view-campaigns' => 'عرض سجلّ الحملات',
                    'delete-campaigns' => 'حذف حملة من السجلّ',
                ],
            ],

            'messages' => [
                'label' => 'رسائل التواصل',
                'icon' => 'mail',
                'permissions' => [
                    /*
                     * Three, not four: nobody CREATES a contact message from
                     * the panel — visitors do, through the public form. A
                     * `create-messages` switch would sit in the roles screen
                     * doing nothing, which the catalog forbids.
                     */
                    'view-messages' => 'عرض رسائل التواصل',
                    'reply-messages' => 'الردّ على الرسائل',
                    'delete-messages' => 'حذف الرسائل',
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
