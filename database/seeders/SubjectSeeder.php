<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * مواد الفروع الأربعة — نفس القوائم المكتوبة في وصف كل فرع بـBranchSeeder.
 *
 * 🔴 icon و accent_token يحملان أسماء **موجودة فعلاً**: الأيقونة من مجموعة
 * x-icon، والنغمة من النغمات الأربع التي يعرفها domain/subject-card
 * (accent · tag · amber · warn). قيمة غير موجودة تُنتج بطاقة بلا أيقونة
 * أو بلا لون **بصمت** — بلا خطأ ولا تحذير.
 *
 * 🔴 اسم النغمة القادم من القاعدة يجب أن يمرّ عبر مصفوفة PHP ثابتة تربطه
 * بصنف Tailwind كاملاً حرفياً — لا تجميع نصّي. فاحص Tailwind وقت البناء
 * لا ينفّذ PHP، فـ"bg-{$tone}/14" لا يولّد أي CSS.
 *
 * price_override_agora يبقى null: كل المواد على سعر الخطة (35 ₪ = 3500
 * أغورة للمادة في الفصل). العمود لحالة الشذوذ لا للحالة العامة.
 */
class SubjectSeeder extends Seeder
{
    /** [slug, name, icon, accent_token] لكل فرع. */
    private const SUBJECTS = [
        'scientific' => [
            ['math', 'رياضيات', 'chart', 'accent'],
            ['physics', 'فيزياء', 'compass', 'tag'],
            ['chemistry', 'كيمياء', 'beaker', 'amber'],
            ['biology', 'أحياء', 'sparkle', 'warn'],
        ],
        'literary' => [
            ['arabic', 'لغة عربية', 'book', 'accent'],
            ['history', 'تاريخ', 'clock', 'tag'],
            ['geography', 'جغرافيا', 'compass', 'amber'],
            ['english', 'لغة إنجليزية', 'clipboard', 'warn'],
        ],
        'commercial' => [
            ['accounting', 'محاسبة', 'card', 'accent'],
            ['economics', 'اقتصاد وإدارة', 'trending-up', 'tag'],
            ['business-math', 'رياضيات تجارية', 'chart', 'amber'],
        ],
        'industrial' => [
            ['technical-drawing', 'رسم صناعي', 'edit', 'accent'],
            ['production-tech', 'تقنية إنتاج', 'wrench', 'tag'],
            ['electronics', 'إلكترونيات', 'sparkle', 'amber'],
        ],
    ];

    public function run(): void
    {
        foreach (self::SUBJECTS as $branchSlug => $subjects) {
            $branchId = Branch::where('slug', $branchSlug)->value('id');

            if (! $branchId) {
                continue;
            }

            foreach ($subjects as $order => [$slug, $name, $icon, $tone]) {
                Subject::updateOrCreate(
                    // الـslug فريد **داخل الفرع** — المفتاح مركّب لا مفرد.
                    ['branch_id' => $branchId, 'slug' => $slug],
                    [
                        'name' => $name,
                        'icon' => $icon,
                        'accent_token' => $tone,
                        'display_order' => $order + 1,
                        'is_published' => true,
                    ]
                );
            }
        }
    }
}
