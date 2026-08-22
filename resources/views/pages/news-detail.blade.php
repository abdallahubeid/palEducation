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

    $keyPoints = [
        'الامتحانات تبدأ 6 حزيران وتنتهي 25 حزيران — ثلاثة أسابيع لكل الفروع.',
        'جلسة واحدة يومياً بدل جلستين، وتبدأ التاسعة صباحاً.',
        'توزيع العلامات لم يتغيّر: 30% للفصل الأول و70% للامتحان النهائي.',
        'الآلة الحاسبة العلمية مسموحة في الفيزياء والكيمياء.',
    ];

    // Sidebar: deliberately DIFFERENT items from $related below, so the rail and
    // the bottom grid do not show the same three articles twice on one page.
    $mostRead = [
        ['slug' => 'exam-anxiety', 'cat' => __('public.news_cat_tips'),
         'title' => 'قلق الامتحان: ما ينفع فعلاً وما يزيده سوءاً',
         'date' => '2026-07-27', 'label' => '27 تموز 2026'],

        ['slug' => 'library-launch', 'cat' => __('public.news_cat_platform'),
         'title' => 'إطلاق المكتبة المنظّمة لجميع الفروع',
         'date' => '2026-08-12', 'label' => '12 آب 2026'],

        ['slug' => 'physics-lab-videos', 'cat' => __('public.news_cat_platform'),
         'title' => 'إضافة فيديوهات المختبر لمادة الفيزياء',
         'date' => '2026-08-05', 'label' => '5 آب 2026'],
    ];

    $topics = ['التوجيهي', 'امتحانات 2026', 'وزارة التربية', 'جدول الامتحانات', 'الفرع العلمي'];

    $related = [
        ['slug' => 'english-exam-format', 'cat' => __('public.news_cat_exams'),
         'title' => 'تغيير في نمط أسئلة اللغة الإنجليزية',
         'excerpt' => 'سؤال المقال يُستبدل بسؤالَي فهم مقروء، مع بقاء التوزيع الكلي للعلامات كما هو.',
         'date' => '2026-08-01', 'label' => '1 آب 2026', 'read' => 3],

        ['slug' => 'math-curriculum-update', 'cat' => __('public.news_cat_curriculum'),
         'title' => 'تعديلات على منهاج الرياضيات للفرع العلمي',
         'excerpt' => 'حُذفت وحدة المصفوفات من مقرر الفصل الثاني، وأُضيفت تطبيقات على التكامل المحدّد.',
         'date' => '2026-08-16', 'label' => '16 آب 2026', 'read' => 3],

        ['slug' => 'study-plan-tips', 'cat' => __('public.news_cat_tips'),
         'title' => 'كيف تبني خطة مراجعة لا تنهار بعد أسبوع',
         'excerpt' => 'الخطة التي تنجح ليست الأطول بل الأقابل للاستمرار — أربع قواعد عملية.',
         'date' => '2026-08-09', 'label' => '9 آب 2026', 'read' => 6],
    ];
@endphp

