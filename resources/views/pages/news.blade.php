@php
    $categories = [
        'exams'      => __('public.news_cat_exams'),
        'curriculum' => __('public.news_cat_curriculum'),
        'platform'   => __('public.news_cat_platform'),
        'tips'       => __('public.news_cat_tips'),
    ];

    $articles = [
        ['slug' => 'tawjihi-exam-dates-2026', 'cat' => 'exams',
         'title' => 'إعلان مواعيد امتحانات التوجيهي للدورة الأولى 2026',
         'excerpt' => 'أعلنت وزارة التربية والتعليم الجدول الرسمي لامتحانات الثانوية العامة، ويبدأ التطبيق مطلع حزيران لجميع الفروع.',
         'date' => '2026-08-19', 'label' => '19 آب 2026', 'read' => 4, 'featured' => true],

        ['slug' => 'math-curriculum-update', 'cat' => 'curriculum',
         'title' => 'تعديلات على منهاج الرياضيات للفرع العلمي',
         'excerpt' => 'حُذفت وحدة المصفوفات من مقرر الفصل الثاني، وأُضيفت تطبيقات على التكامل المحدّد.',
         'date' => '2026-08-16', 'label' => '16 آب 2026', 'read' => 3],

        ['slug' => 'library-launch', 'cat' => 'platform',
         'title' => 'إطلاق المكتبة المنظّمة لجميع الفروع',
         'excerpt' => 'أوراق العمل والملخّصات والامتحانات السابقة صارت مجمّعة بفرعك ومادتك، بدل تشتّتها بين المجموعات.',
         'date' => '2026-08-12', 'label' => '12 آب 2026', 'read' => 2],

        ['slug' => 'study-plan-tips', 'cat' => 'tips',
         'title' => 'كيف تبني خطة مراجعة لا تنهار بعد أسبوع',
         'excerpt' => 'الخطة التي تنجح ليست الأطول بل الأقابل للاستمرار — أربع قواعد عملية من تجربة طلبة سابقين.',
         'date' => '2026-08-09', 'label' => '9 آب 2026', 'read' => 6],

        ['slug' => 'physics-lab-videos', 'cat' => 'platform',
         'title' => 'إضافة فيديوهات المختبر لمادة الفيزياء',
         'excerpt' => 'تجارب المنهاج مصوّرة خطوة بخطوة، لطلبة المدارس التي لا تتوفّر فيها مختبرات مجهّزة.',
         'date' => '2026-08-05', 'label' => '5 آب 2026', 'read' => 3],

        ['slug' => 'english-exam-format', 'cat' => 'exams',
         'title' => 'تغيير في نمط أسئلة اللغة الإنجليزية',
         'excerpt' => 'سؤال المقال يُستبدل بسؤالَي فهم مقروء، مع بقاء التوزيع الكلي للعلامات كما هو.',
         'date' => '2026-08-01', 'label' => '1 آب 2026', 'read' => 3],

        ['slug' => 'exam-anxiety', 'cat' => 'tips',
         'title' => 'قلق الامتحان: ما ينفع فعلاً وما يزيده سوءاً',
         'excerpt' => 'ثلاث ممارسات يوصي بها مرشدون تربويون، وممارستان شائعتان تضرّان أكثر مما تنفعان.',
         'date' => '2026-07-27', 'label' => '27 تموز 2026', 'read' => 5],
    ];

    $featured = collect($articles)->firstWhere('featured', true);
    $rest = collect($articles)->reject(fn ($a) => ! empty($a['featured']))->values();
@endphp

