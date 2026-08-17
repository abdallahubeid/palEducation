---
name: using-superpowers
description: Use when starting work on any palEducation task - establishes how to find and route to the right skill before doing substantive work
adapted-for: palEducation (solo dev, Arabic conversational workflow)
---

<SUBAGENT-STOP>
If you were dispatched as a subagent to execute a specific task, ignore this skill.
</SUBAGENT-STOP>

> **⚠️ هذا السكيل مُعدَّل عن الأصل.** التعديل موثّق في القسم الأخير — اقرأه قبل الاعتماد عليه.

<IMPORTANT>
If there is a meaningful chance a skill applies to the work you are about to do, invoke it.

If a skill clearly applies to your task, you do not get to skip it because it feels like overhead.
</IMPORTANT>

## The Rule

**Invoke the relevant skill BEFORE substantive work** — before writing code, before building a component, before designing a screen, before fixing a bug, before planning.

**استثناء (تعديل خاص بهذا المشروع):** سؤال توضيحي قصير في المحادثة **لا يتطلّب** استدعاء سكيل أولاً. اسأل، ثم استدعِ السكيل عند بدء الشغل الفعلي. راجع القسم الأخير للسبب.

بعد الاستدعاء: أعلن "أستخدم سكيل [X] لـ[الغرض]" والتزم به. لو فيه checklist، اعمل مهمة لكل بند.

## Skill Priority

سكيلز العملية أولاً (تحدّد المنهج)، ثم سكيلز التنفيذ.

- "خلينا نبني X" → `brainstorming` أولاً
- "فيه bug" → `systematic-debugging` أولاً — الجذر قبل العَرَض
- "خلّص هذي المهمة" → `writing-plans` ثم `executing-plans`
- "خلّصت" → `verification-before-completion` قبل ادعاء الإنجاز

## توجيه خاص بـ palEducation

| الطلب | السكيل |
|---|---|
| ميزة جديدة · شاشة جديدة · مكوّن جديد | `brainstorming` |
| متطلبات غامضة أو متناقضة | `understand` |
| مهمة متعددة الخطوات | `writing-plans` → `executing-plans` |
| bug · اختبار فاشل · سلوك غريب | `systematic-debugging` |
| كتابة ميزة أو إصلاح | `test-driven-development` |
| قبل قول "تم" | `verification-before-completion` |
| مراجعة مخرجات قبل اعتمادها | `refine` |
| قاعدة مكتسبة تستحق البقاء | `doctrine-keeper` |
| سياق الجلسة امتلأ | `handoff` |

**قبل أي شغل يمسّ الواجهة:** اقرأ `DESIGN.md` و`.claude/rules/rtl-bilingual.md`. غير قابل للتفاوض.

**قبل أي شغل يمسّ قرار معلّق** (م-1 نطاق الاشتراك · م-2 سلوك الكويز · م-4 شاشات الدفع · م-5 استضافة الفيديو): **توقّف واسأل.** لا تفترض.

## Red Flags

هذه الأفكار تعني توقّف — أنت تبرّر:

| الفكرة | الحقيقة |
|---|---|
| "ده مجرد سؤال بسيط" | لو رح تكتب كود بعده، هو مهمة. افحص السكيلز. |
| "خليني أستكشف الكود أولاً" | السكيلز تقول لك **كيف** تستكشف. |
| "السكيل مبالغة هنا" | البسيط يصير معقّداً. استخدمه. |
| "أنا بعرف هذا السكيل" | السكيلز تتطوّر. اقرأ النسخة الحالية. |
| "خليني أعمل هذا الشي الواحد بس" | افحص **قبل** أي فعل. |
| "هذا بيحسّ إنه إنتاجي" | الفعل غير المنضبط يهدر وقتاً. |

## Platform Adaptation

لو ظهر الـ harness هنا، اقرأ ملفه في `references/`: Codex · Pi · Antigravity · Hermes · Gemini.

## User Instructions

تعليمات المستخدم (`CLAUDE.md`، طلب مباشر) **تسبق** السكيلز، والسكيلز تسبق السلوك الافتراضي. لا تتجاوز سكيلاً إلا لو قال المستخدم ذلك صراحةً.

---

## ما عُدِّل عن الأصل ولماذا

| الأصل | هنا | السبب |
|---|---|---|
| `<EXTREMELY-IMPORTANT>` + "1% chance" + "not negotiable" + "cannot rationalize" | `<IMPORTANT>` بصياغة أهدأ | الصياغة الأصلية تدفع لاستدعاء سكيل عند كل تفاعل تقريباً |
| "invoke BEFORE **any** response **including clarifying questions**" | استثناء للأسئلة التوضيحية القصيرة | جلسة عربية تفاعلية مع مطوّر منفرد؛ استدعاء سكيل قبل سؤال من سطر واحد احتكاك بلا مقابل |
| Red Flag: "Skill check comes BEFORE clarifying questions" | حُذف | يناقض الاستثناء أعلاه |
| Red Flag: "Questions are tasks" | "لو رح تكتب كود بعده، هو مهمة" | تمييز بين سؤال محادثة وسؤال يسبق تنفيذاً |
| — | جدول توجيه خاص بـ palEducation | ربط السكيلز بحالات المشروع الفعلية |
| — | تنبيه القرارات المعلّقة | أخطر مصدر لإعادة العمل في هذا المشروع |

**لاستعادة النسخة الأصلية حرفياً:**

```bash
git clone --depth 1 https://github.com/obra/superpowers.git /tmp/sp && cp /tmp/sp/skills/using-superpowers/SKILL.md .claude/skills/using-superpowers/SKILL.md
```
