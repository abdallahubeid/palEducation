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
         'date' => '2026-08-19', 'label' => '19 آب 2026', 'featured' => true],

        ['slug' => 'math-curriculum-update', 'cat' => 'curriculum',
         'title' => 'تعديلات على منهاج الرياضيات للفرع العلمي',
         'excerpt' => 'حُذفت وحدة المصفوفات من مقرر الفصل الثاني، وأُضيفت تطبيقات على التكامل المحدّد.',
         'date' => '2026-08-16', 'label' => '16 آب 2026'],

        ['slug' => 'library-launch', 'cat' => 'platform',
         'title' => 'إطلاق المكتبة المنظّمة لجميع الفروع',
         'excerpt' => 'أوراق العمل والملخّصات والامتحانات السابقة صارت مجمّعة بفرعك ومادتك، بدل تشتّتها بين المجموعات.',
         'date' => '2026-08-12', 'label' => '12 آب 2026'],

        ['slug' => 'study-plan-tips', 'cat' => 'tips',
         'title' => 'كيف تبني خطة مراجعة لا تنهار بعد أسبوع',
         'excerpt' => 'الخطة التي تنجح ليست الأطول بل الأقابل للاستمرار — أربع قواعد عملية من تجربة طلبة سابقين.',
         'date' => '2026-08-09', 'label' => '9 آب 2026'],

        ['slug' => 'physics-lab-videos', 'cat' => 'platform',
         'title' => 'إضافة فيديوهات المختبر لمادة الفيزياء',
         'excerpt' => 'تجارب المنهاج مصوّرة خطوة بخطوة، لطلبة المدارس التي لا تتوفّر فيها مختبرات مجهّزة.',
         'date' => '2026-08-05', 'label' => '5 آب 2026'],

        ['slug' => 'english-exam-format', 'cat' => 'exams',
         'title' => 'تغيير في نمط أسئلة اللغة الإنجليزية',
         'excerpt' => 'سؤال المقال يُستبدل بسؤالَي فهم مقروء، مع بقاء التوزيع الكلي للعلامات كما هو.',
         'date' => '2026-08-01', 'label' => '1 آب 2026'],

        ['slug' => 'exam-anxiety', 'cat' => 'tips',
         'title' => 'قلق الامتحان: ما ينفع فعلاً وما يزيده سوءاً',
         'excerpt' => 'ثلاث ممارسات يوصي بها مرشدون تربويون، وممارستان شائعتان تضرّان أكثر مما تنفعان.',
         'date' => '2026-07-27', 'label' => '27 تموز 2026'],
    ];

    $featured = collect($articles)->firstWhere('featured', true);
    $rest = collect($articles)->reject(fn ($a) => ! empty($a['featured']))->values();
@endphp

<x-layouts.public :title="__('public.news_title')">

    {{-- رأس الصفحة + أدوات الفلترة --}}
    <section class="py-14 lg:py-20">
        <div class="mx-auto max-w-[1280px] px-6" data-news-filter>
            <div class="max-w-3xl">
                <x-ui.rule-label>{{ __('nav.news') }}</x-ui.rule-label>
                <h1 class="mt-5 text-h1 font-bold text-ink">{{ __('public.news_title') }}</h1>
                <p class="measure mt-4 text-lead text-steel">{{ __('public.news_subtitle') }}</p>
            </div>

            <div class="mt-8 flex flex-col gap-3 lg:flex-row lg:items-center">
                <label class="relative flex min-w-0 flex-1 items-center lg:max-w-sm">
                    <x-icon name="search" class="pointer-events-none absolute start-4 size-4 text-muted" />
                    <input type="search"
                           data-news-search
                           placeholder="{{ __('public.news_search_placeholder') }}"
                           class="h-12 w-full rounded-full border border-hairline bg-canvas ps-11 pe-4 text-body text-ink
                                  placeholder:text-muted transition hover:border-hairline-strong
                                  focus-visible:border-accent focus-visible:outline-none">
                </label>

                {{-- شرائح التصنيف — قابلة للتمرير أفقياً داخل حاويتها على الجوال --}}
                <div class="-mx-6 overflow-x-auto px-6 lg:mx-0 lg:overflow-visible lg:px-0">
                    <div role="tablist" class="flex w-max items-center gap-2 lg:w-auto">
                        <button type="button" role="tab" data-news-cat="" aria-selected="true"
                                class="news-chip is-active inline-flex min-h-11 shrink-0 items-center rounded-full border border-hairline
                                       bg-canvas px-4 text-ui font-medium text-steel transition hover:border-hairline-strong">
                            {{ __('public.news_filter_all') }}
                        </button>

                        @foreach ($categories as $key => $label)
                            <button type="button" role="tab" data-news-cat="{{ $key }}" aria-selected="false"
                                    class="news-chip inline-flex min-h-11 shrink-0 items-center rounded-full border border-hairline
                                           bg-canvas px-4 text-ui font-medium text-steel transition hover:border-hairline-strong">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- الخبر الأبرز --}}
            @if ($featured)
                <div data-news-item
                     data-search="{{ $featured['title'] }} {{ $featured['excerpt'] }}"
                     data-cat="{{ $featured['cat'] }}"
                     class="mt-10">
                    <p class="mb-3 text-micro font-semibold text-stone">{{ __('public.news_featured_label') }}</p>

                    <x-domain.news-card
                        featured
                        :title="$featured['title']"
                        :excerpt="$featured['excerpt']"
                        :date="$featured['date']"
                        :date-label="$featured['label']"
                        :category="$categories[$featured['cat']]"
                        :href="route('news.show', $featured['slug'])" />
                </div>
            @endif

            {{-- data-news-heading: يختفي مع اختفاء الشبكة التي تليه --}}
            <p data-news-heading class="mt-12 text-micro font-semibold text-stone">{{ __('public.news_latest_label') }}</p>

            <div class="mt-3 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($rest as $article)
                    <div data-news-item
                         data-search="{{ $article['title'] }} {{ $article['excerpt'] }}"
                         data-cat="{{ $article['cat'] }}">
                        <x-domain.news-card
                            :title="$article['title']"
                            :excerpt="$article['excerpt']"
                            :date="$article['date']"
                            :date-label="$article['label']"
                            :category="$categories[$article['cat']]"
                            :href="route('news.show', $article['slug'])" />
                    </div>
                @endforeach
            </div>

            <div data-news-empty hidden class="tile mt-8">
                <x-ui.empty-state icon="search"
                                  :title="__('public.news_no_results_title')"
                                  :body="__('public.news_no_results_body')" />
            </div>

            <div class="mt-12">
                <x-ui.pagination :current-page="1" :last-page="3" />
            </div>
        </div>
    </section>

</x-layouts.public>
