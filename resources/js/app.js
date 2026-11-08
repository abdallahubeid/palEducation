// palEducation — تفاعلات الواجهة
//
// ⚠️ Alpine مثبَّت عبر npm مباشرةً، لا عبر Livewire 3 (غير مثبّت).
//
// 🔁 كان **للوحة الأدمن وحدها**. لم يعد كذلك: نموذج إضافة رأي الطالب في
// الصفحة الرئيسية يستعمله (نافذة عائمة بحالة نجوم وإرسال غير متزامن) —
// وهي حالة تفاعلية حقيقية لا يبرّر إعادة كتابتها بـvanilla. الحزمة تُحمَّل
// أصلاً في كل صفحة، فالكلفة صفر.
//
// ما بقي من تفاعل الواجهة العامة يبقى vanilla — لا تُعاد كتابته بلا سبب.
import Alpine from 'alpinejs';

/*
 * SweetAlert2 — bundled through Vite, never a CDN.
 *
 * 🔴 The stylesheet is imported here rather than into app.css on purpose.
 * Tailwind 4 puts its utilities inside @layer and SweetAlert2 ships plain
 * unlayered CSS: unlayered rules beat layered ones regardless of
 * specificity, so pulling it into app.css would let .swal2-* silently
 * outrank Tailwind utilities anywhere the two meet. Imported from JS it
 * lands in its own chunk and only ever styles the dialog.
 */
/*
 * 🔴 مُجمَّع عبر npm لا عبر CDN — نفس قاعدة SweetAlert2: أداة الملف الشخصي
 * يجب أن تعمل بلا اتصال بطرف ثالث، وبنسخة مثبّتة لا تتغيّر تحتنا.
 */
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Alpine = Alpine;

/*
 * 🔴 Alpine.start() ليست هنا — بل في **آخر الملف** بعد تسجيل كل مكوّنات
 * Alpine.data().
 *
 * السبب: start() تُطلق حدث `alpine:init` **تزامنياً**. فاستدعاؤها هنا
 * يعني أن أي `Alpine.data(...)` مكتوب لاحقاً في الملف يُسجَّل بعد فوات
 * الحدث، فلا يوجد المكوّن أصلاً. والعطل صامت في الصفحة: `x-text` لا
 * يكتب شيئاً، و`:class` لا يُطبَّق فيظهر المفتاح **بلا خلفية** — أي
 * غير مرئي تماماً. وقع هذا فعلاً.
 */

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ── درج التنقّل على الجوال ───────────────────────────── */
function initNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const drawer = document.querySelector('[data-nav-drawer]');
    if (!toggle || !drawer) return;

    const setOpen = (open) => {
        drawer.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
    };

    toggle.addEventListener('click', () => setOpen(drawer.hidden));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !drawer.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });

    drawer.querySelectorAll('a').forEach((l) => l.addEventListener('click', () => setOpen(false)));

    window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
        if (e.matches) setOpen(false);
    });
}

/* ── الكشف عند التمرير + العدّادات + أشرطة التقدّم ───── */
function initReveal() {
    const items = document.querySelectorAll('.reveal, [data-count], [data-bar]');
    if (!items.length) return;

    const settle = (el) => {
        el.classList.add('is-in');
        if (el.hasAttribute('data-count')) el.textContent = formatNum(el.dataset.count);
        if (el.hasAttribute('data-bar')) el.style.width = el.dataset.bar + '%';
    };

    // بلا IntersectionObserver أو مع تقليل الحركة: أظهر كل شيء فوراً
    if (reduceMotion || !('IntersectionObserver' in window)) {
        items.forEach(settle);
        return;
    }

    // من هنا فقط نسمح للـCSS بالإخفاء — بعد التأكد أننا قادرون على الإظهار
    document.documentElement.classList.add('js-reveal');

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;

                el.classList.add('is-in');
                if (el.hasAttribute('data-count')) countUp(el);
                if (el.hasAttribute('data-bar')) el.style.width = el.dataset.bar + '%';

                io.unobserve(el);
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -40px' }
    );

    items.forEach((el) => io.observe(el));

    // شبكة أمان: المستند المخفي لا يُطلق IntersectionObserver.
    // لو بقي شيء مخفياً بعد 4 ثوانٍ، أظهره بلا حركة بدل ترك صفحة فارغة.
    setTimeout(() => {
        items.forEach((el) => {
            if (!el.classList.contains('is-in')) {
                settle(el);
                io.unobserve(el);
            }
        });
    }, 4000);
}

/* أرقام عربية-غربية بفاصل آلاف */
function formatNum(n) {
    return Number(n).toLocaleString('en-US');
}

function countUp(el) {
    const target = Number(el.dataset.count);
    const duration = 1400;
    const start = performance.now();

    const step = (now) => {
        const p = Math.min((now - start) / duration, 1);
        // easeOutExpo — سريع ثم يستقر
        const eased = p === 1 ? 1 : 1 - Math.pow(2, -10 * p);
        el.textContent = formatNum(Math.round(target * eased));
        if (p < 1) requestAnimationFrame(step);
    };

    requestAnimationFrame(step);
}

/* ── السلايدر — يدعم أكثر من نسخة في الصفحة ──────────── */
function initSliders() {
    document.querySelectorAll('[data-slider]').forEach(initSlider);
}

function initSlider(root) {
    if (!root) return;

    const slides = [...root.querySelectorAll('[data-slide]')];
    const dots = [...root.querySelectorAll('[data-slide-dot]')];
    if (slides.length < 2) return;

    const INTERVAL = Number(root.dataset.interval) || 6500;
    const DURATION = 1000; // مطابق لـ smartSpeed في القالب
    let index = 0;
    let timer = null;
    let animating = false;

    const show = (next, animate = true) => {
        const target = (next + slides.length) % slides.length;
        if (target === index && animate) return;
        if (animating) return;

        const outgoing = slides[index];
        const incoming = slides[target];
        index = target;

        slides.forEach((s, i) => {
            const on = i === index;
            s.setAttribute('aria-hidden', String(!on));
            // العناصر المخفية تخرج من ترتيب التنقّل بلوحة المفاتيح
            s.querySelectorAll('a, button').forEach((el) => {
                if (on) el.removeAttribute('tabindex');
                else el.setAttribute('tabindex', '-1');
            });
        });

        dots.forEach((d, i) => d.setAttribute('aria-selected', String(i === index)));

        if (!animate || reduceMotion || outgoing === incoming) {
            slides.forEach((s) => s.classList.remove('is-active', 'is-entering', 'is-leaving'));
            incoming.classList.add('is-active');
            return;
        }

        animating = true;

        outgoing.classList.remove('is-active');
        outgoing.classList.add('is-leaving');

        incoming.classList.add('is-active', 'is-entering');

        setTimeout(() => {
            outgoing.classList.remove('is-leaving');
            incoming.classList.remove('is-entering');
            animating = false;
        }, DURATION);
    };

    const start = () => {
        if (reduceMotion) return;
        stop();
        timer = setInterval(() => show(index + 1), INTERVAL);
    };

    const stop = () => {
        if (timer) clearInterval(timer);
        timer = null;
    };

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            show(i);
            start();
        });
    });

    root.querySelector('[data-slide-prev]')?.addEventListener('click', () => {
        show(index - 1);
        start();
    });

    root.querySelector('[data-slide-next]')?.addEventListener('click', () => {
        show(index + 1);
        start();
    });

    // التوقّف عند التحويم أو التركيز — لا تسحب الشريحة من تحت المستخدم
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);

    // التوقّف عند إخفاء التبويب — لا فائدة من التدوير بلا مشاهد
    document.addEventListener('visibilitychange', () => {
        document.visibilityState === 'hidden' ? stop() : start();
    });

    /* ── السحب بالماوس واللمس ─────────────────────────── */
    const THRESHOLD = 60;
    const isRtl = document.documentElement.dir === 'rtl';
    let startX = null;
    let dragging = false;

    const onDown = (e) => {
        // نتجاهل السحب لو بدأ من زر أو رابط — الضغط أولى
        if (e.target.closest('a, button')) return;
        startX = e.clientX;
        dragging = true;
        stop();
        root.classList.add('is-grabbing');
    };

    const onUp = (e) => {
        if (!dragging || startX === null) return;
        dragging = false;
        root.classList.remove('is-grabbing');

        const delta = e.clientX - startX;
        startX = null;

        if (Math.abs(delta) >= THRESHOLD) {
            // في RTL السحب لليمين يعني «التالي»
            const forward = isRtl ? delta > 0 : delta < 0;
            show(index + (forward ? 1 : -1));
        }

        start();
    };

    root.addEventListener('pointerdown', onDown);
    root.addEventListener('pointerup', onUp);
    root.addEventListener('pointercancel', () => {
        dragging = false;
        startX = null;
        root.classList.remove('is-grabbing');
        start();
    });

    // منع سحب الصور داخل الشريحة أثناء السحب
    root.querySelectorAll('img').forEach((img) => (img.draggable = false));

    /* ── الأسهم بلوحة المفاتيح ────────────────────────── */
    root.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        e.preventDefault();
        const forward = isRtl ? e.key === 'ArrowLeft' : e.key === 'ArrowRight';
        show(index + (forward ? 1 : -1));
        start();
    });

    show(0, false);
    start();
}

/* ── العودة لأعلى الصفحة ──────────────────────────────── */
function initToTop() {
    const btn = document.querySelector('[data-to-top]');
    if (!btn) return;

    // العتبة تُحسب من ارتفاع الهيرو لا برقم ثابت:
    // الزر لا يظهر إلا بعد تجاوز الهيرو فعلياً مهما تغيّر ارتفاعه.
    const hero = document.querySelector('.hero-canvas');
    let threshold = 600;

    const measure = () => {
        threshold = hero
            ? hero.offsetTop + hero.offsetHeight - 120
            : 600;
    };

    const toggle = () => {
        btn.classList.toggle('is-shown', window.scrollY > threshold);
    };

    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    });

    window.addEventListener('scroll', toggle, { passive: true });
    window.addEventListener('resize', () => { measure(); toggle(); }, { passive: true });

    measure();
    toggle();
}

/* ── مودالات — عامة لأي [data-modal] بالصفحة ─────────────
   الفتح: أي عنصر data-modal-open="id" يفتح #id. الإغلاق: data-modal-close
   داخله، أو النقر على الخلفية، أو Esc. modals[id].open() متاحة للفتح
   البرمجي (مثال: مودال "جاهز للاختبار؟" عند انتهاء المحاضرة). */
const modals = {};

function initModals() {
    document.querySelectorAll('[data-modal]').forEach((modal) => {
        const id = modal.id;
        if (!id) return;

        const closers = modal.querySelectorAll('[data-modal-close]');
        let lastFocused = null;

        const open = () => {
            lastFocused = document.activeElement;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            closers[0]?.focus();
        };

        const close = () => {
            modal.hidden = true;
            document.body.style.overflow = '';
            lastFocused?.focus();
        };

        document.querySelectorAll(`[data-modal-open="${id}"]`).forEach((o) => o.addEventListener('click', open));
        closers.forEach((c) => c.addEventListener('click', close));

        // فتح تلقائي عند حدث مخصّص — مثال: data-modal-open-on="lecture:ended"
        if (modal.dataset.modalOpenOn) {
            document.addEventListener(modal.dataset.modalOpenOn, open);
        }

        // الإغلاق بالنقر على الخلفية أو بمفتاح Esc
        modal.addEventListener('click', (e) => {
            if (e.target === modal) close();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.hidden) close();
        });

        modals[id] = { open, close };
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initNav();
    initReveal();
    initSliders();
    initToTop();
    initModals();
    initAccordionFallback();
    initStudentSidebar();
    initTabs();
    initLecturePlayer();
    initLectureProgress();
    initFocusMode();
    initQuizRunner();
    initLectureViewSwitcher();
    initStandaloneQuizResult();
    initSubjectFilter();
    initLibrary();
    initNotifications();
    initProfileForm();
    initNotificationDropdown();
    initSidebarCollapse();
    initPasswordToggle();
    initDirectionToggle();
    initDemoForms();
    initRegisterWizard();
    initForgotPassword();
    initBranchPicker();
    initDataTables();
    initDropzones();
    initQuizBuilder();
    initBranchFilter();
    initNewsFilter();
    initReadingProgress();
    initShare();
    initPlanCalculator();
    initContactForm();
    initHashScroll();
    initAdminFilter();
    initRolePermissions();
    initAdminShell();
    initTrashSelection();
    initFlash();
    initConfirmActions();
    initSettingsTabs();
});

/* ── الوصول لمرساة عبر الصفحات (#about · #pricing) ────────
   الشريط العام يربط «من نحن» و«الأسعار» بقسمين على الرئيسية.

   احتياطي دفاعي لا إصلاح لعطل مؤكَّد: الكشف عند التمرير (.js-reveal)
   يُخفي المحتوى ويزيح مواضع العناصر أثناء التحميل، وهو سبب معروف
   لضياع تمرير المرساة التلقائي. لم يُتحقَّق منه في بيئة المعاينة
   لأن التمرير البرمجي لا يعمل فيها أصلاً (حتى behavior:'auto').
   الاستدعاء عديم الأثر لو كان المتصفّح قد مرّر فعلاً. */
function initHashScroll() {
    const hash = window.location.hash;
    if (!hash || hash.length < 2) return;

    let target;
    try {
        target = document.querySelector(hash);
    } catch {
        return; // hash غير صالح كمحدِّد CSS
    }
    if (!target) return;

    const jump = () => {
        target.scrollIntoView({
            behavior: reduceMotion ? 'auto' : 'smooth',
            block: 'start',
        });
    };

    // إطارَان: الأول بعد التخطيط، والثاني بعد أن يستقرّ الكشف عند التمرير
    requestAnimationFrame(() => requestAnimationFrame(jump));
}

/* ── فهرس الفروع — بحث على العميل ─────────────────────────
   أربعة فروع فقط، فالبحث هنا كماليّ لا ضروري — لكنه يمنع
   شعور «لا يوجد ما أفعله» على صفحة قصيرة. */
