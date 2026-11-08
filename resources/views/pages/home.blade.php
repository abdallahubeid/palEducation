@php
    // 🔁 The $branches mock is gone: the cards now read from the `branches`
    // table via HomeController ($branchCards).
    // بيانات عرض — تُستبدل باستعلامات عند بناء نماذج المجال

    // 🔁 The $features mock is gone: the cards now read from the `features`
    // table via HomeController ($featureCards).

    // 🔁 The $teachers mock is gone: the roster now reads from the
    // `teachers` table via HomeController ($teacherCards).

    $stats = [
        ['n' => 480, 'l' => __('home.stat_lectures'), 'icon' => 'play'],
        ['n' => 33, 'l' => __('home.stat_teachers'), 'icon' => 'users'],
        ['n' => 120, 'l' => __('home.stat_files'), 'icon' => 'folder'],
        ['n' => 2400, 'l' => __('home.stat_students'), 'icon' => 'compass'],
    ];

    $slides = [
        ['n' => 1, 'tone' => 'accent', 'img' => 'images/hero/slide-1-focus.jpg'],
        ['n' => 2, 'tone' => 'tag', 'img' => 'images/hero/slide-2-quiz.jpg'],
        ['n' => 3, 'tone' => 'amber', 'img' => 'images/hero/slide-3-library.jpg'],
    ];
@endphp