<x-layouts.public :title="__('public.news_title')">

    {{--
        Restructured 2026-08-22 after measuring BBC Arabic, Al Arabiya and
        Donia Al-Watan. Editorial structure: header and controls on a canvas
        band, then a wide split lead story, then a three-up grid. Filtering
        covers the lead story too, and a whole block hides when it has no
        results (data-news-block) - never a heading over emptiness.
    --}}
    <div data-news-filter>

        {{-- ═══ Header and filter controls - a canvas band separating this surface from the rest ═══ --}}
        <section class="border-b border-hairline bg-canvas py-12 lg:py-16">
            <div class="mx-auto max-w-[1280px] px-6">
                <div class="max-w-3xl">
                    <x-ui.rule-label>{{ __('nav.news') }}</x-ui.rule-label>
                    <h1 class="mt-5 text-h1 font-bold text-ink">{{ __('public.news_title') }}</h1>
                    <p class="measure mt-4 text-lead text-steel">{{ __('public.news_subtitle') }}</p>
                </div>

                <div class="mt-9 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <label class="relative flex min-w-0 items-center lg:w-80">
                        <x-icon name="search" class="pointer-events-none absolute start-4 size-4 text-muted" />
                        <input type="search"
                               data-news-search
                               placeholder="{{ __('public.news_search_placeholder') }}"
                               class="h-12 w-full rounded-full border border-hairline bg-canvas ps-11 pe-4 text-body text-ink
                                      placeholder:text-muted transition hover:border-hairline-strong
                                      focus-visible:border-accent focus-visible:outline-none">
                    </label>

                    {{-- Live counter, updated on every filter pass. A bare number sidesteps
                         the six Arabic plural forms. --}}
                    <p class="text-caption text-stone">
                        {{ __('public.news_results_label') }}
                        <span data-news-count class="num font-semibold text-ink">{{ count($articles) }}</span>
                    </p>
                </div>

                {{--
                    A segmented control rather than loose chips: one surface holds them
                    so they read as a single group. Scrolls horizontally on mobile
                    (-mx-6 extends it to the container edge, overflow-x-auto clips it,
                    so the page itself never scrolls sideways).
                --}}
                <div class="-mx-6 mt-5 overflow-x-auto px-6 lg:mx-0 lg:px-0">
                    <div role="tablist" class="inline-flex w-max items-center gap-1 rounded-full bg-surface p-1">
                        <button type="button" role="tab" data-news-cat="" aria-selected="true"
                                class="news-chip is-active inline-flex min-h-11 shrink-0 items-center rounded-full
                                       px-4 text-ui font-medium text-steel transition hover:text-ink">
                            {{ __('public.news_filter_all') }}
                        </button>

                        @foreach ($categories as $key => $label)
                            <button type="button" role="tab" data-news-cat="{{ $key }}" aria-selected="false"
                                    class="news-chip inline-flex min-h-11 shrink-0 items-center rounded-full
                                           px-4 text-ui font-medium text-steel transition hover:text-ink">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-[1280px] px-6">

            {{-- ═══ Lead story - horizontal split, the biggest visual lift on this screen ═══ --}}
            @if ($featured)
                <section data-news-block class="pt-12 lg:pt-16">
                    <div class="flex items-center gap-3">
                        <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
                        <h2 class="text-micro font-semibold text-stone">{{ __('public.news_featured_label') }}</h2>
                        <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
                    </div>

                    <div class="mt-6"
                         data-news-item
                         data-search="{{ $featured['title'] }} {{ $featured['excerpt'] }}"
                         data-cat="{{ $featured['cat'] }}">
                        <x-domain.news-card
                            layout="split"
                            eager
                            :title="$featured['title']"
                            :excerpt="$featured['excerpt']"
                            :date="$featured['date']"
                            :date-label="$featured['label']"
                            :reading-minutes="$featured['read']"
                            :category="$categories[$featured['cat']]"
                            :href="route('news.show', $featured['slug'])" />
                    </div>
                </section>
            @endif

            {{-- ═══ Latest news ═══ --}}
            <section data-news-block class="pt-12 lg:pt-16">
                <div class="flex items-center gap-3">
                    <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
                    <h2 class="text-micro font-semibold text-stone">{{ __('public.news_latest_label') }}</h2>
                    <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
                </div>

                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($rest as $article)
                        <div data-news-item
                             data-search="{{ $article['title'] }} {{ $article['excerpt'] }}"
                             data-cat="{{ $article['cat'] }}">
                            <x-domain.news-card
                                :title="$article['title']"
                                :excerpt="$article['excerpt']"
                                :date="$article['date']"
                                :date-label="$article['label']"
                                :reading-minutes="$article['read']"
                                :category="$categories[$article['cat']]"
                                :href="route('news.show', $article['slug'])" />
                        </div>
                    @endforeach
                </div>
            </section>

            <div data-news-empty hidden class="tile mt-12">
                <x-ui.empty-state icon="search"
                                  :title="__('public.news_no_results_title')"
                                  :body="__('public.news_no_results_body')" />
            </div>

            <div class="pb-16 pt-12 lg:pb-24">
                <x-ui.pagination :current-page="1" :last-page="3" />
            </div>
        </div>
    </div>

</x-layouts.public>
