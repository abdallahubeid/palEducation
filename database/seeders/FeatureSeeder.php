<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

/**
 * The four "what sets us apart" cards on the home page.
 *
 * Copy is taken from the section as it currently stands in the codebase
 * (lang/ar/home.php, keys f1..f4), which is what a visitor sees today, so
 * the seeded rows reproduce the live page rather than approximate it.
 *
 * 🔴 icon and accent_token hold NAMES resolved at render time — an icon from
 * the x-icon set and a tone from the card's palette map. A value outside
 * those sets draws a card with no icon or no colour, silently.
 *
 * ⚠️ firstOrCreate keyed on the title: seeders run again after every deploy,
 * and updateOrCreate would push factory copy over text the admin has edited.
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [
                'title' => 'الاختبار يتبع الفيديو تلقائياً',
                'description' => 'لا زرّ ولا خطوة إضافية. تنتهي المحاضرة فيبدأ التقييم، لأن القياس والشرح ما يزال حاضراً أنفع من مراجعة بعد أسبوع.',
                'icon' => 'clipboard',
                'accent_token' => 'accent',
            ],
            [
                'title' => 'مكتبة مستقلّة ومنظّمة',
                'description' => 'أوراق عمل وملخّصات وامتحانات سابقة، مرتّبة حسب الفرع والمادة في موضع واحد لا يتطلّب بحثاً.',
                'icon' => 'folder',
                'accent_token' => 'tag',
            ],
            [
                'title' => 'معلّم المادة هو واضع الاختبار',
                'description' => 'يبني المعلّم اختباره لحظة رفع المحاضرة، فيقيس ما شرحه بالضبط — لا أسئلة عامّة من بنك جاهز.',
                'icon' => 'users',
                'accent_token' => 'amber',
            ],
            [
                'title' => 'محتوى فرعك دون سواه',
                'description' => 'تحديد الفرع يُخفي كل مادة لا تعنيك. لا قوائم طويلة، ولا محتوى يزاحم منهجك على انتباهك.',
                'icon' => 'compass',
                'accent_token' => 'warn',
            ],
        ];

        foreach ($features as $index => $feature) {
            Feature::firstOrCreate(
                ['title' => $feature['title']],
                [
                    'description' => $feature['description'],
                    'icon' => $feature['icon'],
                    'accent_token' => $feature['accent_token'],
                    'display_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
