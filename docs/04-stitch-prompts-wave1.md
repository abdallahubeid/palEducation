# برومتات Stitch — الموجة الأولى

> **3 شاشات مرجعية** تُنتج ~70% من مكتبة المكوّنات
> **المصدر:** [DESIGN.md](../DESIGN.md) · [03-screens-detail.md](03-screens-detail.md)

---

## طريقة الاستخدام

1. افتح **stitch.withgoogle.com**
2. الصق **كتلة نظام التصميم** (أدناه) في حقل الـ Design System / DESIGN.md
3. أنشئ شاشة جديدة والصق **برومت الشاشة** — واحدة في كل مرة، لا الثلاثة معاً
4. بعد التوليد راجع **قائمة الفحص** في آخر الملف

**لماذا التعليمات بالإنجليزية:** Stitch يفهم التعليمات الإنجليزية بدقة أعلى بكثير. النصوص المعروضة على الشاشة مكتوبة بالعربية حرفياً بين علامتَي اقتباس حتى لا يترجم أو يخترع.

---

# 📋 كتلة نظام التصميم — الصقها مرة واحدة

```
DESIGN SYSTEM — pal education

LANGUAGE & DIRECTION (highest priority)
- Interface language: ARABIC only. Layout direction: RIGHT-TO-LEFT (RTL).
- Everything mirrors: sidebars sit on the RIGHT, text aligns RIGHT, lists start from the RIGHT.
- Never output English UI labels. Use only the exact Arabic strings given in each screen prompt.
- Numbers stay Western digits (0-9) and stay left-to-right. Video players, code, and math equations stay left-to-right.

TYPOGRAPHY
- Arabic sans-serif (IBM Plex Sans Arabic / Noto Sans Arabic style). Numbers in Inter.
- letter-spacing MUST be exactly 0 on every element. NEVER use negative letter-spacing or tight tracking. This breaks Arabic letterforms.
- Never use uppercase transforms.
- Scale: hero 56px/700/1.35 · h1 36px/700/1.35 · h2 28px/600/1.40 · h3 24px/600/1.45 · h4 20px/600/1.50
- Body 16px/400/line-height 1.75 · large body 18px/1.80 · UI text 15px/1.70 · captions 14px/1.60
- Minimum 16px for anything the user reads. Never smaller for reading text.

COLOR
- Primary action: #0a0a0a near-black. EVERY primary button is black and fully pill-shaped.
- Accent: #00d4a4 mint. Use RARELY — progress bar fills, "completed" badges, featured card border, focus rings. Never as a background or on body text.
- Green text color: #1ba673 (darker mint, for contrast on white).
- Break-rhythm: #f55a3c orange — exactly ONE element per page.
- Tag blue: #3772cf (branch badges) · Amber: #c37d0d (expiring) · Muted red: #d45656 (errors, never pure red)
- Canvas #ffffff · Section surface #f7f7f7 · Soft surface #fafafa
- Borders: #e5e5e5 for cards, #ededed for row dividers. Always 1px.
- Text: #0a0a0a headings · #1c1c1e body · #3a3a3c secondary · #5a5a5c tertiary · #888888 captions

SHAPE & DEPTH
- Radius: cards 12px · inputs and small elements 8px · tiny chips 6px · buttons FULLY ROUNDED pill (9999px)
- FLAT system. Hierarchy comes from 1px hairline borders and spacing — not shadows.
- Only ONE shadow allowed in the entire system: a deep soft drop on the hero product mockup.
- Featured pricing card gets a soft mint-tinted glow. Nothing else glows.

SPACING
- 4px base. Steps: 4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 48 · 64 · 96 · 120
- Section gaps 64-96px on marketing pages, 32px on dense app surfaces.
- Max container width 1280px. Reading text max 68 characters wide.

STRICTLY FORBIDDEN — these make a design look AI-generated
- No purple or blue gradients. No glassmorphism or backdrop blur.
- No floating cards with colored shadows. No neon glows. No gradient borders.
- No emoji used as icons — use thin consistent line icons only.
- No everything-centered layouts. Align to the start (right in RTL).
- No three identical cards with colored circular icons.
- No random radii like 17px or 23px. Stick to the scale.
- No generic stock photos of smiling people at laptops.
- No more than three chromatic colors total.
```

---

# 🎯 برومت 1 — الصفحة الرئيسية