function initBranchFilter() {
    const root = document.querySelector('[data-branch-filter]');
    const grid = document.querySelector('[data-branch-grid]');
    if (!root || !grid) return;

    const search = root.querySelector('[data-branch-search]');
    const items = Array.from(grid.querySelectorAll('[data-branch-item]'));
    const empty = document.querySelector('[data-branch-empty]');

    search?.addEventListener('input', () => {
        const term = search.value.trim().toLowerCase();

        items.forEach((item) => {
            item.hidden = term !== '' && !(item.dataset.search || '').toLowerCase().includes(term);
        });

        const anyVisible = items.some((i) => !i.hidden);
        grid.hidden = !anyVisible;
        if (empty) empty.hidden = anyVisible;
    });
}

/* ── فهرس الأخبار — بحث + تصنيف، بمنطق AND بينهما ───────── */
function initNewsFilter() {
    const root = document.querySelector('[data-news-filter]');
    if (!root) return;

    const search = root.querySelector('[data-news-search]');
    const chips = Array.from(root.querySelectorAll('[data-news-cat]'));
    const items = Array.from(root.querySelectorAll('[data-news-item]'));
    const empty = root.querySelector('[data-news-empty]');
    let activeCat = '';

    function apply() {
        const term = (search?.value || '').trim().toLowerCase();

        items.forEach((item) => {
            const matchesTerm = !term || (item.dataset.search || '').toLowerCase().includes(term);
            const matchesCat = !activeCat || item.dataset.cat === activeCat;
            item.hidden = !(matchesTerm && matchesCat);
        });

        const visible = items.filter((i) => !i.hidden);
        if (empty) empty.hidden = visible.length > 0;

        // عدّاد النتائج الحيّ
        const counter = root.querySelector('[data-news-count]');
        if (counter) counter.textContent = String(visible.length);

        // الكتلة كاملة (ترويسة + شبكة) تختفي إذا خلت من نتائج — لا ترويسة فوق فراغ
        root.querySelectorAll('[data-news-block]').forEach((block) => {
            block.hidden = !Array.from(block.querySelectorAll('[data-news-item]')).some((i) => !i.hidden);
        });
    }

    search?.addEventListener('input', apply);

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            activeCat = chip.dataset.newsCat;
            chips.forEach((c) => {
                const on = c === chip;
                c.classList.toggle('is-active', on);
                c.setAttribute('aria-selected', String(on));
            });
            apply();
        });
    });

    apply();
}

/* ── منطقة الرفع بالسحب والإفلات ──────────────────────────
   ⚠️ بلا خادم: شريط التقدّم محاكاة صريحة (data-dropzone-simulate).
   يُستبدل بتقدّم رفع حقيقي عند حسم م-5. */
function initDropzones() {
    document.querySelectorAll('[data-dropzone]').forEach((zone) => {
        const input = zone.querySelector('[data-dropzone-input]');
        const target = zone.querySelector('[data-dropzone-target]');
        const list = zone.querySelector('[data-dropzone-files]');
        const tpl = zone.querySelector('[data-dropzone-file-template]');
        if (!input || !target || !list || !tpl) return;

        const simulate = zone.hasAttribute('data-dropzone-simulate');
        const multiple = input.hasAttribute('multiple');

        const fmtSize = (bytes) => {
            if (bytes < 1024) return `${bytes} B`;
            if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
            if (bytes < 1073741824) return `${(bytes / 1048576).toFixed(1)} MB`;
            return `${(bytes / 1073741824).toFixed(2)} GB`;
        };

        function addFile(file) {
            if (!multiple) list.innerHTML = '';

            const node = tpl.content.firstElementChild.cloneNode(true);
            node.querySelector('[data-file-name]').textContent = file.name;
            node.querySelector('[data-file-size]').textContent = fmtSize(file.size);

            node.querySelector('[data-file-remove]').addEventListener('click', () => {
                node.remove();
                if (!list.children.length) input.value = '';
            });

            list.appendChild(node);

            if (simulate) {
                const wrap = node.querySelector('[data-file-progress]');
                const bar = node.querySelector('[data-file-bar]');
                const done = node.querySelector('[data-file-done]');
                wrap.hidden = false;

                let pct = 0;
                const tick = setInterval(() => {
                    pct = Math.min(100, pct + Math.random() * 18 + 6);
                    bar.style.width = `${pct}%`;
                    if (pct >= 100) {
                        clearInterval(tick);
                        wrap.hidden = true;
                        done.hidden = false;
                        // حفظ مسودّة تلقائي — يظهر بعد اكتمال الرفع
                        document.querySelector('[data-draft-saved]')?.removeAttribute('hidden');
                    }
                }, 260);
            }
        }

        input.addEventListener('change', () => {
            Array.from(input.files || []).forEach(addFile);
        });

        // السحب والإفلات
        ['dragenter', 'dragover'].forEach((ev) =>
            target.addEventListener(ev, (e) => {
                e.preventDefault();
                target.classList.add('border-accent', 'bg-accent-soft/40');
            }));

        ['dragleave', 'drop'].forEach((ev) =>
            target.addEventListener(ev, (e) => {
                e.preventDefault();
                target.classList.remove('border-accent', 'bg-accent-soft/40');
            }));

        target.addEventListener('drop', (e) => {
            const files = Array.from(e.dataTransfer?.files || []);
            (multiple ? files : files.slice(0, 1)).forEach(addFile);
        });
    });
}

/* ── بناء الكويز — أعقد نموذج في المشروع ──────────────────
   الحالة تعيش في الـDOM (استنساخ <template>) لا في كائن JS موازٍ،
   فينتقل نفس الـmarkup إلى Livewire لاحقاً بلا إعادة تصميم. */
function initQuizBuilder() {
    const root = document.querySelector('[data-quiz-builder]');
    if (!root) return;

    const list = root.querySelector('[data-questions]');
    const emptyEl = root.querySelector('[data-questions-empty]');
    const addBtn = root.querySelector('[data-add-question]');
    const countEl = root.querySelector('[data-question-count]');
    const pointsEl = root.querySelector('[data-total-points]');
    const publishBtn = root.querySelector('[data-publish]');
    const publishHint = root.querySelector('[data-publish-hint]');

    const qTpl = document.querySelector('[data-question-template]');
    const optTpl = document.querySelector('[data-option-template]');
    const tfTpl = document.querySelector('[data-truefalse-template]');
    if (!list || !qTpl) return;

    let seq = 0;
    let pendingDelete = null;

    function refresh() {
        const questions = Array.from(list.querySelectorAll('[data-question]'));

        questions.forEach((q, i) => {
            q.querySelector('[data-question-number]').textContent = i + 1;
            q.querySelector('[data-move-up]').disabled = i === 0;
            q.querySelector('[data-move-down]').disabled = i === questions.length - 1;
            [q.querySelector('[data-move-up]'), q.querySelector('[data-move-down]')].forEach((b) => {
                b.classList.toggle('opacity-30', b.disabled);
                b.classList.toggle('pointer-events-none', b.disabled);
            });
        });

        const total = questions.reduce((sum, q) => {
            const v = parseInt(q.querySelector('[data-question-points] input')?.value, 10);
            return sum + (Number.isNaN(v) ? 0 : v);
        }, 0);

        if (countEl) countEl.textContent = questions.length;
        if (pointsEl) pointsEl.textContent = total;
        if (emptyEl) emptyEl.hidden = questions.length > 0;

        // م-3: لا نشر بلا سؤال واحد على الأقل
        if (publishBtn) publishBtn.disabled = questions.length === 0;
        if (publishHint) publishHint.hidden = questions.length > 0;
    }

    function addOption(question, checked = false) {
        const optWrap = question.querySelector('[data-options]');
        const node = optTpl.content.firstElementChild.cloneNode(true);
        const radio = node.querySelector('[data-correct-radio]');

        radio.name = `correct_${question.dataset.qid}`;
        radio.checked = checked;

        node.querySelector('[data-remove-option]').addEventListener('click', () => {
            if (optWrap.querySelectorAll('[data-option]').length <= 2) return; // خياران حدّ أدنى
            node.remove();
        });

        optWrap.appendChild(node);
    }

    function addQuestion() {
        const node = qTpl.content.firstElementChild.cloneNode(true);
        node.dataset.qid = ++seq;

        // MCQ: أربعة خيارات افتراضية، الأول صحيح
        for (let i = 0; i < 4; i++) addOption(node, i === 0);

        // صح/خطأ
        const tfWrap = node.querySelector('[data-truefalse]');
        [['true', 'صح'], ['false', 'خطأ']].forEach(([val, label]) => {
            const tf = tfTpl.content.firstElementChild.cloneNode(true);
            const r = tf.querySelector('[data-tf-radio]');
            r.name = `tf_${node.dataset.qid}`;
            r.value = val;
            if (val === 'true') r.checked = true;
            tf.querySelector('[data-tf-label]').textContent = label;
            tfWrap.appendChild(tf);
        });

        // تبديل نوع السؤال يبدّل كتلة الخيارات المعروضة
        const typeSelect = node.querySelector('[data-question-type] select');
        typeSelect.addEventListener('change', () => {
            node.querySelectorAll('[data-qtype-block]').forEach((block) => {
                block.hidden = block.dataset.qtypeBlock !== typeSelect.value;
            });
        });

        node.querySelector('[data-add-option]').addEventListener('click', () => addOption(node));
        node.querySelector('[data-question-points] input').addEventListener('input', refresh);

        node.querySelector('[data-move-up]').addEventListener('click', () => {
            const prev = node.previousElementSibling;
            if (prev) { list.insertBefore(node, prev); refresh(); }
        });

        node.querySelector('[data-move-down]').addEventListener('click', () => {
            const next = node.nextElementSibling;
            if (next) { list.insertBefore(next, node); refresh(); }
        });

        node.querySelector('[data-remove-question]').addEventListener('click', () => {
            pendingDelete = node;
            modals['question-delete']?.open();
        });

        list.appendChild(node);
        refresh();
        node.querySelector('textarea')?.focus();
    }

    // تأكيد الحذف — كل حذف يمرّ عبر ConfirmDialog
    document.querySelector('#question-delete [data-confirm-accept]')?.addEventListener('click', (e) => {
        e.preventDefault();
        pendingDelete?.remove();
        pendingDelete = null;
        modals['question-delete']?.close();
        refresh();
    });

    addBtn?.addEventListener('click', addQuestion);
    refresh();
}


/* ── جدول البيانات — بحث + فرز + فلاتر + ترقيم، كله على العميل ──────
   يخدم أي [data-table] بالصفحة. الصفوف تُقرأ من الـDOM لا من مصفوفة JS،
   فالترتيب يبقى في المصدر ويسهل نقله لـLivewire لاحقاً بلا إعادة تصميم.

   عقد الصف: data-row · data-search · data-sort-{key} · data-filter-{name}
   حالتان فارغتان مختلفتان عمداً: «لا بيانات» ≠ «لا نتائج للبحث». */
function initDataTables() {
    document.querySelectorAll('[data-table]').forEach((table) => {
        const body = table.querySelector('[data-table-body]');
        if (!body) return;

        const allRows = Array.from(body.querySelectorAll('[data-row]'));
        const searchInput = table.querySelector('[data-table-search]');
        const filters = Array.from(table.querySelectorAll('[data-table-filter]'));
        const emptyEl = table.querySelector('[data-table-empty]');
        const noResultsEl = table.querySelector('[data-table-no-results]');
        const countEl = table.querySelector('[data-table-count]');
        const pagerEl = table.querySelector('[data-table-pagination]');
        const footerEl = table.querySelector('[data-table-footer]');
        const tableEl = table.querySelector('table');

        const perPage = parseInt(table.dataset.perPage, 10) || 8;
        let currentPage = 1;
        let sortKey = null;
        let sortDir = 'asc';

        const getFilterName = (el) => el.dataset.tableFilter
            || el.querySelector('[data-table-filter]')?.dataset.tableFilter;

        // الفلاتر مبنية على x-ui.select: العنصر الخارجي يحمل السمة، والقيمة في <select> بداخله
        const filterControls = filters.map((el) => ({
            name: getFilterName(el),
            input: el.matches('select') ? el : el.querySelector('select'),
        })).filter((f) => f.name && f.input);

        function matches(row) {
            const term = (searchInput?.value || '').trim().toLowerCase();
            if (term && !(row.dataset.search || '').toLowerCase().includes(term)) return false;

            for (const f of filterControls) {
                const val = f.input.value;
                if (val && row.dataset[`filter${f.name.charAt(0).toUpperCase()}${f.name.slice(1)}`] !== val) {
                    return false;
                }
            }
            return true;
        }

        function sortRows(rows) {
            if (!sortKey) return rows;

            const btn = table.querySelector(`[data-sort-key="${sortKey}"]`);
            const numeric = btn?.dataset.sortType === 'number';

            return rows.slice().sort((a, b) => {
                const av = a.dataset[`sort${sortKey.charAt(0).toUpperCase()}${sortKey.slice(1)}`] ?? '';
                const bv = b.dataset[`sort${sortKey.charAt(0).toUpperCase()}${sortKey.slice(1)}`] ?? '';

                const cmp = numeric
                    ? (parseFloat(av) || 0) - (parseFloat(bv) || 0)
                    // مقارنة عربية صحيحة — localeCompare بلغة ar لا ترتيب بايتات
                    : String(av).localeCompare(String(bv), 'ar');

                return sortDir === 'asc' ? cmp : -cmp;
            });
        }

        function render() {
            const visible = sortRows(allRows.filter(matches));
            const total = visible.length;
            const pages = Math.max(1, Math.ceil(total / perPage));
            if (currentPage > pages) currentPage = pages;

            const start = (currentPage - 1) * perPage;
            const pageRows = visible.slice(start, start + perPage);

            // إعادة الترتيب في الـDOM لتطبيق الفرز فعلياً
            allRows.forEach((r) => { r.hidden = true; });
            pageRows.forEach((r) => { r.hidden = false; body.appendChild(r); });

            const hasAnyData = allRows.length > 0;
            const hasResults = total > 0;

            if (emptyEl) emptyEl.hidden = hasAnyData;
            if (noResultsEl) noResultsEl.hidden = !hasAnyData || hasResults;
            if (tableEl) tableEl.parentElement.hidden = !hasResults;
            if (footerEl) footerEl.hidden = !hasResults;

            if (countEl && hasResults) {
                countEl.textContent = (countEl.dataset.countTemplate || ':from–:to / :total')
                    .replace(':from', start + 1)
                    .replace(':to', start + pageRows.length)
                    .replace(':total', total);
            }

            renderPager(pages);
        }

        function renderPager(pages) {
            if (!pagerEl) return;
            pagerEl.innerHTML = '';
            if (pages <= 1) return;

            const mkBtn = (label, page, opts = {}) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = opts.active
                    ? 'num grid size-11 lg:size-9 shrink-0 place-items-center rounded-md bg-accent text-ui font-medium text-on-primary'
                    : 'num grid size-11 lg:size-9 shrink-0 place-items-center rounded-md text-ui font-medium text-steel transition hover:bg-surface disabled:pointer-events-none disabled:opacity-40';
                b.textContent = label;
                b.disabled = !!opts.disabled;
                if (opts.active) b.setAttribute('aria-current', 'page');
                b.addEventListener('click', () => { currentPage = page; render(); });
                return b;
            };

            pagerEl.appendChild(mkBtn('‹', currentPage - 1, { disabled: currentPage === 1 }));
            for (let p = 1; p <= pages; p++) {
                pagerEl.appendChild(mkBtn(String(p), p, { active: p === currentPage }));
            }
            pagerEl.appendChild(mkBtn('›', currentPage + 1, { disabled: currentPage === pages }));
        }

        // الفرز — نقرة تبدّل الاتجاه، وaria-sort يعكس الحالة لقارئ الشاشة
        table.querySelectorAll('[data-sort-key]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.sortKey;
                if (sortKey === key) {
                    sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortKey = key;
                    sortDir = 'asc';
                }

                table.querySelectorAll('[data-sort-col]').forEach((th) => {
                    const isActive = th.dataset.sortCol === sortKey;
                    th.setAttribute('aria-sort', isActive ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none');
                    const icon = th.querySelector('[data-sort-icon]');
                    if (icon) {
                        icon.classList.toggle('rotate-180', isActive && sortDir === 'desc');
                        icon.classList.toggle('text-accent', isActive);
                        icon.classList.toggle('text-muted', !isActive);
                    }
                });

                currentPage = 1;
                render();
            });
        });

        searchInput?.addEventListener('input', () => { currentPage = 1; render(); });
        filterControls.forEach((f) => f.input.addEventListener('change', () => { currentPage = 1; render(); }));

        render();
    });
}

