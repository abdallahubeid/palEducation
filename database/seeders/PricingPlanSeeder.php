<?php

namespace Database\Seeders;

use App\Models\PricingPlan;
use Illuminate\Database\Seeder;

/**
 * The three pricing tiers on the public pricing section.
 *
 * Copy is taken from the section as it currently stands in the codebase
 * (lang/ar/home.php, the plan_* keys), which is what a visitor sees today.
 *
 * ⚠️ Two small corrections against the brief, using the live copy as the
 * source of truth: "ابدأ التجربة" (the brief reads "ابتدأ") and
 * "متابعة تقدّمك وإحصاءاتك" (the brief lost the space). Seeding a typo would
 * put it on the public page.
 *
 * 🔴 firstOrCreate keyed on the name: seeders run again after every deploy,
 * and updateOrCreate would push factory copy over an edited price — the one
 * field on this page where a silent revert costs money.
 */
class PricingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'تجربة',
                'badge' => 'جرّب المنصة قبل أن تدفع',
                'price' => 0,
                'period' => 'مجاناً',
                'highlight_badge' => null,
                'features' => [
                    'أول محاضرة من كل مادة',
                    'الاختبار المرافق لها',
                    'تصفّح المكتبة دون تنزيل',
                ],
                'btn_text' => 'ابدأ التجربة',
                'btn_link' => '/register',
            ],
            [
                'name' => 'فصل دراسي',
                'badge' => 'الخيار الأنسب لمعظم الطلبة',
                'price' => 120,
                'period' => '/ للفصل',
                // The ribbon label. At most one plan should carry it.
                'highlight_badge' => 'الأكثر اختياراً',
                'features' => [
                    'مواد فرعك كاملة دون استثناء',
                    'جميع المحاضرات والاختبارات',
                    'تنزيل كل ملفات المكتبة',
                    'متابعة تقدّمك وإحصاءاتك',
                ],
                'btn_text' => 'اشترك الآن',
                'btn_link' => '/pricing',
            ],
            [
                'name' => 'سنة كاملة',
                'badge' => 'الأوفر — يغطّي عام التوجيهي كله',
                'price' => 200,
                'period' => '/ للسنة',
                'highlight_badge' => null,
                'features' => [
                    'كل ما تتضمّنه خطة الفصل',
                    'الفصلان الأول والثاني',
                    'المواد الجديدة فور إضافتها',
                    'أولوية في الدعم الفني',
                ],
                'btn_text' => 'اشترك للسنة',
                'btn_link' => '/pricing',
            ],
        ];

        foreach ($plans as $index => $plan) {
            PricingPlan::firstOrCreate(
                ['name' => $plan['name']],
                [
                    'badge' => $plan['badge'],
                    'price' => $plan['price'],
                    'period' => $plan['period'],
                    'features' => $plan['features'],
                    'highlight_badge' => $plan['highlight_badge'],
                    'btn_text' => $plan['btn_text'],
                    'btn_link' => $plan['btn_link'],
                    'display_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
