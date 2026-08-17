# السكيلز المثبّتة — palEducation

16 سكيل مُنتقاة من مستودعين، ومُكيّفة لهذا المشروع.

**المصادر:**
- [obra/superpowers](https://github.com/obra/superpowers) — منهجيات هندسية (MIT)
- [AQaddora/pure-skill-suite](https://github.com/AQaddora/pure-skill-suite) — حلقة PURE (MIT)

---

## من `superpowers` (9)

| السكيل | متى تستخدمه |
|---|---|
| `brainstorming` | **قبل أي ميزة جديدة** — يحوّل الفكرة لتصميم قبل الكود |
| `writing-plans` | مهمة متعددة الخطوات تحتاج خطة مكتوبة |
| `executing-plans` | تنفيذ خطة مكتوبة بنقاط مراجعة |
| `systematic-debugging` | أي bug — **الجذر قبل العَرَض**، إصلاح الأعراض فشل |
| `test-driven-development` | كتابة الاختبار أولاً · Pest 5 مثبّت |
| `verification-before-completion` | **قبل قول "تم"** — دليل قبل الادعاء |
| `requesting-code-review` | مراجعة قبل اعتبار ميزة منتهية |
| `receiving-code-review` | التعامل مع الملاحظات بتحقّق لا بموافقة مجاملة |
| `writing-skills` | إنشاء سكيل جديد خاص بالمشروع |

## من `pure-skill-suite` (8)

| السكيل | متى تستخدمه |
|---|---|
| `prime` | بداية جلسة جديدة — تحميل سياق المشروع |
| `understand` | متطلبات غامضة — يسأل سؤالاً واحداً في كل مرة |
| `refine` | مراجعة ذاتية بعدسة مهندس أول قبل الاعتماد |
| `doctrine-keeper` | التقاط قاعدة مكتسبة إلى `.claude/rules/doctrine.md` |
| `handoff` | امتلاء سياق الجلسة — نقل للجلسة التالية بلا فقدان |
| `handoff-receiver` | استقبال جلسة منقولة |
| `status-beacon` | تسجيل تقدّم أثناء مهمة طويلة |
| `report-back` | تقرير إنجاز مكتوب في ملف لا في الدردشة فقط |

## من `awesome-design-md` (0)

هذا المستودع **لا يحتوي سكيلز إطلاقاً** — هو 74 ملف `DESIGN.md` فقط. أُخذ منه:
- **الصيغة** (tokens في YAML frontmatter) → بُني عليها `DESIGN.md` الخاص بالمشروع
- **5 مراجع** منسوخة إلى `.claude/design-references/`

---

## المجموعة الثانية — ثُبِّتت بطلب المستخدم (10)

كنت قد استبعدتها مبدئياً؛ المستخدم اختار التغطية الكاملة. **مثبّتة الآن، مع تحفّظات صريحة:**

| السكيل | المصدر | الحالة |
|---|---|---|
| `using-superpowers` | superpowers | ✏️ **مُعدَّل** — خُفِّفت بوّابة "استدعِ سكيلاً قبل أي رد بما فيها أسئلة التوضيح". التعديل موثّق داخل الملف |
| `using-git-worktrees` | superpowers | ⚠️ **لا يعمل** حتى `git init` |
| `finishing-a-development-branch` | superpowers | ⚠️ **لا يعمل** حتى `git init` |
| `dispatching-parallel-agents` | superpowers | ✅ يعمل — لكن كل وكيل يبدأ من سياق صفر |
| `subagent-driven-development` | superpowers | ⚠️ سكربتاته تكتب في `.superpowers/sdd/` وتفترض git |
| `execute` | pure | ✅ مُعاد التوجيه للمشروع |
| `organize-agents` | pure | ✅ سجلّ التشغيل → `.claude/runs/` |
| `pure-orchestrator` | pure | ✅ يعمل الآن — `execute` و`organize-agents` صارا مثبّتين |
| `3d-modeler` | pure | ⚠️ يتطلب `three` + `threebox-plugin` + `@lumaai/luma-web` — **غير مثبّتة** |
| `montage-creator` | pure | ⚠️ يتطلب `ffmpeg` — **غير مثبّت** |

**الحساب النهائي:** 14 (superpowers) + 13 (pure-skill-suite) = **27 متاح · 27 مثبّت.**
`awesome-design-md` = 0 سكيلز (74 ملف DESIGN.md) → أُخذت منه الصيغة + 5 مراجع.

### ملاحظة على الكلفة
كل سكيل مثبّت يظهر في قائمة السكيلز عند كل جلسة ويستهلك سياقاً. الـ27 مقبولة، لكن لو لاحظت بطئاً أو تشتتاً، أول المرشحين للحذف: `3d-modeler` · `montage-creator` (اعتمادياتهما غير مثبّتة أصلاً فلا يعملان).

لحذف أي سكيل:
```bash
rm -rf .claude/skills/<name>
```

---

## التعديلات المُطبَّقة

1. **إعادة توجيه مسارات الذاكرة** — كل مرجع لـ `~/ai-doctrine.md` خارج المشروع أُعيد توجيهه إلى `.claude/rules/doctrine.md` داخل المشروع، و`~/.vibe-coding/runs/` إلى `.claude/runs/`. السبب: طلبك كان حفظ كل شيء **داخل مجلد المشروع**.

2. **لم يُشغَّل `install.sh`** — كان سيُنشئ symlink يستبدل `CLAUDE.md` بملف `~/ai-doctrine.md` خارجي، ويثبّت السكيلز عالمياً في `~/.claude/skills/`. أي أنه كان **سيمسح** الـ`CLAUDE.md` المطلوب. النسخ تمّ يدوياً.

3. **لم تُثبَّت الـ hooks** — `superpowers/hooks/` يسجّل `SessionStart` hook يعمل عند كل بدء جلسة. تعديل سلوك الـ harness لم يكن ضمن الطلب.

---

## إضافة سكيلز لاحقاً

السكيلز المستبعدة موجودة في المستودعات الأصلية. لإضافة واحد:

```bash
git clone --depth 1 https://github.com/obra/superpowers.git /tmp/sp && cp -r /tmp/sp/skills/<name> .claude/skills/
```

أو اطلب مني إضافته وسأنسخه وأكيّفه.