/* ── نماذج تجريبية — بلا خادم بعد ─────────────────────────
   تمنع الإرسال الفعلي وتنتقل للوجهة. عمداً بدل method="GET":
   إرسال GET كان سيضع كلمة المرور في شريط العنوان. */
function initDemoForms() {
    document.querySelectorAll('[data-demo-submit]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!form.reportValidity()) return;
            window.location.href = form.dataset.demoSubmit;
        });
    });
}

/* ── معالج إنشاء الحساب — خطوتان ──────────────────────────
   9 حقول في شاشة واحدة = إرهاق نموذج. الانتقال للخطوة 2 لا يتم
   إلا بعد اجتياز حقول الخطوة 1 للتحقق الأصلي في المتصفّح. */
function initRegisterWizard() {
    const wizard = document.querySelector('[data-wizard]');
    if (!wizard) return;

    const steps = wizard.querySelectorAll('[data-wizard-step]');
    const bars = wizard.querySelectorAll('[data-wizard-bar]');
    const status = wizard.querySelector('[data-wizard-status]');
    const titleEl = wizard.querySelector('[data-wizard-title]');
    if (!steps.length) return;

    const titles = [wizard.dataset.step1Title, wizard.dataset.step2Title];

    const show = (index) => {
        steps.forEach((step) => {
            step.hidden = Number(step.dataset.wizardStep) !== index;
        });

        bars.forEach((bar) => {
            const active = Number(bar.dataset.wizardBar) <= index;
            bar.classList.toggle('bg-accent', active);
            bar.classList.toggle('bg-hairline', !active);
        });

        if (status) status.textContent = status.dataset.template.replace(':current', index);
        if (titleEl && titles[index - 1]) titleEl.textContent = titles[index - 1];

        // أول حقل في الخطوة الجديدة يستقبل التركيز — لا يبدأ المستخدم ضائعاً
        steps[index - 1]?.querySelector('input, select')?.focus();
        wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    wizard.querySelector('[data-wizard-next]')?.addEventListener('click', () => {
        const current = wizard.querySelector('[data-wizard-step="1"]');
        const fields = current.querySelectorAll('input[required], select[required]');

        for (const field of fields) {
            if (!field.checkValidity()) {
                field.reportValidity();
                return;
            }
        }
        show(2);
    });

    wizard.querySelector('[data-wizard-prev]')?.addEventListener('click', () => show(1));
}

/* ── نسيت كلمة المرور — حالة النجاح تحلّ محلّ النموذج ────── */
function initForgotPassword() {
    const root = document.querySelector('[data-forgot]');
    if (!root) return;

    const formWrap = root.querySelector('[data-forgot-form]');
    const sentWrap = root.querySelector('[data-forgot-sent]');
    const form = root.querySelector('[data-forgot-submit]');

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        if (!form.reportValidity()) return;

        formWrap.hidden = true;
        sentWrap.hidden = false;
        sentWrap.querySelector('[data-forgot-resend]')?.focus();
    });

    root.querySelector('[data-forgot-resend]')?.addEventListener('click', (e) => {
        e.currentTarget.disabled = true;
        e.currentTarget.classList.add('opacity-50', 'pointer-events-none');
    });
}

/* ── اختيار الفرع — قرار شبه دائم ─────────────────────────
   زر التأكيد معطّل حتى يقع اختيار فعلي، ومودال التأكيد يذكر اسم
   الفرع المختار حرفياً — «هل أنت متأكد؟» عامة لا تحقّق الغرض. */
function initBranchPicker() {
    const picker = document.querySelector('[data-branch-picker]');
    if (!picker) return;

    const confirmBtn = picker.querySelector('[data-branch-confirm]');
    const nameSlot = document.querySelector('[data-branch-confirm-name]');
    if (!confirmBtn) return;

    picker.addEventListener('change', (e) => {
        if (e.target.name !== 'branch_id') return;

        confirmBtn.disabled = false;

        const label = e.target.closest('[data-branch-name]');
        if (nameSlot && label) nameSlot.textContent = label.dataset.branchName;
    });

    confirmBtn.addEventListener('click', () => {
        if (confirmBtn.disabled) return;
        modals['branch-confirm']?.open();
    });

    /* حين يكون المُنتقي نموذج POST حقيقياً (تدفّق تسجيل جوجل)، زر
       التأكيد داخل المودال يُرسل النموذج بدل أن ينتقل برابط — المودال
       يبني زرّه كـ<a href> افتراضياً وهذا لا يصلح لطلب POST. */
    if (picker.tagName === 'FORM' && picker.method?.toLowerCase() === 'post') {
        document
            .getElementById('branch-confirm')
            ?.querySelector('[data-confirm-accept]')
            ?.addEventListener('click', (e) => {
                e.preventDefault();
                picker.submit();
            });
    }
}

/* ── كشف/إخفاء كلمة المرور ────────────────────────────────
   يعمل لأي [data-password-field] بالصفحة. الزر type=button فلا
   يُرسل النموذج، وaria-pressed يعكس الحالة لقارئ الشاشة. */
function initPasswordToggle() {
    document.querySelectorAll('[data-password-field]').forEach((field) => {
        const input = field.querySelector('[data-password-input]');
        const toggle = field.querySelector('[data-password-toggle]');
        if (!input || !toggle) return;

        toggle.addEventListener('click', () => {
            const revealed = input.type === 'text';
            input.type = revealed ? 'password' : 'text';

            const label = revealed ? toggle.dataset.showLabel : toggle.dataset.hideLabel;
            toggle.setAttribute('aria-label', label || '');
            toggle.setAttribute('aria-pressed', String(!revealed));

            // تبديل الأيقونة بتبديل مسارها — لا إعادة بناء للعنصر
            const svg = toggle.querySelector('svg');
            if (svg) {
                svg.innerHTML = revealed ? PASSWORD_ICONS.eye : PASSWORD_ICONS.eyeOff;
            }
        });
    });
}

/* مسارات الأيقونتين — مطابقة حرفياً لما في components/icon.blade.php */
const PASSWORD_ICONS = {
    eye: '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.75"/>',
    eyeOff:
        '<path d="M10.6 6.7A8 8 0 0 1 12 6.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-2.7 3.6"/>' +
        '<path d="M6.2 8.4A16.6 16.6 0 0 0 2.5 12s3.5 6.5 9.5 6.5a9 9 0 0 0 3.4-.7"/>' +
        '<path d="M10.1 10.1a2.75 2.75 0 0 0 3.8 3.8"/><path d="m3.5 3.5 17 17"/>',
};

/* ── مبدّل الاتجاه — صفحة دليل النظام فقط ──────────────────
   يقلب dir على <html> مباشرة كي تُختبر القواعد المنطقية فعلياً
   لا محاكاةً. ملاحظة: قاعدة [dir='ltr'] في app.css تُبدّل الخط إلى
   Inter أيضاً — سلوك مقصود (الواجهة الإنجليزية بـInter) لا عطل. */
function initDirectionToggle() {
    const toggle = document.querySelector('[data-dir-toggle]');
    if (!toggle) return;

    const label = toggle.querySelector('[data-dir-label]');
    const root = document.documentElement;

    toggle.addEventListener('click', () => {
        const next = root.getAttribute('dir') === 'rtl' ? 'ltr' : 'rtl';
        root.setAttribute('dir', next);

        if (label) {
            label.textContent = next === 'rtl' ? toggle.dataset.labelRtl : toggle.dataset.labelLtr;
        }
    });
}

/* ── قائمة الإشعارات المنسدلة في الشريط العلوي ────────────
   يفتح/يغلق بالنقر على الجرس، يُغلق بالنقر خارجه أو بمفتاح Esc —
   بلا Alpine (غير مثبّت)، نفس نمط initModals(). */
function initNotificationDropdown() {
    const wrap = document.querySelector('[data-notif-dropdown]');
    if (!wrap) return;

    const toggle = wrap.querySelector('[data-notif-toggle]');
    const panel = wrap.querySelector('[data-notif-panel]');
    const markAllBtn = wrap.querySelector('[data-notif-mark-all]');
    const dot = toggle.querySelector('[data-notif-dot]');

    const close = () => {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    const open = () => {
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
    };

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        if (panel.hidden) open(); else close();
    });

    document.addEventListener('click', (e) => {
        if (!panel.hidden && !wrap.contains(e.target)) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) {
            close();
            toggle.focus();
        }
    });

    markAllBtn?.addEventListener('click', () => {
        panel.querySelectorAll('[data-notification-item]').forEach((item) => {
            item.classList.remove('bg-accent-soft/50');
            item.querySelector('[data-unread-dot]')?.remove();
        });
        dot?.remove();
        markAllBtn.hidden = true;
    });
}

/* ── طي الشريط الجانبي إلى أيقونات فقط — تفضيل محفوظ محلياً ── */
function initSidebarCollapse() {
    const shell = document.querySelector('[data-shell]');
    const toggle = document.querySelector('[data-sidebar-collapse-toggle]');
    if (!shell || !toggle) return;

    const STORAGE_KEY = 'sidebar-collapsed';
    const collapseLabel = toggle.dataset.collapseLabel || toggle.getAttribute('aria-label');
    const expandLabel = toggle.dataset.expandLabel || collapseLabel;

    const setCollapsed = (collapsed) => {
        shell.classList.toggle('is-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', String(!collapsed));
        toggle.setAttribute('aria-label', collapsed ? expandLabel : collapseLabel);
        toggle.setAttribute('title', collapsed ? expandLabel : collapseLabel);
        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        } catch {
            /* تجاهل */
        }
    };

    let saved = null;
    try {
        saved = localStorage.getItem(STORAGE_KEY);
    } catch {
        saved = null;
    }
    if (saved === '1') setCollapsed(true);

    toggle.addEventListener('click', () => {
        setCollapsed(!shell.classList.contains('is-collapsed'));
    });
}

/* ── المكتبة — بحث + فلترين (مادة/نوع) + معاينة عامة ─────
   منطق AND بين الثلاثة، على العميل بلا خادم — نفس نمط موادي. */
function initLibrary() {
    const wrap = document.querySelector('[data-library]');
    if (!wrap) return;

    const searchInput = wrap.querySelector('[data-library-search]');
    const subjectSelect = wrap.querySelector('[data-library-filter-subject]');
    const typeSelect = wrap.querySelector('[data-library-filter-type]');
    const rows = [...wrap.querySelectorAll('[data-library-row]')];
    const noResults = wrap.querySelector('[data-library-no-results]');

    const apply = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        const subject = subjectSelect?.value || 'all';
        const type = typeSelect?.value || 'all';
        let visible = 0;

        rows.forEach((row) => {
            const matchesSubject = subject === 'all' || row.dataset.fileSubject === subject;
            const matchesType = type === 'all' || row.dataset.fileType === type;
            const matchesSearch = !query || (row.dataset.fileSearch || '').toLowerCase().includes(query);
            const show = matchesSubject && matchesType && matchesSearch;
            row.hidden = !show;
            if (show) visible += 1;
        });

        if (noResults) noResults.hidden = visible > 0;
    };

    searchInput?.addEventListener('input', apply);
    subjectSelect?.addEventListener('change', apply);
    typeSelect?.addEventListener('change', apply);

    // معاينة عامة — مودال واحد يُملأ بمحتوى الصف المنقور عليه
    const previewModal = document.getElementById('file-preview-modal');
    if (previewModal) {
        wrap.querySelectorAll('[data-file-preview-trigger]').forEach((trigger) => {
            const open = () => {
                previewModal.querySelector('[data-preview-name]').textContent = trigger.dataset.fileName || '';
                const subjectPart = trigger.dataset.fileSubject ? `${trigger.dataset.fileSubject} · ` : '';
                previewModal.querySelector('[data-preview-meta]').textContent =
                    `${subjectPart}${trigger.dataset.fileSize || ''} · ${trigger.dataset.fileDate || ''}`;
                previewModal.querySelector('[data-preview-download]').setAttribute('href', trigger.dataset.fileHref || '#');
                modals[previewModal.id]?.open();
            };

            trigger.addEventListener('click', (e) => {
                if (e.target.closest('[data-file-download]')) return; // زر التنزيل يعمل طبيعياً بلا معاينة
                open();
            });
            trigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    open();
                }
            });
        });
    }
}

