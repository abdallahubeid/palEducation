@php
    /*
     * 🔴 Everything on this page comes from the controller now. The header
     * copy is admin-editable settings; the articles are rows.
     *
     * ⚠️ The category filter that used to sit here is GONE. It filtered a
     * hard-coded `cat` key on each article, and `news` has no category
     * column — chips over a column that does not exist would filter
     * everything into nothing. Search still works, on title and summary.
     */
    $lead = $articles->first();
    $rest = $articles->slice(1);
@endphp

<x-layouts.public :title="$header['title']">

    <div data-news-filter>

        {{-- ═══ Header and search — a canvas band separating this surface ═══ --}}
        <section class="border-b border-hairline bg-canvas py-12 lg:py-16">
            <div class="mx-auto max-w-[1280px] px-6">
                <div class="max-w-3xl">
                    <x-ui.rule-label>{{ $header['badge_text'] }}</x-ui.rule-label>
                    <h1 class="mt-5 text-h1 font-bold text-ink">{{ $header['title'] }}</h1>

                    @if (filled($header['sub_title']))
                        <p class="measure mt-4 text-lead text-steel">{{ $header['sub_title'] }}</p>
                    @endif
                </div>

                @if ($articles->isNotEmpty())
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

                        {{-- Live counter. A bare number sidesteps the six
                             Arabic plural forms. --}}
                        <p class="text-caption text-stone">
                            {{ __('public.news_results_label') }}
                            <span data-news-count class="num font-semibold text-ink">{{ $articles->count() }}</span>
                        </p>
                    </div>
                @endif
            </div>
        </section>

        <div class="mx-auto max-w-[1280px] px-6">

            @if ($articles->isEmpty())
                {{-- A real empty state, not a page of headings over nothing. --}}
                <div class="tile my-12">
                    <x-ui.empty-state icon="clipboard"
                                      :title="__('public.news_empty_title')"
                                      :body="__('public.news_empty_body')" />
                </div>
            @else
                {{-- ═══ Lead story — the newest, given the biggest lift ═══ --}}
                <section data-news-block class="pt-12 lg:pt-16">
                    <div class="flex items-center gap-3">
                        <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
                        <h2 class="text-micro font-semibold text-stone">{{ __('public.news_featured_label') }}</h2>
                        <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
                    </div>

                    <div class="mt-6"
                         data-news-item
                         data-search="{{ $lead->title }} {{ $lead->summary }}">
                        <x-domain.news-card
                            layout="split"
                            eager
                            :image="$lead->cover_url"
                            :title="$lead->title"
                            :excerpt="$lead->summary"
                            :date="$lead->published_at->toDateString()"
                            :date-label="$lead->published_at->translatedFormat('j F Y')"
                            :reading-minutes="$lead->readingMinutes()"
                            {{-- The by-line takes the category slot: there is
                                 no category column, and an empty kicker would
                                 leave the headline floating. --}}
                            :category="$lead->authorName()"
                            :href="route('news.show', $lead)" />
                    </div>
                </section>

                {{-- ═══ The rest ═══ --}}
                @if ($rest->isNotEmpty())
                    <section data-news-block class="pt-12 lg:pt-16">
                        <div class="flex items-center gap-3">
                            <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
                            <h2 class="text-micro font-semibold text-stone">{{ __('public.news_latest_label') }}</h2>
                            <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
                        </div>

                        <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($rest as $article)
                                <div data-news-item
                                     data-search="{{ $article->title }} {{ $article->summary }}">
                                    <x-domain.news-card
                                        :image="$article->cover_url"
                                        :title="$article->title"
                                        :excerpt="$article->summary"
                                        :date="$article->published_at->toDateString()"
                                        :date-label="$article->published_at->translatedFormat('j F Y')"
                                        :reading-minutes="$article->readingMinutes()"
                                        :category="$article->authorName()"
                                        :href="route('news.show', $article)" />
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <div data-news-empty hidden class="tile mt-12">
                    <x-ui.empty-state icon="search"
                                      :title="__('public.news_no_results_title')"
                                      :body="__('public.news_no_results_body')" />
                </div>

                {{-- ⚠️ The paginator's own links, not the static x-ui.pagination
                     that used to sit here showing "1 of 3" regardless. --}}
                @if ($articles->hasPages())
                    <div class="pb-4 pt-12">{{ $articles->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    <x-site.cta-final variant="deep" />

</x-layouts.public>
