@props([
    /*
     * All five default to NULL, not to text. Null means "take the admin's
     * setting"; passing a value overrides it for one page. A literal default
     * here would be a second copy of the copy — the exact drift that made
     * these settings dynamic in the first place.
     */
    'title' => null,
    'subTitle' => null,
    'btnText' => null,
    'btnLink' => null,
    'note' => null,

    // canvas — the light block used on the home page.
    // deep    — the dark band the branch pages already use.
    'variant' => 'canvas',

    // Optional second link. No settings fallback: it is page-specific
    // ("all branches", "the news"), so there is nothing global to fall to.
    'secondaryText' => null,
    'secondaryLink' => null,
])

@php
    /*
     * 🔴 Read once here rather than passed down from every controller. This
     * block sits at the bottom of several unrelated pages, and requiring
     * each one to remember to fetch and forward the settings is how one page
     * ends up silently rendering an empty CTA.
     */
    $cta = \App\Models\Setting::finalCta();

    $copy = [
        'title' => $title ?? $cta['title'],
        'sub_title' => $subTitle ?? $cta['sub_title'],
        'btn_text' => $btnText ?? $cta['btn_text'],
        'btn_link' => $btnLink ?? $cta['btn_link'],
        'note' => $note ?? $cta['sub_title2'],
    ];

    /*
     * 🔴 Every class written out in full, literally. Tailwind's scanner does
     * not execute PHP, so "text-{$tone}" generates no CSS at all — a trap
     * this project has already fallen into twice.
     */
    $skins = [
        'canvas' => [
            'shell' => 'hero-canvas',
            'title' => 'text-ink',
            'sub' => 'text-steel',
            'note' => 'text-stone',
            'button' => 'primary',
            'secondary' => 'text-accent-deep hover:text-ink',
        ],
        'deep' => [
            'shell' => 'bg-linear-to-bl from-deep-from to-deep-to',
            'title' => 'text-on-dark',
            'sub' => 'text-on-dark-muted',
            'note' => 'text-on-dark-muted',
            'button' => 'on-dark',
            'secondary' => 'text-accent-on-dark hover:text-on-dark',
        ],
    ];

    $skin = $skins[$variant] ?? $skins['canvas'];

    // ⚠️ The link is passed through the same guard the footer uses: a value
    // typed into settings that is neither a path, an anchor, nor an absolute
    // URL becomes '#' rather than a broken or injected href.
    $href = \App\Models\Setting::safeHref($copy['btn_link']);
@endphp

<section {{ $attributes->class(['pb-20 lg:pb-24']) }}>
    <div class="mx-auto max-w-[1280px] px-6">
        <div class="{{ $skin['shell'] }} reveal overflow-hidden rounded-xl px-8 py-16 text-center lg:px-16 lg:py-20">

            <h2 class="text-h1 font-bold {{ $skin['title'] }}">{{ $copy['title'] }}</h2>

            @if (filled($copy['sub_title']))
                <p class="measure mx-auto mt-4 text-lead {{ $skin['sub'] }}">{{ $copy['sub_title'] }}</p>
            @endif

            {{-- The button renders only with both halves present: text
                 without a link is a dead control, and a link with no text is
                 an invisible one. --}}
            @if (filled($copy['btn_text']))
                <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <x-ui.button :variant="$skin['button']" size="lg" :href="$href" class="w-full sm:w-auto">
                        {{ $copy['btn_text'] }}
                        {{-- Directional icon: it points the way forward, so it
                             mirrors with the writing direction. --}}
                        <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                    </x-ui.button>

                    @if (filled($secondaryText) && filled($secondaryLink))
                        <a href="{{ $secondaryLink }}"
                           class="inline-flex min-h-11 items-center rounded-full px-5 text-ui font-semibold
                                  transition {{ $skin['secondary'] }}">
                            {{ $secondaryText }}
                        </a>
                    @endif
                </div>
            @endif

            {{-- The reassurance line, below the button and smaller. --}}
            @if (filled($copy['note']))
                <p class="mt-5 text-caption {{ $skin['note'] }}">{{ $copy['note'] }}</p>
            @endif
        </div>
    </div>
</section>
