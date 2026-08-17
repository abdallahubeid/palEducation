---
version: 1.0
name: palEducation-design-system
base: Mintlify DESIGN.md — Arabic-adapted
description: "هوية بصرية لمنصة توجيهي فلسطينية، مبنية على نظام Mintlify بالكامل: أسود #0a0a0a كلون أساسي مسيطر على الأزرار الحبّية، أخضر نعناعي #00d4a4 كتمييز نادر محجوز لإشارات التقدّم والصواب، وبرتقالي #f55a3c لكسر الإيقاع في اللحظات العاطفية. أسطح مسطّحة بحدود شعرة 1px، بلا تدرّجات إلا في نطاقات الهيرو الجوّية. نظام طباعة ثلاثي النصوص: وجه عربي للعربية، Inter للاتيني والأرقام، Geist Mono للمعادلات والكود — بتباعد حرفي مصفّر بالكامل وارتفاعات سطور مرفوعة لتناسب المحارف العربية المتصلة."

# ═══════════════════════════════════════════════
# ALL COLORS INHERITED FROM MINTLIFY — UNCHANGED
# ═══════════════════════════════════════════════
colors:
  # ── Primary / Brand ──
  primary: "#0a0a0a"
  on-primary: "#ffffff"
  brand-green: "#00d4a4"
  brand-green-deep: "#00b48a"
  brand-green-soft: "#7cebcb"
  brand-tag: "#3772cf"
  brand-warn: "#c37d0d"
  brand-annotate: "#1ba673"
  brand-error: "#d45656"
  accent-orange: "#f55a3c"
  accent-orange-deep: "#cc3a1f"

  # ── Atmospheric hero bands ──
  hero-sky-from: "#87a8c8"
  hero-sky-to: "#f5e9d8"
  hero-dark-from: "#1a3d4a"
  hero-dark-to: "#2d5a4f"

  # ── Surfaces ──
  canvas: "#ffffff"
  canvas-dark: "#0a0a0a"
  surface: "#f7f7f7"
  surface-soft: "#fafafa"
  surface-code: "#1c1c1e"
  hairline: "#e5e5e5"
  hairline-soft: "#ededed"
  hairline-dark: "#1f1f1f"

  # ── Text ──
  ink: "#0a0a0a"
  charcoal: "#1c1c1e"
  slate: "#3a3a3c"
  steel: "#5a5a5c"
  stone: "#888888"
  muted: "#a8a8aa"
  on-dark: "#ffffff"
  on-dark-muted: "#b3b3b3"

  # ── Dark mode (مشتقّ — Mintlify لم ينشر وضعاً داكناً) ──
  dark-canvas: "#0a0a0a"
  dark-surface: "#141416"
  dark-surface-2: "#1c1c1e"
  dark-hairline: "#1f1f1f"
  dark-hairline-strong: "#2a2a2c"

# ═══════════════════════════════════════════════
# TYPOGRAPHY — tri-script, Arabic-safe
# letterSpacing: 0 everywhere (Mintlify's negative
# tracking breaks Arabic letter connection)
# ═══════════════════════════════════════════════
typography:
  hero-display:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 56px
    fontWeight: 700
    lineHeight: 1.35
    letterSpacing: 0
  display-lg:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 44px
    fontWeight: 700
    lineHeight: 1.35
    letterSpacing: 0
  heading-1:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 36px
    fontWeight: 700
    lineHeight: 1.35
    letterSpacing: 0
  heading-2:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 28px
    fontWeight: 600
    lineHeight: 1.40
    letterSpacing: 0
  heading-3:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 24px
    fontWeight: 600
    lineHeight: 1.45
    letterSpacing: 0
  heading-4:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 20px
    fontWeight: 600
    lineHeight: 1.50
    letterSpacing: 0
  heading-5:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 18px
    fontWeight: 600
    lineHeight: 1.55
    letterSpacing: 0
  subtitle:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 18px
    fontWeight: 400
    lineHeight: 1.80
    letterSpacing: 0
  body-lg:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 18px
    fontWeight: 400
    lineHeight: 1.80
    letterSpacing: 0
  body-md:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 16px
    fontWeight: 400
    lineHeight: 1.75
    letterSpacing: 0
  body-md-medium:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 16px
    fontWeight: 500
    lineHeight: 1.75
    letterSpacing: 0
  body-sm:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 15px
    fontWeight: 400
    lineHeight: 1.70
    letterSpacing: 0
  body-sm-medium:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 15px
    fontWeight: 500
    lineHeight: 1.70
    letterSpacing: 0
  caption:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.60
    letterSpacing: 0
  micro-label:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 13px
    fontWeight: 600
    lineHeight: 1.50
    letterSpacing: 0
    textTransform: none
  button-md:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 15px
    fontWeight: 600
    lineHeight: 1.30
    letterSpacing: 0
  numeric:
    fontFamily: Inter
    fontVariantNumeric: tabular-nums
    letterSpacing: 0
  latin-inline:
    fontFamily: Inter
    fontWeight: 400
  code-md:
    fontFamily: Geist Mono
    fontSize: 15px
    fontWeight: 400
    lineHeight: 1.60
    direction: ltr
  code-inline:
    fontFamily: Geist Mono
    fontSize: 14px
    fontWeight: 500
    lineHeight: 1.40
    direction: ltr

