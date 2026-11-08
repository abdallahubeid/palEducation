<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * إعداد موقع — مفتاح/قيمة.
 *
 * 🔴 لا تُخزَّن هنا أي قيمة لا يجوز أن يراها زائر غير مسجّل. هذه النسخة
 * بلا عمود is_public (§3.32 تُعرّف الجدول الأغنى للمرحلة 10)، فلا شيء في
 * القاعدة يميّز العام من الخاص. إعدادات التقييم — عتبة 90% وسقف المحاولات
 * وسياسة الاحتساب — تنتظر ذلك العمود.
 */
class Setting extends Model
{
    use HasFactory;

    /**
     * مفاتيح روابط التواصل في الشريط العلوي.
     *
     * ثابتة في مكان واحد: الكنترولر يتحقّق منها، والبذرة تُنشئها، والعرض
     * يقرؤها. ثلاث نسخ من نفس القائمة تتباعد؛ نسخة واحدة لا تفعل.
     */
    public const NAVBAR_SOCIAL_KEYS = [
        'navbar.social_link1',
        'navbar.social_link2',
        'navbar.social_link3',
    ];

    /**
     * بانر الدعوة الأصفر في الصفحة الرئيسية — مفاتيحه وقيمه الابتدائية.
     *
     * 🔴 مصدر واحد: البذرة تكتب منه، والكنترولر يتحقّق بمفاتيحه، والعرض
     * يقرأ منه عند غياب الصفّ. القيم هنا هي النصّ الذي كان مكتوباً في
     * lang/ar/home.php قبل أن يصير المحتوى قابلاً للتحرير.
     *
     * ⚠️ محتوى هذا البانر خرج من نظام الترجمة عمداً: صار محتوىً يحرّره
     * الأدمن كالشرائح، لا نصّ واجهة. دعم لغة ثانية له يحتاج عموداً
     * للّغة في settings — لا مفتاح ترجمة.
     *
     * 🔴 هذه القيم تُعرَض لزائر غير مسجّل، وهو بالضبط ما يجعلها آمنة في
     * جدول settings المبسّط الذي لا يملك عمود is_public بعد.
     */
    public const CTA_DEFAULTS = [
        'cta.title' => 'أول محاضرة من كل مادة — مجاناً',
        'cta.sub_title' => 'جرّب المنصة كاملة قبل أن تدفع شيكلاً واحداً، ودون بطاقة ائتمان.',
        'cta.btn_text' => 'ابدأ التجربة',
        'cta.btn_link' => '/register',
    ];

    /**
     * ترويسة قسم «آلية العمل» في الصفحة الرئيسية.
     *
     * ⚠️ الترويسة وحدها هنا. الخطوات نفسها صفوف في جدول steps لأنها
     * قائمة متغيّرة الطول تُضاف وتُحذف — ومفتاح/قيمة يمثّل حقلاً واحداً
     * لا مجموعة صفوف.
     */
    public const HOW_DEFAULTS = [
        'how.badge_text' => 'آلية العمل',
        'how.title' => 'ثلاث خطوات تفصلك عن أول اختبار',
        'how.sub_title' => 'من إنشاء الحساب إلى أول تقييم — أقل من خمس دقائق.',
    ];

