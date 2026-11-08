@php
    /*
     * 🔴 Everything here comes from the controller. This file used to carry a
     * hard-coded article — headline, body, key points, "most read" rail and
     * topic tags — and rendered it for EVERY slug.
     *
     * ⚠️ Three of those blocks are gone rather than faked: key points, topic
     * tags and a "most read" rail need columns the schema does not have
     * (`news` holds title · summary · content · image · author · dates). A
     * "most read" list with no view counter would be an ordinary recent list
     * wearing a label that lies.
     */
    $publishedLabel = $article->published_at->translatedFormat('j F Y');
@endphp

<x-layouts.public :title="$article->title">

    <article>

        {{-- ═══ Hero ═══ --}}
        <section class="border-b border-hairline bg-canvas py-10 lg:py-14">
            <div class="mx-auto max-w-[1280px] px-6">
                <x-ui.breadcrumb :items="[
                    ['label' => __('nav.home'), 'href' => route('home')],
                    ['label' => __('nav.news'), 'href' => route('news.index')],
                    ['label' => $article->title],
                ]" />

                <div class="mt-8 grid gap-8 lg:grid-cols-2 lg:items-center">

                    {{-- ترتيب الـDOM هو ترتيب الجوال، وإحداثيات الشبكة تعيد
                         التموضع على سطح المكتب — فتنعكس مع الاتجاه مجاناً. --}}
                    <div class="lg:col-start-2">
                        @if ($article->cover_url)
                            <x-ui.media-slot :src="$article->cover_url" :alt="$article->title"
                                             ratio="16/9" class="rounded-xl" loading="eager" />
                        @endif
                    </div>

                    <div class="lg:col-start-1 lg:row-start-1">
                        <h1 class="text-h1 font-bold leading-[1.3] text-ink">{{ $article->title }}</h1>

                        @if (filled($article->summary))
                            <p class="measure mt-5 text-lead leading-[1.8] text-steel">{{ $article->summary }}</p>
                        @endif

                        {{-- ═══ By-line ═══ --}}
                        <div class="mt-7 flex flex-wrap items-center gap-4">
                            <div class="flex items-center gap-3">
                                <x-ui.avatar :name="$article->authorName()" size="md" />
                                <div class="min-w-0">
                                    <p class="text-caption font-semibold text-ink">{{ $article->authorName() }}</p>
                                    {{-- Dates stay LTR even on an RTL page, and
                                         the machine-readable value is the
                                         datetime attribute, not the label. --}}
                                    <p class="mt-0.5 text-micro text-stone">
                                        <time datetime="{{ $article->published_at->toDateString() }}">{{ $publishedLabel }}</time>
                                        ·
                                        {{ __('public.news_reading_time', ['count' => $article->readingMinutes()]) }}
                                    </p>
                                </div>
                            </div>

                            <x-ui.share-row :title="$article->title" class="ms-auto" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══ Body ═══ --}}
        <section class="py-14 lg:py-20">
            <div class="mx-auto max-w-[1280px] px-6">
                {{--
                    عرض القراءة ثابت والنصّ يبدأ من بداية العمود.

                    🔴 nl2br(e()) — يُهرَّب أولاً ثم تُضاف الأسطر. النصّ يكتبه
                    أدمن، لكن الأدمن الذي ينسخ من صفحة ويب يجلب معه وسوماً،
                    وهذه صفحة عامة.
                --}}
                <div class="measure text-body leading-[1.9] text-steel
                            [&>p]:mt-5 [&>p:first-child]:mt-0">
                    <p>{!! nl2br(e($article->content)) !!}</p>
                </div>
            </div>
        </section>

        {{-- ═══ Related ═══ --}}
        @if ($related->isNotEmpty())
            <section class="border-t border-hairline bg-canvas py-14 lg:py-20">
                <div class="mx-auto max-w-[1280px] px-6">
                    <x-ui.section-head
                        :eyebrow="__('nav.news')"
                        :title="__('public.news_related_title')" />

                    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($related as $item)
                            <x-domain.news-card
                                :image="$item->cover_url"
                                :title="$item->title"
                                :excerpt="$item->summary"
                                :date="$item->published_at->toDateString()"
                                :date-label="$item->published_at->translatedFormat('j F Y')"
                                :reading-minutes="$item->readingMinutes()"
                                :category="$item->authorName()"
                                :href="route('news.show', $item)" />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <x-site.cta-final variant="deep"
                          :secondary-text="__('nav.news')"
                          :secondary-link="route('news.index')" />
    </article>

</x-layouts.public>