# ═══════════════════════════════════════════════
# INHERITED UNCHANGED FROM MINTLIFY
# ═══════════════════════════════════════════════
rounded:
  xs: 4px
  sm: 6px
  md: 8px
  lg: 12px
  xl: 16px
  xxl: 24px
  full: 9999px

spacing:
  xxs: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 20px
  xl: 24px
  xxl: 32px
  xxxl: 40px
  section-sm: 48px
  section: 64px
  section-lg: 96px
  hero: 120px
  reading-max: 68ch
  container-max: 1280px

elevation:
  flat: "none"
  subtle: "rgba(0, 0, 0, 0.04) 0px 1px 2px 0px"
  card: "rgba(0, 0, 0, 0.08) 0px 4px 12px 0px"
  mockup: "rgba(0, 0, 0, 0.12) 0px 24px 48px -8px"
  brand-glow: "rgba(0, 212, 164, 0.08) 0px 8px 24px"
  focus: "0 0 0 3px rgba(0, 212, 164, 0.25)"

motion:
  fast: "120ms cubic-bezier(0.4, 0, 0.2, 1)"
  base: "180ms cubic-bezier(0.4, 0, 0.2, 1)"

components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.button-md}"
    rounded: "{rounded.full}"
    padding: "11px 22px"
  button-accent-green:
    backgroundColor: "{colors.brand-green}"
    textColor: "{colors.primary}"
    typography: "{typography.button-md}"
    rounded: "{rounded.full}"
    padding: "11px 22px"
  button-on-dark:
    backgroundColor: "{colors.on-dark}"
    textColor: "{colors.primary}"
    rounded: "{rounded.full}"
    padding: "11px 22px"
  button-secondary:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    rounded: "{rounded.full}"
    padding: "11px 22px"
    border: "1px solid {colors.hairline}"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "8px 12px"
  button-danger:
    backgroundColor: "{colors.brand-error}"
    textColor: "{colors.on-dark}"
    rounded: "{rounded.full}"
    padding: "11px 22px"
  card-base:
    backgroundColor: "{colors.canvas}"
    rounded: "{rounded.lg}"
    padding: "{spacing.xl}"
    border: "1px solid {colors.hairline}"
  card-feature:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.lg}"
    padding: "{spacing.xxl}"
  branch-card:
    backgroundColor: "{colors.canvas}"
    rounded: "{rounded.lg}"
    padding: "{spacing.xl}"
    border: "1px solid {colors.hairline}"
  subject-card:
    backgroundColor: "{colors.canvas}"
    rounded: "{rounded.lg}"
    padding: "{spacing.xl}"
    border: "1px solid {colors.hairline}"
  lecture-row:
    backgroundColor: "transparent"
    typography: "{typography.body-sm}"
    padding: "{spacing.md} 0"
    border: "0 0 1px {colors.hairline-soft} solid"
  file-row:
    backgroundColor: "transparent"
    typography: "{typography.body-sm}"
    padding: "{spacing.md} 0"
    border: "0 0 1px {colors.hairline-soft} solid"
  quiz-option:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-md}"
    rounded: "{rounded.md}"
    padding: "{spacing.md} {spacing.lg}"
    border: "1px solid {colors.hairline}"
    minHeight: 56px
  quiz-option-selected:
    backgroundColor: "{colors.canvas}"
    border: "2px solid {colors.primary}"
  quiz-option-correct:
    backgroundColor: "rgba(0, 212, 164, 0.10)"
    border: "2px solid {colors.brand-annotate}"
  quiz-option-wrong:
    backgroundColor: "rgba(212, 86, 86, 0.08)"
    border: "2px solid {colors.brand-error}"
  pricing-card:
    backgroundColor: "{colors.canvas}"
    rounded: "{rounded.lg}"
    padding: "{spacing.xxl}"
    border: "1px solid {colors.hairline}"
  pricing-card-featured:
    backgroundColor: "{colors.canvas}"
    rounded: "{rounded.lg}"
    padding: "{spacing.xxl}"
    border: "2px solid {colors.brand-green}"
    shadow: "{elevation.brand-glow}"
  text-input:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-md}"
    rounded: "{rounded.md}"
    padding: "{spacing.sm} {spacing.md}"
    border: "1px solid {colors.hairline}"
    height: 44px
  text-input-focused:
    border: "2px solid {colors.brand-green}"
  badge-branch:
    backgroundColor: "rgba(55, 114, 207, 0.12)"
    textColor: "{colors.brand-tag}"
    typography: "{typography.micro-label}"
    rounded: "{rounded.sm}"
    padding: "3px 10px"
  badge-completed:
    backgroundColor: "rgba(0, 212, 164, 0.14)"
    textColor: "{colors.brand-annotate}"
    typography: "{typography.micro-label}"
    rounded: "{rounded.full}"
    padding: "3px 10px"
  badge-duration:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.steel}"
    typography: "{typography.numeric}"
    rounded: "{rounded.sm}"
    padding: "3px 8px"
  badge-sub-active:
    backgroundColor: "rgba(0, 212, 164, 0.14)"
    textColor: "{colors.brand-annotate}"
    rounded: "{rounded.full}"
    padding: "3px 10px"
  badge-sub-expiring:
    backgroundColor: "rgba(195, 125, 13, 0.14)"
    textColor: "{colors.brand-warn}"
    rounded: "{rounded.full}"
    padding: "3px 10px"
  badge-sub-expired:
    backgroundColor: "rgba(212, 86, 86, 0.12)"
    textColor: "{colors.brand-error}"
    rounded: "{rounded.full}"
    padding: "3px 10px"
  promo-banner:
    backgroundColor: "{colors.canvas-dark}"
    textColor: "{colors.on-dark}"
    typography: "{typography.body-sm-medium}"
    padding: "{spacing.sm} {spacing.md}"
  sidebar-nav-item:
    backgroundColor: "transparent"
    textColor: "{colors.steel}"
    typography: "{typography.body-sm}"
    rounded: "{rounded.sm}"
    padding: "{spacing.xs} {spacing.md}"
  sidebar-nav-item-active:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.body-sm-medium}"
  segmented-tab:
    textColor: "{colors.steel}"
    typography: "{typography.body-sm-medium}"
    padding: "{spacing.sm} {spacing.md}"
    border: "0 0 2px transparent solid"
  segmented-tab-active:
    textColor: "{colors.ink}"
    border: "0 0 2px {colors.ink} solid"
  progress-bar:
    backgroundColor: "{colors.hairline}"
    fillColor: "{colors.brand-green}"
    rounded: "{rounded.full}"
    height: 6px
  video-shell:
    backgroundColor: "{colors.canvas-dark}"
    rounded: "{rounded.lg}"
    direction: ltr
    aspectRatio: "16/9"
  equation-block:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.charcoal}"
    typography: "{typography.code-md}"
    rounded: "{rounded.md}"
    padding: "{spacing.md}"
    direction: ltr
  hero-band-sky:
    backgroundColor: "linear-gradient(180deg, {colors.hero-sky-from} 0%, {colors.hero-sky-to} 100%)"
    padding: "{spacing.hero}"
  hero-band-dark:
    backgroundColor: "linear-gradient(135deg, {colors.hero-dark-from} 0%, {colors.hero-dark-to} 100%)"
    textColor: "{colors.on-dark}"
    padding: "{spacing.hero}"
  hero-product-mockup:
    backgroundColor: "{colors.canvas}"
    rounded: "{rounded.lg}"
    border: "1px solid {colors.hairline-soft}"
    shadow: "{elevation.mockup}"
  testimonial-card-feature:
    backgroundColor: "{colors.accent-orange}"
    textColor: "{colors.on-dark}"
    rounded: "{rounded.lg}"
    padding: "{spacing.section}"
  faq-accordion-item:
    backgroundColor: "{colors.canvas}"
    rounded: "{rounded.md}"
    padding: "{spacing.xl}"
    border: "1px solid {colors.hairline-soft}"
  footer-region:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.steel}"
    padding: "{spacing.section} {spacing.xxl}"
    border: "1px 0 0 {colors.hairline} solid"