/* ── التنبيهات — تعليم الكل كمقروء (محلي لهذه الصفحة) ──── */
function initNotifications() {
    const wrap = document.querySelector('[data-notifications]');
    if (!wrap) return;

    const markAllBtn = wrap.querySelector('[data-mark-all-read]');
    const countEl = wrap.querySelector('[data-unread-count]');
    const tabs = wrap.querySelectorAll('[data-notif-tab]');
    const groups = wrap.querySelectorAll('[data-notif-group]');
    const noneUnread = wrap.querySelector('[data-notif-none-unread]');

    const rows = () => Array.from(wrap.querySelectorAll('[data-notif-row]'));
    const activeTab = () =>
        wrap.querySelector('[data-notif-tab][aria-selected="true"]')?.dataset.notifTab || 'all';

    /* يحوّل عنصراً واحداً إلى «مقروء» — بصرياً وفي بياناته معاً، وإلا
       بقي فلتر «غير المقروءة» يراه غير مقروء. */
    function markRead(item) {
        item.classList.remove('bg-accent-soft/50');
        item.querySelector('[data-unread-dot]')?.remove();

        const title = item.querySelector('p');
        if (title) {
            title.classList.remove('font-semibold');
            title.classList.add('font-medium');
        }

        const row = item.closest('[data-notif-row]');
        if (row) row.dataset.unread = '0';
    }

    function refresh() {
        // العدّ من نقاط «غير المقروء» نفسها لا من data-notif-row:
        // صفحة الطالب لا تلفّ عناصرها بصفوف، فالعدّ منها كان سيُصفّر
        // العدّاد هناك ويخفي زر «تعليم الكل» فور التحميل.
        const unread = wrap.querySelectorAll('[data-notification-item] [data-unread-dot]').length;

        if (countEl) countEl.textContent = unread;
        if (markAllBtn) markAllBtn.hidden = unread === 0;

        // شارة الجرس في الشريط العلوي تتبع نفس العدّاد
        if (unread === 0) document.querySelector('[data-notif-dot]')?.remove();

        applyFilter(activeTab());
    }

    function applyFilter(mode) {
        if (!tabs.length) return;

        rows().forEach((row) => {
            row.hidden = mode === 'unread' && row.dataset.unread !== '1';
        });

        // أخفِ أي مجموعة زمنية لم يبقَ فيها صف ظاهر
        groups.forEach((g) => {
            g.hidden = !Array.from(g.querySelectorAll('[data-notif-row]')).some((r) => !r.hidden);
        });

        if (noneUnread) noneUnread.hidden = rows().some((r) => !r.hidden);
    }

    // النقر على إشعار يعلّمه مقروءاً قبل الانتقال لمصدره
    wrap.querySelectorAll('[data-notification-item]').forEach((item) => {
        item.addEventListener('click', () => {
            markRead(item);
            refresh();
        });
    });

    markAllBtn?.addEventListener('click', () => {
        wrap.querySelectorAll('[data-notification-item]').forEach(markRead);
        refresh();
    });

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((t) => {
                const active = t === tab;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-selected', String(active));
            });
            applyFilter(tab.dataset.notifTab);
        });
    });

    refresh();
}

/* ── ملفي — تأكيد حفظ واجهي فقط (لا خادم بعد) ───────────── */
function initProfileForm() {
    const saveBtn = document.querySelector('[data-profile-save]');
    const toast = document.querySelector('[data-profile-saved-toast]');
    if (!saveBtn || !toast) return;

    saveBtn.addEventListener('click', (e) => {
        e.preventDefault();
        toast.hidden = false;
        setTimeout(() => {
            toast.hidden = true;
        }, 2500);
    });
}

/* ── موادي — بحث وفلترة على العميل بلا خادم ─────────────
   منطق AND بين البحث (اسم المادة أو المعلم) والفلتر النشط. */
function initSubjectFilter() {
    const wrap = document.querySelector('[data-subject-filter]');
    if (!wrap) return;

    const searchInput = wrap.querySelector('[data-subject-search]');
    const filterBtns = [...wrap.querySelectorAll('[data-subject-filter-btn]')];
    const cards = [...wrap.querySelectorAll('[data-subject-card]')];
    const noResults = wrap.querySelector('[data-subject-no-results]');

    let activeFilter = 'all';

    const apply = () => {
        const query = (searchInput?.value || '').trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach((card) => {
            const matchesFilter = activeFilter === 'all' || card.dataset.subjectStatus === activeFilter;
            const matchesSearch = !query || (card.dataset.subjectName || '').toLowerCase().includes(query);
            const show = matchesFilter && matchesSearch;
            card.hidden = !show;
            if (show) visibleCount += 1;
        });

        if (noResults) noResults.hidden = visibleCount > 0;
    };

    filterBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            activeFilter = btn.dataset.subjectFilterBtn;
            filterBtns.forEach((b) => b.classList.toggle('is-active', b === btn));
            apply();
        });
    });

    searchInput?.addEventListener('input', apply);
}

/* ── صفحة نتيجة الكويز المستقلة — تُحسب فور التحميل ─────────
   الوحيدة التي تصل إليها فعلياً: إنهاء الكويز من student/quiz.blade.php
   (لا نتيجة مضمّنة هناك) يحفظ في نفس مفتاح localStorage ثم يتنقّل هنا. */
function initStandaloneQuizResult() {
    const resultView = document.querySelector('[data-quiz-result-standalone]');
    if (!resultView) return;

    const quizId = resultView.dataset.quizId || 'default';
    const storageKey = `quiz-demo-${quizId}`;
    const attemptsKey = `quiz-attempts-${quizId}`;

    let answers = {};
    try {
        answers = JSON.parse(localStorage.getItem(storageKey) || '{}');
    } catch {
        answers = {};
    }
    const attempts = parseInt(localStorage.getItem(attemptsKey) || '1', 10) || 1;

    renderQuizResult(resultView, answers, attempts);

    resultView.querySelector('[data-quiz-retry]')?.addEventListener('click', () => {
        try {
            localStorage.removeItem(storageKey);
        } catch {
            /* تجاهل */
        }
        window.location.href = resultView.dataset.retakeHref || '#';
    });
}

/* ── الكويز — سؤال واحد بالشاشة، بلا مؤقّت ─────────────────
   حفظ تلقائي في localStorage لكل إجابة — انقطاع الاتصال أو إغلاق
   التبويب لا يضيّع المحاولة (يُستبدل بحفظ عبر الشبكة عند وجود Livewire). */
function initQuizRunner() {
    const runner = document.querySelector('[data-quiz-runner]');
    if (!runner) return;

    const questions = [...runner.querySelectorAll('[data-quiz-question]')];
    const total = questions.length;
    if (!total) return;

    const quizId = runner.dataset.quizId || 'default';
    const storageKey = `quiz-demo-${quizId}`;
    const attemptsKey = `quiz-attempts-${quizId}`;
    const currentLabel = runner.querySelector('[data-quiz-current]');
    const progressFill = runner.querySelector('[data-quiz-progress] .bar-fill');
    const progressTrack = runner.querySelector('[data-quiz-progress] [role="progressbar"]');
    const prevBtn = runner.querySelector('[data-quiz-prev]');
    const nextBtn = runner.querySelector('[data-quiz-next]');
    const finishBtn = runner.querySelector('[data-quiz-finish]');
    const finishConfirm = document.getElementById('quiz-unanswered-confirm');
    const resultView = document.querySelector('[data-lecture-view="result"]');

    let current = 0;

    const loadSaved = () => {
        try {
            return JSON.parse(localStorage.getItem(storageKey) || '{}');
        } catch {
            return {};
        }
    };

    const saveAnswer = (index, value) => {
        const saved = loadSaved();
        saved[index] = value;
        try {
            localStorage.setItem(storageKey, JSON.stringify(saved));
        } catch {
            /* التخزين المحلي غير متاح — لا نكسر التجربة، فقط بلا استرجاع لاحق */
        }
    };

    // استرجاع أي إجابات محفوظة من محاولة سابقة (تحديث الصفحة، انقطاع نت)
    const saved = loadSaved();
    questions.forEach((q, i) => {
        if (saved[i] === undefined) return;
        const input = q.querySelector(`[data-quiz-option][value="${CSS.escape(String(saved[i]))}"]`);
        if (input) input.checked = true;
    });

    runner.addEventListener('change', (e) => {
        if (!e.target.matches('[data-quiz-option]')) return;
        const q = e.target.closest('[data-quiz-question]');
        saveAnswer(q.dataset.index, e.target.value);
    });

    const isAnswered = (q) => !!q.querySelector('[data-quiz-option]:checked');

    const render = () => {
        questions.forEach((q, i) => {
            q.hidden = i !== current;
        });

        if (currentLabel) currentLabel.textContent = String(current + 1);

        const percent = Math.round(((current + 1) / total) * 100);
        if (progressFill) progressFill.style.width = percent + '%';
        if (progressTrack) progressTrack.setAttribute('aria-valuenow', String(percent));

        prevBtn.disabled = current === 0;
        const isLast = current === total - 1;
        nextBtn.hidden = isLast;
        finishBtn.hidden = !isLast;
    };

    prevBtn.addEventListener('click', () => {
        if (current > 0) {
            current -= 1;
            render();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (current < total - 1) {
            current += 1;
            render();
        }
    });

    // ── إنهاء الكويز — نتيجة محسوبة فعلياً من الإجابات المحفوظة، لا وهمية ──
    const getAttempts = () => {
        const n = parseInt(localStorage.getItem(attemptsKey) || '0', 10);
        return Number.isFinite(n) ? n : 0;
    };

    const completeQuiz = () => {
        const attempts = getAttempts() + 1;
        try {
            localStorage.setItem(attemptsKey, String(attempts));
        } catch {
            /* بلا تخزين محلي: عدّاد المحاولات لا يُحفظ، لكن العرض يستمر */
        }

        if (resultView) {
            renderQuizResult(resultView, loadSaved(), attempts);
            showLectureView('result');
        } else {
            window.location.href = finishBtn.href;
        }
    };

    finishBtn.addEventListener('click', (e) => {
        const unanswered = questions.filter((q) => !isAnswered(q)).length;
        if (unanswered > 0 && finishConfirm) {
            e.preventDefault();
            const answeredCount = total - unanswered;
            finishConfirm.querySelectorAll('[data-answered-count]').forEach((el) => {
                el.textContent = String(answeredCount);
            });
            modals[finishConfirm.id]?.open();
        } else if (resultView) {
            e.preventDefault();
            completeQuiz();
        }
        // لا نتيجة مضمّنة ولا أسئلة ناقصة: href الحقيقي يعمل طبيعياً (احتياط بلا JS)
    });

    // زر «إنهاء رغم ذلك» داخل مودال التأكيد — نفس معالجة الإنهاء عند التضمين
    if (finishConfirm && resultView) {
        finishConfirm.querySelector('[data-confirm-accept]')?.addEventListener('click', (e) => {
            e.preventDefault();
            modals[finishConfirm.id]?.close();
            completeQuiz();
        });
    }

    // إعادة المحاولة — يمسح الإجابات المحفوظة ويعيد الكويز لأوّله
    document.querySelectorAll('[data-quiz-retry]').forEach((btn) => {
        btn.addEventListener('click', () => {
            try {
                localStorage.removeItem(storageKey);
            } catch {
                /* تجاهل */
            }
            questions.forEach((q) => {
                q.querySelectorAll('[data-quiz-option]').forEach((i) => {
                    i.checked = false;
                });
            });
            current = 0;
            render();
            showLectureView('quiz');
        });
    });

    render();
}

/* ── عرض نتيجة الكويز — تُحسب من الإجابات المحفوظة فعلياً ────
   لا درجة ثابتة: لو أجاب الطالب كل شيء خطأ ستظهر 0/كذا فعلاً. */
function renderQuizResult(resultView, answers, attempts) {
    const reviewRows = [...resultView.querySelectorAll('[data-review-row]')];
    const yourAnswerLabel = resultView.dataset.yourAnswerLabel || '';
    const correctAnswerLabel = resultView.dataset.correctAnswerLabel || '';
    let correct = 0;

    reviewRows.forEach((row) => {
        const index = row.dataset.index;
        const correctIndex = row.dataset.correctIndex;
        const options = JSON.parse(row.dataset.options || '[]');
        const userIndex = answers[index];
        const isCorrect = userIndex !== undefined && String(userIndex) === String(correctIndex);
        if (isCorrect) correct += 1;

        const box = row.querySelector('[data-review-answers]');
        if (!box) return;

        let html = '';
        if (userIndex === undefined) {
            html += `<p class="text-stone">${yourAnswerLabel}: —</p>`;
        } else {
            html += `<p class="font-medium ${isCorrect ? 'text-accent-deep' : 'text-error-deep'}">${yourAnswerLabel}: ${options[userIndex] ?? ''}</p>`;
        }
        if (!isCorrect) {
            html += `<p class="font-medium text-accent-deep">${correctAnswerLabel}: ${options[correctIndex] ?? ''}</p>`;
        }
        box.innerHTML = html;
    });

    const total = reviewRows.length;
    const percent = total > 0 ? Math.round((correct / total) * 100) : 0;

    const summary = resultView.querySelector('[data-quiz-result-summary]');
    if (summary) {
        const ringFill = summary.querySelector('[data-score-ring-fill]');
        if (ringFill) {
            const circumference = 2 * Math.PI * 52;
            ringFill.style.strokeDasharray = `${circumference}px`;
            ringFill.style.strokeDashoffset = `${circumference * (1 - percent / 100)}px`;
        }

        const set = (selector, text) => {
            const el = summary.querySelector(selector);
            if (el) el.textContent = text;
        };

        set('[data-score-percent-label]', `${percent}%`);
        set('[data-score-fraction]', `${correct} / ${total}`);
        set('[data-score-correct]', String(correct));
        set('[data-score-incorrect]', String(total - correct));
        set('[data-score-percent-mini]', `${percent}%`);

        const message = summary.querySelector('[data-score-message]');
        if (message) {
            message.textContent =
                percent >= 80 ? summary.dataset.msgExcellent
                : percent >= 50 ? summary.dataset.msgGood
                : summary.dataset.msgPractice;
        }
    }

    // توصية م-2: محاولتان — الزر يختفي بعدها (القرار الفعلي لم يُحسم بعد)
    const retryBtn = resultView.querySelector('[data-quiz-retry]');
    const retryUnavailable = resultView.querySelector('[data-quiz-retry-unavailable]');
    if (retryBtn && retryUnavailable) {
        const canRetry = attempts < 2;
        retryBtn.hidden = !canRetry;
        retryUnavailable.hidden = canRetry;
    }
}

/* ── مشغّل المحاضرة — عرض تجريبي بانتظار قرار مزوّد الفيديو (م-5) ──
   لا فيديو حقيقياً هنا. زر [data-video-play] يحاكي التشغيل، وزر
   [data-simulate-lecture-end] المعزول بوضوح كأداة عرض يحاكي الانتهاء.
   الحدثان lecture:play / lecture:ended هما ما سيُطلقه المشغّل الحقيقي
   لاحقاً (حدث ended الأصلي لعنصر <video> أو ما يعادله عند مزوّد SDK) —
   بقية النظام (وضع التركيز، مودال الكويز) لا يهمّها المصدر. */
function initLecturePlayer() {
    const player = document.querySelector('[data-video-player]');
    if (!player) return;

    const playBtn = player.querySelector('[data-video-play]');
    const status = player.querySelector('[data-video-status]');
    const simulateEndBtn = document.querySelector('[data-simulate-lecture-end]');

    playBtn?.addEventListener('click', () => {
        player.classList.add('is-playing');
        if (status) status.textContent = status.dataset.playingLabel || status.textContent;
        document.dispatchEvent(new CustomEvent('lecture:play'));
    });

    simulateEndBtn?.addEventListener('click', () => {
        document.dispatchEvent(new CustomEvent('lecture:ended'));
    });
}

/* ── تقدّم مشاهدة المحاضرة — م-5 ───────────────────────────
   🔴 تحقّق خادمي موثوق: المشغّل لا يقول «اكتمل». يرسل المقطع المُشاهَد منذ
   آخر نبضة [from,to] بالثواني عبر YouTube IFrame API، والخادم يدمج المقاطع
   ويقرّر الاكتمال.

   ترقية لا شرط: لو تعذّر تحميل واجهة يوتيوب أو رُفض الطلب، الصفحة تعمل
   ومشاهدة الفيديو تعمل — التقدّم وحده لا يُسجَّل. */
function initLectureProgress() {
    const host = document.querySelector('[data-lecture-progress]');
    const iframe = host?.querySelector('iframe');
    const url = host?.dataset.lectureProgressUrl;
    if (!host || !iframe || !url) return;

    // تحميل واجهة يوتيوب مرّة واحدة للصفحة.
    if (!window.YT && !document.querySelector('script[src*="youtube.com/iframe_api"]')) {
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(tag);
    }

    let player = null;
    let segmentStart = null;   // currentTime حين بدأ التشغيل المتّصل الحالي
    let poll = null;

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    async function send(from, to) {
        from = Math.floor(from);
        to = Math.floor(to);
        // مقاطع دقيقة (تحديث موضع، اهتزاز) لا تُرسَل.
        if (to - from < 2) return;

        try {
            await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ from, to }),
                keepalive: true,
            });
        } catch {
            // انقطاع لحظي — النبضة التالية تغطّي نفس المدى.
        }
    }

    function flush() {
        if (segmentStart === null || !player) return;

        const now = player.getCurrentTime?.() ?? segmentStart;

        // فقط التشغيل الأمامي المتّصل يُحتسب — القفز للخلف يبدأ مقطعاً جديداً.
        if (now > segmentStart) send(segmentStart, now);

        segmentStart = now;
    }

    function build() {
        if (player || !window.YT?.Player) return;

        player = new window.YT.Player(iframe, {
            events: {
                onStateChange: (event) => {
                    const YT = window.YT;

                    if (event.data === YT.PlayerState.PLAYING) {
                        segmentStart = player.getCurrentTime();
                        clearInterval(poll);
                        poll = setInterval(flush, 15000);

                        return;
                    }

                    clearInterval(poll);
                    flush();
                    segmentStart = null;
                },
            },
        });
    }

    if (window.YT?.Player) {
        build();
    }

    // يوتيوب ينادي هذه عالمياً حين تجهز الواجهة — نسلسل مع أي مستمع سابق.
    const previous = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = () => {
        previous?.();
        build();
    };

    // مغادرة الصفحة أو إخفاء التبويب: أرسل ما تبقّى قبل أن يضيع.
    window.addEventListener('pagehide', flush);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) flush();
    });
}