<x-layouts.public :title="$article['title']">

    {{--
        Restructured 2026-08-22 into an editorial article surface.

        Measured live on a real BBC Arabic article and applied here:
        - Reading column 645px ~= 68 characters, which matches our own `.measure`
          exactly. Confirmation, not a change.
        - Their subheads run 32px against a 16px body (2.0x). Ours were text-h3
          (24px = 1.5x), so they were raised to text-h2 (28px = 1.75x) - a jump
          large enough to actually read as a section break.
        - Cover is 16:9. Ours was 21:9 across 1280px: a cinema bar that swallows
          a news photo instead of presenting it.

        Added as modern editorial patterns: reading-progress bar, key-points box,
        sticky share rail on desktop, and a ruled attribution bar.
    --}}

    {{-- Reading progress. Driven by width, not transform: it grows from the inline-start
         edge automatically in both directions, so it needs no rtl: variant. --}}
    <div class="pointer-events-none fixed inset-x-0 top-0 z-50 h-1" aria-hidden="true">
        <div data-read-progress class="h-full w-0 bg-accent"></div>
    </div>

    <article>

        {{--
            ═══ Split hero ═══

            A self-contained band with its own internal grid: text on the inline-start
            side, cover on the inline-end side. In Arabic (RTL) that puts the headline
            right and the image left; in LTR it mirrors automatically, because grid
            column lines follow the inline direction. No rtl: variant anywhere.

            Why this does NOT reintroduce the multi-axis problem fixed earlier today:
            the earlier defect was a cover that BROKE OUT of the reading column and
            straddled a bordered band edge. Here the hero is a closed band (bg-canvas
            + border-b) with its own grid, and the reading column starts cleanly below
            it. A band may have its own internal axis; a floating figure may not.

            DOM ORDER IS THE MOBILE ORDER, deliberately:
              1 kicker + headline   2 cover   3 excerpt + attribution
            That is exactly the requested mobile stack, so no `order` utility is used
            and assistive tech reads the same sequence sighted users see. Desktop then
            re-places these three with explicit grid coordinates.

            Row sizing: grid-rows-[auto_1fr] with both text blocks self-start. If the
            cover ends up taller than the text, the surplus lands in the 1fr row BELOW
            the metadata instead of being split between the two text blocks — which is
            what would otherwise prise the headline and excerpt apart. This is why the
            grid uses per-item alignment rather than a blanket items-center.
        --}}
        <header class="border-b border-hairline bg-canvas pb-10 pt-8 lg:pb-14 lg:pt-10">
            <div class="mx-auto max-w-[1280px] px-6">
                <x-ui.breadcrumb :items="[
                    ['label' => __('nav.home'), 'href' => route('home')],
                    ['label' => __('public.news_breadcrumb'), 'href' => route('news.index')],
                    ['label' => $article['title']],
                ]" />

                <div class="mt-8 lg:grid lg:grid-cols-12 lg:grid-rows-[auto_1fr] lg:gap-x-8 lg:gap-y-0">

                    {{-- 1 · Kicker + headline --}}
                    <div class="lg:col-span-7 lg:col-start-1 lg:row-start-1 lg:self-start">
                        <span class="inline-block border-s-2 border-accent ps-2.5 text-micro font-semibold text-accent-deep">
                            {{ $article['category'] }}
                        </span>

                        <h1 class="mt-4 text-h2 font-bold text-ink sm:text-h1">{{ $article['title'] }}</h1>
                    </div>

                    {{--
                        2 · Cover. ratio="16/9" emits aspect-ratio:16/9 — the same computed
                        value as Tailwind's aspect-video. media-slot puts object-cover on the
                        <img>, so a real photo of any source ratio fills the frame without
                        letterboxing or distortion.

                        rounded-xxl (24px) is this project's large-surface radius. Stock
                        rounded-2xl is 16px and is not part of the declared token scale
                        (xs · sm · md · lg · xl · xxl), so it is not used here.
                    --}}
                    <figure class="mt-6 lg:col-span-5 lg:col-start-8 lg:row-span-2 lg:row-start-1 lg:mt-0 lg:self-center">
                        <div class="overflow-hidden rounded-xxl ring-1 ring-hairline">
                            <x-ui.media-slot :alt="$article['title']" icon="compass" ratio="16/9" loading="eager" class="w-full" />
                        </div>

                        <figcaption class="mt-3 text-caption text-stone">
                            {{ __('public.news_cover_caption') }}
                        </figcaption>
                    </figure>

                    {{-- 3 · Excerpt + attribution --}}
                    <div class="mt-6 lg:col-span-7 lg:col-start-1 lg:row-start-2 lg:mt-7 lg:self-start">
                        <p class="text-lead leading-[1.8] text-slate">{{ $article['lede'] }}</p>

                        {{-- Attribution bar: author, date, reading time and share, set off by a rule --}}
                        <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-hairline pt-5">
                            <div class="flex items-center gap-3">
                                <x-ui.avatar :name="$article['author']" size="md" />

                                <div class="min-w-0">
                                    <p class="text-ui font-semibold text-ink">{{ $article['author'] }}</p>

                                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-caption text-stone">
                                        <time datetime="{{ $article['date'] }}" dir="ltr" class="num">{{ $article['dateLabel'] }}</time>
                                        <span class="size-1 rounded-full bg-hairline-strong" aria-hidden="true"></span>
                                        <span class="num">{{ __('public.news_reading_time', ['count' => $article['readingMinutes']]) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{--
                                Hands off at lg, the exact breakpoint where the editorial
                                sidebar appears alongside and carries its own share row.
                                Below lg the sidebar stacks far down the page, so the hero
                                copy is the one that matters. The handoff breakpoint must
                                always match the partner's - mismatching them previously left
                                a band of widths with no share control at all.
                            --}}
                            <x-ui.share-row :title="$article['title']" class="lg:hidden" />
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- ═══ Article body ═══ --}}
        <div class="py-12 lg:py-16">
            <div class="mx-auto max-w-[1280px] px-6">
                {{--
                    ═══ Two-column editorial body ═══

                    Replaces the previous symmetric (1fr | 68ch | 1fr) centred column,
                    which left ~340px of dead margin on each side at 1280px and made the
                    article read as isolated on the page.

                    Measured on Al Jazeera's article page at 1280px: 12-column grid in a
                    1200px container, main stream 834px (71%) on the inline start, sticky
                    sidebar 278-370px (24%) on the inline end. This mirrors that.

                    Column placement is by grid coordinate only, so direction is handled
                    for free: col-start-1 is the RIGHT side in RTL and the LEFT in LTR.
                    No rtl: variant. DOM order is main-then-sidebar, which is also the
                    required mobile stacking order, so no `order` utility either.
                --}}
                <div class="lg:grid lg:grid-cols-12 lg:gap-10 xl:gap-12">

                    {{-- ── Main stream (col-span-8, inline start) ── --}}
                    <div data-read-article class="lg:col-span-8 lg:col-start-1">

                        {{--
                            The key-points box spans the FULL column width while the prose
                            below it is capped at the reading measure. That gives the column
                            a full-width anchor at its head, so the capped prose reads as a
                            deliberate text block rather than as a narrow strip floating in
                            an over-wide column.
                        --}}

                        <x-domain.key-points :points="$keyPoints" />

                        {{--
                            VERTICAL RHYTHM IS DECLARED ONCE, ON THIS CONTAINER.

                            Child-combinator variants ([&>h2]:mt-14 etc.) set the spacing for
                            every element type instead of repeating mt-* on each tag. Two
                            reasons: the rhythm stays internally consistent because there is
                            one place to change it, and article content added later inherits
                            it automatically rather than being hand-spaced tag by tag.

                            The scale is deliberately generous, and asymmetric by design - a
                            heading binds to the text BELOW it, so the space above an h2 (56px)
                            is much larger than the space below it (20px). Equal spacing on
                            both sides would leave headings visually floating between sections
                            instead of introducing one.

                            [&>*:first-child]:mt-0 stops the first element inheriting a top
                            margin it does not need.

                            Styling stays explicit on each tag; only SPACING is centralised.
                        --}}
                        {{--
                            RED LINE: prose stays at <=68 characters (.measure), 16px,
                            line-height 1.75, even though the column around it is wider.

                            The measure is the invariant across every portal measured -
                            BBC Arabic 68ch, Al Jazeera 64ch - what differs is the font
                            size used to fill a wide column (Al Jazeera runs 22px to reach
                            64ch in an 834px column). This directive specifies 16px, so the
                            column keeps trailing slack on the sidebar side rather than
                            stretching lines past the legible limit.

                            `.measure` WITHOUT mx-auto: the block sits flush against the
                            column's inline start, so nothing is centre-floated.
                        --}}
                        <div class="measure mt-10 text-body leading-[1.75] text-steel
                                    [&>*:first-child]:mt-0 [&>figure]:mt-12 [&>h2]:mt-14 [&>h3]:mt-10 [&>p]:mt-5 [&>ul]:mt-5">

                            {{--
                                Opening paragraph set one step up from body copy (18px lead,
                                charcoal rather than steel). Standard editorial standfirst: it
                                gives the reader a run-up into the piece and visually separates
                                the start of the prose from the summary box above it.
                            --}}
                            <p class="text-lead leading-[1.8] text-charcoal">
                                القرار الذي انتظره آلاف الطلبة صدر أخيراً، ومعه تفاصيل تغيّر طريقة الاستعداد
                                للأسابيع الثلاثة الأخيرة. فيما يلي ما نعرفه حتى الآن، وما يعنيه عملياً لجدولك اليومي.
                            </p>

                            <h2 class="text-h3 font-semibold text-ink sm:text-h2">الجدول الزمني</h2>
                            <p>
                                تبدأ الامتحانات يوم السبت الموافق <bdi dir="ltr">2026-06-06</bdi> وتستمر حتى
                                <bdi dir="ltr">2026-06-25</bdi>، بواقع جلسة واحدة يومياً تبدأ الساعة
                                <bdi dir="ltr">9:00</bdi> صباحاً. وتُخصَّص أيام الجمعة للراحة في كل الأسابيع الثلاثة.
                            </p>

                            <h2 class="text-h3 font-semibold text-ink sm:text-h2">ما الذي تغيّر عن العام الماضي</h2>
                            <p>
                                التغيير الأبرز هو تقليص عدد الجلسات اليومية من جلستين إلى جلسة واحدة، وهو ما يعني
                                فترة مراجعة أطول بين كل مادة وأخرى. أما توزيع العلامات فبقي كما هو: <bdi dir="ltr">30%</bdi>
                                للفصل الأول و<bdi dir="ltr">70%</bdi> للامتحان النهائي.
                            </p>

                            <h3 class="text-h4 font-semibold text-ink">أبرز التعديلات التشغيلية</h3>
                            <ul class="flex flex-col gap-3">
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

                            {{--
                                Pull quote. <figure>/<figcaption> rather than a bare blockquote,
                                so the attribution is programmatically tied to the quotation
                                instead of being a loose sibling paragraph.

                                The oversized mark is the typographic guillemet, not an SVG:
                                this icon set is stroke-only with fill="none" and a drawn quote
                                glyph would break that language, while « is the correct opening
                                mark for Arabic and sits naturally at the inline start.
                                aria-hidden, because the quotation is already announced by the
                                blockquote role - a screen reader should not read punctuation.
                            --}}
                            <figure class="relative overflow-hidden rounded-xl border-s-4 border-accent bg-canvas p-6 shadow-subtle sm:p-8">
                                {{--
                                    Positioned fully INSIDE the card. A negative top offset
                                    gets sliced by the overflow-hidden that the rounded corners
                                    and the accent border require, and a half-cut glyph reads
                                    as a rendering bug rather than a flourish. At accent/12 it
                                    sits behind the quotation as a watermark - the blockquote
                                    is `relative`, so it paints above and stays fully legible.
                                --}}
                                <span class="pointer-events-none absolute top-1 start-4 select-none text-[5rem] leading-none text-accent/12"
                                      aria-hidden="true">«</span>

                                <blockquote class="relative">
                                    <p class="text-h4 font-medium leading-[1.75] text-charcoal">
                                        الطالب الذي يراجع ساعة كل يوم يتفوّق على من يراجع سبع ساعات يوم الجمعة — ليس لأنه أذكى،
                                        بل لأن الذاكرة تعمل بالتكرار المتباعد.
                                    </p>
                                </blockquote>

                                <figcaption class="relative mt-6 flex items-center gap-3 border-t border-hairline pt-5">
                                    <x-ui.avatar name="أ. سامر خليل" size="sm" />

                                    <span class="min-w-0">
                                        <span class="block text-ui font-semibold text-ink">أ. سامر خليل</span>
                                        <span class="block text-caption text-stone">معلّم الرياضيات</span>
                                    </span>
                                </figcaption>
                            </figure>

                            <h2 class="text-h3 font-semibold text-ink sm:text-h2">ماذا يعني هذا لخطة مراجعتك</h2>
                            <p>
                                الجلسة الواحدة يومياً تمنحك مساءً كاملاً للمراجعة قبل مادة الغد. عملياً هذا يعني أن
                                خطة المراجعة يجب أن تُبنى على وحدات يومية قصيرة لا على ماراثونات نهاية الأسبوع —
                                وهو ما تدعمه بنية المحاضرات على المنصة أصلاً: محاضرة واحدة يتبعها كويز فوري.
                            </p>

                            <p>
                                سيُنشر الجدول التفصيلي لكل فرع على الموقع الرسمي للوزارة، وسنضيفه إلى مكتبة المنصة
                                فور صدوره ليكون متاحاً لكل طالب ضمن فرعه.
                            </p>
                        </div>

                        {{-- Article footer --}}
                        <div class="mt-10 border-t border-hairline pt-6">
                            <x-ui.share-row :title="$article['title']" :label="__('public.news_share')" />

                            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                                <a href="{{ route('news.index') }}"
                                   class="inline-flex min-h-11 items-center gap-2 text-ui font-semibold text-accent-deep transition hover:underline">
                                    <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                                    {{ __('public.news_back_to_index') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    {{--
                        ── Editorial sidebar (col-span-4, inline end) ──

                        The <aside> grid item stretches to the row height by default, which
                        is exactly what the inner sticky div needs as travel room. Putting
                        `sticky` on the grid item itself would have nothing to travel within.

                        Stacks below the main stream under lg, in DOM order, with no
                        `order` utility - main content first, as required.
                    --}}
                    <aside class="mt-12 lg:col-span-4 lg:col-start-9 lg:mt-0">
                        <div class="flex flex-col gap-8 lg:sticky lg:top-24">

                            {{-- 1 · Author + share --}}
                            <x-ui.sidebar-widget :title="__('public.news_about_author')" icon="user">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :name="$article['author']" size="lg" />

                                    <div class="min-w-0">
                                        <p class="text-ui font-semibold text-ink">{{ $article['author'] }}</p>
                                        <p class="text-caption text-stone">{{ __('public.news_author_role') }}</p>
                                    </div>
                                </div>

                                <p class="mt-4 text-body leading-[1.75] text-steel">
                                    {{ __('public.news_author_bio') }}
                                </p>

                                <div class="mt-4 flex items-center gap-2 border-t border-hairline pt-4 text-caption text-stone">
                                    <x-icon name="clock" class="size-4 shrink-0" />
                                    {{ __('public.news_published_on') }}
                                    <time datetime="{{ $article['date'] }}" dir="ltr" class="num">{{ $article['dateLabel'] }}</time>
                                </div>

                                <x-ui.share-row :title="$article['title']" class="mt-4" />
                            </x-ui.sidebar-widget>

                            {{--
                                2 · Most read. Compact news-card layout - thumbnail plus
                                headline, no excerpt. Deliberately different articles from
                                the related grid at the foot of the page, so the same three
                                items are not shown twice.
                            --}}
                            <x-ui.sidebar-widget :title="__('public.news_most_read')"
                                                 icon="trending-up"
                                                 :href="route('news.index')"
                                                 :link="__('public.news_view_all')">
                                <ol class="flex flex-col gap-5">
                                    @foreach ($mostRead as $item)
                                        <li>
                                            <x-domain.news-card
                                                layout="compact"
                                                :title="$item['title']"
                                                :date="$item['date']"
                                                :date-label="$item['label']"
                                                :category="$item['cat']"
                                                :href="route('news.show', $item['slug'])" />
                                        </li>
                                    @endforeach
                                </ol>
                            </x-ui.sidebar-widget>

                            {{--
                                3 · Topic chips. min-h-11 keeps every chip a valid touch
                                target; they are real navigation, not decoration.
                            --}}
                            <x-ui.sidebar-widget :title="__('public.news_topics')" icon="folder">
                                <ul class="flex flex-wrap gap-2">
                                    @foreach ($topics as $topic)
                                        <li>
                                            <a href="{{ route('news.index') }}"
                                               class="inline-flex min-h-11 items-center rounded-full bg-surface px-4 text-ui
                                                      font-medium text-steel transition hover:bg-accent-soft hover:text-accent-deep">
                                                {{ $topic }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </x-ui.sidebar-widget>
                        </div>
                    </aside>
                </div>
            </div>
        </div>

        {{-- ═══ Related news ═══ --}}
        <section class="border-t border-hairline bg-canvas py-14 lg:py-20">
            <div class="mx-auto max-w-[1280px] px-6">
                {{--
                    Header row: section head on the inline start, "all news" on the inline
                    end. flex justify-between mirrors with direction on its own. The link
                    drops below the heading on mobile (flex-col) rather than being squeezed
                    onto the same line.
                --}}
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <x-ui.section-head
                        :eyebrow="__('nav.news')"
                        :title="__('public.news_related_title')"
                        :lede="__('public.news_related_subtitle')" />

                    <a href="{{ route('news.index') }}"
                       class="inline-flex min-h-11 shrink-0 items-center gap-2 text-ui font-semibold text-accent-deep
                              transition hover:underline">
                        {{ __('public.news_back_to_index') }}
                        <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                    </a>
                </div>

                {{--
                    Three related items in a 3-up grid: one row, no orphan card. The card
                    itself equalises height (flex h-full), so the row stays even regardless
                    of headline length.
                --}}
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        <x-domain.news-card
                            :title="$item['title']"
                            :excerpt="$item['excerpt']"
                            :date="$item['date']"
                            :date-label="$item['label']"
                            :reading-minutes="$item['read']"
                            :category="$item['cat']"
                            :href="route('news.show', $item['slug'])" />
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ═══ Call to action - the only primary button on this screen ═══ --}}
        <section class="py-14 lg:py-20">
            <div class="mx-auto max-w-[1280px] px-6">
                <div class="tile flex flex-col items-start gap-6 p-8 sm:p-10 lg:flex-row lg:items-center lg:justify-between">
                    <div class="measure">
                        <h2 class="text-h3 font-bold text-ink">{{ __('public.news_cta_title') }}</h2>
                        <p class="mt-3 text-body leading-[1.75] text-steel">{{ __('public.news_cta_body') }}</p>
                    </div>

                    <x-ui.button size="lg"
                                 :href="Route::has('auth.register') ? route('auth.register') : '#'"
                                 class="w-full shrink-0 sm:w-auto">
                        {{ __('public.branches_cta_action') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </article>

</x-layouts.public>
