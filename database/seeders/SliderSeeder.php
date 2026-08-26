<?php

namespace Database\Seeders;

use App\Enums\MediaType;
use App\Models\Media;
use App\Models\Slider;
use Illuminate\Database\Seeder;

/**
 * شرائح الهيرو — منقولة من مصفوفة $slides الثابتة في home.blade.php.
 *
 * المصدر كان موزّعاً على ملفّين: المصفوفة في القالب تحمل الصورة والنغمة،
 * والنصوص في lang/ar/home.php تحت المفاتيح slide_N_*. جُمع الاثنان هنا.
 *
 * 🔴 ثلاث ملاحظات على التحويل، مسجّلة لأنها ليست نقلاً حرفياً:
 *
 * ١) العنوان كان سطرين بلونين (`line_1` بلون الحبر و`line_2` بلون accent).
 *    جدول sliders يحمل `title` واحداً، فدُمج السطران بمسافة. **المحتوى
 *    كامل، والتلوين ثنائي اللون هو ما سقط** — يُستعاد بتقسيم العنوان في
 *    العرض أو بعمود `title_highlight` لاحقاً، وكلاهما خارج نطاق هذه الدفعة.
 *
 * ٢) `tone` (accent/tag/amber) كان يمرَّر لـmedia-slot ولا عمود له في
 *    sliders ولا في media. سقط. البطاقات تعرض بالنغمة الافتراضية حتى
 *    يُضاف عمود — قرار عرض لا بيانات.
 *
 * ٣) زرّ الدعوة كان `href="#"` في القالب الساكن — رابط ميّت. بُذر هنا
 *    على `/register` لأن المسار صار حقيقياً، و`#branches` للزرّ الثانوي
 *    كما هو في القالب.
 *
 * updateOrCreate على `title`: إعادة التشغيل تُحدّث ولا تُكرّر.
 */
class SliderSeeder extends Seeder
{
    /**
     * المصفوفة الأصلية بعد الدمج.
     * [badge, line_1, line_2, lede, image, alt]
     */
    private const SLIDES = [
        [
            'badge' => 'منصة التوجيهي الفلسطينية',
            'line_1' => 'التوجيهي ليس صعباً.',
            'line_2' => 'التشتّت هو الصعب.',
            'lede' => 'تختار فرعك مرّة واحدة، فتختفي عن شاشتك كل مادة لا تخصّك. لا قوائم طويلة تبحث فيها، ولا محتوى يصرفك عن منهجك.',
            'image' => 'images/hero/slide-1-focus.jpg',
            'alt' => 'طالب توجيهي يدرس على المنصة',
        ],
        [
            'badge' => 'حلقة تعلّم مغلقة',
            'line_1' => 'تنتهي المحاضرة،',
            'line_2' => 'فيُفتح الاختبار تلقائياً.',
            'lede' => 'لا يوجد زرّ «ابدأ الاختبار». يصلك التقييم والشرح ما يزال حاضراً في ذهنك، فتعرف موقعك قبل الامتحان لا بعده.',
            'image' => 'images/hero/slide-2-quiz.jpg',
            'alt' => 'شاشة الاختبار تُفتح بعد انتهاء المحاضرة',
        ],
        [
            'badge' => 'مكتبة منظّمة',
            'line_1' => 'مراجعك كاملة',
            'line_2' => 'في مكان واحد.',
            'lede' => 'أوراق عمل وملخّصات وامتحانات وزارية سابقة، مرتّبة حسب فرعك ومادتك — بدل البحث عنها بين المحاضرات.',
            'image' => 'images/hero/slide-3-library.jpg',
            'alt' => 'مكتبة ملفات منظّمة حسب المادة',
        ],
    ];

    public function run(): void
    {
        foreach (self::SLIDES as $order => $slide) {
            $title = $slide['line_1'].' '.$slide['line_2'];

            $slider = Slider::updateOrCreate(
                ['title' => $title],
                [
                    'badge_text' => $slide['badge'],
                    'sub_title' => $slide['lede'],
                    'btn1_text' => 'ابدأ الآن',
                    'btn1_link' => '/register',
                    'btn2_text' => 'تصفّح الفروع',
                    'btn2_link' => '#branches',
                    'display_order' => $order + 1,
                    'is_active' => true,
                ]
            );

            // ⚠️ لا createMany ولا create مباشرة: إعادة تشغيل البذرة كانت
            // ستُراكم صورة جديدة في كل مرة على نفس الشريحة. المفتاح هو
            // المسار — نفس الصورة تُحدَّث ولا تُستنسخ.
            Media::updateOrCreate(
                [
                    'mediable_type' => Slider::class,
                    'mediable_id' => $slider->id,
                    'path' => $slide['image'],
                ],
                [
                    'type' => MediaType::Image,
                    'alt_text' => $slide['alt'],
                    'display_order' => 1,
                ]
            );
        }
    }
}