    /**
     * "About us" section. Text extracted from the section as it stands in
     * the codebase (lang/ar/home.php + pages/home.blade.php), which is the
     * live source of what visitors see today.
     *
     * ⚠️ The three checklist items (about_check_1..3) stay in the language
     * file: they were not part of the requested field list, and a variable
     * length list does not fit a fixed key/value form.
     */
    public const ABOUT_DEFAULTS = [
        'about.badge_text' => 'من نحن',
        'about.title1' => 'بنيناها لأننا عبرنا هذا الطريق',
        'about.content1' => 'ما ينقص طالب التوجيهي ليس المحتوى، بل ترتيبه. يفتح الإنترنت فيجد آلاف المقاطع، أكثرها خارج فرعه، ولا شيء فيها يقيس إن كان قد فهم فعلاً — فيمضي وقته في البحث قبل أن يبدأ الدراسة.',
        'about.title2' => 'رسالتنا',
        'about.content2' => 'أن نُبعد عن طريق الطالب كل ما لا يخصّه، ونضع أمامه مساره وحده: واضحاً، ومرتّباً، ومقيساً بعد كل خطوة.',
        'about.title_box' => 'جرّب أول محاضرة من كل مادة في فرعك مجاناً، ثم قرّر.',
        'about.btn_text' => 'ابدأ مجاناً',
        'about.btn_link' => '/register',
        'about.img' => 'images/about/classroom.jpg',
    ];

    /**
     * "Why us" section header and its two buttons.
     *
     * ⚠️ The counter cards on the other side stay static, as requested.
     * So do the three checklist items — same reason as ABOUT_DEFAULTS.
     */
    public const WHY_US_DEFAULTS = [
        'why_us.badge_text' => 'لماذا نحن',
        'why_us.title' => 'محتوى حقيقي، لا وعود',
        'why_us.description' => 'كل رقم هنا يمثّل محتوى رفعه معلّم يدرّس هذه المادة فعلاً — مصوّراً خصيصاً لمنهج التوجيهي الفلسطيني، ومرتبطاً باختبار وضعه صاحب الشرح نفسه.',
        'why_us.btn1_text' => 'ابدأ مجاناً',
        'why_us.btn1_link' => '/register',
        'why_us.btn2_text' => 'تصفّح الفروع',
        'why_us.btn2_link' => '#branches',
    ];

    /**
     * Branches section HEADER only.
     *
     * 🔴 The cards themselves are rows in the `branches` table, not keys
     * here: they carry a slug, an icon, a colour token, an order and a
     * soft delete flag. Flattening four records into key/value pairs would
     * mean twenty-odd keys with the record boundary living in the string.
     */
    public const BRANCHES_DEFAULTS = [
        'branches.badge_text' => 'اختر مسارك',
        'branches.title' => 'لكل فرع عالمه الخاص',
        'branches.sub_title' => 'حدّد فرعك مرّة واحدة، فتجد بعدها مواده ومعلّميه ومكتبته وحدها — لا شيء سواها.',
    ];

    /**
     * "Quick tour" video band.
     *
     * ⏳ video_path ships empty: decision M-5 (video provider) is still
     * open, and the player in this section is a placeholder until it is
     * settled. An uploaded file here is served from the public disk — fine
     * for a marketing trailer, never for lecture content.
     */
    public const VIDEO_TOUR_DEFAULTS = [
        'video_tour.badge_text' => 'جولة سريعة',
        'video_tour.title' => 'شاهد المنصة قبل أن تسجّل',
        'video_tour.sub_title' => 'دقيقتان تشرحان كل شيء: كيف تحدّد فرعك، وكيف تتابع محاضرتك، وكيف يُفتح الاختبار تلقائياً بعدها.',
        'video_tour.video_path' => '',
    ];

    /**
     * "What sets us apart" section header.
     *
     * 🔴 Header only. The four cards are rows in the `features` table: they
     * carry an icon name, a colour token, an order and a soft delete, and
     * flattening a variable-length list into key/value pairs would put the
     * record boundary inside the key string.
     */
    public const FEATURES_DEFAULTS = [
        'features.badge_text' => 'ما يميّزنا',
        'features.title' => 'أربعة قرارات تصنع الفرق',
    ];

