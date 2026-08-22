<?php

/*
|--------------------------------------------------------------------------
| Wave 3 - Pricing and checkout (screens 7-10)
|--------------------------------------------------------------------------
| Unblocked by decision M-1, resolved 2026-08-22: two subscription models
| coexist - per-subject (pick and combine individual subjects) and a
| discounted full-branch pass.
|
| Payment is manual by design (bank transfer / e-wallet / local agent /
| cash), which is what decision M-4 settled on for the Palestinian context.
*/

return [
    // ── Pricing page (7) ──
    'pricing_eyebrow' => 'الأسعار',
    'pricing_title' => 'ادفع لما تحتاجه فقط',
    'pricing_lede' => 'اختر المواد التي تريدها مادةً مادة، أو افتح فرعك كاملاً بسعر أوفر. لا رسوم خفيّة، ولا تجديد تلقائي.',

    'model_per_subject' => 'اشتراك بالمادة',
    'model_per_subject_summary' => 'اختر المواد التي تريدها فقط، وادفع بحسب عددها.',
    'model_branch' => 'الاشتراك الشامل للفرع',
    'model_branch_summary' => 'كل مواد فرعك دفعةً واحدة، بسعر أقل من شرائها منفردة.',
    'model_branch_badge' => 'الأوفر',

    // ── Calculator ──
    'calc_title' => 'احسب اشتراكك',
    'calc_branch_label' => 'فرعك',
    'calc_subjects_label' => 'اختر موادك',
    'calc_per_subject_price' => ':price ₪ للمادة الواحدة / الفصل',
    'calc_selected' => 'المواد المختارة',
    'calc_empty' => 'لم تختر أي مادة بعد.',
    'calc_cta' => 'تابع للدفع',
    'calc_switch_hint' => 'اخترت :count مواد بـ:total ₪ — الاشتراك الشامل يفتح الفرع كاملاً بـ:branch ₪ فقط.',
    'calc_switch_action' => 'بدّل للاشتراك الشامل',
    'calc_subject_count' => 'مادة',
    'calc_all_subjects' => 'كل مواد الفرع',
    'calc_saving' => 'توفّر :amount ₪',

    // ── Comparison table ──
    'compare_title' => 'ما الذي يشمله كل اشتراك؟',
    'compare_lede' => 'الفرق بين الاشتراكين في النطاق لا في جودة المحتوى — المادة الواحدة تصلك كاملة في الحالتين.',
    'compare_feature' => 'الميزة',
    'compare_included' => 'مشمول',
    'compare_excluded' => 'غير مشمول',
    'compare_f_videos' => 'محاضرات مصوّرة كاملة',
    'compare_f_quiz' => 'كويز فوري بعد كل محاضرة',
    'compare_f_library' => 'تنزيل ملفات المكتبة',
    'compare_f_progress' => 'متابعة التقدّم والإحصاءات',
    'compare_f_qa' => 'أسئلة وأجوبة مع المعلّم',
    'compare_f_all_subjects' => 'كل مواد الفرع دون استثناء',
    'compare_f_new_subjects' => 'المواد الجديدة فور إضافتها',
    'compare_f_priority' => 'أولوية في الدعم الفني',
    'compare_scope_selected' => 'للمواد المختارة',
    'compare_scope_all' => 'لكل المواد',

    // ── FAQ ──
    'faq_title' => 'أسئلة شائعة',
    'faq_q1' => 'هل أستطيع إضافة مادة بعد الاشتراك؟',
    'faq_a1' => 'نعم. تضيف أي مادة في أي وقت وتدفع سعرها للفترة المتبقية من فصلك فقط، ولا تُحتسب عليك المدة التي مضت.',
    'faq_q2' => 'ما الفرق بين الاشتراك بالمادة والاشتراك الشامل؟',
    'faq_a2' => 'النطاق فقط. المادة الواحدة تصلك كاملة في الحالتين — بمحاضراتها وكويزاتها وملفاتها. الاشتراك الشامل يفتح مواد فرعك كلها بسعر أقل من شرائها منفردة، ويشمل أي مادة تُضاف لاحقاً.',
    'faq_q3' => 'كيف أدفع؟',
    'faq_a3' => 'بالتحويل البنكي أو المحفظة الإلكترونية أو عبر وكيل محلي أو نقداً في المكتب. بعد الدفع ترفع صورة الإيصال، ويُفعَّل اشتراكك بعد المراجعة خلال 24 ساعة عادةً.',
    'faq_q4' => 'هل هناك تجديد تلقائي؟',
    'faq_a4' => 'لا. لا نحفظ بيانات بطاقات ولا نخصم تلقائياً. حين تقترب نهاية اشتراكك نُذكّرك، والتجديد قرارك وحدك.',
    'faq_q5' => 'ماذا لو اخترت الفرع الخطأ؟',
    'faq_a5' => 'راسل الدعم قبل انقضاء أسبوع من التفعيل وننقل اشتراكك للفرع الصحيح دون رسوم.',
    'faq_q6' => 'هل أستطيع التجربة قبل الدفع؟',
    'faq_a6' => 'نعم. أول محاضرة من كل مادة مفتوحة مجاناً مع الكويز المرافق لها، بلا بطاقة ولا التزام.',

    // ── Pricing CTA band ──
    'cta_title' => 'ما زلت متردّداً؟',
    'cta_body' => 'ابدأ بالمحاضرات المجانية أولاً — أول محاضرة من كل مادة مفتوحة، ثم قرّر.',
    'cta_action' => 'جرّب مجاناً',

    // ── Checkout (8) ──
    'checkout_title' => 'إتمام الطلب',
    'checkout_step_order' => 'الطلب',
    'checkout_step_pay' => 'الدفع',
    'checkout_step_review' => 'المراجعة',

    'summary_title' => 'ملخّص الطلب',
    'summary_plan' => 'نوع الاشتراك',
    'summary_branch' => 'الفرع',
    'summary_subjects' => 'المواد',
    'summary_period' => 'المدة',
    'summary_subtotal' => 'المجموع الفرعي',
    'summary_discount' => 'خصم الاشتراك الشامل',
    'summary_total' => 'الإجمالي',
    'summary_currency' => '₪',
    'summary_term' => 'فصل دراسي واحد',
    'summary_edit' => 'تعديل',

    'student_info_title' => 'بياناتك',
    'student_info_note' => 'راجع بياناتك قبل المتابعة — سنستخدم رقمك للتواصل بشأن الدفع.',
    'student_name' => 'الاسم',
    'student_branch' => 'الفرع',
    'student_phone' => 'رقم الجوال',

    'payment_title' => 'طريقة الدفع',
    'payment_note' => 'كل الطرق يدوية: تدفع أولاً ثم ترفع صورة الإيصال، ونفعّل اشتراكك بعد المراجعة.',
    'payment_bank' => 'تحويل بنكي',
    'payment_bank_desc' => 'حوالة إلى حساب المنصة من أي بنك محلي.',
    'payment_wallet' => 'محفظة إلكترونية',
    'payment_wallet_desc' => 'جوّال باي أو محفظة الرقمية — تحويل فوري من هاتفك.',
    'payment_agent' => 'وكيل محلي',
    'payment_agent_desc' => 'ادفع نقداً عند أقرب وكيل معتمد في مدينتك.',
    'payment_cash' => 'نقداً في المكتب',
    'payment_cash_desc' => 'زُر مكتبنا في رام الله وادفع مباشرة.',
    'checkout_cta' => 'متابعة لرفع الإيصال',
    'checkout_back' => 'رجوع للأسعار',

    // ── Payment proof (9) ──
    'proof_title' => 'رفع إثبات الدفع',
    'proof_lede' => 'حوّل المبلغ إلى الحساب أدناه، ثم ارفع صورة الإيصال ليُفعَّل اشتراكك.',
    'proof_instructions_title' => 'تفاصيل التحويل',
    'proof_account_name' => 'اسم المستفيد',
    'proof_account_number' => 'رقم الحساب',
    'proof_iban' => 'الآيبان',
    'proof_bank_name' => 'البنك',
    'proof_amount_due' => 'المبلغ المطلوب',
    'proof_warning' => 'حوّل المبلغ بالضبط كما هو. أي فرق يؤخّر المراجعة.',

    'proof_form_title' => 'بيانات الإيصال',
    'proof_upload_title' => 'ارفع صورة الإيصال أو ملف PDF',
    'proof_upload_hint' => 'اسحب الملف هنا أو انقر للاختيار — JPG أو PNG أو PDF',
    'proof_upload_max' => 'الحد الأقصى 5 ميغابايت',
    'proof_reference' => 'رقم العملية أو المرجع',
    'proof_reference_hint' => 'تجده في رسالة التأكيد أو على إيصال البنك.',
    'proof_date' => 'تاريخ التحويل',
    'proof_time' => 'وقت التحويل',
    'proof_note' => 'ملاحظة للمراجع (اختياري)',
    'proof_note_placeholder' => 'أي تفصيل يساعدنا في مطابقة تحويلك...',
    'proof_cta' => 'أرسل الإيصال',
    'proof_back' => 'رجوع لطريقة الدفع',

    // ── Pending review (10) ──
    'pending_title' => 'طلبك قيد المراجعة',
    'pending_body' => 'استلمنا إيصالك. يراجعه فريقنا ويفعّل اشتراكك عادةً خلال 24 ساعة، وسيصلك إشعار فور التفعيل.',
    'pending_ref_label' => 'رقم الطلب',
    'pending_submitted_at' => 'أُرسل في',
    'pending_expected' => 'المدة المتوقّعة',
    'pending_expected_value' => 'خلال 24 ساعة',
    'pending_receipt' => 'الإيصال المرفوع',
    'pending_status' => 'الحالة',
    'pending_status_value' => 'بانتظار المراجعة',
    'pending_what_next' => 'ماذا بعد؟',
    'pending_next_1' => 'يراجع فريقنا الإيصال ويطابقه مع التحويل.',
    'pending_next_2' => 'يصلك إشعار على المنصة وعلى جوالك فور التفعيل.',
    'pending_next_3' => 'تبدأ مباشرةً من لوحتك — لا خطوة إضافية.',
    'pending_meanwhile' => 'وحتى ذلك الحين، المحاضرات المجانية متاحة لك.',
    'pending_dashboard' => 'العودة للوحتي',
    'pending_support' => 'تواصل مع الدعم',
];