---

## Overview

هوية palEducation مبنية على نظام **Mintlify** بالكامل — ألوانه الـ33، سلّم أنصافه، سلّم مسافاته، سلّم ارتفاعه، ونظام مكوّناته. التعديل الوحيد **نظام الطباعة**، لسبب تقني لا ذوقي: `Inter` و`Geist Mono` لا يحتويان محارف عربية، والتباعد الحرفي السالب يكسر اتصال الحروف.

**الشخصية البصرية:**
منصة تبدو كـ**أداة يثق بها طالب**، لا كموقع تسويقي يحاول إقناعه. أسطح مسطّحة، حدود شعرة رفيعة، أسود حاسم على الأزرار، وأخضر نعناعي يظهر **نادراً** فيصير ظهوره إشارة لا زينة.

**الركائز الخمس:**
1. **الأسود هو الفعل** — كل زر أساسي أسود حبّي. لا استثناء.
2. **الأخضر النعناعي إشارة نادرة** — التقدّم · الإكمال · الإجابة الصحيحة · الاشتراك الساري. **لا شيء غيرها.**
3. **البرتقالي يكسر الإيقاع مرة واحدة** — بطاقة واحدة في الشاشة كلها، للحظة عاطفية.
4. **مسطّح افتراضياً** — الهرمية من الحدود والمسافات، لا من الظلال.
5. **العربية تتنفّس** — ارتفاع سطر 1.75، تباعد حرفي صفر، حد أدنى 16px للنص المقروء.