    /**
     * Pricing section header and the link through to the full comparison.
     *
     * 🔴 Header only. The tier cards are rows in `pricing_plans` with their
     * own screens: a price, a JSON bullet list, an order and a soft delete.
     *
     * ⚠️ No "prices are in shekels…" note key here, deliberately. That line
     * was removed from the page rather than moved, so re-adding it as a
     * setting would quietly bring it back.
     */
    public const PRICING_DEFAULTS = [
        'pricing.badge_text' => 'الأسعار',
        'pricing.title' => 'ادفع لما تحتاجه فقط',
        'pricing.sub_title' => 'اشترك بالمادة الواحدة، أو افتح فرعك كاملاً بسعر أوفر — بلا رسوم خفيّة ولا تجديد تلقائي.',
        'pricing.btn_text' => 'قارن الاشتراكين بالتفصيل',
        'pricing.btn_link' => '/pricing',
    ];

    /**
     * Teachers section header and the link through to the full roster.
     *
     * 🔴 Header only. The cards are `teachers` rows, and everything shown on
     * them is DERIVED: the portrait from the polymorphic media relation, the
     * branch through the teacher's subjects, the lecture count from the
     * lectures table. None of it is copy anyone types here.
     */
    public const TEACHERS_DEFAULTS = [
        'teachers.badge_text' => 'نخبة المعلّمين',
        'teachers.title' => 'معلّمون تعرفهم من صفوفهم',
        'teachers.sub_title' => 'كل معلّم مسؤول عن مادته وحدها: يرفع محاضراتها، ويضع اختباراتها، ويتابع نتائج طلبتها.',
        'teachers.btn_text' => 'تعرّف على جميع المعلّمين',
        'teachers.btn_link' => '/teachers',
    ];

    /**
     * Testimonials section header.
     *
     * 🔴 Header only. The reviews themselves are `testimonials` rows with
     * their own screens, and the reviewer's name, branch and city are read
     * from the linked student account — none of it is typed here.
     */
    public const TESTIMONIALS_DEFAULTS = [
        'testimonials.badge_text' => 'آراء الطلبة',
        'testimonials.title' => 'ماذا قال من جرّبها',
        'testimonials.sub_title' => 'طلبة من فروع ومدن مختلفة، هذا ما قالوه بعد فصل دراسي كامل على المنصة.',

        /*
         * The "add your review" button.
         *
         * 🔴 btn_text EMPTY hides the button entirely — that is the off
         * switch, and it is why the field is nullable.
         *
         * ⚠️ btn_link EMPTY is the normal case: the button opens the
         * submission modal in place. Filling it turns the button into an
         * ordinary link to that destination instead, for sending people to
         * a form elsewhere without touching code.
         */
        'testimonials.btn_text' => 'أضف رأيك',
        'testimonials.btn_link' => '',
    ];

    /**
     * FAQ section header and its "still stuck?" contact link.
     *
     * 🔴 Header only. The questions are `faqs` rows with their own screens:
     * they carry an order, a visibility flag and a soft delete, and a
     * variable-length list does not fit fixed key/value pairs.
     */
    public const FAQ_DEFAULTS = [
        'faq.badge_text' => 'أسئلة شائعة',
        'faq.title' => 'أسئلة تدور في ذهن كل طالب',
        'faq.sub_title' => 'لم تجد إجابتك؟ راسلنا وسنردّ خلال يوم عمل واحد.',
        'faq.btn_text' => 'تواصل معنا',
        'faq.btn_link' => '/contact',
    ];

    /**
     * The closing call to action, below the FAQ.
     *
     * `sub_title2` is the reassurance line under the button ("no card
     * needed…"), kept separate from `sub_title` because it sits below the
     * button rather than above it and reads at a different size.
     */
    /**
     * The public news page header.
     *
     * ⚠️ Header copy ONLY. The articles themselves live in the `news` table —
     * settings hold what surrounds a list, never the list.
     */
    public const NEWS_DEFAULTS = [
        'news.badge_text' => 'أخبارنا',
        'news.title' => 'آخر المستجدّات',
        'news.sub_title' => 'قرارات الوزارة، وتعديلات المناهج، وكل جديد على المنصّة — في مكان واحد.',
    ];

