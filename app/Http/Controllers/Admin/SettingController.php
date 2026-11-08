<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Step;
use App\Notifications\CriticalSettingChangedNotification;
use App\Support\AdminNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * الإعدادات — صفحة واحدة بتبويبات.
 *
 * 🔴 لا شاشة نظرة عامة ولا شاشة لكل قسم: صفحة واحدة تعرض كل الألواح،
 * والتبويب يبدّل بينها على العميل. كل لوح يحفظ عبر مساره الخاص ويعود
 * إلى نفس التبويب.
 */
class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'groups' => Setting::GROUPS,
            'settings' => Setting::map(),

            /*
             * ⚠️ خاصّ بلوح «آلية العمل» وحده، لكنه يُمرَّر على مستوى
             * الصفحة: حلقة @include تمرّر مجموعة ثابتة لكل لوح، والبيانات
             * الموروثة من العرض الأب تصل الجزئيات كلها. لوح واحد يقرؤه.
             *
             * غير المحذوفة كلها لا active() فقط: الأدمن يجب أن يرى ما
             * يديره، وإخفاء صفّ من شاشة إدارته يجعله غير قابل للإصلاح.
             */
            'steps' => Step::ordered()->get(),

        ]);
    }

    public function updateNavbar(Request $request): RedirectResponse
    {
        // starts_with:https مقصود لا url وحدها: رابط غير مشفّر في شريط علوي
        // يظهر لكل زائر يُنتج تحذير متصفّح على صفحة سليمة.
        $validated = $this->validateForTab($request, 'navbar', [
            'social_link1' => ['nullable', 'url', 'starts_with:https://', 'max:500'],
            'social_link2' => ['nullable', 'url', 'starts_with:https://', 'max:500'],
            'social_link3' => ['nullable', 'url', 'starts_with:https://', 'max:500'],
        ], [
            'starts_with' => __('admin.setting_https_only'),
        ]);

        return $this->persist('navbar', $validated, Setting::NAVBAR_SOCIAL_KEYS);
    }

    /**
     * بانر الدعوة — العنوان والنصّ التمهيدي ونصّ الزرّ ووجهته.
     *
     * 🔴 العنوان ونصّ الزرّ ووجهته إجبارية: البانر عنصر تخطيط ثابت في
     * الصفحة الرئيسية، وحقل فارغ منها يُنتج عنواناً خالياً أو زرّاً بلا
     * نصّ أمام كل زائر. النصّ التمهيدي وحده اختياري — إخفاؤه قرار تصميم
     * مشروع، والعرض يُسقط الفقرة كلّها عند خلوّه.
     */
    public function updateCta(Request $request): RedirectResponse
    {
        $validated = $this->validateForTab($request, 'cta', [
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],
            'btn_text' => ['required', 'string', 'max:40'],
            /*
             * ⚠️ ليست `url`: الوجهة قد تكون مساراً داخلياً (/register) أو
             * مرساة (#pricing)، وكلاهما يسقط في قاعدة url. والقيد الحقيقي
             * ليس الشكل بل **المخطَّط**: هذه القيمة تهبط في href على صفحة
             * عامة، فـjavascript: أو data: فيها تصير تنفيذاً في متصفّح كل
             * زائر. حصر البداية في ثلاث صيغ يغلق ذلك عند المصدر لا عند العرض.
             */
            'btn_link' => ['required', 'string', 'max:500', self::LINK_RULE],
        ], [
            'btn_link.regex' => __('admin.setting_link_scheme'),
        ]);

        return $this->persist('cta', $validated, array_keys(Setting::CTA_DEFAULTS));
    }

    /**
     * ترويسة «آلية العمل» — الشارة والعنوان والنصّ التمهيدي.
     *
     * الخطوات لا تُحفَظ هنا: لها StepController لأن عددها متغيّر، ودمج
     * قائمة متغيّرة الطول في نموذج ترويسة واحد يجعل الحفظ الجزئي مستحيلاً
     * — تعديل خطوة كان سيُرسل الترويسة والخطوات الثلاث كلّها معاً.
     */
    public function updateHow(Request $request): RedirectResponse
    {
        $validated = $this->validateForTab($request, 'how', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],
        ]);

        return $this->persist('how', $validated, array_keys(Setting::HOW_DEFAULTS));
    }

    /**
     * "About us" section.
     *
     * The image is handled apart from the text keys because a file input
     * that was left empty must NOT blank the stored path — an admin fixing
     * a typo in the heading would silently drop the section's photo.
     */
    public function updateAbout(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'about', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title1' => ['required', 'string', 'max:150'],
            'content1' => ['nullable', 'string', 'max:1000'],
            'title2' => ['required', 'string', 'max:150'],
            'content2' => ['nullable', 'string', 'max:1000'],
            'title_box' => ['nullable', 'string', 'max:300'],
            'btn_text' => ['required', 'string', 'max:40'],
            'btn_link' => ['required', 'string', 'max:500', self::LINK_RULE],
            'img' => ['nullable', 'image', 'max:4096'],
        ], [
            'btn_link.regex' => __('admin.setting_link_scheme'),
        ]);

        $data['img'] = $this->storedFile($request, 'about', 'img', Setting::get('about.img'));

        return $this->persist('about', $data, array_keys(Setting::ABOUT_DEFAULTS));
    }

    /**
     * "Why us" section header.
     *
     * The counter cards beside it stay static on purpose — they are four
     * hardcoded figures in the template, and wiring them here would put a
     * claim about platform size behind an unvalidated text box.
     */
    public function updateWhyUs(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'why_us', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'btn1_text' => ['required', 'string', 'max:40'],
            'btn1_link' => ['required', 'string', 'max:500', self::LINK_RULE],
            'btn2_text' => ['nullable', 'string', 'max:40'],
            'btn2_link' => ['nullable', 'string', 'max:500', self::LINK_RULE],
        ], [
            'btn1_link.regex' => __('admin.setting_link_scheme'),
            'btn2_link.regex' => __('admin.setting_link_scheme'),
        ]);

        return $this->persist('why_us', $data, array_keys(Setting::WHY_US_DEFAULTS));
    }

    /**
     * "What sets us apart" header. The four cards are Feature records with
     * their own screens under /admin/features.
     */
    public function updateFeatures(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'features', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
        ]);

        return $this->persist('features', $data, array_keys(Setting::FEATURES_DEFAULTS));
    }

    /**
     * The contact page — header, channels, hours and the FAQ deflection.
     *
     * 🔴 At least one channel is required. A contact page whose every route
     * has been cleared is a page inviting people to get in touch with no way
     * to do it, and nothing else on the screen would say so.
     */
    public function updateContact(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'contact', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],

            /*
             * ⚠️ Not `regex` on a phone shape. Numbers here are written for
             * humans (+970 599 123 456) and the dial-safe form is derived at
             * render time, so the stored value only has to be readable —
             * over-validating it would reject a perfectly good local format.
             */
            'whatsapp' => ['nullable', 'required_without_all:contact.phone,contact.email', 'string', 'max:40'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],

            'working_hours' => ['nullable', 'string', 'max:600'],

            'faq_notice' => ['nullable', 'string', 'max:400'],
            'faq_btn_text' => ['nullable', 'required_with:contact.faq_btn_link', 'string', 'max:80'],
            'faq_btn_link' => ['nullable', 'required_with:contact.faq_btn_text', 'string', 'max:500', self::LINK_RULE],
        ], [
            'whatsapp.required_without_all' => 'اترك وسيلة تواصل واحدة على الأقل: واتساب أو هاتف أو بريد.',
            'faq_btn_text.required_with' => 'النصّ والوجهة يُملآن معاً.',
            'faq_btn_link.required_with' => 'النصّ والوجهة يُملآن معاً.',
            'faq_btn_link.regex' => __('admin.setting_link_scheme'),
        ]);

        return $this->persist('contact', $data, array_keys(Setting::CONTACT_DEFAULTS));
    }

    /**
     * The closing call to action, below the FAQ.
     *
     * 🔴 Title, button text and destination are required: this band is a
     * fixed layout element on every visit, and an empty one is a blank
     * stripe with a dead button in the last thing a visitor sees.
     */
    public function updateFinalCta(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'final_cta', [
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],
            'btn_text' => ['required', 'string', 'max:80'],
            'btn_link' => ['required', 'string', 'max:500', self::LINK_RULE],
            'sub_title2' => ['nullable', 'string', 'max:200'],
        ], [
            'btn_link.regex' => __('admin.setting_link_scheme'),
        ]);

        return $this->persist('final_cta', $data, array_keys(Setting::FINAL_CTA_DEFAULTS));
    }

    /**
     * Footer copy and its four link columns.
     *
     * 🔁 Each link is now TWO explicit fields rather than a line inside a
     * textarea, so every destination is validated on the way in and a bad
     * one is refused with a message instead of silently rewritten on read.
     *
     * ⚠️ A link's text and URL are required TOGETHER: text with no
     * destination renders a dead anchor, and a destination with no text
     * renders nothing at all while looking configured. Both empty is the
     * normal way to leave a slot unused.
     */
    public function updateFooter(Request $request): RedirectResponse
    {
        $rules = [
            'newsletter_title' => ['required', 'string', 'max:120'],
            'newsletter_sub_title' => ['nullable', 'string', 'max:400'],
            'copyright_text' => ['required', 'string', 'max:200'],
            'made_in_text' => ['nullable', 'string', 'max:120'],
        ];

        $messages = [];

        foreach (Setting::FOOTER_COLUMNS as $column) {
            $rules[$column.'_title'] = ['nullable', 'string', 'max:80'];

            for ($slot = 1; $slot <= Setting::FOOTER_LINKS_PER_COLUMN; $slot++) {
                $text = "{$column}_link{$slot}_text";
                $url = "{$column}_link{$slot}_url";

                /*
                 * 🔴 required_with references the FULLY QUALIFIED name.
                 * The rule keys get prefixed with the tab later, but the
                 * reference inside a rule is resolved against the request
                 * ROOT — so a bare "col1_link1_text" points at a top-level
                 * field that does not exist, and the rule silently never
                 * fires. No error, no message, just no validation.
                 */
                $rules[$text] = ['nullable', 'required_with:footer.'.$url, 'string', 'max:80'];
                $rules[$url] = ['nullable', 'required_with:footer.'.$text, 'string', 'max:500', self::LINK_RULE];

                // ⚠️ Keys go in UNPREFIXED: validateForTab() adds the tab
                // name itself, so writing "footer.…" here would produce
                // "footer.footer.…" and the message would never be found.
                $messages["{$text}.required_with"] = 'النصّ والوجهة يُملآن معاً.';
                $messages["{$url}.required_with"] = 'النصّ والوجهة يُملآن معاً.';
                $messages["{$url}.regex"] = __('admin.setting_link_scheme');
            }
        }

        $data = $this->validateForTab($request, 'footer', $rules, $messages);

        return $this->persist('footer', $data, array_keys(Setting::FOOTER_DEFAULTS));
    }

    /**
     * FAQ section header and its "still stuck?" contact link.
     *
     * ⚠️ Header only. The questions are `faqs` rows with their own screens.
     */
    public function updateFaq(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'faq', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],
            'btn_text' => ['nullable', 'required_with:btn_link', 'string', 'max:80'],
            'btn_link' => ['nullable', 'required_with:btn_text', 'string', 'max:500', self::LINK_RULE],
        ], [
            'btn_link.regex' => __('admin.setting_link_scheme'),
        ]);

        return $this->persist('faq', $data, array_keys(Setting::FAQ_DEFAULTS));
    }

    /**
     * Testimonials section header.
     *
     * ⚠️ Header only. The reviews are `testimonials` rows, and the
     * reviewer's name, branch and city are read from the linked student —
     * there is nothing here to type about an individual review.
     */
    public function updateTestimonials(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'testimonials', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],
            // Empty text hides the button; empty link means "open the modal".
            // Neither is an error, so neither is required.
            'btn_text' => ['nullable', 'string', 'max:60'],
            'btn_link' => ['nullable', 'string', 'max:500', self::LINK_RULE],
        ], [
            'btn_link.regex' => __('admin.setting_link_scheme'),
        ]);

        return $this->persist('testimonials', $data, array_keys(Setting::TESTIMONIALS_DEFAULTS));
    }

    /**
     * Teachers section header and its roster link.
     *
     * ⚠️ Header only. Everything on the cards themselves is derived from the
     * teachers, subjects, lectures and media tables — there is nothing here
     * to type about an individual teacher.
     */
    public function updateTeachers(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'teachers', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],
            'btn_text' => ['nullable', 'required_with:btn_link', 'string', 'max:80'],
            'btn_link' => ['nullable', 'required_with:btn_text', 'string', 'max:500', self::LINK_RULE],
        ], [
            'btn_link.regex' => __('admin.setting_link_scheme'),
        ]);

        return $this->persist('teachers', $data, array_keys(Setting::TEACHERS_DEFAULTS));
    }

    /**
     * Pricing section header and its comparison link.
     *
     * ⚠️ No note field. The "prices are in shekels…" line was removed from
     * the page entirely, not relocated — giving it a setting key here would
     * quietly put it back.
     */
    public function updatePricing(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'pricing', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],
            'btn_text' => ['nullable', 'required_with:btn_link', 'string', 'max:80'],
            'btn_link' => ['nullable', 'required_with:btn_text', 'string', 'max:500', self::LINK_RULE],
        ], [
            'btn_link.regex' => __('admin.setting_link_scheme'),
        ]);

        return $this->persist('pricing', $data, array_keys(Setting::PRICING_DEFAULTS));
    }

    /** Branches section header. The cards themselves are Branch records. */
    public function updateBranches(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'branches', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],
        ]);

        return $this->persist('branches', $data, array_keys(Setting::BRANCHES_DEFAULTS));
    }

    /**
     * "Quick tour" band.
     *
     * ⚠️ mimetypes, not just mimes: the extension is attacker controlled,
     * and this file is written under the document root where the server
     * decides how to execute what it finds.
     */
    /** Header copy for the public news page. */
    public function updateNews(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'news', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],
        ]);

        return $this->persist('news', $data, array_keys(Setting::NEWS_DEFAULTS));
    }

    public function updateVideoTour(Request $request): RedirectResponse
    {
        $data = $this->validateForTab($request, 'video_tour', [
            'badge_text' => ['required', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:150'],
            'sub_title' => ['nullable', 'string', 'max:300'],
            'video_path' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:51200'],
        ]);

        $data['video_path'] = $this->storedFile(
            $request, 'video_tour', 'video_path', Setting::get('video_tour.video_path'),
        );

        return $this->persist('video_tour', $data, array_keys(Setting::VIDEO_TOUR_DEFAULTS));
    }

    /*
    |--------------------------------------------------------------------------
    | مساعدات
    |--------------------------------------------------------------------------
    */

    /**
     * Key/value persistence — the project pattern, one row per field.
     *
     * 🔴 It loops over the DEFAULTS map, not over the request. A loop over
     * request input writes whatever the browser sent: an attacker (or a
     * stale form) adds one extra field and creates an arbitrary settings
     * row, and every value lands unvalidated. The key list is fixed in
     * code; the request only supplies values for keys we already declared.
     *
     * @param  array<string, mixed>  $data  validated values, short keys
     * @param  array<int, string>  $keys  full "group.field" keys this form owns
     */
    private function persist(string $tab, array $data, array $keys): RedirectResponse
    {
        /*
         * ⚠️ إشعار واحد لكل حفظ تبويب، لا واحد لكل مفتاح. الحفظ يكتب عشرات
         * الصفوف، وإشعار لكل منها يجعل الجرس عديم الفائدة بعد أول تعديل.
         *
         * والمُعدِّل مُستثنى: إخبار من عدّل الإعدادات بأنه عدّل الإعدادات ضجيج.
         */
        AdminNotifier::send(
            new CriticalSettingChangedNotification(
                Setting::GROUPS[$tab]['label'] ?? $tab,
                request()->user()?->displayName() ?: 'أدمن',
            ),
            except: request()->user(),
        );

        foreach ($keys as $key) {
            $field = substr($key, strpos($key, '.') + 1);

            // array_key_exists, not ??: a field absent from $data was not
            // part of this form, and must keep its stored value.
            if (array_key_exists($field, $data)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $data[$field]]);
            }
        }

        flash()->success('تم تحديث البيانات بنجاح.');

        return $this->backToTab($tab);
    }

    /**
     * Stores an uploaded file and returns its root-relative path, or the
     * current path when nothing was uploaded.
     *
     * ⚠️ Returns null (meaning "skip this key") rather than an empty string
     * when there is no upload and no stored value, so an untouched file
     * input never overwrites anything.
     */
    private function storedFile(Request $request, string $tab, string $field, ?string $current): ?string
    {
        $file = $request->file($tab.'.'.$field);

        if ($file === null) {
            return $current;
        }

        // 'custom' disk root is public/ — see config/filesystems.php. The
        // returned path is root-relative, which is what asset() expects.
        return $file->store('uploads/settings', 'custom');
    }

    /**
     * Allowed shapes for any admin-authored link rendered into an href on a
     * public page: internal path, in-page anchor, or absolute http(s).
     *
     * 🔴 Not a `url` rule: /register and #pricing are both valid targets
     * that `url` rejects. The real constraint is the SCHEME — javascript:
     * or data: here executes in every visitor's browser.
     */
    private const LINK_RULE = 'regex:/^(#|\/|https?:\/\/)/';

    /**
     * تحقّق يعيد — عند الفشل — إلى **لوح صاحب النموذج** لا إلى أول تبويب.
     *
     * ⚠️ السلوك الافتراضي `back()` يعتمد على ترويسة referer، وهي ما يبدّله
     * سكربت التبويبات في شريط العنوان. الاعتماد عليها يجعل مكان ظهور
     * الأخطاء رهناً بترويسة قد يحجبها المتصفّح — والنتيجة أخطاء تحقّق
     * تُومَض على لوح مخفيّ، فيبدو الحفظ كأنه لم يفعل شيئاً.
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @return array<string, mixed>
     */
    private function validateForTab(Request $request, string $tab, array $rules, array $messages = []): array
    {
        $validator = Validator::make(
            $request->all(),
            $this->prefixKeys($rules, $tab),
            $this->prefixMessageKeys($messages, $tab),
        );

        if ($validator->fails()) {
            throw (new ValidationException($validator))
                ->redirectTo($this->tabUrl($tab));
        }

        return data_get($validator->validated(), $tab, []);
    }

    /**
     * 🔴 حقول كل لوح تحت اسم اللوح: name="cta[title]" لا name="title".
     *
     * السبب بنيوي لا تجميلي: الألواح كلها تُرسَل في صفحة واحدة، و`old()`
     * و`$errors` نطاقهما الطلب كلّه لا النموذج. باسم مسطّح، فشل تحقّق في
     * لوح «بانر الدعوة» كان يملأ حقل «آلية العمل» بمُدخَل لوح آخر ويرسم
     * تحته خطأً ليس له — بصمت، لأن الحقلين يتشاركان كلمة title لا أكثر.
     * البادئة تجعل التصادم مستحيلاً بدل أن تجعله موثَّقاً.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function prefixKeys(array $rules, string $tab): array
    {
        $prefixed = [];

        foreach ($rules as $field => $rule) {
            $prefixed[$tab.'.'.$field] = $rule;
        }

        return $prefixed;
    }

    /**
     * ⚠️ مفتاح بلا نقطة اسم قاعدة تنطبق على كل الحقول (starts_with) —
     * تبقى كما هي. المفتاح المنقّط (btn_link.regex) حقلٌ يحتاج البادئة.
     *
     * @param  array<string, string>  $messages
     * @return array<string, string>
     */
    private function prefixMessageKeys(array $messages, string $tab): array
    {
        $prefixed = [];

        foreach ($messages as $key => $message) {
            $prefixed[str_contains($key, '.') ? $tab.'.'.$key : $key] = $message;
        }

        return $prefixed;
    }

    /**
     * يعيد إلى الصفحة **وإلى التبويب نفسه**.
     *
     * ⚠️ المرساة لا تصل الخادم أبداً، فالتبويب يُمرَّر كاستعلام `tab`
     * ويلتقطه العرض. بدونه يعود المستخدم دائماً للتبويب الأول بعد كل حفظ.
     */
    private function backToTab(string $tab): RedirectResponse
    {
        return redirect()->to($this->tabUrl($tab));
    }

    private function tabUrl(string $tab): string
    {
        return route('admin.settings.index', ['tab' => $tab]);
    }
}