---

## 🚫 ما نرفضه — الابتعاد عن نمطية الذكاء الاصطناعي

هذه ليست قائمة أذواق. كل بند منها علامة مميِّزة لواجهة مولّدة آلياً، وكلها **ممنوعة** في هذا المشروع:

| ❌ ممنوع | ✅ البديل في نظامنا |
|---|---|
| تدرّجات بنفسجية/زرقاء (`#667eea → #764ba2`) | أسطح مسطّحة بيضاء بحدود `{colors.hairline}` |
| زجاجية ضبابية (glassmorphism · `backdrop-blur`) | حدود شعرة 1px صريحة |
| بطاقات عائمة بظلال ملوّنة | `{elevation.flat}` + حد 1px |
| توهّج نيون · ظلال ملوّنة | ظل واحد فقط: `{elevation.mockup}` للـmockup |
| أيقونات emoji بدل أيقونات حقيقية | أيقونات خطية موحّدة السماكة |
| كل شيء متمركز عمودياً | تخطيطات غير متماثلة · محاذاة للبداية |
| ثلاث بطاقات متطابقة بأيقونات دائرية ملوّنة | صفوف بأوزان بصرية مختلفة |
| أشكال blob عضوية عشوائية في الخلفية | نطاقات هيرو جوّية محدّدة فقط |
| حدود متدرّجة (gradient borders) | `2px solid {colors.brand-green}` للمميّز فقط |
| صور stock: أشخاص يبتسمون أمام لابتوب | لقطات حقيقية من المنصة · رسوم مخصّصة |
| أنصاف عشوائية (17px · 23px) | سلّم الأنصاف حصراً |
| ألوان كثيرة «للحيوية» | أسود + أخضر نعناعي + برتقالي واحد |