    public const FINAL_CTA_DEFAULTS = [
        'final_cta.title' => 'فرعك بانتظارك',
        'final_cta.sub_title' => 'سجّل مجاناً وشاهد أول محاضرة من كل مادة قبل أن تقرّر.',
        'final_cta.btn_text' => 'أنشئ حسابك المجاني',
        'final_cta.btn_link' => '/register',
        'final_cta.sub_title2' => 'دون بطاقة ائتمان · يمكنك الإلغاء في أي وقت',
    ];

    /**
     * Footer copy and its four navigation columns.
     *
     * 🔁 REVERSAL, recorded. Each column's links used to live in ONE key as a
     * newline list of `Label | /href`. That was compact, but it made every
     * link invisible to validation — a malformed line was silently rewritten
     * on read instead of refused on save, and an admin had to learn a syntax
     * to edit a footer. Explicit keys were requested and are what ship: one
     * field per label, one per destination, each validated on the way in.
     *
     * ⚠️ FOUR link slots per column, uniformly, even though columns 3 and 4
     * only use three today. A fixed grid keeps the key names predictable and
     * lets a column grow without a migration; an empty text field simply
     * drops that row from the page.
     */
    public const FOOTER_DEFAULTS = [
        'footer.newsletter_title' => 'كن أول من يعرف',
        'footer.newsletter_sub_title' => 'مستجدّات الوزارة، ومواعيد الامتحانات، والمواد الجديدة على المنصة — رسالة واحدة أسبوعياً، دون حشو.',
        'footer.copyright_text' => 'جميع الحقوق محفوظة — pal education :year',
        'footer.made_in_text' => 'صُنع في فلسطين',

        // ── العمود الأول · المنصة ──────────────────────────────
        'footer.col1_title' => 'المنصة',
        'footer.col1_link1_text' => 'الفروع',
        'footer.col1_link1_url' => '/branches',
        'footer.col1_link2_text' => 'الأسعار',
        'footer.col1_link2_url' => '/pricing',
        'footer.col1_link3_text' => 'المكتبة',
        'footer.col1_link3_url' => '#',
        'footer.col1_link4_text' => 'المعلّمون',
        'footer.col1_link4_url' => '#',

        // ── العمود الثاني · عن المنصة ──────────────────────────
        'footer.col2_title' => 'عن المنصة',
        'footer.col2_link1_text' => 'من نحن',
        'footer.col2_link1_url' => '/#about',
        'footer.col2_link2_text' => 'الأخبار',
        'footer.col2_link2_url' => '/news',
        'footer.col2_link3_text' => 'انضم كمعلّم',
        'footer.col2_link3_url' => '#',
        'footer.col2_link4_text' => 'تواصل معنا',
        'footer.col2_link4_url' => '/contact',

        // ── العمود الثالث · الدعم ──────────────────────────────
        // Slot 4 ships empty: three links today, room for a fourth.
        'footer.col3_title' => 'الدعم',
        'footer.col3_link1_text' => 'مركز المساعدة',
        'footer.col3_link1_url' => '#',
        'footer.col3_link2_text' => 'الأسئلة الشائعة',
        'footer.col3_link2_url' => '/#faq',
        'footer.col3_link3_text' => 'الإبلاغ عن مشكلة',
        'footer.col3_link3_url' => '#',
        'footer.col3_link4_text' => '',
        'footer.col3_link4_url' => '',

        // ── العمود الرابع · قانوني ─────────────────────────────
        'footer.col4_title' => 'قانوني',
        'footer.col4_link1_text' => 'سياسة الخصوصية',
        'footer.col4_link1_url' => '#',
        'footer.col4_link2_text' => 'الشروط والأحكام',
        'footer.col4_link2_url' => '#',
        'footer.col4_link3_text' => 'سياسة الاسترجاع',
        'footer.col4_link3_url' => '#',
        'footer.col4_link4_text' => '',
        'footer.col4_link4_url' => '',
    ];

