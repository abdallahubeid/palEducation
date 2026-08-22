@php
    $article = [
        'title' => 'إعلان مواعيد امتحانات التوجيهي للدورة الأولى 2026',
        'category' => __('public.news_cat_exams'),
        'date' => '2026-08-19',
        'dateLabel' => '19 آب 2026',
        'readingMinutes' => 4,
        'author' => 'فريق pal education',
        'lede' => 'أعلنت وزارة التربية والتعليم الجدول الرسمي لامتحانات الثانوية العامة للدورة الأولى، ويبدأ التطبيق مطلع حزيران ويستمر ثلاثة أسابيع لجميع الفروع.',
    ];

    $related = [
        ['slug' => 'english-exam-format', 'cat' => __('public.news_cat_exams'),
         'title' => 'تغيير في نمط أسئلة اللغة الإنجليزية',
         'excerpt' => 'سؤال المقال يُستبدل بسؤالَي فهم مقروء، مع بقاء التوزيع الكلي للعلامات كما هو.',
         'date' => '2026-08-01', 'label' => '1 آب 2026'],

        ['slug' => 'math-curriculum-update', 'cat' => __('public.news_cat_curriculum'),
         'title' => 'تعديلات على منهاج الرياضيات للفرع العلمي',
         'excerpt' => 'حُذفت وحدة المصفوفات من مقرر الفصل الثاني، وأُضيفت تطبيقات على التكامل المحدّد.',
         'date' => '2026-08-16', 'label' => '16 آب 2026'],

        ['slug' => 'study-plan-tips', 'cat' => __('public.news_cat_tips'),
         'title' => 'كيف تبني خطة مراجعة لا تنهار بعد أسبوع',
         'excerpt' => 'الخطة التي تنجح ليست الأطول بل الأقابل للاستمرار — أربع قواعد عملية.',
         'date' => '2026-08-09', 'label' => '9 آب 2026'],
    ];
@endphp

