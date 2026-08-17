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

    const toggle = () => {
        btn.classList.toggle('is-shown', window.scrollY > 600);
    };

    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    });

    window.addEventListener('scroll', toggle, { passive: true });
    toggle();
}

/* ── مودال الفيديو التعريفي ───────────────────────────── */
function initDemoModal() {
    const modal = document.querySelector('[data-demo-modal]');
    const opener = document.querySelector('[data-demo-open]');
    if (!modal || !opener) return;

    const closers = modal.querySelectorAll('[data-demo-close]');
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

    opener.addEventListener('click', open);
    closers.forEach((c) => c.addEventListener('click', close));

    // الإغلاق بالنقر على الخلفية أو بمفتاح Esc
    modal.addEventListener('click', (e) => {
        if (e.target === modal) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) close();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initNav();
    initReveal();
    initSliders();
    initToTop();
    initDemoModal();
});