    /** The four footer columns, in order. */
    public const FOOTER_COLUMNS = ['col1', 'col2', 'col3', 'col4'];

    /** Link slots per column. Uniform so the key names stay predictable. */
    public const FOOTER_LINKS_PER_COLUMN = 4;

    /**
     * The contact page — header, channels, hours and the FAQ deflection.
     *
     * 🔴 `whatsapp` and `phone` are SEPARATE keys even though they ship with
     * the same number today. They are different channels with different
     * link schemes (wa.me vs tel:), and a business that moves its WhatsApp
     * to a second line should not have to change its landline to do it.
     *
     * ⚠️ working_hours is free text, one row per line. `Label: value` splits
     * on the FIRST colon — Arabic clock times contain colons of their own,
     * so splitting on the last or on every colon would cut "9:00 ص" in half.
     * A line with no colon renders as a note under the rows.
     */
    public const CONTACT_DEFAULTS = [
        'contact.badge_text' => 'تواصل معنا',
        'contact.title' => 'كيف نقدر نساعدك؟',
        'contact.sub_title' => 'سؤال عن اشتراك، مشكلة تقنية، أو اقتراح — اكتب لنا ونردّ عادةً خلال يوم عمل واحد.',

        'contact.whatsapp' => '+970 599 123 456',
        'contact.phone' => '+970 599 123 456',
        'contact.email' => 'support@paleducation.ps',
        'contact.address' => 'رام الله — شارع الإرسال، عمارة النخيل، ط5',

        'contact.working_hours' => "السبت — الخميس: 9:00 ص — 6:00 م\nالجمعة: مغلق\nرسائل واتساب خارج الدوام يُرَدّ عليها صباح اليوم التالي.",

        'contact.faq_notice' => 'معظم الأسئلة عن الاشتراكات والدفع مُجابة في صفحة الأسعار — قد توفّر على نفسك انتظار الردّ.',
        'contact.faq_btn_text' => 'الأسئلة الشائعة',
        'contact.faq_btn_link' => '/#faq',
    ];