```
Design a desktop landing page for "pal education", an Arabic e-learning platform for Palestinian high-school seniors preparing for the Tawjihi national exam.

RIGHT-TO-LEFT ARABIC LAYOUT. Follow the design system exactly.

SECTIONS, top to bottom:

1. STICKY TOP BAR (white, 1px bottom border #e5e5e5, 68px tall)
   - Logo "pal education" at the RIGHT edge
   - Nav links next to it: "الفروع" · "الأخبار" · "الأسعار"
   - At the LEFT edge: text link "تسجيل الدخول" and a black pill button "إنشاء حساب"

2. HERO — atmospheric gradient band, linear gradient from #87a8c8 at top to #f5e9d8 at bottom, 120px vertical padding
   - Heading, 56px bold, dark #0a0a0a, aligned right: "تعلّم لفرعك أنت، بلا تشتيت"
   - Subheading, 18px, line-height 1.8, max 52 characters wide: "محاضرات مصوّرة لكل مادة في فرعك، وكويز فوري بعد كل محاضرة يقيس فهمك مباشرة."
   - Button row: mint #00d4a4 pill "ابدأ مجاناً" then white outlined pill "تصفّح الفروع"
   - Below buttons: a browser-window mockup of the platform's lecture screen — 12px radius, 1px border, deep soft drop shadow. This is the ONLY shadow on the page.

3. BRANCHES — white background, 96px padding
   - Section heading 36px: "اختر فرعك"
   - Subtext 16px #5a5a5c: "كل فرع له مواده وأساتذته ومكتبته الخاصة"
   - Four cards in a row, each: white, 12px radius, 1px border #e5e5e5, 24px padding, a thin line icon at top, branch name 20px bold, then small grey text
     • "الفرع العلمي" / "٨ مواد · ١٢ أستاذ"
     • "الفرع الأدبي" / "٧ مواد · ٩ أساتذة"
     • "الفرع التجاري" / "٦ مواد · ٧ أساتذة"
     • "الفرع الصناعي" / "٦ مواد · ٥ أساتذة"

4. HOW IT WORKS — surface #f7f7f7 background, 96px padding
   - Heading 36px: "ثلاث خطوات فقط"
   - Three steps in a row, numbered 01 02 03 in small grey Inter numerals (this IS a real sequence so numbering is meaningful):
     • "اختر فرعك" / "مرة واحدة فقط — بعدها ترى مواد فرعك دون غيرها"
     • "شاهد المحاضرة" / "فيديو من أستاذ المادة، بجودة عالية ومن أي جهاز"
     • "اختبر نفسك فوراً" / "الكويز يفتح تلقائياً بعد انتهاء الفيديو — بلا خطوة إضافية"

5. DIFFERENTIATORS — white, 96px padding
   - Two wide rows with UNEQUAL visual weight, not identical cards. Each row: text on one side, a UI screenshot on the other, alternating sides.
   - Row 1 heading 28px: "مكتبة منظّمة حسب فرعك ومادتك" / body: "أوراق عمل وملخّصات وامتحانات سابقة، مرتّبة في مكان واحد بدل البحث بين المحاضرات."
   - Row 2 heading 28px: "كويز يضعه أستاذك، لا نظام آلي" / body: "الأستاذ يبني الاختبار لحظة رفع المحاضرة، فيقيس ما شرحه هو بالضبط."

6. TEACHERS — surface #f7f7f7, 64px padding
   - Heading 28px: "أساتذة المنصة"
   - A horizontal row of six teacher items: circular photo 72px, name 16px medium, subject 14px grey below.

7. TESTIMONIALS — white, 96px padding
   - One LARGE card in solid orange #f55a3c with white text — this is the only orange element on the entire page. Contains a 24px quote: "أول مرة أحس إني بدرس بترتيب. كل شي قدامي وما في تشتيت." and attribution "ليان — الفرع العلمي"
   - Beside it, two smaller white cards with 1px borders carrying shorter quotes.

8. FINAL CTA — white, 96px padding, 1px top border
   - Heading 36px centered: "جاهز تبدأ؟"
   - Black pill button "أنشئ حسابك المجاني"

9. FOOTER — white, 1px top border, 64px padding
   - Four link columns with headers: "المنصة" · "الفروع" · "الدعم" · "قانوني"
   - Bottom line 14px grey: "© 2026 pal education — جميع الحقوق محفوظة"
```

---

# 🎯 برومت 2 — لوحة الطالب