<x-layouts.public>

    {{-- ═══════════════════════════════════════════════════════
         ١ · الهيرو — سلايدر: نص + خانة صورة
         ═══════════════════════════════════════════════════════ --}}
    <section class="hero-canvas pt-14 pb-24 lg:pt-20 lg:pb-28"
        aria-roledescription="carousel"
        aria-label="{{ __('home.hero_slider_label') }}">
        <div class="mx-auto max-w-[1280px] px-6">
            <div data-slider class="relative overflow-hidden lg:px-16">

                <div class="slider">
                    @foreach ($slides as $i => $slide)
                        <div data-slide
                            class="slider__slide {{ $i === 0 ? 'is-active' : '' }}"
                            role="group"
                            aria-roledescription="slide"
                            aria-label="{{ $i + 1 }} / {{ count($slides) }}"
                            @if ($i !== 0) aria-hidden="true" @endif>

                            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-16">

                                {{-- النص --}}
                                <div>
                                    <x-ui.rule-label>{{ __('home.slide_' . $slide['n'] . '_eyebrow') }}</x-ui.rule-label>

                                    <h1 class="mt-6 text-h1 font-bold sm:text-display lg:text-hero">
                                        <span
                                            class="block text-ink">{{ __('home.slide_' . $slide['n'] . '_line_1') }}</span>
                                        <span
                                            class="block text-accent-deep">{{ __('home.slide_' . $slide['n'] . '_line_2') }}</span>
                                    </h1>

                                    <p class="mt-6 max-w-xl text-lead text-steel">
                                        {{ __('home.slide_' . $slide['n'] . '_lede') }}
                                    </p>

                                    <div class="mt-9 flex flex-wrap items-center gap-3">
                                        <x-ui.button variant="primary" size="lg" href="#">
                                            {{ __('home.hero_cta') }}
                                            <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                                        </x-ui.button>

                                        <x-ui.button variant="secondary" size="lg" href="#branches">
                                            {{ __('home.hero_alt') }}
                                        </x-ui.button>
                                    </div>
                                </div>

                                {{-- خانة الصورة — تنطوي كصفحة عند التبديل --}}
                                <x-ui.media-slot
                                    class="slider__media"
                                    :src="asset($slide['img'])"
                                    :alt="__('home.slide_' . $slide['n'] . '_alt')"
                                    :tone="$slide['tone']"
                                    ratio="4/3" />
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- أسهم مربّعة مكدّسة عند حافّة البداية — كالقالب --}}
                <div class="slider__nav">
                    <button type="button" data-slide-prev
                        aria-label="{{ __('home.slide_prev') }}"
                        class="slider__arrow cursor-pointer">
                        <x-icon name="arrow" class="size-5 rtl:-scale-x-100" />
                    </button>

                    <button type="button" data-slide-next
                        aria-label="{{ __('home.slide_next') }}"
                        class="slider__arrow cursor-pointer">
                        <x-icon name="arrow" class="size-5 ltr:-scale-x-100" />
                    </button>
                </div>

                {{-- النقاط — للمس وللوضوح --}}
                <div class="mt-10 flex items-center gap-2" role="tablist"
                    aria-label="{{ __('home.hero_slider_label') }}">
                    @foreach ($slides as $i => $slide)
                        <button type="button"
                            data-slide-dot
                            role="tab"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                            aria-label="{{ __('home.hero_go_to', ['n' => $i + 1]) }}"
                            class="slider__dot cursor-pointer"><span></span></button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════
         · بانر التجربة المجانية — أول ما يراه الزائر بعد الهيرو
         ═══════════════════════════════════════════════════════ --}}
    <section class="pb-20 lg:pb-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div
                class="reveal flex flex-col items-center justify-between gap-6 rounded-xl
                        bg-amber px-8 py-10 text-center lg:flex-row lg:px-14 lg:text-start">

                {{-- 🔴 محتوى محرَّر من لوحة الأدمن لا نصّ واجهة: يأتي من
                     settings عبر HomeController، فخرج من نظام الترجمة.
                     مفاتيح home.banner_* حُذفت لئلّا تبقى نسخة ثانية
                     تُقرأ كأنها المصدر. --}}
                <div>
                    <h2 class="text-h2 font-bold text-ink">{{ $cta['title'] }}</h2>

                    {{-- الفقرة كلّها تسقط عند خلوّ الحقل — لا فقرة فارغة
                         تترك فجوة تحت العنوان. --}}
                    @if (filled($cta['sub_title']))
                        <p class="mt-2 text-body text-ink/80">{{ $cta['sub_title'] }}</p>
                    @endif
                </div>

                <x-ui.button variant="on-dark" size="lg" :href="$cta['btn_link']" class="shrink-0">
                    {{ $cta['btn_text'] }}
                    <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                </x-ui.button>
            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٢ · كيف تعمل المنصة — تسلسل حقيقي، فالترقيم له معنى
         ═══════════════════════════════════════════════════════ --}}
    <section id="how" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">

            {{-- 🔴 محتوى محرَّر من لوحة الأدمن لا نصّ واجهة: الترويسة من
                 settings والخطوات من جدول steps، كلاهما عبر HomeController.
                 مفاتيح home.how_* حُذفت لئلّا تبقى نسخة ثانية تُقرأ
                 كأنها المصدر. --}}
            <div class="reveal">
                <x-ui.section-head
                    align="center"
                    :eyebrow="$how['badge_text']"
                    :title="$how['title']"
                    :lede="filled($how['sub_title']) ? $how['sub_title'] : null" />
            </div>

            {{-- القسم كلّه يسقط بلا خطوات: قائمة فارغة تحت ترويسة تعد
                 بثلاث خطوات أسوأ من غياب القسم. --}}
            @if ($steps->isNotEmpty())
                <ol class="relative mt-16 grid gap-8 lg:grid-cols-3 lg:gap-6">

                    {{-- الخيط الرابط بين الخطوات — يظهر على الشاشات الواسعة فقط --}}
                    <span
                        class="absolute inset-x-[16%] top-8 hidden h-px bg-linear-to-l
                                 from-transparent via-hairline-strong to-transparent lg:block"
                        aria-hidden="true"></span>

                    @foreach ($steps as $step)
                        <li class="reveal relative flex flex-col items-center text-center"
                            data-delay="{{ $loop->index }}">
                            {{-- الرقم من موضع الصفّ لا من display_order:
                                 ذاك يرتّب وقد يقفز (10 · 20 · 30)، وهذا
                                 يَعُدّ فيجب أن يكون متّصلاً دائماً. --}}
                            <span
                                class="num relative z-10 grid size-16 place-items-center rounded-full
                                         border border-hairline bg-canvas text-h4 font-bold text-accent-deep">
                                {{ sprintf('%02d', $loop->iteration) }}
                            </span>

                            <h3 class="mt-6 text-h4 font-semibold text-ink">{{ $step->title }}</h3>

                            @if (filled($step->sub_title))
                                <p class="mt-3 max-w-xs text-body text-steel">{{ $step->sub_title }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٣ · من نحن — ثلاث مراحل بلا إحصائيات
         ═══════════════════════════════════════════════════════ --}}
    <section id="about" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">

                {{--
                    🔴 The two bg-ground "corner cut" spans are gone.

                    They were 195×90 opaque rectangles absolutely positioned
                    over the photo's corners. Measured against the live page:
                    container 584×438, source 1200×900 — both exactly 4:3,
                    with object-fit: cover, so the image itself was never
                    cropped or distorted. The whole defect was two blocks of
                    page background painted on top of the photograph, which
                    reads as a rendering fault rather than a design flourish.

                    w-full is explicit so the slot fills its grid column at
                    every breakpoint instead of relying on the default
                    stretch; the aspect ratio then fixes the height and
                    object-cover fills it with no letterboxing.
                --}}
                <div class="reveal">
                    {{-- Image path comes from settings (about.img) and is
                         root-relative, matching the `custom` upload disk. --}}
                    <x-ui.media-slot
                        class="w-full rounded-xl"
                        :src="asset(ltrim($about['img'], '/'))"
                        :alt="__('home.about_image_alt')"
                        tone="accent"
                        ratio="4/3" />
                </div>

                {{-- النص --}}
                <div class="reveal" data-delay="1">
                    {{-- Admin-edited content, not interface copy: read from
                         `settings` via HomeController. The home.about_*
                         language keys were removed so no second copy reads
                         as if it were the source. --}}
                    <x-ui.rule-label>{{ $about['badge_text'] }}</x-ui.rule-label>

                    <h2 class="mt-5 text-h1 font-bold text-ink">{{ $about['title1'] }}</h2>

                    @if (filled($about['content1']))
                        <p class="mt-5 text-body text-steel">{{ $about['content1'] }}</p>
                    @endif

                    <div class="mt-8 grid gap-5 sm:grid-cols-2">

                        {{-- رسالتنا + بنود --}}
                        <div>
                            <h3 class="text-h4 font-semibold text-ink">{{ $about['title2'] }}</h3>

                            @if (filled($about['content2']))
                                <p class="mt-2.5 text-ui text-steel">{{ $about['content2'] }}</p>
                            @endif

                            <ul class="mt-4 flex flex-col gap-2.5">
                                @foreach (['about_check_1', 'about_check_2', 'about_check_3'] as $check)
                                    <li class="flex items-start gap-2.5 text-ui text-slate">
                                        <x-icon name="check" class="mt-1 size-4 shrink-0 text-accent-deep" />
                                        {{ __('home.' . $check) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- صندوق ملوّن بزر — كـ bg-primary p-4 text-center في القالب --}}
                        <div
                            class="flex flex-col items-center justify-center gap-5 rounded-xl
                                    bg-linear-to-bl from-deep-from to-deep-to p-7 text-center">
                            <p class="text-ui text-on-dark/85">{{ $about['title_box'] }}</p>

                            <x-ui.button variant="accent" size="md" :href="$about['btn_link']">
                                {{ $about['btn_text'] }}
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٣ · الإحصائيات — نص في البداية، أرقام في النهاية
         ═══════════════════════════════════════════════════════ --}}
    <section id="stats" class="py-20 lg:py-28">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="grid items-center gap-14 lg:grid-cols-2 lg:gap-20">

                {{-- النص — يبدأ من جهة البداية --}}
                <div class="reveal order-1">
                    {{-- Admin-edited content — see Setting::WHY_US_DEFAULTS.
                         The counter cards on the other side stay static. --}}
                    <x-ui.rule-label>{{ $whyUs['badge_text'] }}</x-ui.rule-label>

                    <h2 class="mt-5 text-h1 font-bold text-ink">{{ $whyUs['title'] }}</h2>

                    @if (filled($whyUs['description']))
                        <p class="mt-5 text-body text-steel">{{ $whyUs['description'] }}</p>
                    @endif

                    <ul class="mt-7 flex flex-col gap-3.5">
                        @foreach (['stats_check_1', 'stats_check_2', 'stats_check_3'] as $check)
                            <li class="flex items-start gap-3 text-ui text-slate">
                                <x-icon name="check" class="mt-1 size-4 shrink-0 text-accent-deep" />
                                {{ __('home.' . $check) }}
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-9 flex flex-wrap gap-3">
                        <x-ui.button variant="primary" size="lg"
                            :href="$whyUs['btn1_link']">{{ $whyUs['btn1_text'] }}</x-ui.button>

                        {{-- Secondary button drops entirely when its text is
                             cleared — a button with no label is not a button. --}}
                        @if (filled($whyUs['btn2_text']))
                            <x-ui.button variant="secondary" size="lg"
                                :href="$whyUs['btn2_link'] ?: '#'">{{ $whyUs['btn2_text'] }}</x-ui.button>
                        @endif
                    </div>
                </div>

                {{-- شبكة المربّعات — لونا العلامة متبادلان كالقالب الأول --}}
                <div class="reveal order-2 grid grid-cols-2 overflow-hidden rounded-xl" data-delay="1">
                    @foreach ($stats as $i => $stat)
                        @php
                            // رقعة شطرنج بلونَي العلامة — كالقالب الأول
                            // البنفسجي يحمل نصاً أبيض، والكهرماني نصاً كحلياً:
                            // الأبيض على #f0a500 يعطي 2.07:1 فقط — راسب.
                            $amber = in_array($i, [1, 2]);
                        @endphp
                        {{-- المقاسات منقولة من القالب: py-5 = 48px · display-5 = 48px · fa-3x ≈ 48px --}}
                        <div @class([
                            'flex flex-col items-center justify-center gap-3 px-6 py-12 text-center',
                            'bg-amber text-ink' => $amber,
                            'bg-accent text-on-primary' => !$amber,
                        ])>
                            <x-icon :name="$stat['icon']" class="size-12 opacity-90" />

                            <p class="num text-[48px] font-bold leading-none">
                                <span data-count="{{ $stat['n'] }}">0</span><span class="opacity-70">+</span>
                            </p>

                            <p class="text-caption opacity-80">{{ $stat['l'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٤ · اختيار المسار — بطاقات متساوية
         ═══════════════════════════════════════════════════════ --}}
    <section id="branches" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">

            <div class="reveal">
                {{-- Header from `settings`, cards from the `branches` table. --}}
                <x-ui.section-head
                    :eyebrow="$branchesHeader['badge_text']"
                    :title="$branchesHeader['title']"
                    :lede="filled($branchesHeader['sub_title']) ? $branchesHeader['sub_title'] : null" />
            </div>

            @if ($branchCards->isNotEmpty())
                <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($branchCards as $branch)
                        <div class="reveal" data-delay="{{ $loop->index }}">
                            {{-- icon and accent_token hold names that must
                                 exist in x-icon / the card's tone map: an
                                 unknown value renders blank, silently. --}}
                            <x-domain.branch-card
                                :name="$branch->name"
                                :summary="$branch->description"
                                :subjects="$branch->subjects_count"
                                :teachers="$branch->teachers_count"
                                :icon="$branch->icon ?? 'beaker'"
                                :tone="$branch->accent_token ?? 'accent'"
                                :href="route('branches.show', $branch->slug)" />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         · شريط الفيديو التعريفي — ⏳ المحتوى معلّق على م-5
         ═══════════════════════════════════════════════════════ --}}
    <section id="demo" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="reveal overflow-hidden rounded-xl bg-linear-to-bl from-deep-from to-deep-to">
                <div class="grid items-center gap-10 p-8 lg:grid-cols-2 lg:gap-16 lg:p-14">

                    <div>
                        {{-- Admin-edited content — see Setting::VIDEO_TOUR_DEFAULTS. --}}
                        <x-ui.rule-label on="dark">{{ $videoTour['badge_text'] }}</x-ui.rule-label>

                        <h2 class="mt-5 text-h1 font-bold text-on-dark">{{ $videoTour['title'] }}</h2>

                        @if (filled($videoTour['sub_title']))
                            <p class="mt-5 max-w-lg text-body text-on-dark/70">{{ $videoTour['sub_title'] }}</p>
                        @endif
                    </div>

                    {{-- لوح التشغيل --}}
                    <button type="button"
                        data-modal-open="demo-video"
                        class="group relative grid aspect-video w-full cursor-pointer place-items-center
                                   overflow-hidden rounded-lg bg-canvas-dark/60 ring-1 ring-on-dark/10
                                   transition hover:ring-accent/50">

                        <span
                            class="grid size-18 place-items-center rounded-full bg-accent text-on-primary
                                     transition duration-300 group-hover:scale-110">
                            <x-icon name="play" class="size-7" />
                        </span>

                        <span class="absolute bottom-4 text-caption text-on-dark/60">{{ __('home.demo_play') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    {{-- مودال الفيديو --}}
    <div id="demo-video" data-modal hidden
        class="fixed inset-0 z-[60] grid place-items-center bg-primary/80 p-6"
        role="dialog" aria-modal="true" aria-label="{{ $videoTour['title'] }}">

        <div class="w-full max-w-4xl">
            <div class="flex justify-end pb-3">
                <button type="button" data-modal-close
                    aria-label="{{ __('home.demo_close') }}"
                    class="grid size-10 cursor-pointer place-items-center rounded-full
                               bg-on-dark/10 text-on-dark transition hover:bg-on-dark/20">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            {{-- Player is force-LTR: the timeline runs left-to-right in every
                 locale, and mirroring it confuses every user.

                 ⏳ A self-hosted <video> until decision M-5 (video provider)
                 is settled; a provider embed replaces this block, not the
                 setting key. --}}
            <div dir="ltr"
                class="grid aspect-video place-items-center overflow-hidden rounded-xl bg-canvas-dark
                                  ring-1 ring-on-dark/10">
                @if (filled($videoTour['video_path']))
                    <video class="size-full" controls preload="none"
                           src="{{ asset(ltrim($videoTour['video_path'], '/')) }}"></video>
                @else
                    <p class="text-caption text-on-dark/50">{{ __('home.demo_pending') }}</p>
                @endif
            </div>
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════════════
         ٥ · الميزات — أربع بطاقات متساوية
         ═══════════════════════════════════════════════════════ --}}
    <section id="features" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">

            {{-- 🔴 Admin-edited content, not interface copy: the header comes
                 from `settings` and the cards from the `features` table, both
                 via HomeController. The home.features_* and home.f*_ language
                 keys were removed so no second copy reads as the source. --}}
            <div class="reveal">
                <x-ui.section-head
                    :eyebrow="$featuresHeader['badge_text']"
                    :title="$featuresHeader['title']" />
            </div>

            @if ($featureCards->isNotEmpty())
                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($featureCards as $feature)
                        <article class="reveal tile group p-6" data-delay="{{ $loop->index % 4 }}">
                            {{-- Every class is written out in full. Tailwind's
                                 scanner does not run PHP, so a class built by
                                 string concatenation generates no CSS at all —
                                 a trap this file has already fallen into. --}}
                            <span @class([
                                'tile__icon',
                                'bg-accent/14 text-accent-deep group-hover:bg-accent group-hover:text-on-primary' =>
                                    $feature->accent_token === 'accent',
                                'bg-tag/12 text-tag group-hover:bg-tag group-hover:text-on-dark' =>
                                    $feature->accent_token === 'tag',
                                'bg-amber/12 text-amber-deep group-hover:bg-amber group-hover:text-ink' =>
                                    $feature->accent_token === 'amber',
                                'bg-warn/14 text-warn-deep group-hover:bg-warn group-hover:text-on-dark' =>
                                    $feature->accent_token === 'warn',
                            ])>
                                <x-icon :name="$feature->icon ?? 'sparkle'" class="size-6" />
                            </span>

                            <h3 class="mt-5 text-h5 font-semibold text-ink">{{ $feature->title }}</h3>

                            @if (filled($feature->description))
                                <p class="mt-2.5 text-ui text-steel">{{ $feature->description }}</p>
                            @endif

                            {{-- The link drops entirely unless BOTH text and
                                 destination are set: text with no href is a
                                 dead link, href with no text is invisible. --}}
                            @if ($feature->hasAction())
                                <a href="{{ $feature->btn_link }}"
                                   class="tile__link mt-4 text-caption font-semibold text-ink">
                                    {{ $feature->btn_text }}
                                    <x-icon name="arrow" class="size-3.5 rtl:-scale-x-100" />
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٦ · الأسعار — ⏳ مبني على افتراض م-1: الاشتراك بمستوى الفرع
         ═══════════════════════════════════════════════════════ --}}
    <section id="pricing" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">

            {{-- 🔴 Admin-edited content, not interface copy: the header comes
                 from `settings` and the tiers from `pricing_plans`, both via
                 HomeController. The home.pricing_* and home.plan_* language
                 keys were removed so no second copy reads as the source. --}}
            <div class="reveal">
                <x-ui.section-head
                    align="center"
                    :eyebrow="$pricingHeader['badge_text']"
                    :title="$pricingHeader['title']"
                    :lede="filled($pricingHeader['sub_title']) ? $pricingHeader['sub_title'] : null" />
            </div>

            @if ($pricingPlans->isNotEmpty())
                {{-- The column count follows the data: three tiers is today's
                     design, but a fourth plan must not spill into a second row
                     of one. --}}
                <div @class([
                    'mt-14 grid items-stretch gap-6',
                    'lg:grid-cols-2' => $pricingPlans->count() === 2,
                    'lg:grid-cols-3' => $pricingPlans->count() !== 2,
                ])>
                    @foreach ($pricingPlans as $plan)
                        <div class="reveal" data-delay="{{ $loop->index }}">
                            <x-domain.plan-card
                                :name="$plan->name"
                                :summary="$plan->badge"
                                :price="$plan->price"
                                :period="$plan->period"
                                :features="$plan->featureList()"
                                :cta="$plan->btn_text"
                                :href="$plan->btn_link ?: '#'"
                                :featured="$plan->isHighlighted()"
                                :badge="$plan->highlight_badge" />
                        </div>
                    @endforeach
                </div>
            @endif

            {{--
                Link through to the full pricing screen (7), which carries the
                per-subject calculator and the comparison table. The homepage
                keeps a tier teaser; the real two-model choice from decision
                M-1 lives on /pricing.

                🔴 The "prices are in shekels and include all fees…" line that
                used to sit here is REMOVED, not relocated: it is a claim about
                billing terms that nothing in the system enforces, and the
                subscription layer that would make it true is unbuilt.
            --}}
            @if (filled($pricingHeader['btn_text']) && filled($pricingHeader['btn_link']))
                <div class="reveal mt-8 flex justify-center">
                    <x-ui.button variant="secondary" size="md" :href="$pricingHeader['btn_link']">
                        {{ $pricingHeader['btn_text'] }}
                        <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                    </x-ui.button>
                </div>
            @endif
        </div>
    </section>




    {{-- ═══════════════════════════════════════════════════════
         ٧ · أبرز المعلمين
         ═══════════════════════════════════════════════════════ --}}
    <section id="teachers" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">

            {{-- 🔴 Header from `settings`; cards built from the database via
                 HomeController. Nothing on a card is typed copy: the portrait
                 comes from the polymorphic media relation, the branch through
                 the teacher's subjects, and the lecture count is counted. The
                 home.teachers_* language keys were removed so no second copy
                 reads as the source. --}}
            <div class="reveal flex flex-wrap items-end justify-between gap-6">
                <x-ui.section-head
                    :eyebrow="$teachersHeader['badge_text']"
                    :title="$teachersHeader['title']"
                    :lede="filled($teachersHeader['sub_title']) ? $teachersHeader['sub_title'] : null" />

                @if (filled($teachersHeader['btn_text']) && filled($teachersHeader['btn_link']))
                    <x-ui.button variant="secondary" size="md"
                                 :href="$teachersHeader['btn_link']" class="shrink-0">
                        {{ $teachersHeader['btn_text'] }}
                        <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                    </x-ui.button>
                @endif
            </div>

            @if ($teacherCards->isNotEmpty())
                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($teacherCards as $teacher)
                        @php
                            $branch = $teacher->primaryBranch();

                            /*
                             * The card tone follows the branch's own colour
                             * token, so a teacher always reads in their
                             * branch's colour. Every class the card can apply
                             * is written out literally in its tone map —
                             * Tailwind's scanner does not run PHP.
                             */
                            $tone = $branch?->accent_token ?: 'accent';
                        @endphp

                        <div class="reveal" data-delay="{{ $loop->index % 4 }}">
                            <x-domain.teacher-card
                                :photo="$teacher->avatarUrl()"
                                :initials="$teacher->initials()"
                                :name="$teacher->full_name"
                                :subject="$teacher->displayTitle()"
                                :branch="$branch?->name ?? ''"
                                :lectures="$teacher->lectures_count"
                                :tone="$tone" />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٨ · آراء الطلبة — ثلاث بطاقات متساوية
         ═══════════════════════════════════════════════════════ --}}
    <section id="voices" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,3fr)_minmax(0,9fr)] lg:gap-10">

                {{-- لوح العنوان — سطح داكن كخلفية القالب المزخرفة --}}
                <div
                    class="reveal flex flex-col justify-center rounded-xl
                            bg-linear-to-bl from-deep-from to-deep-to p-8 lg:p-10">
                    {{-- 🔴 Admin-edited content: header from `settings`, the
                         reviews from `testimonials`, and each reviewer's name,
                         branch and city read live from their linked student
                         account. The home.voices_* and home.voice_* language
                         keys were removed so no second copy reads as source. --}}
                    <x-ui.rule-label on="dark">{{ $testimonialsHeader['badge_text'] }}</x-ui.rule-label>
                    <h2 class="mt-5 text-h2 font-bold text-on-dark">{{ $testimonialsHeader['title'] }}</h2>
                    @if (filled($testimonialsHeader['sub_title']))
                        <p class="mt-4 text-body text-on-dark/70">{{ $testimonialsHeader['sub_title'] }}</p>
                    @endif

                    {{-- 🔴 Empty btn_text hides the button — that is the off
                         switch. A filled btn_link turns it into a plain link
                         to that destination; empty (the normal case) opens
                         the submission modal in place. --}}
                    @if (filled($testimonialsHeader['btn_text']))
                        @if (filled($testimonialsHeader['btn_link']))
                            <x-ui.button variant="accent" size="md"
                                         :href="$testimonialsHeader['btn_link']"
                                         class="mt-7 self-start">
                                {{ $testimonialsHeader['btn_text'] }}
                            </x-ui.button>
                        @else
                            {{-- Dispatches on the window rather than reaching
                                 into the modal's scope: the trigger and the
                                 dialog stay independent of each other.

                                 🔴 x-data="{}" is REQUIRED, not decoration.
                                 Alpine only binds directives inside a
                                 component, so without a scope of its own this
                                 button's x-on:click is never wired — and the
                                 failure is silent: the markup looks correct,
                                 the button renders, and clicking it does
                                 nothing at all. --}}
                            <x-ui.button variant="accent" size="md" type="button"
                                         data-testimonial-open
                                         x-data="{}"
                                         x-on:click="$dispatch('open-testimonial-modal')"
                                         class="mt-7 self-start">
                                {{ $testimonialsHeader['btn_text'] }}
                            </x-ui.button>
                        @endif
                    @endif

                    {{-- Server-rendered confirmation for the no-JS path: the
                         plain POST redirects back here with this flash. --}}
                    @if (session('testimonial.submitted'))
                        <p class="mt-5 rounded-xl bg-on-dark/10 px-4 py-3 text-caption text-on-dark">
                            {{ session('testimonial.submitted') }}
                        </p>
                    @endif
                </div>

                {{-- الكاروسيل --}}
                <div class="reveal" data-delay="1">
                    {{-- overflow-hidden إلزامي: الشرائح تدور خارج الحدود وتُنتج تمريراً أفقياً بدونه --}}
                    <div data-slider data-interval="7500" class="relative overflow-hidden">

                        <div class="slider">
                            @foreach ($testimonials as $testimonial)
                                <div data-slide
                                    class="slider__slide {{ $loop->first ? 'is-active' : '' }}"
                                    role="group" aria-roledescription="slide"
                                    aria-label="{{ $loop->iteration }} / {{ $testimonials->count() }}"
                                    @if (! $loop->first) aria-hidden="true" @endif>

                                    <div class="grid items-center gap-8 md:grid-cols-2 md:gap-10">

                                        {{-- الصورة مع كتلة لونية خلفها كالقالب --}}
                                        <div class="relative ps-10 pt-8">
                                            <span
                                                class="absolute inset-s-0 top-0 h-full w-[calc(50%+2.5rem)] rounded-lg bg-accent/18"
                                                aria-hidden="true"></span>
                                            {{-- Attached portrait first, then the
                                                 student's own avatar; media-slot
                                                 draws its placeholder for null. --}}
                                            <x-ui.media-slot
                                                class="relative rounded-lg"
                                                :src="$testimonial->photoUrl()"
                                                :alt="$testimonial->displayName()"
                                                tone="accent"
                                                ratio="1/1" />
                                        </div>

                                        {{-- النص --}}
                                        <div class="ps-12 md:ps-0">
                                            {{-- 🔴 stars() clamps to 1..5. The column
                                                 is an unsigned tiny int that accepts
                                                 255, and the database has no idea
                                                 seven stars do not exist. --}}
                                            @php $stars = $testimonial->stars(); @endphp

                                            <div class="flex gap-1"
                                                 aria-label="{{ $stars }} / {{ \App\Models\Testimonial::MAX_RATE }}">
                                                @for ($s = 1; $s <= \App\Models\Testimonial::MAX_RATE; $s++)
                                                    <svg @class([
                                                            'size-4',
                                                            'text-accent' => $s <= $stars,
                                                            'text-hairline-strong' => $s > $stars,
                                                        ])
                                                        viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                        <path
                                                            d="m12 2 3 6.5 7 .9-5 4.9 1.2 7L12 18l-6.2 3.3L7 14.3l-5-4.9 7-.9z" />
                                                    </svg>
                                                @endfor
                                            </div>

                                            <blockquote class="mt-4 text-lead text-slate">
                                                {{ $testimonial->description }}
                                            </blockquote>

                                            <div class="mt-6 flex items-center gap-4">
                                                <span
                                                    class="grid size-12 shrink-0 place-items-center rounded-md bg-surface text-accent-deep">
                                                    <svg class="size-6" viewBox="0 0 24 24" fill="currentColor"
                                                        aria-hidden="true">
                                                        <path
                                                            d="M10 7H6a3 3 0 0 0-3 3v7h7v-7H6a1 1 0 0 1 1-1h3zm11 0h-4a3 3 0 0 0-3 3v7h7v-7h-4a1 1 0 0 1 1-1h3z" />
                                                    </svg>
                                                </span>
                                                <span>
                                                    {{-- Live from the linked student, or the
                                                         stored snapshot once that account is
                                                         gone. --}}
                                                    <span
                                                        class="block text-h5 font-semibold text-ink">{{ $testimonial->displayName() }}</span>
                                                    @if (filled($meta = $testimonial->displayMeta()))
                                                        <span class="block text-caption text-stone">{{ $meta }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- أسهم مكدّسة كالقالب --}}
                        <div class="slider__nav slider__nav--inset">
                            <button type="button" data-slide-prev
                                aria-label="{{ __('home.slide_prev') }}"
                                class="slider__arrow slider__arrow--dark cursor-pointer">
                                <x-icon name="arrow" class="size-5 rtl:-scale-x-100" />
                            </button>
                            <button type="button" data-slide-next
                                aria-label="{{ __('home.slide_next') }}"
                                class="slider__arrow slider__arrow--dark cursor-pointer">
                                <x-icon name="arrow" class="size-5 ltr:-scale-x-100" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{--
        Review submission modal — mounted once, outside the section.

        🔴 The student check is server-side: the form only renders for an
        account that resolves to a `students` row. A teacher or admin is
        authenticated but has no such row, and the route refuses them too —
        this just means they are not shown a form that would be rejected.
    --}}
    <x-domain.testimonial-modal :is-student="auth()->check() && auth()->user()->userable instanceof \App\Models\Student" />


    {{-- ═══════════════════════════════════════════════════════
         · الأسئلة الشائعة
         ═══════════════════════════════════════════════════════ --}}
    <section id="faq" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:gap-20">

                {{-- 🔴 Admin-edited content: header from `settings`, questions
                     from the `faqs` table, both via HomeController. The
                     home.faq_* language keys were removed so no second copy
                     reads as the source. --}}
                <div class="reveal lg:sticky lg:top-28 lg:self-start">
                    <x-ui.rule-label>{{ $faqHeader['badge_text'] }}</x-ui.rule-label>

                    <h2 class="mt-5 text-h1 font-bold text-ink">{{ $faqHeader['title'] }}</h2>

                    @if (filled($faqHeader['sub_title']))
                        <p class="mt-5 text-body text-steel">{{ $faqHeader['sub_title'] }}</p>
                    @endif

                    @if (filled($faqHeader['btn_text']) && filled($faqHeader['btn_link']))
                        <x-ui.button variant="secondary" size="md"
                                     :href="$faqHeader['btn_link']" class="mt-7">
                            {{ $faqHeader['btn_text'] }}
                            <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                        </x-ui.button>
                    @endif
                </div>

                @if ($faqs->isNotEmpty())
                    <div class="flex flex-col gap-3">
                        @foreach ($faqs as $faq)
                            {{-- The stagger caps at 3: past that the last card
                                 waits noticeably long for no benefit. --}}
                            <div class="reveal" data-delay="{{ min($loop->index, 3) }}">
                                {{-- First one open, so the section shows what an
                                     answer looks like rather than a stack of
                                     closed bars. --}}
                                <x-ui.accordion-item group="faq"
                                                     :question="$faq->question"
                                                     :open="$loop->first">
                                    {{ $faq->answer }}
                                </x-ui.accordion-item>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٩ · CTA الختامي — المكوّن المشترك نفسه المستخدم في صفحات
         الفروع. كان مكرّراً هنا وفي صفحتَي الفروع بثلاث نسخ من
         نفس الكتلة؛ نسخة واحدة تقرأ نفس الإعدادات.
         ═══════════════════════════════════════════════════════ --}}
    <x-site.cta-final />

</x-layouts.public>
