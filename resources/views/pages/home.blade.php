@php
    // بيانات عرض — تُستبدل باستعلامات عند بناء نماذج المجال
    $branches = [
        ['key' => 'sci', 'icon' => 'beaker',    'subjects' => 8, 'teachers' => 12, 'tone' => 'accent'],
        ['key' => 'lit', 'icon' => 'book',      'subjects' => 7, 'teachers' => 9,  'tone' => 'tag'],
        ['key' => 'com', 'icon' => 'briefcase', 'subjects' => 6, 'teachers' => 7,  'tone' => 'amber'],
        ['key' => 'ind', 'icon' => 'wrench',    'subjects' => 6, 'teachers' => 5,  'tone' => 'warn'],
    ];

    $features = [
        ['icon' => 'clipboard', 'key' => 'f1', 'tone' => 'accent'],
        ['icon' => 'folder',    'key' => 'f2', 'tone' => 'tag'],
        ['icon' => 'users',     'key' => 'f3', 'tone' => 'amber'],
        ['icon' => 'compass',   'key' => 'f4', 'tone' => 'warn'],
    ];

    $teachers = [
        ['photo' => 'images/teachers/team-1.jpg', 'name' => 'أ. سامر خليل', 'subject' => 'الرياضيات',        'branch' => 'علمي',  'lectures' => 42, 'students' => 610, 'tone' => 'accent'],
        ['photo' => 'images/teachers/team-2.jpg', 'name' => 'أ. رنا عوض',   'subject' => 'الفيزياء',         'branch' => 'علمي',  'lectures' => 36, 'students' => 480, 'tone' => 'accent'],
        ['photo' => 'images/teachers/team-3.jpg', 'name' => 'أ. وليد حمد',  'subject' => 'الكيمياء',         'branch' => 'علمي',  'lectures' => 31, 'students' => 395, 'tone' => 'accent'],
        ['photo' => 'images/teachers/team-4.jpg', 'name' => 'أ. مازن سالم', 'subject' => 'اللغة العربية',    'branch' => 'أدبي',  'lectures' => 38, 'students' => 520, 'tone' => 'tag'],
        ['photo' => 'images/teachers/team-5.jpg', 'name' => 'أ. لينا فرح',  'subject' => 'اللغة الإنجليزية', 'branch' => 'أدبي',  'lectures' => 34, 'students' => 460, 'tone' => 'tag'],
        ['photo' => 'images/teachers/team-6.jpg', 'name' => 'أ. هدى ناصر',  'subject' => 'المحاسبة',         'branch' => 'تجاري', 'lectures' => 29, 'students' => 340, 'tone' => 'amber'],
    ];

    $stats = [
        ['n' => 480,  'l' => __('home.stat_lectures'), 'icon' => 'play'],
        ['n' => 33,   'l' => __('home.stat_teachers'), 'icon' => 'users'],
        ['n' => 120,  'l' => __('home.stat_files'),    'icon' => 'folder'],
        ['n' => 2400, 'l' => __('home.stat_students'), 'icon' => 'compass'],
    ];

    $slides = [
        ['n' => 1, 'tone' => 'accent',   'img' => 'images/hero/slide-1-focus.jpg'],
        ['n' => 2, 'tone' => 'tag',    'img' => 'images/hero/slide-2-quiz.jpg'],
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
                                        <span class="block text-ink">{{ __('home.slide_' . $slide['n'] . '_line_1') }}</span>
                                        <span class="block text-accent-deep">{{ __('home.slide_' . $slide['n'] . '_line_2') }}</span>
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
            <div class="reveal flex flex-col items-center justify-between gap-6 rounded-xl
                        bg-amber px-8 py-10 text-center lg:flex-row lg:px-14 lg:text-start">

                <div>
                    <h2 class="text-h2 font-bold text-ink">{{ __('home.banner_title') }}</h2>
                    <p class="mt-2 text-body text-ink/80">{{ __('home.banner_lede') }}</p>
                </div>

                <x-ui.button variant="on-dark" size="lg" href="#" class="shrink-0">
                    {{ __('home.banner_cta') }}
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

            <div class="reveal">
                <x-ui.section-head
                    align="center"
                    :eyebrow="__('home.how_eyebrow')"
                    :title="__('home.how_title')"
                    :lede="__('home.how_lede')" />
            </div>

            <ol class="relative mt-16 grid gap-8 lg:grid-cols-3 lg:gap-6">

                {{-- الخيط الرابط بين الخطوات — يظهر على الشاشات الواسعة فقط --}}
                <span class="absolute inset-x-[16%] top-8 hidden h-px bg-linear-to-l
                             from-transparent via-hairline-strong to-transparent lg:block"
                      aria-hidden="true"></span>

                @foreach ([1, 2, 3] as $i)
                    <li class="reveal relative flex flex-col items-center text-center" data-delay="{{ $i - 1 }}">
                        <span class="num relative z-10 grid size-16 place-items-center rounded-full
                                     border border-hairline bg-canvas text-h4 font-bold text-accent-deep">
                            0{{ $i }}
                        </span>

                        <h3 class="mt-6 text-h4 font-semibold text-ink">{{ __('home.how_' . $i . '_title') }}</h3>
                        <p class="mt-3 max-w-xs text-body text-steel">{{ __('home.how_' . $i . '_body') }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٣ · من نحن — ثلاث مراحل بلا إحصائيات
         ═══════════════════════════════════════════════════════ --}}
    <section id="about" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">

                {{-- الصورة بقصّتين في زاويتين — نمط .about-img من القالب --}}
                <div class="reveal relative">
                    <x-ui.media-slot
                        class="rounded-xl"
                        :src="asset('images/about/classroom.jpg')"
                        :alt="__('home.about_image_alt')"
                        tone="accent"
                        ratio="4/3" />

                    <span class="pointer-events-none absolute start-0 top-0 h-[90px] w-1/3 bg-ground"
                          aria-hidden="true"></span>
                    <span class="pointer-events-none absolute bottom-0 end-0 h-[90px] w-1/3 bg-ground"
                          aria-hidden="true"></span>
                </div>

                {{-- النص --}}
                <div class="reveal" data-delay="1">
                    <x-ui.rule-label>{{ __('home.about_eyebrow') }}</x-ui.rule-label>

                    <h2 class="mt-5 text-h1 font-bold text-ink">{{ __('home.about_title') }}</h2>
                    <p class="mt-5 text-body text-steel">{{ __('home.about_body') }}</p>

                    <div class="mt-8 grid gap-5 sm:grid-cols-2">

                        {{-- رسالتنا + بنود --}}
                        <div>
                            <h3 class="text-h4 font-semibold text-ink">{{ __('home.about_mission_title') }}</h3>
                            <p class="mt-2.5 text-ui text-steel">{{ __('home.about_mission_body') }}</p>

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
                        <div class="flex flex-col items-center justify-center gap-5 rounded-xl
                                    bg-linear-to-bl from-deep-from to-deep-to p-7 text-center">
                            <p class="text-ui text-on-dark/85">{{ __('home.about_cta_body') }}</p>

                            <x-ui.button variant="accent" size="md" href="#">
                                {{ __('home.about_cta_btn') }}
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
                    <x-ui.rule-label>{{ __('home.stats_eyebrow') }}</x-ui.rule-label>

                    <h2 class="mt-5 text-h1 font-bold text-ink">{{ __('home.stats_title') }}</h2>
                    <p class="mt-5 text-body text-steel">{{ __('home.stats_lede') }}</p>

                    <ul class="mt-7 flex flex-col gap-3.5">
                        @foreach (['stats_check_1', 'stats_check_2', 'stats_check_3'] as $check)
                            <li class="flex items-start gap-3 text-ui text-slate">
                                <x-icon name="check" class="mt-1 size-4 shrink-0 text-accent-deep" />
                                {{ __('home.' . $check) }}
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-9 flex flex-wrap gap-3">
                        <x-ui.button variant="primary" size="lg" href="#">{{ __('home.stats_cta') }}</x-ui.button>
                        <x-ui.button variant="secondary" size="lg" href="#branches">{{ __('home.stats_cta_alt') }}</x-ui.button>
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
                            'bg-accent text-on-primary' => ! $amber,
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
                <x-ui.section-head
                    :eyebrow="__('home.branches_eyebrow')"
                    :title="__('home.branches_title')"
                    :lede="__('home.branches_lede')" />
            </div>

            <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($branches as $i => $branch)
                    <div class="reveal" data-delay="{{ $i }}">
                        <x-domain.branch-card
                            :name="__('home.branch_' . $branch['key'] . '_name')"
                            :summary="__('home.branch_' . $branch['key'] . '_summary')"
                            :subjects="$branch['subjects']"
                            :teachers="$branch['teachers']"
                            :icon="$branch['icon']"
                            :tone="$branch['tone']"
                            href="#" />
                    </div>
                @endforeach
            </div>
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
                        <x-ui.rule-label on="dark">{{ __('home.demo_eyebrow') }}</x-ui.rule-label>

                        <h2 class="mt-5 text-h1 font-bold text-on-dark">{{ __('home.demo_title') }}</h2>
                        <p class="mt-5 max-w-lg text-body text-on-dark/70">{{ __('home.demo_lede') }}</p>
                    </div>

                    {{-- لوح التشغيل --}}
                    <button type="button"
                            data-modal-open="demo-video"
                            class="group relative grid aspect-video w-full cursor-pointer place-items-center
                                   overflow-hidden rounded-lg bg-canvas-dark/60 ring-1 ring-on-dark/10
                                   transition hover:ring-accent/50">

                        <span class="grid size-18 place-items-center rounded-full bg-accent text-on-primary
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
         role="dialog" aria-modal="true" aria-label="{{ __('home.demo_title') }}">

        <div class="w-full max-w-4xl">
            <div class="flex justify-end pb-3">
                <button type="button" data-modal-close
                        aria-label="{{ __('home.demo_close') }}"
                        class="grid size-10 cursor-pointer place-items-center rounded-full
                               bg-on-dark/10 text-on-dark transition hover:bg-on-dark/20">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            {{-- المشغّل LTR إجبارياً · المحتوى بانتظار قرار مزوّد الفيديو --}}
            <div dir="ltr" class="grid aspect-video place-items-center rounded-xl bg-canvas-dark
                                  ring-1 ring-on-dark/10">
                <p class="text-caption text-on-dark/50">{{ __('home.demo_pending') }}</p>
            </div>
        </div>
    </div>


    {{-- ═══════════════════════════════════════════════════════
         ٥ · الميزات — أربع بطاقات متساوية
         ═══════════════════════════════════════════════════════ --}}
    <section id="features" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">

            <div class="reveal">
                <x-ui.section-head
                    :eyebrow="__('home.features_eyebrow')"
                    :title="__('home.features_title')" />
            </div>

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($features as $i => $feature)
                    <article class="reveal tile group p-6" data-delay="{{ $i % 4 }}">
                        <span @class([
                            'tile__icon',
                            'bg-accent/14 text-accent-deep group-hover:bg-accent group-hover:text-on-primary'          => $feature['tone'] === 'accent',
                            'bg-tag/12 text-tag group-hover:bg-tag group-hover:text-on-dark'                  => $feature['tone'] === 'tag',
                            'bg-amber/12 text-amber-deep group-hover:bg-amber group-hover:text-ink'    => $feature['tone'] === 'amber',
                            'bg-warn/14 text-warn-deep group-hover:bg-warn group-hover:text-on-dark'          => $feature['tone'] === 'warn',
                        ])>
                            <x-icon :name="$feature['icon']" class="size-6" />
                        </span>

                        <h3 class="mt-5 text-h5 font-semibold text-ink">
                            {{ __('home.' . $feature['key'] . '_title') }}
                        </h3>

                        <p class="mt-2.5 text-ui text-steel">
                            {{ __('home.' . $feature['key'] . '_body') }}
                        </p>

                        <a href="#" class="tile__link mt-4 text-caption font-semibold text-ink">
                            {{ __('home.read_more') }}
                            <x-icon name="arrow" class="size-3.5 rtl:-scale-x-100" />
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٦ · الأسعار — ⏳ مبني على افتراض م-1: الاشتراك بمستوى الفرع
         ═══════════════════════════════════════════════════════ --}}
    <section id="pricing" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">

            <div class="reveal">
                <x-ui.section-head
                    align="center"
                    :eyebrow="__('home.pricing_eyebrow')"
                    :title="__('home.pricing_title')"
                    :lede="__('home.pricing_lede')" />
            </div>

            <div class="mt-14 grid items-stretch gap-6 lg:grid-cols-3">
                <div class="reveal">
                    <x-domain.plan-card
                        :name="__('home.plan_free_name')"
                        :summary="__('home.plan_free_summary')"
                        price="٠"
                        :period="__('home.plan_free_period')"
                        :cta="__('home.plan_free_cta')"
                        :features="[__('home.plan_free_f1'), __('home.plan_free_f2'), __('home.plan_free_f3')]" />
                </div>

                <div class="reveal" data-delay="1">
                    <x-domain.plan-card
                        featured
                        :name="__('home.plan_term_name')"
                        :summary="__('home.plan_term_summary')"
                        price="١٢٠"
                        :period="__('home.plan_term_period')"
                        :cta="__('home.plan_term_cta')"
                        :features="[__('home.plan_term_f1'), __('home.plan_term_f2'), __('home.plan_term_f3'), __('home.plan_term_f4')]" />
                </div>

                <div class="reveal" data-delay="2">
                    <x-domain.plan-card
                        :name="__('home.plan_year_name')"
                        :summary="__('home.plan_year_summary')"
                        price="٢٠٠"
                        :period="__('home.plan_year_period')"
                        :cta="__('home.plan_year_cta')"
                        :features="[__('home.plan_year_f1'), __('home.plan_year_f2'), __('home.plan_year_f3'), __('home.plan_year_f4')]" />
                </div>
            </div>

            <p class="reveal mt-8 text-center text-caption text-stone">{{ __('home.pricing_note') }}</p>
        </div>
    </section>




    {{-- ═══════════════════════════════════════════════════════
         ٧ · أبرز المعلمين
         ═══════════════════════════════════════════════════════ --}}
    <section id="teachers" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">

            <div class="reveal flex flex-wrap items-end justify-between gap-6">
                <x-ui.section-head
                    :eyebrow="__('home.teachers_eyebrow')"
                    :title="__('home.teachers_title')"
                    :lede="__('home.teachers_lede')" />

                <x-ui.button variant="secondary" size="md" href="#" class="shrink-0">
                    {{ __('home.teachers_all') }}
                    <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                </x-ui.button>
            </div>

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($teachers as $i => $teacher)
                    <div class="reveal" data-delay="{{ $i }}">
                        <x-domain.teacher-card
                            :photo="asset($teacher['photo'])"
                            :name="$teacher['name']"
                            :subject="$teacher['subject']"
                            :branch="$teacher['branch']"
                            :lectures="$teacher['lectures']"
                            :students="$teacher['students']"
                            :tone="$teacher['tone']" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٨ · آراء الطلبة — ثلاث بطاقات متساوية
         ═══════════════════════════════════════════════════════ --}}
    <section id="voices" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,3fr)_minmax(0,9fr)] lg:gap-10">

                {{-- لوح العنوان — سطح داكن كخلفية القالب المزخرفة --}}
                <div class="reveal flex flex-col justify-center rounded-xl
                            bg-linear-to-bl from-deep-from to-deep-to p-8 lg:p-10">
                    <x-ui.rule-label on="dark">{{ __('home.voices_eyebrow') }}</x-ui.rule-label>
                    <h2 class="mt-5 text-h2 font-bold text-on-dark">{{ __('home.voices_title') }}</h2>
                    <p class="mt-4 text-body text-on-dark/70">{{ __('home.voices_lede') }}</p>
                </div>

                {{-- الكاروسيل --}}
                <div class="reveal" data-delay="1">
                    {{-- overflow-hidden إلزامي: الشرائح تدور خارج الحدود وتُنتج تمريراً أفقياً بدونه --}}
                    <div data-slider data-interval="7500" class="relative overflow-hidden">

                        <div class="slider">
                            @foreach ([1, 2, 3] as $i)
                                <div data-slide
                                     class="slider__slide {{ $i === 1 ? 'is-active' : '' }}"
                                     role="group" aria-roledescription="slide"
                                     aria-label="{{ $i }} / 3"
                                     @if ($i !== 1) aria-hidden="true" @endif>

                                    <div class="grid items-center gap-8 md:grid-cols-2 md:gap-10">

                                        {{-- الصورة مع كتلة لونية خلفها كالقالب --}}
                                        <div class="relative ps-10 pt-8">
                                            <span class="absolute inset-s-0 top-0 h-full w-[calc(50%+2.5rem)] rounded-lg bg-accent/18"
                                                  aria-hidden="true"></span>
                                            <x-ui.media-slot
                                                class="relative rounded-lg"
                                                :src="asset('images/voices/student-' . $i . '.jpg')"
                                                :alt="__('home.voice_' . $i . '_name')"
                                                tone="accent"
                                                ratio="1/1" />
                                        </div>

                                        {{-- النص --}}
                                        <div class="ps-12 md:ps-0">
                                            <div class="flex gap-1" aria-label="{{ __('home.rating_full') }}">
                                                @for ($s = 0; $s < 5; $s++)
                                                    <svg class="size-4 text-accent" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                        <path d="m12 2 3 6.5 7 .9-5 4.9 1.2 7L12 18l-6.2 3.3L7 14.3l-5-4.9 7-.9z"/>
                                                    </svg>
                                                @endfor
                                            </div>

                                            <blockquote class="mt-4 text-lead text-slate">
                                                {{ __('home.voice_' . $i . '_quote') }}
                                            </blockquote>

                                            <div class="mt-6 flex items-center gap-4">
                                                <span class="grid size-12 shrink-0 place-items-center rounded-md bg-surface text-accent-deep">
                                                    <svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                        <path d="M10 7H6a3 3 0 0 0-3 3v7h7v-7H6a1 1 0 0 1 1-1h3zm11 0h-4a3 3 0 0 0-3 3v7h7v-7h-4a1 1 0 0 1 1-1h3z"/>
                                                    </svg>
                                                </span>
                                                <span>
                                                    <span class="block text-h5 font-semibold text-ink">{{ __('home.voice_' . $i . '_name') }}</span>
                                                    <span class="block text-caption text-stone">{{ __('home.voice_' . $i . '_meta') }}</span>
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


    {{-- ═══════════════════════════════════════════════════════
         · الأسئلة الشائعة
         ═══════════════════════════════════════════════════════ --}}
    <section id="faq" class="py-20 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="grid gap-12 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:gap-20">

                <div class="reveal lg:sticky lg:top-28 lg:self-start">
                    <x-ui.rule-label>{{ __('home.faq_eyebrow') }}</x-ui.rule-label>

                    <h2 class="mt-5 text-h1 font-bold text-ink">{{ __('home.faq_title') }}</h2>
                    <p class="mt-5 text-body text-steel">{{ __('home.faq_lede') }}</p>

                    <x-ui.button variant="secondary" size="md" href="#" class="mt-7">
                        {{ __('home.faq_contact') }}
                        <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                    </x-ui.button>
                </div>

                <div class="flex flex-col gap-3">
                    @foreach ([1, 2, 3, 4, 5, 6] as $i)
                        <div class="reveal" data-delay="{{ min($i - 1, 3) }}">
                            <x-ui.accordion-item group="faq" :question="__('home.faq_' . $i . '_q')" :open="$i === 1">
                                {{ __('home.faq_' . $i . '_a') }}
                            </x-ui.accordion-item>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>


    {{-- ═══════════════════════════════════════════════════════
         ٩ · CTA — النطاق الداكن الوحيد، يبقى كما هو
         ═══════════════════════════════════════════════════════ --}}
    <section class="pb-20 lg:pb-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="hero-canvas reveal overflow-hidden rounded-xl
                        px-8 py-16 text-center lg:px-16 lg:py-20">

                <h2 class="text-h1 font-bold text-ink">{{ __('home.cta_title') }}</h2>
                <p class="mx-auto mt-4 max-w-xl text-lead text-steel">{{ __('home.cta_lede') }}</p>

                <div class="mt-9 flex justify-center">
                    <x-ui.button variant="primary" size="lg" href="#">
                        {{ __('home.cta_btn') }}
                        <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                    </x-ui.button>
                </div>

                <p class="mt-5 text-caption text-stone">{{ __('home.cta_note') }}</p>
            </div>
        </div>
    </section>

</x-layouts.public>