<x-layouts.public :title="$article['title']">

    <article>
        {{-- رأس المقال --}}
        <header class="bg-canvas py-10 lg:py-14">
            <div class="mx-auto max-w-[1280px] px-6">
                <x-ui.breadcrumb :items="[
                    ['label' => __('nav.home'), 'href' => route('home')],
                    ['label' => __('public.news_breadcrumb'), 'href' => route('news.index')],
                    ['label' => $article['title']],
                ]" />

                {{-- العنوان بعرض القراءة لا بعرض الصفحة — سطر طويل يُفقد العين مكانها --}}
                <div class="measure mt-6">
                    <x-ui.badge variant="accent">{{ $article['category'] }}</x-ui.badge>

                    <h1 class="mt-4 text-h1 font-bold text-ink">{{ $article['title'] }}</h1>

                    {{-- سطر النَسب: الكاتب والتاريخ ومدة القراءة — في صدر المقال كما توصي المراجع التحريرية --}}
                    <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-caption text-stone">
                        <span class="inline-flex items-center gap-2">
                            <x-ui.avatar :name="$article['author']" size="sm" />
                            <span class="font-medium text-slate">{{ $article['author'] }}</span>
                        </span>

                        <span class="size-1 rounded-full bg-hairline-strong" aria-hidden="true"></span>

                        <span>
                            {{ __('public.news_published_on') }}
                            <time datetime="{{ $article['date'] }}" dir="ltr" class="num">{{ $article['dateLabel'] }}</time>
                        </span>

                        <span class="size-1 rounded-full bg-hairline-strong" aria-hidden="true"></span>

                        <span class="num">{{ __('public.news_reading_time', ['count' => $article['readingMinutes']]) }}</span>
                    </div>
                </div>
            </div>
        </header>

        {{-- صورة الغلاف --}}
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="-mt-2 overflow-hidden rounded-xxl lg:mt-0">
                <x-ui.media-slot :alt="$article['title']" icon="compass" ratio="21/9" class="w-full" />
            </div>
        </div>

        {{-- متن المقال --}}
        <div class="py-12 lg:py-16">
            <div class="mx-auto max-w-[1280px] px-6">
                {{--
                    🔴 عمود قراءة واحد بعرض ≤68 حرفاً (.measure)، 16px،
                    ارتفاع سطر 1.75. هذه الشاشة الوحيدة في المشروع التي
                    تحمل نصاً عربياً طويلاً — معيار القراءة هنا يُطبَّق حرفياً.
                --}}
                <div class="measure text-body leading-[1.75] text-steel">

                    <p class="text-lead leading-[1.8] text-charcoal">{{ $article['lede'] }}</p>

                    <h2 class="mt-10 text-h3 font-semibold text-ink">الجدول الزمني</h2>
                    <p class="mt-4">
                        تبدأ الامتحانات يوم السبت الموافق <bdi dir="ltr">2026-06-06</bdi> وتستمر حتى
                        <bdi dir="ltr">2026-06-25</bdi>، بواقع جلسة واحدة يومياً تبدأ الساعة
                        <bdi dir="ltr">9:00</bdi> صباحاً. وتُخصَّص أيام الجمعة للراحة في كل الأسابيع الثلاثة.
                    </p>

                    <h2 class="mt-10 text-h3 font-semibold text-ink">ما الذي تغيّر عن العام الماضي</h2>
                    <p class="mt-4">
                        التغيير الأبرز هو تقليص عدد الجلسات اليومية من جلستين إلى جلسة واحدة، وهو ما يعني
                        فترة مراجعة أطول بين كل مادة وأخرى. أما توزيع العلامات فبقي كما هو: <bdi dir="ltr">30%</bdi>
                        للفصل الأول و<bdi dir="ltr">70%</bdi> للامتحان النهائي.
                    </p>

                    <ul class="mt-4 flex flex-col gap-2.5">
                        @foreach ([
                            'جلسة واحدة يومياً بدل جلستين',
                            'يوم راحة إضافي بين المواد العلمية الثقيلة',
                            'إتاحة الآلة الحاسبة العلمية في امتحانات الفيزياء والكيمياء',
                        ] as $point)
                            <li class="flex gap-3">
                                <x-icon name="check-circle" class="mt-1 size-4 shrink-0 text-accent-deep" />
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <h2 class="mt-10 text-h3 font-semibold text-ink">ماذا يعني هذا لخطة مراجعتك</h2>
                    <p class="mt-4">
                        الجلسة الواحدة يومياً تمنحك مساءً كاملاً للمراجعة قبل مادة الغد. عملياً هذا يعني أن
                        خطة المراجعة يجب أن تُبنى على وحدات يومية قصيرة لا على ماراثونات نهاية الأسبوع —
                        وهو ما تدعمه بنية المحاضرات على المنصة أصلاً: محاضرة واحدة يتبعها كويز فوري.
                    </p>

                    {{-- اقتباس — عنصر تحريري يكسر رتابة الفقرات --}}
                    <blockquote class="mt-8 border-s-4 border-accent bg-accent-soft/50 px-5 py-4">
                        <p class="text-lead leading-[1.8] text-charcoal">
                            «الطالب الذي يراجع ساعة كل يوم يتفوّق على من يراجع سبع ساعات يوم الجمعة — ليس لأنه أذكى،
                            بل لأن الذاكرة تعمل بالتكرار المتباعد.»
                        </p>
                        <footer class="mt-2 text-caption text-stone">— أ. سامر خليل، معلّم الرياضيات</footer>
                    </blockquote>

                    <p class="mt-8">
                        سيُنشر الجدول التفصيلي لكل فرع على الموقع الرسمي للوزارة، وسنضيفه إلى مكتبة المنصة
                        فور صدوره ليكون متاحاً لكل طالب ضمن فرعه.
                    </p>
                </div>

                {{-- تذييل المقال --}}
                <div class="measure mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-hairline pt-6">
                    <a href="{{ route('news.index') }}"
                       class="inline-flex min-h-11 items-center gap-2 text-ui font-semibold text-accent-deep transition hover:underline">
                        <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                        {{ __('public.news_back_to_index') }}
                    </a>

                    <x-ui.button size="sm"
                                 :href="Route::has('auth.register') ? route('auth.register') : '#'">
                        {{ __('public.branches_cta_action') }}
                    </x-ui.button>
                </div>
            </div>
        </div>

        {{-- أخبار ذات صلة --}}
        <section class="bg-canvas py-14 lg:py-20">
            <div class="mx-auto max-w-[1280px] px-6">
                <h2 class="text-h2 font-bold text-ink">{{ __('public.news_related_title') }}</h2>

                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <x-domain.news-card
                            :title="$item['title']"
                            :excerpt="$item['excerpt']"
                            :date="$item['date']"
                            :date-label="$item['label']"
                            :category="$item['cat']"
                            :href="route('news.show', $item['slug'])" />
                    @endforeach
                </div>
            </div>
        </section>
    </article>

</x-layouts.public>