```
Design a desktop dashboard for a logged-in student on "pal education", an Arabic e-learning platform.

RIGHT-TO-LEFT ARABIC LAYOUT. The sidebar sits on the RIGHT side of the screen, not the left. Follow the design system exactly.

LAYOUT: fixed right sidebar 260px + main content area.

RIGHT SIDEBAR (white, 1px border on its left edge)
- Logo "pal education" at top, 24px padding
- Nav items, 15px, 8px radius, thin line icon then label:
  "لوحتي" (ACTIVE — background #f7f7f7, text #0a0a0a, medium weight)
  "موادي" · "المكتبة" · "تقدّمي" · "اشتراكي" · "ملفي"
- Inactive items use #5a5a5c text and transparent background.

TOP BAR (white, 1px bottom border, 64px tall)
- Search field at the right: 8px radius, 1px border, placeholder "ابحث في مواد فرعك"
- At the left edge: a bell icon, then a green pill badge "اشتراك ساري" using #1ba673 text on a pale mint background, then a 32px circular avatar.

MAIN CONTENT (background #fafafa, 32px padding, max width 1100px)

1. Greeting row
   - Heading 28px: "أهلاً محمد"
   - Beside it a blue branch chip: "الفرع العلمي" — #3772cf text on pale blue, 6px radius
   - Below, 15px grey: "تبقّى ٤٢ يوماً على انتهاء اشتراكك"

2. FOUR STAT CARDS in a row — white, 12px radius, 1px border #e5e5e5, 24px padding
   Each shows a small grey label 14px, then a large number 32px bold in Inter with tabular figures:
   • "محاضرات أنجزتها" / "٢٤"
   • "متوسط نتائجك" / "٨٦٪"
   • "مواد فرعك" / "٨"
   • "أيام متبقية" / "٤٢"

3. CONTINUE CARD — the single most prominent element on the page
   - Wide white card, 12px radius, 1px border, 24px padding
   - Small grey label: "أكمل من حيث توقفت"
   - A 160x90px thumbnail with 8px radius on one side
   - Beside it: subject name 14px grey "الرياضيات", lecture title 20px bold "المحاضرة ٣ — المشتقات والتفاضل"
   - A thin 6px progress track #e5e5e5 with a mint #00d4a4 fill at 68%, and "٦٨٪" in Inter beside it
   - A black pill button at the far side: "تابع المشاهدة"

4. MY SUBJECTS
   - Section heading 24px: "موادي" with a small text link at the opposite edge: "عرض الكل"
   - Grid of six cards, three per row — white, 12px radius, 1px border, 20px padding
   - Each: thin line icon, subject name 18px semibold, teacher name 14px grey, then a 6px mint progress bar and a percentage in Inter
   - Examples: "الرياضيات / أ. سامر خليل / ٦٨٪" · "الفيزياء / أ. رنا عوض / ٤٥٪" · "الكيمياء / أ. وليد حمد / ٨٠٪" · "الأحياء / أ. هدى ناصر / ٣٠٪" · "اللغة العربية / أ. مازن سالم / ٥٥٪" · "اللغة الإنجليزية / أ. لينا فرح / ٩٠٪"

5. RECENT RESULTS
   - Section heading 24px: "آخر نتائجك"
   - A clean table: white, 12px radius, 1px border. Header row background #f7f7f7, 14px grey labels.
   - Columns right-to-left: "المحاضرة" · "المادة" · "الدرجة" · "التاريخ"
   - Four rows. Scores shown as green #1ba673 medium-weight text in Inter with tabular figures, e.g. "٩ / ١٠"
   - Row dividers 1px #ededed.
```

---

# 🎯 برومت 3 — صفحة المادة