    /**
     * تبويبات صفحة الإعدادات.
     *
     * 🔴 مصدر واحد: شريط التبويبات، واللوح المعروض، والمسار الذي يحفظه —
     * ثلاثتها تُبنى من هنا. إضافة تبويب = صفّ واحد + جزئية باسمه، بلا لمس
     * الشريط ولا الـJS.
     *
     * `partial` اسم جزئية تحت resources/views/admin/settings/tabs/.
     */
    public const GROUPS = [
        'navbar' => [
            'label' => 'الشريط العلوي',
            'icon' => 'link',
            'partial' => 'navbar',
            'route' => 'admin.settings.navbar.update',
        ],
        'cta' => [
            'label' => 'بانر الدعوة',
            'icon' => 'sparkle',
            'partial' => 'cta',
            'route' => 'admin.settings.cta.update',
        ],
        'how' => [
            'label' => 'آلية العمل',
            'icon' => 'compass',
            'partial' => 'how',
            'route' => 'admin.settings.how.update',
        ],
        'about' => [
            'label' => 'من نحن',
            'icon' => 'users',
            'partial' => 'about',
            'route' => 'admin.settings.about.update',
        ],
        'why_us' => [
            'label' => 'لماذا نحن',
            'icon' => 'shield',
            'partial' => 'why-us',
            'route' => 'admin.settings.why-us.update',
        ],
        'branches' => [
            'label' => 'الفروع',
            'icon' => 'book',
            'partial' => 'branches',
            'route' => 'admin.settings.branches.update',
        ],
        'features' => [
            'label' => 'ما يميّزنا',
            'icon' => 'sparkle',
            'partial' => 'features',
            'route' => 'admin.settings.features.update',
        ],
        'teachers' => [
            'label' => 'المعلّمون',
            'icon' => 'users',
            'partial' => 'teachers',
            'route' => 'admin.settings.teachers.update',
        ],
        'testimonials' => [
            'label' => 'آراء الطلبة',
            'icon' => 'sparkle',
            'partial' => 'testimonials',
            'route' => 'admin.settings.testimonials.update',
        ],
        'faq' => [
            'label' => 'الأسئلة الشائعة',
            'icon' => 'alert',
            'partial' => 'faq',
            'route' => 'admin.settings.faq.update',
        ],
        'contact' => [
            'label' => 'تواصل معنا',
            'icon' => 'mail',
            'partial' => 'contact',
            'route' => 'admin.settings.contact.update',
        ],
        'final_cta' => [
            'label' => 'الدعوة الختامية',
            'icon' => 'sparkle',
            'partial' => 'final-cta',
            'route' => 'admin.settings.final-cta.update',
        ],
        'footer' => [
            'label' => 'التذييل',
            'icon' => 'link',
            'partial' => 'footer',
            'route' => 'admin.settings.footer.update',
        ],
        'pricing' => [
            'label' => 'الأسعار',
            'icon' => 'wallet',
            'partial' => 'pricing',
            'route' => 'admin.settings.pricing.update',
        ],
        'news' => [
            'label' => 'أخبارنا',
            'icon' => 'clipboard',
            'partial' => 'news',
            'route' => 'admin.settings.news.update',
        ],
        'video_tour' => [
            'label' => 'جولة سريعة',
            'icon' => 'play',
            'partial' => 'video-tour',
            'route' => 'admin.settings.video-tour.update',
        ],
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    /*
    |--------------------------------------------------------------------------
    | القراءة والكتابة
    |--------------------------------------------------------------------------
    */

    /** قيمة مفتاح، أو البديل إن لم يوجد. */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * كتابة مفتاح — إنشاءً أو تحديثاً.
     *
     * updateOrCreate لا create: المفتاح فريد، ومسار كتابة يفترض الغياب
     * يفشل في المرة الثانية.
     */
    public static function put(string $key, ?string $value): self
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * كل الإعدادات كخريطة مفتاح ⇒ قيمة.
     *
     * استعلام واحد بدل نداء get() لكل مفتاح — التذييل وحده يقرأ خمسة.
     */
    public static function map(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }

    /** محتوى بانر الدعوة: title · sub_title · btn_text · btn_link. */
    public static function cta(): array
    {
        return self::resolve(self::CTA_DEFAULTS);
    }

    /**
     * ترويسة قسم «آلية العمل»: badge_text · title · sub_title.
     *
     * الخطوات نفسها ليست هنا — Step::active()->ordered().
     */
    public static function how(): array
    {
        return self::resolve(self::HOW_DEFAULTS);
    }

    /** "About us" section: badge_text · title1 · content1 · title2 · content2 · title_box · btn_text · btn_link · img */
    public static function about(): array
    {
        return self::resolve(self::ABOUT_DEFAULTS);
    }

    /** "Why us" section header: badge_text · title · description · btn1_* · btn2_* */
    public static function whyUs(): array
    {
        return self::resolve(self::WHY_US_DEFAULTS);
    }

    /** Branches section HEADER only — the cards are Branch records. */
    public static function branchesHeader(): array
    {
        return self::resolve(self::BRANCHES_DEFAULTS);
    }

    /** "What sets us apart" header: badge_text · title */
    public static function features(): array
    {
        return self::resolve(self::FEATURES_DEFAULTS);
    }

    /** Teachers header: badge_text · title · sub_title · btn_text · btn_link */
    public static function teachers(): array
    {
        return self::resolve(self::TEACHERS_DEFAULTS);
    }

    /** Testimonials header: badge_text · title · sub_title */
    public static function testimonials(): array
    {
        return self::resolve(self::TESTIMONIALS_DEFAULTS);
    }

    /** FAQ header: badge_text · title · sub_title · btn_text · btn_link */
    public static function faq(): array
    {
        return self::resolve(self::FAQ_DEFAULTS);
    }

    /** Contact page: header · channels · working_hours · FAQ deflection. */
    public static function contact(): array
    {
        return self::resolve(self::CONTACT_DEFAULTS);
    }

    /**
     * Working hours as rows — [label, value] pairs plus free notes.
     *
     * ⚠️ Splits on the FIRST colon only. Arabic clock times carry their own
     * colons ("9:00 ص — 6:00 م"), so splitting on the last one, or on every
     * one, would cut the time in half.
     *
     * @return array<int, array{label: ?string, value: string}>
     */
    public static function workingHoursRows(): array
    {
        $raw = self::contact()['working_hours'];
        $rows = [];

        foreach (preg_split('/\r\n|\r|\n/', (string) $raw) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $position = mb_strpos($line, ':');

            // No colon: a standalone note, not a label/value row.
            if ($position === false) {
                $rows[] = ['label' => null, 'value' => $line];

                continue;
            }

            $rows[] = [
                'label' => trim(mb_substr($line, 0, $position)),
                'value' => trim(mb_substr($line, $position + 1)),
            ];
        }

        return $rows;
    }

    /**
     * A phone number reduced to what tel: and wa.me will accept.
     *
     * 🔴 Both reject spaces, dashes and parentheses, and wa.me additionally
     * rejects the leading plus. Passing the display string straight into
     * either produces a link that silently does nothing on a real phone.
     */
    public static function dialable(?string $number, bool $keepPlus = true): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        return $keepPlus && $digits !== '' ? '+'.$digits : (string) $digits;
    }

