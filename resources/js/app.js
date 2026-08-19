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
});

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