```
Design a desktop subject page for a student on "pal education", an Arabic e-learning platform.

RIGHT-TO-LEFT ARABIC LAYOUT. Follow the design system exactly.

This screen uses a THREE-COLUMN documentation-style layout. In RTL the reading order is right to left:
- FIRST column (rightmost, 260px): the lecture list navigation
- MIDDLE column (flexible, max 760px): the main content
- LAST column (leftmost, 220px): subject metadata

Above all three: a top bar identical to the dashboard's, and a breadcrumb.

BREADCRUMB (15px, grey #5a5a5c, chevrons pointing in the RTL direction)
"موادي" › "الرياضيات"

RIGHT COLUMN — lecture navigation
- Small grey header label 13px semibold: "محاضرات المادة"
- A vertical list of ten lecture links, 15px, 6px radius, 8px vertical padding:
  • Completed ones show a small mint #1ba673 check mark and grey text
  • The current one is ACTIVE: background #f7f7f7, text #0a0a0a, medium weight
  • Upcoming ones are plain #5a5a5c
- Sample titles: "مقدّمة في النهايات" · "الاتصال والانفصال" · "المشتقات والتفاضل" · "قواعد الاشتقاق" · "تطبيقات المشتقة"

MIDDLE COLUMN — main content
1. Subject header
   - Title 36px bold: "الرياضيات"
   - Below it 15px grey: "أ. سامر خليل · ١٢ محاضرة · ٨ ملفات"
   - A thin 6px progress track with mint fill at 68% and "٦٨٪" beside it in Inter

2. UNDERLINE TABS (not pills) — 15px medium, 12px bottom padding, active tab has a 2px black bottom border and #0a0a0a text; inactive tabs are #5a5a5c with no border
   "المحاضرات" (active) · "الملفات"

3. Under the active tab, a list of lecture rows separated by 1px #ededed dividers, 16px vertical padding. Each row contains, from right to left:
   - Lecture number in small grey Inter numerals: "٠١"
   - Lecture title 16px #0a0a0a
   - Then pushed to the far left edge: a duration chip on #f7f7f7 background with 6px radius showing Inter tabular numbers like "12:45", and for completed lectures a pale mint badge "مكتملة" in #1ba673, and for graded ones a small score "٩/١٠"
   Show six rows: two completed with scores, one current, three upcoming.

LEFT COLUMN — subject metadata (sticky)
- A white card, 12px radius, 1px border #e5e5e5, 20px padding
- Small grey label: "عن المادة"
- Teacher block: 48px circular photo, name 16px medium "أ. سامر خليل", role 14px grey "معلّم الرياضيات"
- A 1px divider, then three stat rows with grey labels and Inter values:
  "المحاضرات" / "١٢" · "الملفات" / "٨" · "مدة المحتوى" / "٤ ساعات"
- A black pill button, full width: "تابع المحاضرة ٣"

Keep everything flat: 1px hairline borders, no shadows anywhere on this screen, no colored cards. The only color accents are the mint progress fill, the mint completed badges, and nothing else.
```

---

# ✅ قائمة الفحص بعد التوليد

Stitch أداة إنجليزية أساساً، ودعمها للـRTL غير مضمون. افحص هذه البنود في كل مخرَج:

| # | الفحص | الإصلاح لو فشل |
|---|---|---|
| 1 | **الاتجاه RTL** — الشريط الجانبي يمين؟ النص محاذى يمين؟ | أعد التوليد مع تشديد: "RTL, sidebar on the RIGHT" في أول سطر |
| 2 | **التباعد الحرفي** — الحروف العربية متصلة؟ | أضف: "letter-spacing: 0 on ALL text, never negative" |
| 3 | **لا نص إنجليزي مخترع** | الصق النصوص العربية حرفياً مرة أخرى |
| 4 | **الأرقام غربية 0-9** لا شرقية | حدّد: "Western Arabic numerals only" |
| 5 | **الأزرار الأساسية سوداء وحبّية** | ذكّر: "#0a0a0a, fully rounded pill" |
| 6 | **النعناعي نادر** — ليس على خلفيات كبيرة | احذف أي استخدام زائد يدوياً |
| 7 | **برتقالي واحد فقط** في الصفحة الرئيسية | نفسه |
| 8 | **لا ظلال** عدا mockup الهيرو | نفسه |
| 9 | **ارتفاع السطر ≥1.7** للنص العربي | عدّل يدوياً |
| 10 | **لا تدرّجات بنفسجية/زرقاء ولا زجاجية** | أعد التوليد مع قسم FORBIDDEN كاملاً |

---

# ⚠️ توقّعات واقعية

**ما سينجح غالباً:** البنية العامة · التخطيط · توزيع الأقسام · اللوحة اللونية · الأنصاف الحبّية.

**ما سيحتاج إصلاحاً يدوياً غالباً:**
- **الخط العربي** — Stitch لا يملك IBM Plex Sans Arabic عادةً؛ سيستبدله. طبيعي، نضبطه عند تحويل التصميم لـBlade.
- **التباعد الحرفي** — النماذج تميل لتطبيق tracking سالب على العناوين تلقائياً. **أخطر بند، افحصه أولاً.**
- **دقة النصوص العربية** — قد يعيد صياغتها أو يقلب ترتيب المقاطع المختلطة.

**الاستخدام الصحيح:** Stitch يعطينا **مسودة بصرية سريعة نتفق عليها**، لا كوداً نهائياً. التنفيذ الفعلي يتم في Blade + Tailwind 4 بالـtokens المضبوطة من `DESIGN.md` — هناك نضمن RTL والطباعة العربية بدقة كاملة.