    /** Public news page header: badge_text · title · sub_title */
    public static function news(): array
    {
        return self::resolve(self::NEWS_DEFAULTS);
    }

    /** Closing CTA: title · sub_title · btn_text · btn_link · sub_title2 */
    public static function finalCta(): array
    {
        return self::resolve(self::FINAL_CTA_DEFAULTS);
    }

    /** Footer copy — the link columns come from footerColumns(). */
    public static function footer(): array
    {
        return self::resolve(self::FOOTER_DEFAULTS);
    }

    /**
     * The four footer columns as [title, links[]] — ready to render.
     *
     * 🔴 Built from EXPLICIT keys, one per label and one per destination.
     * The previous version parsed a `Label | /href` list out of a single
     * textarea, which meant a malformed line was quietly rewritten on read
     * rather than refused on save.
     *
     * ⚠️ A link with no text is skipped, and a column with no title or no
     * surviving links is dropped entirely — a lone heading over empty space
     * is worse than no column.
     *
     * @return array<int, array{title: string, links: array<int, array{label: string, href: string}>}>
     */
    public static function footerColumns(): array
    {
        $footer = self::footer();
        $columns = [];

        foreach (self::FOOTER_COLUMNS as $column) {
            $links = [];

            for ($slot = 1; $slot <= self::FOOTER_LINKS_PER_COLUMN; $slot++) {
                $label = trim((string) ($footer["{$column}_link{$slot}_text"] ?? ''));

                if ($label === '') {
                    continue;
                }

                $links[] = [
                    'label' => $label,
                    'href' => self::safeHref($footer["{$column}_link{$slot}_url"] ?? ''),
                ];
            }

            if ($links === [] || blank($footer[$column.'_title'] ?? '')) {
                continue;
            }

            $columns[] = ['title' => $footer[$column.'_title'], 'links' => $links];
        }

        return $columns;
    }

    /**
     * A destination safe to put in an href, or `#`.
     *
     * 🔴 Kept even though the form now validates each URL on save. These
     * render on EVERY public page, and rows written before that validation
     * existed — or straight into the database — would otherwise reach the
     * browser unchecked. Validation refuses bad input; this makes bad
     * *stored* data harmless.
     */
    public static function safeHref(?string $href): string
    {
        $href = trim((string) $href);

        return preg_match('/^(#|\/|https?:\/\/)/', $href) === 1 ? $href : '#';
    }

