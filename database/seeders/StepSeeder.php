<?php

namespace Database\Seeders;

use App\Models\Step;
use Illuminate\Database\Seeder;

/**
 * خطوات «آلية العمل» الثلاث في الصفحة الرئيسية.
 *
 * 🔴 display_order دلالي لا تجميلي: «حدّد فرعك ← تابع محاضرتك ← قِس
 * فهمك» تسلسل يفقد معناه إن اختلّ ترتيبه، والترقيم المعروض (01 · 02 ·
 * 03) يُشتقّ من موضع الصفّ لا من عمود مخزَّن.
 *
 * ⚠️ firstOrCreate على العنوان: البذرة تُعاد بعد كل نشر، وupdateOrCreate
 * كانت ستُرجع نصّ المصنع فوق نصّ حرّره الأدمن. والمقابلة بالعنوان لا
 * بالمعرّف لأن المعرّفات لا تُثبَّت في البذور.
 */
class StepSeeder extends Seeder
{
    public function run(): void
    {
        $steps = [
            [
                'title' => 'حدّد فرعك',
                'sub_title' => 'خطوة واحدة في بداية رحلتك، تُعيد ترتيب المنصة بأكملها حولك فلا يظهر لك إلا منهجك.',
            ],
            [
                'title' => 'تابع محاضرتك',
                'sub_title' => 'شرح مصوّر من معلّم المادة نفسه، بجودة عالية، ومن أي جهاز — هاتفك أو حاسوبك.',
            ],
            [
                'title' => 'قِس فهمك فوراً',
                'sub_title' => 'يُفتح الاختبار تلقائياً عند انتهاء المحاضرة، وتظهر الإجابة الصحيحة إلى جوار إجابتك مباشرة.',
            ],
        ];

        foreach ($steps as $index => $step) {
            Step::firstOrCreate(
                ['title' => $step['title']],
                [
                    'sub_title' => $step['sub_title'],
                    'display_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
