// palEducation — تفاعلات خفيفة بلا اعتماديات
// (Alpine يأتي مع Livewire 3؛ يُستبدل هذا الملف عند تثبيته)

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
    initHashScroll();
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

        const anyVisible = items.some((i) => !i.hidden);
        if (empty) empty.hidden = anyVisible;

        // عناوين الأقسام تختفي مع اختفاء محتواها — لا ترويسة فوق فراغ
        root.querySelectorAll('[data-news-heading]').forEach((h) => {
            const scope = h.nextElementSibling;
            if (!scope) return;
            h.hidden = !Array.from(scope.querySelectorAll('[data-news-item]')).some((i) => !i.hidden);
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
        if (e.target.name !== 'branch') return;

        confirmBtn.disabled = false;

        const label = e.target.closest('[data-branch-name]');
        if (nameSlot && label) nameSlot.textContent = label.dataset.branchName;
    });

    confirmBtn.addEventListener('click', () => {
        if (confirmBtn.disabled) return;
        modals['branch-confirm']?.open();
    });
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