    /** Pricing header: badge_text · title · sub_title · btn_text · btn_link */
    public static function pricing(): array
    {
        return self::resolve(self::PRICING_DEFAULTS);
    }

    /** "Quick tour" band: badge_text · title · sub_title · video_path */
    public static function videoTour(): array
    {
        return self::resolve(self::VIDEO_TOUR_DEFAULTS);
    }

    /**
     * يقرأ مجموعة مفاتيح ويُرجعها بأسماء قصيرة بلا بادئة المجموعة.
     *
     * ⚠️ الافتراضي يُطبَّق عند **غياب الصفّ** لا عند خلوّ قيمته. الفرق
     * ليس تفصيلاً: أدمن يمسح حقلاً اختيارياً يريد إخفاءه، فإعادة النصّ
     * الافتراضي مكانه تجعل الحقل يبدو معطّلاً. الصفّ الغائب وحده يعني
     * «لم يُبذَر بعد».
     *
     * @param  array<string, string>  $defaults  مفاتيح كاملة (group.field) ⇐ قيمة
     * @return array<string, string>
     */
    /** مفتاح الربط في الحاوية — يُقرأ من AppServiceProvider أيضاً. */
    public const MAP_BINDING = 'settings.map';

    /**
     * كل الإعدادات، مقروءة **مرة واحدة لكل طلب**.
     *
     * 🔴 صفحة عامة واحدة تستدعي resolve() ثلاث مرات على الأقل (الشريط
     * العلوي · التذييل · الدعوة الختامية)، وكل استدعاء كان استعلاماً
     * منفصلاً. الجدول عشرات الصفوف لا آلاف، فقراءته كاملاً مرة واحدة أرخص
     * من ثلاث قراءات مُصفّاة — وتُبقي الميزانية ثابتة مهما زادت الأقسام.
     *
     * ⚠️ ربط **scoped** في الحاوية لا خاصية static. الفرق ليس أسلوبياً:
     * الخاصية الساكنة تعيش ما دامت العملية حيّة، فتبقى قديمة بين مهام
     * الطابور، وبين حالات الاختبار في نفس العملية — وهو ما أسقط اختبارين
     * فعلاً قبل هذا التصحيح. الربط المُنطَّق تُفرّغه Laravel بين الطلبات
     * وبين مهام الطابور، وكل اختبار يحصل على حاوية جديدة.
     *
     * وذاكرة طلب لا cache دائم: قيمة يعدّلها الأدمن تظهر في الطلب التالي
     * مباشرة، لا بعد انتهاء مدّة صلاحية.
     */
    public static function flushRequestCache(): void
    {
        app()->forgetInstance(self::MAP_BINDING);
    }

    /**
     * 🔴 على أحداث النموذج لا داخل put(): نصف الكتابات في المشروع تمرّ بـ
     * updateOrCreate مباشرةً (البذور، الكنترولرز، الاختبارات)، وإبطال مربوط
     * بدالة واحدة كان سيترك تلك المسارات تقرأ قيمة قديمة **بصمت** — وهو
     * بالضبط ما يجعل حفظ إعداد يبدو بلا أثر.
     */
    protected static function booted(): void
    {
        static::saved(fn () => static::flushRequestCache());
        static::deleted(fn () => static::flushRequestCache());
    }

    /** @return array<string, string> */
    private static function allValues(): array
    {
        return app(self::MAP_BINDING);
    }

    private static function resolve(array $defaults): array
    {
        $stored = static::allValues();

        $content = [];

        foreach ($defaults as $key => $default) {
            $short = substr($key, strpos($key, '.') + 1);
            $content[$short] = array_key_exists($key, $stored)
                ? (string) $stored[$key]
                : $default;
        }

        return $content;
    }
}
