@props([
    'name'     => '',
    'price'    => '',
    'period'   => '',
    'summary'  => '',
    'features' => [],
    'cta'      => '',
    'featured' => false,
])

<div @class([
    'tile relative flex h-full flex-col p-8',
    'ring-2 ring-accent shadow-accent-glow' => $featured,
])>

    @if ($featured)
        <span class="absolute -top-3 inline-flex items-center rounded-full bg-accent
                     px-3 py-1 text-micro font-semibold text-primary">
            {{ __('home.plan_popular') }}
        </span>
    @endif

    <h3 class="text-h4 font-semibold text-ink">{{ $name }}</h3>
    <p class="mt-1.5 text-caption text-steel">{{ $summary }}</p>

    <p class="mt-6 flex items-baseline gap-2">
        <span class="num text-display font-bold text-ink">{{ $price }}</span>
        <span class="text-caption text-stone">{{ $period }}</span>
    </p>

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
                 href="#"
                 class="mt-8 w-full">
        {{ $cta }}
    </x-ui.button>
</div>
