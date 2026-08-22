@props([
    'name'     => '',
    'price'    => '',
    'oldPrice' => null,        // struck-through price, for a discounted plan
    'period'   => '',
    'summary'  => '',
    'features' => [],
    'cta'      => '',
    'href'     => '#',
    'featured' => false,
    'badge'    => null,        // overrides the default "most popular" ribbon label
    'note'     => null,        // small line under the price, e.g. a saving
])

{{--
    Prices are ALWAYS Western numerals (0-9) in Inter via `.num` - see DESIGN.md,
    "No Eastern Arabic-Indic digits". The `.num` class also applies tabular-nums so
    figures line up across stacked cards.
--}}

<div @class([
    'tile relative flex h-full flex-col p-8',
    'ring-2 ring-accent shadow-accent-glow' => $featured,
])>

    @if ($featured || $badge)
        <span class="absolute -top-3 inline-flex items-center rounded-full bg-accent
                     px-3 py-1 text-micro font-semibold text-on-primary">
            {{ $badge ?? __('home.plan_popular') }}
        </span>
    @endif

    <h3 class="text-h4 font-semibold text-ink">{{ $name }}</h3>
    <p class="mt-1.5 text-caption text-steel">{{ $summary }}</p>

    <p class="mt-6 flex flex-wrap items-baseline gap-x-2 gap-y-1">
        @if ($oldPrice)
            <span class="num text-h4 text-muted line-through">{{ $oldPrice }}</span>
        @endif
        <span class="num text-display font-bold text-ink">{{ $price }}</span>
        <span class="text-caption text-stone">{{ $period }}</span>
    </p>

    @if ($note)
        <p class="mt-2 text-caption font-semibold text-accent-deep">{{ $note }}</p>
    @endif

    <ul class="mt-7 flex flex-1 flex-col gap-3">
        @foreach ($features as $feature)
            <li class="flex items-start gap-2.5 text-ui text-slate">
                <x-icon name="check" class="mt-1 size-4 text-accent-deep" />
                <span>{{ $feature }}</span>
            </li>
        @endforeach
    </ul>

    <x-ui.button :variant="$featured ? 'primary' : 'secondary'"
                 size="md"
                 :href="$href"
                 class="mt-8 w-full">
        {{ $cta }}
    </x-ui.button>
</div>
