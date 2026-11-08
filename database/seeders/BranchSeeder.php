<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * الفروع الأربعة — نفس الأسماء والمعرّفات المستخدمة في الواجهات المبنية
 * (صفحة الفروع، صفحة الفرع، شاشة التسجيل، شاشة اختيار الفرع).
 *
 * ⏳ تسمية الوزارة الجديدة (أدبي ← الإنساني · تجاري ← الريادة والأعمال)
 * قرار منتج معلّق. الأسماء هنا هي ما يبحث عنه الطلبة فعلياً.
 */
class BranchSeeder extends Seeder
{
    public function run(): void
    {
        // 🔴 icon و accent_token يحملان أسماء موجودة فعلاً في المجموعتين:
        // الأيقونة من x-icon، والنغمة من ui/selectable-card. قيمة غير
        // موجودة تُنتج بطاقة بلا أيقونة أو بلا لون بصمت.
        /*
         * Card copy comes from the landing page as it stands —
         * lang/ar/home.php (branch_*_name / branch_*_summary).
         *
         * 🔴 No subject/teacher figures here. Those were columns once; they
         * are now derived at query time by Branch::scopeWithCardCounts(),
         * so the card can never advertise a number the content contradicts.
         */
        $branches = [
            ['slug' => 'scientific', 'name' => 'الفرع العلمي', 'icon' => 'beaker', 'accent_token' => 'accent', 'display_order' => 1,
                'description' => 'رياضيات وفيزياء وكيمياء وأحياء، بشرح مفصّل للمسائل والمعادلات خطوة بخطوة.'],
            ['slug' => 'literary', 'name' => 'الفرع الأدبي', 'icon' => 'book', 'accent_token' => 'tag', 'display_order' => 2,
                'description' => 'لغة عربية وتاريخ وجغرافيا وتربية إسلامية، مع تحليل النصوص وأساليب الإجابة النموذجية.'],
            ['slug' => 'commercial', 'name' => 'الفرع التجاري', 'icon' => 'briefcase', 'accent_token' => 'amber', 'display_order' => 3,
                'description' => 'محاسبة واقتصاد وإدارة، بتمارين تطبيقية على نماذج الامتحانات الوزارية السابقة.'],
            ['slug' => 'industrial', 'name' => 'الفرع الصناعي', 'icon' => 'wrench', 'accent_token' => 'warn', 'display_order' => 4,
                'description' => 'تخصّصات مهنية وتقنية بشرح عملي يربط الجانب النظري بالتطبيق داخل الورشة.'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(['slug' => $branch['slug']], $branch);
        }
    }
}