/* ── وضع التركيز — الشريط الجانبي ينطوي أثناء المشاهدة ────
   [data-shell].is-focus يُقرأ في app.css. يعود عند الانتهاء. */
function initFocusMode() {
    const shell = document.querySelector('[data-shell]');
    if (!shell) return;

    // lecture:play/ended = تشغيل الفيديو الفعلي. focus-mode:enter/exit = عام،
    // يشمل أيضاً التبديل لعرض الكويز المضمّن (initLectureViewSwitcher).
    ['lecture:play', 'focus-mode:enter'].forEach((evt) =>
        document.addEventListener(evt, () => shell.classList.add('is-focus')));
    ['lecture:ended', 'focus-mode:exit'].forEach((evt) =>
        document.addEventListener(evt, () => shell.classList.remove('is-focus')));
}

/* ── التبديل بين عرض الفيديو وعرض الكويز — بلا انتقال صفحة ──
   [data-embed-target="video|quiz"] يعترض النقر (نمط Sprints.ai): يُظهر
   [data-lecture-view="..."] المطابق ويُخفي الآخر، بدل الانتقال لمسار
   الكويز المستقل. ذلك المسار يبقى موجوداً كرابط حقيقي احتياطي — لو
   تعطّل JS أو فُتح في تبويب جديد، التنقّل العادي يعمل كما هو. */
/* ── إدارة عروض صفحة المحاضرة الثلاثة: فيديو/كويز/نتيجة ──────
   دالة واحدة مشتركة يستدعيها initLectureViewSwitcher() (نقر الشريط
   الجانبي) و initQuizRunner() (الإنهاء وإعادة المحاولة) — تضمن ظهور
   عرض واحد فقط دائماً بصرف النظر عن نقطة الاستدعاء. */
function showLectureView(mode) {
    const views = {
        video: document.querySelector('[data-lecture-view="video"]'),
        quiz: document.querySelector('[data-lecture-view="quiz"]'),
        result: document.querySelector('[data-lecture-view="result"]'),
    };
    if (!views.video && !views.quiz && !views.result) return;

    Object.entries(views).forEach(([key, el]) => {
        if (el) el.hidden = key !== mode;
    });

    // وضع التركيز أثناء الكويز فقط — النتيجة والفيديو شاشتان عاديتان
    document.dispatchEvent(new CustomEvent(mode === 'quiz' ? 'focus-mode:enter' : 'focus-mode:exit'));
}

function initLectureViewSwitcher() {
    if (!document.querySelector('[data-lecture-view]')) return;

    document.querySelectorAll('[data-embed-target]').forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            showLectureView(trigger.dataset.embedTarget);
        });
    });
}

/* ── التبويبات — عامة لأي [data-tabs] بالصفحة ────────────
   صنف .is-active يُبدَّل بلا مساس بأصناف Tailwind الثابتة —
   انظر .tab-trigger.is-active في app.css. */
function initTabs() {
    document.querySelectorAll('[data-tabs]').forEach((wrap) => {
        const triggers = wrap.querySelectorAll('[data-tab-trigger]');
        const panels = wrap.querySelectorAll('[data-tab-panel]');
        if (!triggers.length || !panels.length) return;

        const activate = (key) => {
            triggers.forEach((t) => {
                const on = t.dataset.tabTrigger === key;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', String(on));
            });
            panels.forEach((p) => {
                p.hidden = p.dataset.tabPanel !== key;
            });
        };

        triggers.forEach((t) => t.addEventListener('click', () => activate(t.dataset.tabTrigger)));
    });
}

/* ── الشريط الجانبي — قشرة الطالب ─────────────────────────
   نفس منطق initNav(): درج على الجوال، ثابت من lg. الفتح/الإغلاق
   عبر صنف .is-open (انظر app.css)، لا عبر Alpine — غير مثبّت بعد. */
function initStudentSidebar() {
    const drawer = document.querySelector('[data-sidebar-drawer]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const openers = document.querySelectorAll('[data-sidebar-open]');
    if (!drawer || !openers.length) return;

    const setOpen = (open) => {
        drawer.classList.toggle('is-open', open);
        backdrop?.classList.toggle('is-open', open);
        document.body.style.overflow = open ? 'hidden' : '';
    };

    openers.forEach((btn) => btn.addEventListener('click', () => setOpen(true)));
    drawer.querySelectorAll('[data-sidebar-close]').forEach((btn) => btn.addEventListener('click', () => setOpen(false)));
    backdrop?.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && drawer.classList.contains('is-open')) setOpen(false);
    });

    drawer.querySelectorAll('a').forEach((l) => l.addEventListener('click', () => setOpen(false)));

    window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
        if (e.matches) setOpen(false);
    });
}

/* ── الأكورديون الحصري ────────────────────────────────
   خاصية details[name] الأصلية تتكفّل بالحصرية في المتصفحات
   الحديثة. هذا احتياط للقديمة التي لا تدعمها. */
function initAccordionFallback() {
    if ("name" in document.createElement("details")) return;

    document.querySelectorAll("details[name]").forEach((d) => {
        d.addEventListener("toggle", () => {
            if (!d.open) return;
            const g = d.getAttribute("name");
            document.querySelectorAll(`details[name="${g}"]`).forEach((o) => {
                if (o !== d) o.open = false;
            });
        });
    });
}

/* ── Reading progress bar (news detail) ───────────────────
   Measures progress through the ARTICLE BODY only, not the page: the body
   ends well before "related news" and the CTA, and measuring the whole
   document would leave the bar at 100% two screens after the article ends.

   Driven by width, not transform: it grows from the inline-start edge
   automatically in both directions with no rtl: variant. (Same principle as
   the inset-inline rule in doctrine.md - a logical value beats flipping a
   physical one by hand.) */
function initReadingProgress() {
    const bar = document.querySelector('[data-read-progress]');
    const article = document.querySelector('[data-read-article]');
    if (!bar || !article) return;

    let ticking = false;

    const update = () => {
        ticking = false;

        const rect = article.getBoundingClientRect();
        const start = rect.top + window.scrollY;
        // Scrollable distance inside the article, minus one viewport
        const span = article.offsetHeight - window.innerHeight;

        if (span <= 0) {
            bar.style.width = '0%';
            return;
        }

        const passed = window.scrollY - start;
        const pct = Math.min(100, Math.max(0, (passed / span) * 100));
        bar.style.width = pct.toFixed(2) + '%';
    };

    const onScroll = () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(update);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    update();
}

/* ── Contact form ─────────────────────────────────────────
   No backend yet, so submission is an explicit demo: validate what the browser
   can validate, show a sending state, then swap the form for a success panel.

   The success panel REPLACES the form rather than appearing above it, so nobody
   is left staring at a form they already sent. Same pattern as forgot-password.

   Button labels come from data-* fed by the lang file - no Arabic in JS. */
function initContactForm() {
    const root = document.querySelector('[data-contact-form]');
    if (!root) return;

    const formPanel = root.querySelector('[data-contact-panel="form"]');
    const successPanel = root.querySelector('[data-contact-panel="success"]');
    const submit = root.querySelector('[data-contact-submit]');
    const reset = root.querySelector('[data-contact-reset]');
    if (!formPanel || !successPanel || !submit) return;

    const fields = () => Array.from(formPanel.querySelectorAll('input, select, textarea'));
    const idleLabel = submit.textContent.trim();

    /*
     * 🔁 The form POSTS for real now. This used to fake the whole thing —
     * a setTimeout that swapped in the success panel while nothing was ever
     * sent anywhere.
     *
     * What is left is progressive enhancement only: a disabled button and a
     * "sending…" label so a slow connection does not look like a dead click,
     * and a double submit cannot queue two messages. The server renders the
     * success panel on the redirect, so none of this is load-bearing.
     */
    formPanel.addEventListener('submit', () => {
        // The browser's own constraint validation already ran and blocked
        // the submit if anything was invalid — by here it has passed.
        submit.disabled = true;
        submit.textContent = submit.dataset.sendingLabel || idleLabel;
    });

    // Announce the confirmation when the server renders it, so screen-reader
    // users are told the message went rather than left on a silent page.
    if (!successPanel.hidden) {
        const heading = successPanel.querySelector('h2');
        heading?.setAttribute('tabindex', '-1');
        heading?.focus();
    }

    reset?.addEventListener('click', () => {
        fields().forEach((f) => {
            if (f.tagName === 'SELECT') f.selectedIndex = 0;
            else f.value = '';
        });
        successPanel.hidden = true;
        formPanel.hidden = false;
        formPanel.querySelector('input')?.focus();
    });
}

/* ── Pricing calculator (screen 7) ────────────────────────
   Per-subject subscription pricing, the interactive half of decision M-1.

   State lives in the DOM (which checkboxes are checked), not in a parallel JS
   object, so the same markup can move to Livewire later untouched.

   All user-facing wording comes from data-* attributes fed by the lang file -
   no Arabic string is ever written in JavaScript. */
