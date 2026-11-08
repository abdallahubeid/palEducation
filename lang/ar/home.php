<?php

return [
    // ── الهيرو — سلايدر بثلاث شرائح ────────────────────────
    'hero_cta' => 'ابدأ الآن',
    'hero_alt' => 'تصفّح الفروع',
    'hero_slider_label' => 'عرض تعريفي بالمنصة',
    'hero_go_to' => 'الانتقال إلى الشريحة :n',
    'slide_prev' => 'الشريحة السابقة',
    'slide_next' => 'الشريحة التالية',

    'slide_1_eyebrow' => 'منصة التوجيهي الفلسطينية',
    'slide_1_line_1' => 'التوجيهي ليس صعباً.',
    'slide_1_line_2' => 'التشتّت هو الصعب.',
    'slide_1_lede' => 'تختار فرعك مرّة واحدة، فتختفي عن شاشتك كل مادة لا تخصّك. لا قوائم طويلة تبحث فيها، ولا محتوى يصرفك عن منهجك.',
    'slide_1_alt' => 'طالب توجيهي يدرس على المنصة',

    'slide_2_eyebrow' => 'حلقة تعلّم مغلقة',
    'slide_2_line_1' => 'تنتهي المحاضرة،',
    'slide_2_line_2' => 'فيُفتح الاختبار تلقائياً.',
    'slide_2_lede' => 'لا يوجد زرّ «ابدأ الاختبار». يصلك التقييم والشرح ما يزال حاضراً في ذهنك، فتعرف موقعك قبل الامتحان لا بعده.',
    'slide_2_alt' => 'شاشة الاختبار تُفتح بعد انتهاء المحاضرة',

    'slide_3_eyebrow' => 'مكتبة منظّمة',
    'slide_3_line_1' => 'مراجعك كاملة',
    'slide_3_line_2' => 'في مكان واحد.',
    'slide_3_lede' => 'أوراق عمل وملخّصات وامتحانات وزارية سابقة، مرتّبة حسب فرعك ومادتك — بدل البحث عنها بين المحاضرات.',
    'slide_3_alt' => 'مكتبة ملفات منظّمة حسب المادة',

    'image_placeholder' => 'مكان الصورة',

    // ── كيف تعمل المنصة ─────────────────────────────────────
    // 🔁 القسم كلّه خرج من الترجمة: الترويسة في settings
    // (Setting::HOW_DEFAULTS) والخطوات صفوف في جدول steps.

    // ── من نحن ──────────────────────────────────────────────
    // 🔁 Moved to `settings` — Setting::ABOUT_DEFAULTS.

    'about_check_1' => 'محتوى فرعك دون سواه',
    'about_check_2' => 'تقييم يتبع كل محاضرة مباشرة',
    'about_check_3' => 'مكتبة مرتّبة حسب المادة',

    'about_image_alt' => 'طلبة توجيهي يدرسون على المنصة',

    // ── الإحصائيات ──────────────────────────────────────────
    // 🔁 Header and buttons moved to `settings` — Setting::WHY_US_DEFAULTS.
    //    The four counter cards below stay static, by request.

    'stats_check_1' => 'لا محتوى مستورد ولا مقاطع مجمّعة من الإنترنت',
    'stats_check_2' => 'كل محاضرة مرتبطة باختبار من معلّمها',
    'stats_check_3' => 'المحتوى يتوسّع أسبوعياً مع كل إضافة جديدة',

    'stat_lectures' => 'محاضرة مصوّرة',
    'stat_teachers' => 'معلّم متخصّص',
    'stat_files' => 'ملف في المكتبة',
    'stat_students' => 'طالب مسجّل',

    // ── الفروع ──────────────────────────────────────────────
    // 🔁 Header moved to `settings` (Setting::BRANCHES_DEFAULTS);
    //    card copy moved to the `branches` table.

    'subjects_unit' => 'مادة',
    'teachers_unit' => 'معلّم',
    'lectures_unit' => 'محاضرة',
    'students_unit' => 'طالب',
    'browse_branch' => 'تصفّح الفرع',
    'read_more' => 'اقرأ المزيد',

    // ── شريط الفيديو ────────────────────────────────────────
    // 🔁 Header moved to `settings` — Setting::VIDEO_TOUR_DEFAULTS.
    'demo_play' => 'تشغيل الفيديو التعريفي',
    'demo_close' => 'إغلاق',
    'demo_pending' => 'الفيديو التعريفي قيد الإعداد',

    // ── الأسعار ─────────────────────────────────────────────
    // 🔁 Header moved to `settings` (Setting::PRICING_DEFAULTS);
    //    tier copy moved to the `pricing_plans` table.
    // 🗑️ pricing_note was DELETED, not moved: it claimed billing terms
    //    nothing in the system enforces, and phase 8 is unbuilt.
    // ⚠️ plan_popular · plan_term_cta · plan_term_period stay — the
    //    standalone /pricing screen and plan-card still read them.
    'plan_popular' => 'الأكثر اختياراً',
    'currency' => 'شيكل',

    'plan_term_period' => '/ للفصل',
    'plan_term_cta' => 'اشترك الآن',

    // ── الميزات ─────────────────────────────────────────────
    // 🔁 Header moved to `settings` (Setting::FEATURES_DEFAULTS);
    //    card copy moved to the `features` table.

    // ── البانر الأوسط ───────────────────────────────────────
    // 🔁 banner_title · banner_lede · banner_cta نُقلت إلى جدول settings
    // (Setting::CTA_DEFAULTS) — صارت محتوىً يحرّره الأدمن لا نصّ واجهة.

    // ── المعلمون ────────────────────────────────────────────
    // 🔁 Header moved to `settings` (Setting::TEACHERS_DEFAULTS); the
    //    cards are built from the teachers / subjects / lectures /
    //    media tables. teacher_* action labels stay — teacher-card
    //    still reads them for its aria labels.
    // ⚠️ teachers_eyebrow STAYS: the branch detail page still reads it
    //    for its own roster heading. Deleting it broke that page, and
    //    the key check caught it.
    'teachers_eyebrow' => 'نخبة المعلّمين',
    'teacher_profile' => 'الملف الشخصي',
    'teacher_lectures' => 'المحاضرات',
    'teacher_files' => 'الملفات',

    // ── آراء الطلبة ─────────────────────────────────────────
    // 🔁 Header moved to `settings` (Setting::TESTIMONIALS_DEFAULTS);
    //    the reviews are rows in `testimonials`, each linked to a
    //    student account that supplies the name, branch and city.
    'rating_full' => 'خمس نجوم من خمس',

    // ── الأسئلة الشائعة ─────────────────────────────────────
    // 🔁 Header moved to `settings` (Setting::FAQ_DEFAULTS); the
    //    questions are rows in the `faqs` table with their own screens.

    // ── CTA ────────────────────────────────────────────────
];