> **الاختبار:** لو أزلت الشعار وسألت «هل هذه صُنعت بقالب؟» — الجواب يجب أن يكون لا. المصدر: الانضباط، لا الزخرفة.

---

## Colors

### الأساسي والتمييز

| اللون | القيمة | يُستخدم في |
|---|---|---|
| **Primary Black** `{colors.primary}` | `#0a0a0a` | كل زر أساسي · العناوين · التبويب النشط |
| **Mint** `{colors.brand-green}` | `#00d4a4` | تعبئة شريط التقدّم · حد البطاقة المميّزة · حلقة التركيز · علامة الإكمال |
| **Mint Deep** `{colors.brand-annotate}` | `#1ba673` | **نص** الحالات الخضراء (تباين أعلى من النعناعي على الأبيض) |
| **Orange** `{colors.accent-orange}` | `#f55a3c` | بطاقة واحدة فقط في الشاشة — كسر إيقاع مقصود |
| **Tag Blue** `{colors.brand-tag}` | `#3772cf` | شارة الفرع الدراسي |

> **قاعدة الندرة:** الأخضر النعناعي على سطح كبير أو نص عادي = فقدان الإشارة. لو ظهر في مكان لا يعني «تقدّم أو صواب» — احذفه.

### دلالات المجال

| الحالة | اللون | المكوّن |
|---|---|---|
| اشتراك ساري | `{colors.brand-annotate}` | `badge-sub-active` |
| اشتراك ≤7 أيام | `{colors.brand-warn}` `#c37d0d` | `badge-sub-expiring` + `promo-banner` |
| اشتراك منتهٍ | `{colors.brand-error}` `#d45656` | `badge-sub-expired` |
| محاضرة مكتملة | `{colors.brand-annotate}` | `badge-completed` |
| إجابة صحيحة | `{colors.brand-annotate}` | `quiz-option-correct` |
| إجابة خاطئة | `{colors.brand-error}` | `quiz-option-wrong` |
| الفرع الدراسي | `{colors.brand-tag}` | `badge-branch` |

> 🔴 **الأحمر يوضّح ولا يوبّخ.** `{colors.brand-error}` `#d45656` أحمر **مكتوم عمداً** — ليس `#ff0000`. الخلفية عند 8% شفافية فقط. لا أيقونة ✗ عملاقة، لا اهتزاز، لا صوت.

### سلّم الأسطح
`canvas` `#ffffff` → `surface-soft` `#fafafa` → `surface` `#f7f7f7`
الحدود: `hairline` `#e5e5e5` للبطاقات · `hairline-soft` `#ededed` لفواصل الصفوف.

### سلّم النص
`ink` `#0a0a0a` عناوين · `charcoal` `#1c1c1e` نص · `slate` `#3a3a3c` ثانوي · `steel` `#5a5a5c` وصفي · `stone` `#888888` تسميات · `muted` `#a8a8aa` معطّل.

### الوضع الداكن
Mintlify لم ينشر وضعاً داكناً — اشتُقّ من توكناته الداكنة الموجودة. **ليس رفاهية: الطلبة يدرسون ليلاً.**
`dark-canvas` `#0a0a0a` → `dark-surface` `#141416` → `dark-surface-2` `#1c1c1e` · حدود `#1f1f1f` / `#2a2a2c` · نص `#ffffff` / `#b3b3b3`.
`{colors.brand-green}` يبقى كما هو — يعمل على الداكن بتباين ممتاز.

---

## Typography — نظام ثلاثي النصوص

### توزيع الوجوه

| النص | الوجه | لماذا |
|---|---|---|
| **العربية** (كل الواجهة) | **IBM Plex Sans Arabic** | أقرب وجه عربي لحياد Inter ووضوحه · أوزان حقيقية 400/500/600/700 · مجاني |
| **اللاتيني والأرقام** | **Inter** ← وجه Mintlify نفسه | محفوظ كما هو |
| **المعادلات والكود** | **Geist Mono** ← وجه Mintlify نفسه | محفوظ كما هو · `dir="ltr"` |

