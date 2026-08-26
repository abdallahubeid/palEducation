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
        $branches = [
            ['slug' => 'scientific', 'name' => 'علمي', 'icon' => 'beaker', 'accent_token' => 'accent', 'display_order' => 1,
                'description' => 'رياضيات · فيزياء · كيمياء · أحياء'],
            ['slug' => 'literary', 'name' => 'أدبي', 'icon' => 'book', 'accent_token' => 'tag', 'display_order' => 2,
                'description' => 'لغة عربية · تاريخ · جغرافيا · لغة إنجليزية'],
            ['slug' => 'commercial', 'name' => 'تجاري', 'icon' => 'briefcase', 'accent_token' => 'amber', 'display_order' => 3,
                'description' => 'محاسبة · اقتصاد وإدارة · رياضيات تجارية'],
            ['slug' => 'industrial', 'name' => 'صناعي', 'icon' => 'wrench', 'accent_token' => 'warn', 'display_order' => 4,
                'description' => 'رسم صناعي · تقنية إنتاج · إلكترونيات'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(['slug' => $branch['slug']], $branch);
        }
    }
}