function initPlanCalculator() {
    const root = document.querySelector('[data-calc]');
    if (!root) return;

    const unit = Number(root.dataset.calcUnit) || 0;
    const branchPrice = Number(root.dataset.calcBranchPrice) || 0;

    // ui/select and ui/checkbox merge attributes onto their WRAPPER, so reach
    // through to the real controls rather than querying the hook directly.
    const branchSelect = root.querySelector('[data-calc-branch] select');
    const groups = Array.from(root.querySelectorAll('[data-calc-group]'));
    const countEl = root.querySelector('[data-calc-count]');
    const totalEl = root.querySelector('[data-calc-total]');
    const emptyEl = root.querySelector('[data-calc-empty]');
    const nudge = root.querySelector('[data-calc-nudge]');
    const nudgeText = root.querySelector('[data-calc-nudge-text]');
    const switchBtn = root.querySelector('[data-calc-switch]');
    const cta = root.querySelector('[data-calc-cta]');

    const activeGroup = () => groups.find((g) => !g.hidden);

    function update() {
        const group = activeGroup();
        if (!group) return;

        const checked = Array.from(group.querySelectorAll('input[type="checkbox"]')).filter((i) => i.checked);
        const count = checked.length;
        const total = count * unit;

        if (countEl) countEl.textContent = String(count);
        if (totalEl) totalEl.textContent = String(total);
        if (emptyEl) emptyEl.hidden = count > 0;

        // A disabled anchor cannot be expressed with the disabled attribute, so
        // the CTA is muted with aria-disabled + pointer-events while empty.
        if (cta) {
            const off = count === 0;
            cta.classList.toggle('pointer-events-none', off);
            cta.classList.toggle('opacity-50', off);
            cta.setAttribute('aria-disabled', String(off));
        }

        // Break-even nudge: past this point the branch pass is simply cheaper.
        if (nudge && nudgeText) {
            const worthSwitching = total > branchPrice;
            nudge.hidden = !worthSwitching;
            if (worthSwitching) {
                nudgeText.textContent = (nudge.dataset.template || '')
                    .replace('%count%', String(count))
                    .replace('%total%', String(total));
            }
        }
    }

    branchSelect?.addEventListener('change', () => {
        groups.forEach((g) => {
            g.hidden = g.dataset.calcGroup !== branchSelect.value;
            // Switching branch clears the previous branch's picks, so the total
            // never reflects subjects the student can no longer see.
            g.querySelectorAll('input[type="checkbox"]').forEach((i) => { i.checked = false; });
        });
        update();
    });

    root.addEventListener('change', (e) => {
        if (e.target.matches('input[type="checkbox"]')) update();
    });

    switchBtn?.addEventListener('click', () => {
        const target = document.querySelector('[data-branch-plan]');
        target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target?.classList.add('ring-4');
        window.setTimeout(() => target?.classList.remove('ring-4'), 1600);
    });

    update();
}

/* ── Share and copy-link ──────────────────────────────────
   No platform logos: navigator.share opens the OS share sheet (which already
   contains WhatsApp on mobile); desktop falls back to copy-link.

   The native share button starts hidden in the HTML and is revealed here only
   when the API exists - never promise a button that cannot work. */
function initShare() {
    const rows = document.querySelectorAll('[data-share]');
    if (!rows.length) return;

    const canShare = typeof navigator.share === 'function';

    rows.forEach((row) => {
        const title = row.dataset.shareTitle || document.title;
        const url = window.location.href;
        const feedback = row.querySelector('[data-share-feedback]');
        const nativeBtn = row.querySelector('[data-share-native]');
        const copyBtn = row.querySelector('[data-share-copy]');

        const say = (msg) => {
            if (!feedback) return;
            feedback.textContent = msg;
            window.setTimeout(() => { feedback.textContent = ''; }, 2400);
        };

        if (nativeBtn && canShare) {
            nativeBtn.hidden = false;
            nativeBtn.addEventListener('click', () => {
                // A user dismissing the sheet throws AbortError - not a reportable error
                navigator.share({ title, url }).catch(() => {});
            });
        }

        copyBtn?.addEventListener('click', async () => {
            // Label comes from the lang file via data-copied-label, never hardcoded here
            const done = () => say(row.dataset.copiedLabel || '');

            try {
                await navigator.clipboard.writeText(url);
                done();
            } catch {
                // The clipboard API needs a secure context (https/localhost) - legacy fallback
                const tmp = document.createElement('textarea');
                tmp.value = url;
                tmp.setAttribute('readonly', '');
                tmp.style.position = 'fixed';
                tmp.style.opacity = '0';
                document.body.appendChild(tmp);
                tmp.select();
                try { document.execCommand('copy'); done(); } catch { /* nothing further we can do */ }
                tmp.remove();
            }
        });
    });
}

/* ── لوحة الأدمن — تصفية جدول على العميل ──────────────────
   القائمة عشرات لا آلاف، فالتصفية الفورية بلا رحلة خادم أهدأ.
   تبحث في data-search المُجمَّع خادمياً لا في نصّ الصفّ: النصّ يحمل
   أرقاماً وتواريخ تُنتج مطابقات عشوائية. */
function initAdminFilter() {
    document.querySelectorAll('[data-admin-filter]').forEach((root) => {
        const input = root.querySelector('[data-admin-filter-input]');
        const rows = [...root.querySelectorAll('[data-admin-filter-row]')];
        const empty = root.querySelector('[data-admin-filter-empty]');
        if (!input || !rows.length) return;

        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            let shown = 0;

            rows.forEach((row) => {
                const hit = !q || (row.dataset.search || '').toLowerCase().includes(q);
                row.hidden = !hit;
                if (hit) shown++;
            });

            if (empty) empty.hidden = shown !== 0;
        });
    });
}

/* ── لوحة الأدمن — تبديل صلاحية فوري ──────────────────────
   ترقية تدريجية: النموذج يعمل بلا هذا السكربت (زرّ حفظ يزامن الكل).
   هنا نعترض التغيير ونرسله وحده، فيبقى المستخدم في مكانه.

   ⚠️ ليس Alpine — Livewire 3 غير مثبّت فلا Alpine في المشروع. */
function initRolePermissions() {
    const form = document.querySelector('[data-role-permissions]');
    if (!form) return;

    const url = form.dataset.toggleUrl;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!url || !token) return;   // بلا رمز CSRF نترك النموذج يعمل بالحفظ العادي

    form.querySelectorAll('input[data-permission]').forEach((box) => {
        box.addEventListener('change', async () => {
            const granted = box.checked;
            box.disabled = true;

            try {
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ permission: box.dataset.permission, granted }),
                });

                /* الفشل يُعيد المفتاح لموضعه السابق: مفتاح يبقى مضاءً بعد
                   رفض الخادم يكذب على المستخدم بشأن حالة النظام. */
                if (!res.ok) box.checked = !granted;
            } catch {
                box.checked = !granted;
            } finally {
                box.disabled = false;
            }
        });
    });
}

/* ── قشرة لوحة الأدمن — درج الجوال وطيّ الشريط الجانبي ────
   بديل vanilla عن Alpine (غير متوفّر: Livewire 3 غير مثبّت).
   حالة الطيّ تُحفظ في localStorage فتنجو من التنقّل بين الصفحات. */
function initAdminShell() {
    const shell = document.querySelector('[data-admin-shell]');
    if (!shell) return;

    const sidebar = shell.querySelector('[data-admin-sidebar]');
    const overlay = shell.querySelector('[data-admin-overlay]');
    const labels = shell.querySelectorAll('[data-admin-label]');
    const rules = shell.querySelectorAll('[data-admin-collapsed-rule]');
    const collapseIcon = shell.querySelector('[data-admin-collapse-icon]');
    const KEY = 'paledu-admin-sidebar-collapsed';

    /* ── الدرج على الجوال ── */
    const openDrawer = () => {
        sidebar.classList.remove('max-lg:-translate-x-full', 'max-lg:rtl:translate-x-full');
        overlay.hidden = false;
    };
    const closeDrawer = () => {
        sidebar.classList.add('max-lg:-translate-x-full', 'max-lg:rtl:translate-x-full');
        overlay.hidden = true;
    };

    closeDrawer();   // الحالة الابتدائية: مغلق على الجوال، ظاهر على سطح المكتب

    shell.querySelector('[data-admin-open]')?.addEventListener('click', openDrawer);
    shell.querySelector('[data-admin-close]')?.addEventListener('click', closeDrawer);
    overlay?.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => e.key === 'Escape' && closeDrawer());

    /* ── الطيّ على سطح المكتب ── */
    const applyCollapsed = (collapsed) => {
        sidebar.classList.toggle('lg:w-20', collapsed);
        sidebar.classList.toggle('lg:w-64', !collapsed);
        labels.forEach((el) => el.classList.toggle('lg:hidden', collapsed));
        rules.forEach((el) => { el.hidden = !collapsed; });
        /* السهم يشير لاتجاه الفتح — يُقلب بصنف إضافي فوق rtl:-scale-x-100 */
        collapseIcon?.classList.toggle('lg:rotate-180', collapsed);
    };

    applyCollapsed(localStorage.getItem(KEY) === 'true');

    shell.querySelector('[data-admin-collapse]')?.addEventListener('click', () => {
        const next = !(localStorage.getItem(KEY) === 'true');
        localStorage.setItem(KEY, next);
        applyCollapsed(next);
    });
}

/* ── سلّة المحذوفات — تحديد جماعي ─────────────────────────
   بديل vanilla عن Alpine. النموذج يعمل بلا هذا السكربت: كل صفّ يحمل
   إجراءيه المفردين، والإجراءات على «الكل» لا تحتاج تحديداً أصلاً —
   المعطَّل بلا JS هو التحديد الجماعي وحده. */
function initTrashSelection() {
    const root = document.querySelector('[data-trash]');
    if (!root) return;

    const master = root.querySelector('[data-trash-all]');
    const boxes = [...root.querySelectorAll('[data-trash-item]')];
    const bar = root.querySelector('[data-trash-bulk]');
    const counter = root.querySelector('[data-trash-count]');
    const forms = [...root.querySelectorAll('[data-trash-form]')];
    if (!boxes.length) return;

    const selected = () => boxes.filter((b) => b.checked).map((b) => b.value);

    const sync = () => {
        const picked = selected();
        if (counter) counter.textContent = picked.length;
        if (bar) bar.hidden = picked.length === 0;

        if (master) {
            master.checked = picked.length === boxes.length;
            /* الحالة الثالثة: بعض لا كل. تمنع «تحديد الكل» من الكذب. */
            master.indeterminate = picked.length > 0 && picked.length < boxes.length;
        }
    };

    master?.addEventListener('change', () => {
        boxes.forEach((b) => { b.checked = master.checked; });
        sync();
    });

    boxes.forEach((b) => b.addEventListener('change', sync));

    /* 🔴 الخانات تعيش في الجدول لا داخل نموذجي الإجراء، وخانة خارج النموذج
       لا تُرسَل معه. تُحقن الرموز كحقول مخفية لحظة الإرسال. */
    forms.forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('[data-trash-token]').forEach((el) => el.remove());

            selected().forEach((token) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[]';
                input.value = token;
                input.setAttribute('data-trash-token', '');
                form.appendChild(input);
            });
        });
    });

    sync();
}

/* ── Admin notifications — SweetAlert2 toasts ──────────────────────
 *
 * 🔴 The server still renders every message as visible HTML inside
 * x-admin.flash, and this reads those nodes and re-presents them. It does
 * NOT consume a JSON payload, and that is deliberate:
 *
 *   - notifications broke three separate times here, each time silently,
 *     and every failure was in the presentation layer. The raw text living
 *     in the HTML is what FlashMatrixTest pins, and it is the reason a
 *     broken toast is now visible rather than invisible.
 *   - with no JS the server markup simply stays on screen and still works.
 *
 * So SweetAlert is an UPGRADE over a working baseline, never the only path.
 */
/*
 * ONE dialog shape for the whole panel.
 *
 * 🔴 Every alert — created, updated, deleted, restored, failed, and every
 * confirmation — is the same centred modal with the same radius, padding,
 * typography and button styling. Mixing corner toasts with centre modals
 * made identical outcomes look like different kinds of event; a delete that
 * whispered from the corner and a delete that stopped the page read as two
 * different features rather than one.
 *
 * RTL comes from <html dir>, which SweetAlert honours; buttonsStyling is
 * off so the buttons are project components rather than library defaults.
 */
const SWAL_BASE = {
    buttonsStyling: false,
    reverseButtons: true,
    heightAuto: false,
    customClass: {
        popup: 'swal-panel',
        title: 'swal-panel__title',
        htmlContainer: 'swal-panel__body',
        actions: 'swal-panel__actions',
        confirmButton: 'swal-panel__btn swal-panel__btn--confirm',
        denyButton: 'swal-panel__btn swal-panel__btn--deny',
        cancelButton: 'swal-panel__btn swal-panel__btn--cancel',
    },
};

const swalPanel = Swal.mixin(SWAL_BASE);

/* Server tone class → SweetAlert icon. That class is already the pinned
   contract with the Blade component, so it is the safest thing to key on. */
const FLASH_TONES = [
    { match: 'bg-error/10', icon: 'error', title: 'حدث خطأ' },
    { match: 'bg-warn/10', icon: 'warning', title: 'تم الحذف' },
    { match: 'bg-tag/10', icon: 'info', title: 'تم التحديث' },
    { match: 'bg-accent/10', icon: 'success', title: 'تمّت العملية بنجاح' },
];

function initFlash() {
    const root = document.querySelector('[data-flash]');
    if (!root) return;

    const items = [...root.querySelectorAll('[data-flash-item]')];
    if (!items.length) return;

    /* The undo control is a real PATCH form with CSRF rendered in the shell.
       The toast never links to it — it SUBMITS it. A restore mutates server
       state, and an <a> injected by JS carries no token. */
    const undoForm = document.querySelector('[data-admin-undo] form');
    const undoLabel = document.querySelector('[data-admin-undo] [data-undo-label]')?.textContent?.trim();

    /* Drop the server markup only now that SweetAlert is definitely taking
       over, so a failed import leaves the working baseline in place. */
    root.remove();

    /* Modals are blocking, so they queue instead of stacking: firing four
       at once would leave three invisible behind the first. */
    const queue = items
        .map((item) => {
            const text = item.querySelector('p')?.textContent?.trim() ?? '';
            if (!text) return null;

            const tone = FLASH_TONES.find((t) => item.classList.contains(t.match)) ?? FLASH_TONES[2];

            return { text, tone };
        })
        .filter(Boolean);

    (async () => {
        for (const [i, entry] of queue.entries()) {
            /* Undo rides on the first delete notice only — one restore form
               exists per response, and offering it twice would be a lie. */
            const canUndo = Boolean(undoForm) && entry.tone.icon === 'warning' && i === 0;

            const result = await swalPanel.fire({
                icon: entry.tone.icon,
                title: entry.tone.title,
                text: entry.text,
                showConfirmButton: true,
                confirmButtonText: 'حسناً',
                showDenyButton: canUndo,
                denyButtonText: undoLabel || 'تراجع',
                /* Errors wait for a click. The rest close on their own —
                   but "fix the errors in the form" may be the only thing
                   explaining why nothing saved, so it never disappears. */
                timer: entry.tone.icon === 'error' ? undefined : 4000,
                timerProgressBar: entry.tone.icon !== 'error',
            });

            if (result.isDenied && canUndo) {
                undoForm.submit();

                return;
            }
        }
    })();
}

