@props([
    'title'    => '',
    'excerpt'  => null,
    'date'     => '',          // ISO value for the datetime attribute
    'dateLabel' => '',         // displayed label
    'category' => null,
    'image'    => null,
    'href'     => '#',
    'layout'   => 'stacked',   // stacked | split | compact
    'readingMinutes' => null,
    'eager'    => false,       // above-the-fold image
])

{{--
    News card - three layouts.

    Decisions taken from a measured survey of BBC Arabic, Al Arabiya and
    Donia Al-Watan (2026-08-22):

    1. One 16:9 ratio across all three layouts. All three portals use it;
       mixing ratios (we had 16/9 for the lead and 16/10 for the grid) makes
       a grid read as visually unstable.
    2. Category as a kicker above the headline, not a badge floating on the
       image. All three separate category from artwork - an overlaid pill is
       a blog trope, not an editorial one. Built with a `border-s-2` rule so
       it mirrors with direction automatically.
    3. Excerpt clamped to two lines and the headline to three - card heights
       stay disciplined without forcing a fixed height.
    4. No "read more" button. The headline is the link, and the arrow in the
       metadata row is signal enough. None of the three puts a CTA on a card.

    Date sits in <time dir="ltr"> - numerals never mirror, in any language.
--}}

@php
    $loading = $eager ? 'eager' : 'lazy';
@endphp

@if ($layout === 'compact')

    {{-- Compact: thumbnail plus headline. For side lists and "more from" blocks. --}}
    <article {{ $attributes->merge(['class' => 'group']) }}>
        <a href="{{ $href }}" class="flex items-start gap-4">
            <x-ui.media-slot :src="$image" :alt="$title" icon="compass" ratio="16/9" :loading="$loading"
                             class="w-24 shrink-0 rounded-lg sm:w-28" />

            <div class="min-w-0 flex-1">
                @if ($category)
                    <span class="text-micro font-semibold text-accent-deep">{{ $category }}</span>
                @endif

                <h3 class="mt-1 line-clamp-2 text-body font-semibold text-ink transition group-hover:text-accent-deep">
                    {{ $title }}
                </h3>

                <time datetime="{{ $date }}" dir="ltr" class="num mt-1.5 block text-caption text-stone">{{ $dateLabel }}</time>
            </div>
        </a>
    </article>

@elseif ($layout === 'split')

    {{-- Lead: horizontal split from lg. The biggest visual lift on the index. --}}
    <article {{ $attributes->merge(['class' => 'tile group overflow-hidden']) }}>
        <a href="{{ $href }}" class="grid h-full lg:grid-cols-2">

            <div class="relative overflow-hidden">
                <x-ui.media-slot :src="$image" :alt="$title" icon="compass" ratio="16/9" :loading="$loading"
                                 class="w-full transition duration-500 group-hover:scale-105 lg:h-full" />
            </div>

            <div class="flex flex-col justify-center p-6 sm:p-8 lg:p-10">
                @if ($category)
                    <span class="w-fit border-s-2 border-accent ps-2.5 text-micro font-semibold text-accent-deep">
                        {{ $category }}
                    </span>
                @endif

                <h3 class="mt-4 text-h3 font-bold text-ink transition group-hover:text-accent-deep lg:text-h2">
                    {{ $title }}
                </h3>

                @if ($excerpt)
                    <p class="mt-4 line-clamp-3 text-lead leading-[1.8] text-steel">{{ $excerpt }}</p>
                @endif

                <div class="mt-6 flex items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-caption text-stone">
                        <time datetime="{{ $date }}" dir="ltr" class="num">{{ $dateLabel }}</time>

                        @if ($readingMinutes)
                            <span class="size-1 rounded-full bg-hairline-strong" aria-hidden="true"></span>
                            <span class="num">{{ __('public.news_minutes', ['count' => $readingMinutes]) }}</span>
                        @endif
                    </div>

                    <x-icon name="arrow" class="size-5 text-muted transition group-hover:text-accent-deep rtl:-scale-x-100" />
                </div>
            </div>
        </a>
    </article>

@else

    {{-- Default: image on top, content below. --}}
    <article {{ $attributes->merge(['class' => 'tile group flex h-full flex-col overflow-hidden']) }}>
        <a href="{{ $href }}" class="flex h-full flex-col">

            {{-- media-slot enforces the ratio itself - do not wrap it in a second aspect box --}}
            <div class="relative overflow-hidden">
                <x-ui.media-slot :src="$image" :alt="$title" icon="compass" ratio="16/9" :loading="$loading"
                                 class="w-full transition duration-500 group-hover:scale-105" />
            </div>

            <div class="flex flex-1 flex-col p-5 sm:p-6">
                @if ($category)
                    <span class="w-fit border-s-2 border-accent ps-2.5 text-micro font-semibold text-accent-deep">
                        {{ $category }}
                    </span>
                @endif

                <h3 class="mt-3 line-clamp-3 text-h4 font-semibold text-ink transition group-hover:text-accent-deep">
                    {{ $title }}
                </h3>

                {{-- The excerpt is read as prose: 16px at line-height 1.75 --}}
                @if ($excerpt)
                    <p class="mt-2.5 line-clamp-2 text-body leading-[1.75] text-steel">{{ $excerpt }}</p>
                @endif

                <div class="mt-auto flex items-center justify-between gap-3 pt-5">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-caption text-stone">
                        <time datetime="{{ $date }}" dir="ltr" class="num">{{ $dateLabel }}</time>

                        @if ($readingMinutes)
                            <span class="size-1 rounded-full bg-hairline-strong" aria-hidden="true"></span>
                            <span class="num">{{ __('public.news_minutes', ['count' => $readingMinutes]) }}</span>
                        @endif
                    </div>

                    <x-icon name="arrow" class="size-4 text-muted transition group-hover:text-accent-deep rtl:-scale-x-100" />
                </div>
            </div>
        </a>
    </article>

@endif
