<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Frequently asked questions on the public home page.
 *
 * Copy taken from the section as it stands in the codebase
 * (lang/ar/home.php, the faq_* keys) — what a visitor sees today.
 *
 * ⚠️ Two of these answers describe decisions that are RECORDED but not yet
 * enforced in code:
 *   - the subscription being branch-wide (decision M-1);
 *   - two attempts with the highest score counted (decision M-2).
 * Both are settled decisions, so the copy is accurate about intent. Neither
 * has a `subscriptions` or `quiz_attempts` table behind it yet, so nothing
 * in the system currently makes them true.
 *
 * 🔴 firstOrCreate keyed on the question: seeders run again after every
 * deploy, and updateOrCreate would push factory copy over an edited answer.
 */
class FaqSeeder extends Seeder
{
    /** [question, answer] — order follows the array. */
    private const QUESTIONS = [
        [
            'هل يمكنني تغيير فرعي بعد اختياره؟',
            'اختيار الفرع شبه دائم لأنه يحدّد كل المحتوى الذي تراه. وإن وقع خطأ، يمكنك طلب تغييره من إدارة المنصة عبر صفحة ملفك الشخصي، ويُنفّذ الطلب خلال يوم عمل واحد.',
        ],
        [
            'هل يفتح الاشتراك جميع المواد أم كل مادة على حدة؟',
            'الاشتراك على مستوى الفرع، أي أنه يفتح مواد فرعك كاملة دفعة واحدة: جميع المحاضرات والاختبارات وملفات المكتبة. ولا يوجد دفع منفصل لكل مادة.',
        ],
        [
            'هل يلزم إنهاء الفيديو كاملاً ليُفتح الاختبار؟',
            'تُشترط المشاهدة الفعلية لمعظم المحاضرة، لأن الاختبار مبنيّ على ما شُرح فيها تحديداً. أما تخطّي الفيديو إلى نهايته فلا يفتحه.',
        ],
        [
            'هل يمكنني إعادة الاختبار إن لم تُرضِني درجتي؟',
            'نعم، لديك محاولتان لكل اختبار وتُحتسب لك الأعلى بينهما. كما أن النتيجة لا تُغلق المحاضرة التالية، فبإمكانك متابعة دراستك دون توقّف.',
        ],
        [
            'هل تعمل المنصة على الهاتف؟',
            'نعم، وهي مصمّمة للهاتف أولاً. شاشتا المحاضرة والاختبار على وجه الخصوص صُمّمتا للهاتف قبل الشاشات الكبيرة، لأن أغلب طلبتنا يفتحون المنصة من هواتفهم.',
        ],
        [
            'كيف تتم عملية الدفع؟ وما الطرق المتاحة؟',
            'تختار خطتك فتصلك تعليمات التحويل، وبعد إتمامه ترفع صورة الإيصال. نراجع الطلب ونفعّل اشتراكك خلال أربع وعشرين ساعة عادةً.',
        ],
    ];

    public function run(): void
    {
        foreach (self::QUESTIONS as $index => [$question, $answer]) {
            Faq::firstOrCreate(
                ['question' => $question],
                [
                    'answer' => $answer,
                    'display_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