/* ── Destructive-action confirmation ───────────────────────────────
 *
 * Any form carrying data-confirm asks first. Replaces window.confirm(),
 * which cannot be styled and reads as a browser warning rather than as
 * part of the panel.
 *
 * ⚠️ Progressive, not required: the form still posts normally if this
 * script never runs. The guard is the route permission, never the dialog.
 */
function initConfirmActions() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1') return;

            event.preventDefault();

            /* Same mixin as the outcome notices: a confirmation and the
               notice that follows it are one conversation, so they must not
               look like two different systems talking. */
            swalPanel
                .fire({
                    icon: 'warning',
                    title: form.dataset.confirmTitle || 'تأكيد',
                    text: form.dataset.confirm,
                    showCancelButton: true,
                    confirmButtonText: form.dataset.confirmAction || 'نعم، تابع',
                    cancelButtonText: 'إلغاء',
                    /* Focus starts on Cancel: the destructive button must
                       not be one stray Enter away. */
                    focusCancel: true,
                })
                .then((result) => {
                    if (!result.isConfirmed) return;

                    /* Flag then resubmit: form.submit() does not re-fire
                       this listener, but the flag keeps that true if it
                       ever changes. */
                    form.dataset.confirmed = '1';
                    form.submit();
                });
        });
    });
}

/* ── تبويبات الإعدادات ────────────────────────────────────
   ترقية تدريجية: كل الألواح تصل مرئية من الخادم، وهذا يخفي غير النشط.
   بلا هذا السكربت تظهر النماذج تحت بعضها وكلها تعمل — لا تُعطَّل الصفحة. */
function initSettingsTabs() {
    const root = document.querySelector('[data-settings-tabs]');
    if (!root) return;

    const tabs = [...root.querySelectorAll('[data-settings-tab]')];
    const panels = [...root.querySelectorAll('[data-settings-panel]')];
    if (!tabs.length) return;

    const on = 'border-accent text-accent-deep';
    const off = 'border-transparent text-steel hover:border-hairline-strong hover:text-ink';

    const activate = (key) => {
        tabs.forEach((tab) => {
            const isOn = tab.dataset.settingsTab === key;
            tab.setAttribute('aria-selected', isOn);
            tab.classList.remove(...(isOn ? off : on).split(' '));
            tab.classList.add(...(isOn ? on : off).split(' '));
        });

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.settingsPanel !== key;
        });

        /* ⚠️ replaceState لا pushState: التبويب ليس صفحة، فلا يجب أن يُراكم
           مدخلات في سجلّ المتصفّح تجعل زرّ الرجوع يمشي عبر التبويبات بدل
           مغادرة الصفحة. والاستعلام يبقى فيصمد التبويب عبر التحديث. */
        const url = new URL(window.location);
        url.searchParams.set('tab', key);
        window.history.replaceState({}, '', url);
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => activate(tab.dataset.settingsTab));
    });

    /* التبويب المفتوح: ما ضبطه الخادم في aria-selected (بعد الحفظ) وإلا الأول */
    const initial = tabs.find((t) => t.getAttribute('aria-selected') === 'true') ?? tabs[0];
    activate(initial.dataset.settingsTab);
}

/* ── شبكة الصلاحيات — Alpine ──────────────────────────────
   حالة المفاتيح + حفظ فوري في الخلفية بلا إعادة تحميل.

   🔴 حالة القسم **محسوبة** من الصلاحيات تحته لا مخزّنة منفصلة: مفتاح قسم
   بحالة مستقلّة يكذب لحظة تغيير صلاحية مفردة تحته. */
Alpine.data('permissionGrid', (config) => ({
        state: config.state,
        locked: config.locked || [],
        toggleUrl: config.toggleUrl,
        groupUrl: config.groupUrl,
        csrf: config.csrf,
        busy: false,

        /* ── الحالات المحسوبة ──
           🔴 مفتاح القسم يقرأ all() ولا يخزّن حالته: هذا هو التزامن في
           الاتجاهين كلّه. تشغيله يشغّل الأبناء، وإطفاء ابن واحد يجعل all()
           تُرجع false فينطفئ المفتاح فوراً — بلا مستمع ولا مزامنة يدوية. */
        count(group) {
            return Object.values(this.state[group]).filter(Boolean).length;
        },
        all(group) {
            return this.count(group) === Object.keys(this.state[group]).length;
        },

        /* ── تبديل صلاحية مفردة ── */
        async toggle(group, permission, granted) {
            const previous = this.state[group][permission];
            this.state[group][permission] = granted;

            /* بلا مسار = وضع النموذج: الحالة تكفي، والإرسال يحمل الخانات. */
            if (!this.toggleUrl) return;

            const ok = await this.send(this.toggleUrl, { permission, granted });

            /* الفشل يُعيد المفتاح لموضعه: مفتاح يبقى مضاءً بعد رفض الخادم
               يكذب على المستخدم بشأن حالة النظام. */
            if (!ok) this.state[group][permission] = previous;
        },

        /* ── تبديل قسم كامل ── */
        async toggleGroup(group, granted) {
            const previous = { ...this.state[group] };

            Object.keys(this.state[group]).forEach((permission) => {
                /* المقفلة لا تُطفأ مع القسم — تُستثنى لا تمنع الباقي. */
                if (!granted && this.locked.includes(permission)) return;
                this.state[group][permission] = granted;
            });

            if (!this.groupUrl) return;

            /* 🔴 نداء واحد للقسم لا نداء لكل صلاحية: أربعة طلبات متسابقة
               على مخزن Spatie المؤقّت تجعل آخرها يفوز، فتضيع نتائج البقية. */
            const ok = await this.send(this.groupUrl, { group, granted });

            if (!ok) this.state[group] = previous;
        },

        /* ── الإرسال ── */
        async send(url, body) {
            if (this.busy) return false;
            this.busy = true;

            try {
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(body),
                });

                return res.ok;
            } catch {
                return false;
            } finally {
                this.busy = false;
            }
        },
    }));

/* ═══════════════════════════════════════════════════════════════
   النشرة البريدية — تحديد المستلمين وطيّ قائمة الشريط الجانبي
   ═══════════════════════════════════════════════════════════════ */

/*
 * 🔴 المصدر الوحيد للحقيقة هو خانات الاختيار نفسها، لا مصفوفة في Alpine.
 * الخانة هي الحقل المُرسَل فعلاً؛ أي حالة موازية في JS تنحرف عنها عند أول
 * تغيير لا يمرّ بالمستمع — والانحراف هنا يعني إرسال بريد لمن لم يُحدَّد.
 * لذلك `count` مشتقّ بالعدّ لا محسوب تراكمياً.
 */
function countChecked(root, selector) {
    return root.querySelectorAll(`${selector}:checked`).length;
}

Alpine.data('subscriberSelection', () => ({
    count: 0,

    init() {
        this.recount();
    },

    get allChecked() {
        const boxes = this.$el.querySelectorAll('[data-row-check]');

        // ⚠️ صفر خانات ليست «الكل محدَّد»: بلا هذا الشرط تظهر خانة
        // «تحديد الكل» مؤشَّرة على جدول لا يملك ما يُحدَّد.
        return boxes.length > 0 && this.count === boxes.length;
    },

    recount() {
        this.count = countChecked(this.$el, '[data-row-check]');
    },

    toggleAll(checked) {
        this.$el.querySelectorAll('[data-row-check]').forEach((box) => {
            box.checked = checked;
        });

        this.recount();
    },
}));

Alpine.data('campaignAudience', (total) => ({
    count: total,

    init() {
        this.recount();
    },

    get allChecked() {
        const boxes = this.$el.querySelectorAll('[data-audience-check]');

        return boxes.length > 0 && this.count === boxes.length;
    },

    recount() {
        this.count = countChecked(this.$el, '[data-audience-check]');
    },

    toggleAll(checked) {
        /*
         * ⚠️ يشمل الصفوف المخفيّة بالتصفية عمداً. «تحديد الكل» تعني القائمة
         * كلها لا ما يظهر منها الآن — ولو عنت المرئي فقط لأرسل الأدمن نشرة
         * إلى ثلاثة أشخاص وهو يظن أنه أرسلها إلى ثلاثمئة.
         */
        this.$el.querySelectorAll('[data-audience-check]').forEach((box) => {
            box.checked = checked;
        });

        this.recount();
    },

    /* تصفية عرض فقط — لا تلمس ما هو محدَّد إطلاقاً. */
    filter(term) {
        const needle = term.trim().toLowerCase();
        let visible = 0;

        this.$el.querySelectorAll('[data-audience-row]').forEach((row) => {
            const match = row.dataset.email.toLowerCase().includes(needle);
            row.hidden = !match;
            if (match) visible++;
        });

        const empty = this.$el.querySelector('[data-audience-empty]');
        if (empty) empty.hidden = visible > 0;
    },

    /*
     * 🔴 آخر حاجز قبل الإرسال. الخادم يرفض القائمة الفارغة أيضاً، لكن الرفض
     * هناك يكلّف الأدمن رحلة ذهاب وإياب بنشرة كتبها للتوّ.
     */
    guard(event) {
        if (this.count === 0) event.preventDefault();
    },
}));

/*
 * معاينة حملة — مودال واحد يُغذّى من الصفّ المضغوط.
 *
 * 🔴 النصّ يصل عبر data-* لا عبر تضمينه في onclick: Blade يهرّب السمات،
 * فنشرة تحتوي علامة اقتباس لا تكسر الوسم.
 */
Alpine.data('campaignPreview', () => ({
    showing: false,
    subject: '',
    body: '',
    sender: '',
    count: 0,
    date: '',

    open(button) {
        this.subject = button.dataset.campaignSubject || '';
        this.body = button.dataset.campaignBody || '';
        this.sender = button.dataset.campaignSender || '';
        this.count = button.dataset.campaignCount || 0;
        this.date = button.dataset.campaignDate || '';
        this.showing = true;

        /*
         * ⚠️ التركيز ينتقل داخل المودال: بدونه يبقى في الصفّ خلف الطبقة،
         * فمستخدم لوحة المفاتيح يتنقّل في جدول لا يراه. و$nextTick لأن
         * العنصر ما زال مخفيّاً في هذه اللحظة ولا يقبل التركيز.
         */
        this.$nextTick(() => this.$refs.closer?.focus());

        // منع تمرير الصفحة خلف المودال.
        document.body.style.overflow = 'hidden';
    },

    close() {
        this.showing = false;
        document.body.style.overflow = '';
    },

    destroy() {
        // لو أُزيل المكوّن والمودال مفتوح، لا تترك الصفحة بلا تمرير.
        document.body.style.overflow = '';
    },
}));
/* ═══════════════════════════════════════════════════════════════
   جرس الإشعارات — استطلاع دوري بلا تحديث للصفحة
   ═══════════════════════════════════════════════════════════════ */

/* ═══════════════════════════════════════════════════════════════
   محاضرات المعلّم — نموذج واحد للإنشاء والتعديل
   ═══════════════════════════════════════════════════════════════ */

/* ═══════════════════════════════════════════════════════════════
   مكتبة المعلّم — نموذج واحد للرفع والتعديل
   ═══════════════════════════════════════════════════════════════ */

Alpine.data('teacherLibrary', () => ({
    formModal: false,
    editing: false,
    action: '',
    form: {
        title: '', subject_id: '', lecture_id: '', category: 'summary',
        unit_name: '', page_count: '', is_published: false,
    },

    init() {
        /*
         * ⚠️ النموذج المعاد بأخطاء يُفتح على ما كُتب: Blade أعاد ملء الحقول
         * بـold()، وتصفيرها هنا كان سيمسح ما كتبه المعلّم للتوّ.
         *
         * 🔴 editing وaction يُقرآن من الخادم: تعديل فشل تحقّقه يجب أن يُعاد
         * فتحه تعديلاً على مسار PUT الصحيح، لا رفعاً جديداً — نفس عقد
         * teacher/lectures.
         */
        if (this.$root.dataset.reopen === '1') {
            this.editing = this.$root.dataset.reopenEditing === '1';
            this.action = this.$root.dataset.reopenAction || this.$root.dataset.storeUrl;

            try {
                Object.assign(this.form, JSON.parse(this.$root.dataset.reopenForm || '{}'));
            } catch {
                /* الافتراضات تبقى */
            }

            this.formModal = true;
        }
    },

    openCreate() {
        this.editing = false;
        this.action = this.$root.dataset.storeUrl;
        this.form = {
            title: '', subject_id: '', lecture_id: '', category: 'summary',
            unit_name: '', page_count: '', is_published: false,
        };
        this.formModal = true;
    },

    /* يُملأ من بيانات الصفّ المعروضة — لا طلب شبكة لجلب ما هو ظاهر أصلاً. */
    openEdit(file) {
        this.editing = true;
        this.action = file.action;
        this.form = {
            title: file.title ?? '',
            subject_id: String(file.subject_id ?? ''),
            lecture_id: file.lecture_id ? String(file.lecture_id) : '',
            category: file.category ?? 'summary',
            unit_name: file.unit_name ?? '',
            page_count: file.page_count ?? '',
            is_published: Boolean(file.is_published),
        };
        this.formModal = true;
    },

    close() {
        this.formModal = false;
    },
}));
Alpine.data('teacherLectures', () => ({
    formModal: false,
    editing: false,
    action: '',
    form: { title: '', subject_id: '', video_url: '', description: '', unit_name: '', status: 'draft' },

    /*
     * ⚠️ النسخة الابتدائية تُلتقط من الخادم عند التركيب: حين يفشل التحقّق
     * يعيد Blade ملء الحقول بـold()، وتصفيرها هنا كان سيمسح ما كتبه
     * المعلّم للتوّ — وهو أسوأ ما يمكن أن يفعله نموذج بعد خطأ.
     */
    init() {
        this.snapshot = { ...this.form };

        // نموذج أُعيد بأخطاء: يُفتح مباشرةً على ما كُتب بدل أن يختفي.
        if (this.$root.dataset.reopen === '1') {
            this.editing = this.$root.dataset.reopenEditing === '1';
            this.action = this.$root.dataset.reopenAction || this.action;

            try {
                Object.assign(this.form, JSON.parse(this.$root.dataset.reopenForm || '{}'));
            } catch {
                /* الافتراضات تبقى */
            }

            this.formModal = true;
        }
    },

    openCreate() {
        this.editing = false;
        this.action = this.$root.dataset.storeUrl;
        this.form = { ...this.snapshot, title: '', subject_id: '', video_url: '', description: '', unit_name: '', status: 'draft' };
        this.formModal = true;
    },

    /* يُملأ من بيانات الصفّ المعروضة — لا طلب شبكة لجلب ما هو ظاهر أصلاً. */
    openEdit(lecture) {
        this.editing = true;
        this.action = lecture.action;
        this.form = {
            title: lecture.title ?? '',
            subject_id: String(lecture.subject_id ?? ''),
            video_url: lecture.video_url ?? '',
            description: lecture.description ?? '',
            unit_name: lecture.unit_name ?? '',
            status: lecture.status ?? 'draft',
        };
        this.formModal = true;
    },

    close() {
        this.formModal = false;
    },
}));
/* جرس الإشعارات — استطلاع دوري، يخدم قشرتَي الأدمن والمعلّم بلا فرق.
   المسارات تُمرَّر من Blade فتغيير بادئة مسار لا يترك سلسلة مكسورة في JS. */