**بدائل عربية مقبولة:** Noto Sans Arabic (أكثر حياداً) · Almarai (أنظف للعناوين). **مرفوض:** أي وجه بوزن واحد أو زخرفي.

### السلّم — والفرق عن Mintlify

| Token | Mintlify | palEducation | سبب التغيير |
|---|---|---|---|
| `hero-display` | 72px · **-2px** | **56px · 0** | التباعد السالب يكسر اتصال الحروف |
| `display-lg` | 56px · **-1.5px** | **44px · 0** | نفسه |
| `heading-1` | 48px · **-1px** | **36px · 0** | نفسه |
| `heading-2` | 36px · **-0.5px** | **28px · 0** | نفسه |
| `heading-3` | 28px · 1.25 | 24px · **1.45** | ارتفاع سطر للعربية |
| `subtitle` | 18px · 1.50 | 18px · **1.80** | نفسه |
| `body-md` | 16px · 1.50 | 16px · **1.75** | امتدادات المحارف تتصادم عند 1.50 |
| `body-sm` | **14px** | **15px** · 1.70 | 14px تحت الحد المقروء للعربية |
| `micro-uppercase` | 11px · UPPERCASE · +0.5px | **13px · بلا uppercase · 0** | لا حالة أحرف في العربية |
| `button-md` | 14px | **15px** | نفس سبب body-sm |

### القواعد الملزمة
1. **`letter-spacing: 0` على كل نص عربي** — بلا استثناء واحد
2. **≥16px لأي نص يقرأه الطالب** — وصف المحاضرة · سؤال الكويز · نص الخبر
3. **15px حد أدنى لعناصر الواجهة** — التنقّل · خلايا الجداول · الأزرار
4. **14px للبيانات الوصفية فقط** — لا يُقرأ كنص
5. **لا `text-transform: uppercase`** على العربية
6. **الأرقام غربية 0-9** بـ Inter و`tabular-nums` في كل جدول
7. **عرض القراءة ≤68 حرفاً**

---

## الاتجاه — RTL افتراضي

**لا `left` ولا `right` في المشروع.** logical properties حصراً: `ms/me` · `ps/pe` · `text-start/end` · `border-s/e` · `start-*/end-*`.

**يبقى LTR دائماً:** مشغّل الفيديو (`video-shell`) · المعادلات (`equation-block`) · الكود · الأرقام والتواريخ · البريد والروابط.

**النص المختلط:** أي مقطع لاتيني داخل جملة عربية يُلَفّ بـ`<bdi>` — حرج في المحتوى العلمي.

التفاصيل: [.claude/rules/rtl-bilingual.md](.claude/rules/rtl-bilingual.md)

---

## Components

### الأزرار — حبّية دائماً `{rounded.full}`

| الصيغة | التنسيق | الاستخدام |
|---|---|---|
| `button-primary` | أسود `#0a0a0a` · نص أبيض | **الفعل الرئيسي — واحد لكل شاشة** |
| `button-accent-green` | نعناعي · نص أسود | CTA التسجيل في الهيرو فقط |
| `button-on-dark` | أبيض · نص أسود | على نطاقات الهيرو الداكنة |
| `button-secondary` | شفاف · حد `hairline` | فعل ثانوي |
| `button-ghost` | شفاف · `{rounded.md}` | إجراءات الجداول |
| `button-danger` | `brand-error` | الحذف — عبر `ConfirmDialog` دائماً |

ارتفاع اللمس **≥44px** على الجوال. **لا تليّن الأنصاف الحبّية أبداً** — الزر المربّع يقرأ كأداة طرف ثالث.

### مكوّنات المجال

**`quiz-option`** — أهم مكوّن في المنصة
حد 1px · `{rounded.md}` · ارتفاع ≥56px · مساحة نقر كاملة العرض
مختار → حد 2px أسود · صحيح → حد `brand-annotate` وخلفية 10% · خاطئ → حد `brand-error` وخلفية 8%

**`video-shell`** — `dir="ltr"` · 16:9 · `{rounded.lg}` · خلفية `canvas-dark` · يُطلق حدث الانتهاء الذي يفتح الكويز

**`progress-bar`** — مسار `hairline` · تعبئة `brand-green` · ارتفاع 6px · `{rounded.full}`

**`badge-*`** — الفرع (أزرق) · المدة (رمادي + `tabular-nums`) · الإكمال (أخضر) · الاشتراك (٣ حالات)

**`promo-banner`** — شريط أسود أعلى الصفحة، يظهر **فقط** عند اقتراب انتهاء الاشتراك

**`equation-block`** — `dir="ltr"` · Geist Mono · خلفية `surface` — حرج للفرع العلمي

### التخطيط ثلاثي الأعمدة
نمط Mintlify الوثائقي مُسقَط على **صفحة المادة**:
`قائمة المحاضرات (240px)` \| `الفيديو والمحتوى (720px)` \| `معلومات المادة (200px)`
تحت 1024px → العمودان الجانبيان يصيران درجاً · تحت 768px → عمود واحد.

---

## Do's and Don'ts

### ✅ افعل
- اجعل كل زر أساسي **أسود حبّياً** — هذا توقيع النظام
- احجز `{colors.brand-green}` للتقدّم والصواب والاشتراك الساري فقط
- استخدم `{colors.brand-annotate}` للنص الأخضر لا `brand-green` (تباين)
- ابقَ مسطّحاً: حد 1px بدل الظل
- صفّر `letter-spacing` على كل عربية
- ارتفاع سطر ≥1.75 للنص المقروء
- `<bdi>` حول كل مقطع لاتيني داخل جملة عربية
- بطاقة برتقالية **واحدة** في الشاشة كلها
- `EmptyState` لكل قائمة · `ConfirmDialog` لكل حذف

### ❌ لا تفعل
- لا تليّن الأنصاف الحبّية للأزرار
- لا تضع نعناعياً على سطح كبير أو نص عادي
- لا تُدخل لوناً كروماتيكياً رابعاً
- لا تنسخ `letter-spacing` السالب من Mintlify
- لا تستخدم Inter للعربية — لا يحتوي محارفها
- لا تعكس مشغّل الفيديو
- لا تنزل بالنص المقروء تحت 16px
- لا تستخدم أرقاماً شرقية (٠-٩)
- لا تضع أكثر من زر أساسي في شاشة
- **لا شيء من قائمة «ما نرفضه» أعلاه**

---

## Responsive

| النقطة | العرض | التغيير |
|---|---|---|
| Mobile | <480px | عمود واحد · `hero-display` → 32px · الشريط الجانبي درج · لمس ≥44px |
| Mobile-lg | 480–767px | بطاقات 2-up · `hero-display` → 40px |
| Tablet | 768–1023px | شبكة 2-up · الشريط الجانبي أيقونات · التخطيط الثلاثي → عمودان |
| Desktop | 1024–1279px | التخطيط الثلاثي كامل · بطاقات 3-up |
| Wide | ≥1280px | الحاوية تتوقف عند 1280px |

**الجوال أولاً حرفياً** لشاشات: المحاضرة · الكويز · المادة. غالبية طلبة التوجيهي يفتحون المنصة من الهاتف.

---

## Accessibility

- تباين ≥**4.5:1** للنص · ≥3:1 للكبير — لهذا `brand-annotate` للنص لا `brand-green`
- حلقة تركيز مرئية على كل عنصر تفاعلي (`{elevation.focus}`) — **لا `outline: none`**
- هدف لمس ≥44×44px
- التنقّل بلوحة المفاتيح يعمل في الكويز كاملاً
- لا تنقل معنى باللون وحده — أضف أيقونة أو نصاً
- احترم `prefers-reduced-motion`

---

## Known Gaps

| # | الفجوة | الحالة |
|---|---|---|
| 1 | الشعار | ⏳ لم يصل |
| 2 | نطاق الاشتراك — يحدّد `pricing-card` | ⏳ م-1 |
| 3 | سلوك الكويز — يحدّد `quiz-option` | ⏳ م-2 |
| 4 | آلية الدفع | ⏳ م-4 |
| 5 | مزوّد الفيديو — يحدّد `video-shell` | ⏳ م-5 |
| 6 | الرسوم التوضيحية لنطاقات الهيرو | ⏳ |