Alpine.data('bellFeed', (endpoints) => ({
    open: false,
    items: [],
    unread: 0,
    loading: true,
    failed: false,
    timer: null,
    chime: false,

    init() {
        /*
         * ⚠️ التفضيل يُقرأ داخل try: المتصفّح في وضع خاص أو بمنع تخزين
         * المواقع يرمي عند مجرّد القراءة، لا عند الكتابة فقط.
         */
        try {
            this.chime = localStorage.getItem('paledu-notif-chime') === '1';
        } catch {
            this.chime = false;
        }

        this.fetchItems();
        this.schedule();

        /*
         * 🔴 يتوقّف الاستطلاع حين يُخفى التبويب ويستأنف عند العودة. بدونه كل
         * تبويب لوحة متروك مفتوحاً يضرب الخادم أربع مرات في الدقيقة إلى
         * الأبد — ويُبقي الجلسة حيّة إلى ما لا نهاية أيضاً.
         */
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stop();
            } else {
                this.fetchItems();
                this.schedule();
            }
        });
    },

    destroy() {
        this.stop();
    },

    schedule() {
        this.stop();
        this.timer = setInterval(() => this.fetchItems(), 15000);
    },

    stop() {
        if (this.timer) clearInterval(this.timer);
        this.timer = null;
    },

    async fetchItems() {
        try {
            const res = await fetch(endpoints.index, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            /*
             * 🔴 الجلسة المنتهية توقف الاستطلاع نهائياً. بدون ذلك يستمرّ
             * التبويب المتروك في ضرب مسار يعيد 401 كل خمس عشرة ثانية
             * إلى الأبد.
             */
            if (res.status === 401 || res.status === 419 || res.status === 403) {
                this.stop();
                this.failed = true;
                return;
            }

            if (!res.ok) throw new Error(res.status);

            const data = await res.json();
            const previous = this.unread;

            this.items = data.notifications ?? [];
            this.unread = data.unread_count ?? 0;
            this.failed = false;

            // النغمة عند الزيادة فقط — لا عند كل استطلاع ناجح.
            if (this.unread > previous && previous !== 0) this.ping();
        } catch {
            /*
             * ⚠️ فشل الشبكة صامت عمداً: انقطاع لحظي لا يستحق تنبيهاً، والجرس
             * يبقي آخر قائمة معروفة بدل أن يفرغ نفسه.
             */
            this.failed = true;
        } finally {
            this.loading = false;
        }
    },

    /* نغمة قصيرة مُولَّدة — بلا ملف صوت يُحمَّل مع كل صفحة. */
    ping() {
        if (!this.chime) return;

        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;

            const ctx = new Ctx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.0001, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.05, ctx.currentTime + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.25);

            osc.connect(gain).connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.26);
            osc.onended = () => ctx.close();
        } catch {
            // المتصفّح يمنع الصوت قبل أول تفاعل — لا شيء يُفعل حياله.
        }
    },

    toggleChime() {
        this.chime = !this.chime;

        try {
            localStorage.setItem('paledu-notif-chime', this.chime ? '1' : '0');
        } catch {
            // تفضيل عرض فقط: تعذّر حفظه لا يمنع تشغيله لهذه الجلسة.
        }
    },

    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    async markRead(item) {
        if (item.read) return;

        // ⚠️ تفاؤلي: الشارة تنقص فوراً ثم يصحّحها الخادم. الانتظار حتى تعود
        // الاستجابة يجعل النقرة تبدو غير مستجيبة على شبكة بطيئة.
        item.read = true;
        this.unread = Math.max(0, this.unread - 1);

        try {
            const res = await fetch(endpoints.read.replace('__ID__', item.id), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (res.ok) this.unread = (await res.json()).unread_count ?? this.unread;
        } catch {
            this.fetchItems();   // إعادة المزامنة عند الفشل
        }
    },

    async markAll() {
        this.items.forEach((item) => { item.read = true; });
        this.unread = 0;

        try {
            await fetch(endpoints.readAll, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
        } catch {
            this.fetchItems();
        }
    },

    /* فتح إشعار: يُعلَّم مقروءاً ثم يُنتقل — لا العكس. */
    async visit(item) {
        await this.markRead(item);

        if (item.url) window.location.href = item.url;
    },
}));
/* ═══════════════════════════════════════════════════════════════
   صفحة الإشعارات — إجراءات غير متزامنة
   ═══════════════════════════════════════════════════════════════ */

/*
 * 🔴 كل دالة هنا **تُرجع** النتيجة، والصفّ هو من يحدّث حالته منها. الأب لا
 * يملك وصولاً إلى نطاق الابن في Alpine، فمحاولة قلب `read` من داخل الأب
 * كانت ستفشل بصمت — تنجح الكتابة على الخادم ولا يتغيّر شيء على الشاشة.
 */
Alpine.data('notificationsPage', (endpoints, unreadCount, readCount) => ({
    unread: unreadCount,
    read: readCount,
    total: unreadCount + readCount,
    busy: false,

    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    async call(url, method) {
        this.busy = true;

        try {
            const res = await fetch(url, {
                method,
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!res.ok) return null;

            const data = await res.json();

            // العدّادات تأتي من الخادم في كل استجابة، فلا تُحسب مرتين.
            this.unread = data.unread_count ?? this.unread;
            this.read = data.read_count ?? this.read;
            this.total = data.total_count ?? this.total;

            return data;
        } catch {
            return null;
        } finally {
            this.busy = false;
        }
    },

    /* يقلب صفّاً بين مقروء وغير مقروء. يُرجع الحمولة المحدَّثة أو null. */
    async toggle(id) {
        const data = await this.call(endpoints.toggle.replace('__ID__', id), 'POST');

        return data?.notification ?? null;
    },

    async remove(id) {
        const data = await this.call(endpoints.destroy.replace('__ID__', id), 'DELETE');

        return data !== null;
    },

    /*
     * الانتقال بعد تعليم الإشعار مقروءاً لا قبله.
     *
     * ⚠️ يستعمل مسار read لا toggle: read متكافئ القوى (idempotent) فيترك
     * المقروء مقروءاً، بينما toggle كان سيجعل فتحَ إشعارٍ مقروء يعيده غير
     * مقروء — عكس ما تعنيه النقرة تماماً.
     */
    async visit(id, url) {
        await this.call(endpoints.read.replace('__ID__', id), 'POST');

        window.location.href = url;
    },
    /*
     * 🔴 الإجراءان الجماعيان يعيدان تحميل الصفحة بعد نجاحهما، بعنوانها الحالي
     * (فيبقى التبويب ورقم الصفحة). تحديث الصفوف في المكان كان سيترك ترقيم
     * الصفحات كاذباً: حذف كل المقروءة من صفحة ٣ يترك صفحة ٣ فارغة وعدّاداً
     * لا يطابق ما يُعرض.
     */
    async markAll() {
        if (await this.call(endpoints.readAll, 'POST')) window.location.reload();
    },

    async clearRead() {
        if (await this.call(endpoints.clearRead, 'DELETE')) window.location.reload();
    },
}));
Alpine.data('sidebarDisclosure', (initiallyOpen = false) => ({
    open: initiallyOpen,
    collapsed: false,

    init() {
        /*
         * 🔴 مصدر حالة الطيّ هو الشريط الجانبي نفسه لا [data-shell]: قشرة
         * الأدمن لا تستعمل is-collapsed إطلاقاً (تلك لقشرتَي الطالب
         * والمعلّم)، بل تبدّل lg:w-20 على [data-admin-sidebar] في
         * initAdminShell. مراقبة العنصر الخطأ كانت ستفشل بصمت — بلا خطأ
         * ولا شيء في الـconsole.
         */
        const sidebar = document.querySelector('[data-admin-sidebar]');
        if (!sidebar) return;

        const sync = () => {
            this.collapsed = sidebar.classList.contains('lg:w-20');
        };

        sync();

        /*
         * ⚠️ مراقب لا مستمع حدث: الطيّ يُبدَّل بـclassList.toggle ولا يُطلق
         * أي حدث. المراقبة على السمة نفسها هي الرابط الوحيد الذي لا ينكسر
         * لو تغيّر ذلك الكود.
         */
        this.observer = new MutationObserver(sync);
        this.observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    },

    destroy() {
        this.observer?.disconnect();
    },

    /* مفتوحة دائماً في شريط الأيقونات — انظر تعليق القالب. */
    get expanded() {
        return this.open || this.collapsed;
    },
}));

/* ═══════════════════════════════════════════════════════════════
   صورة الحساب — قصّ وتدوير ومعاينة
   ═══════════════════════════════════════════════════════════════ */

Alpine.data('avatarEditor', () => ({
    cropping: false,
    viewing: false,
    busy: false,
    cropper: null,
    fileName: '',

    /* اختيار ملف يفتح المحرّر — ولا يرفع شيئاً بعد. */
    pick(event) {
        const file = event.target.files?.[0];
        if (!file) return;

        this.fileName = file.name;

        /*
         * ⚠️ قراءة محلّية بالكامل: الصورة لا تغادر المتصفّح قبل أن يضغط
         * المستخدم «تأكيد». إلغاء المحرّر يجب ألّا يترك ملفاً على الخادم.
         */
        const reader = new FileReader();
        reader.onload = (e) => this.openCropper(e.target.result);
        reader.readAsDataURL(file);

        /*
         * 🔴 تصفير حقل الاختيار: بدونه لا يُطلق `change` لو اختار المستخدم
         * **نفس** الملف مرّة ثانية بعد الإلغاء — فيبدو الزر معطّلاً.
         */
        event.target.value = '';
    },

    openCropper(src) {
        this.cropping = true;

        this.$nextTick(() => {
            const image = this.$refs.source;
            image.src = src;

            this.cropper?.destroy();

            this.cropper = new Cropper(image, {
                aspectRatio: 1,          // الصورة الرمزية دائرية — 1:1 حصراً
                viewMode: 1,             // لا يخرج إطار القصّ عن حدود الصورة
                autoCropArea: 0.9,
                background: false,
                responsive: true,
                /*
                 * ⚠️ الحاوية بـdir="ltr": أدوات cropper تحسب الإحداثيات
                 * من اليسار، وقلبها مع الصفحة يجعل السحب يتحرّك عكس اليد.
                 */
            });

            this.$refs.confirm?.focus();
        });
    },

    rotate(degrees) {
        this.cropper?.rotate(degrees);
    },

    zoom(ratio) {
        this.cropper?.zoom(ratio);
    },

    reset() {
        this.cropper?.reset();
    },

    /*
     * 🔴 النتيجة تُوضَع في <input type="file"> حقيقي عبر DataTransfer، فيصل
     * الخادم **ملفاً** يخضع لقاعدة mimes الاعتيادية. Base64 في حقل نصّي كان
     * سيجعل نوع الملف ادّعاءَ العميل لا فحصَ الخادم.
     */
    confirm() {
        if (!this.cropper || this.busy) return;

        this.busy = true;

        const canvas = this.cropper.getCroppedCanvas({
            width: 512,
            height: 512,
            imageSmoothingQuality: 'high',
        });

        canvas.toBlob((blob) => {
            if (!blob) {
                this.busy = false;
                return;
            }

            const file = new File([blob], this.safeName(), { type: 'image/png' });

            /*
             * ⚠️ DataTransfer غير متاح في متصفّحات قديمة جداً. في تلك الحالة
             * لا نُسقط الرفع بصمت: نغلق المحرّر ونُبقي الرسالة ظاهرة بدل أن
             * يضغط المستخدم زرّاً لا يفعل شيئاً.
             */
            if (typeof DataTransfer === 'undefined') {
                this.busy = false;
                this.cropping = false;
                window.alert('متصفّحك لا يدعم القصّ داخل الصفحة — حدّثه أو ارفع صورة مربّعة.');
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.$refs.upload.files = transfer.files;

            this.close();
            this.$refs.form.submit();
        }, 'image/png', 0.92);
    },

    /* اسم ملف آمن: اسم المصدر قد يحمل محارف مسار. */
    safeName() {
        const base = (this.fileName || 'avatar').replace(/\.[^.]+$/, '').replace(/[^\w-]+/g, '-');

        return `${base || 'avatar'}.png`;
    },

    close() {
        this.cropper?.destroy();
        this.cropper = null;
        this.cropping = false;
        this.busy = false;
    },

    /* عارض الصورة بحجمها الكامل. */
    view() {
        this.viewing = true;
    },

    closeViewer() {
        this.viewing = false;
    },

    destroy() {
        this.cropper?.destroy();
    },
}));
/* ⚠️ آخر سطر في الملف عمداً: start() تُطلق alpine:init تزامنياً،
   فكل Alpine.data() يجب أن يُسجَّل قبلها. */
Alpine.start();
