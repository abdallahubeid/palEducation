@props([
    'icon'  => 'circle',
    'title' => '',
    'body'  => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 px-6 py-12 text-center']) }}>
    <span class="grid size-14 place-items-center rounded-full bg-surface text-stone">
        <x-icon :name="$icon" class="size-6" />
    </span>

    <div class="max-w-xs">
        <p class="text-ui font-semibold text-ink">{{ $title }}</p>
        @if ($body)
            <p class="mt-1.5 text-caption text-steel">{{ $body }}</p>
        @endif
    </div>

    {{ $slot }}
</div>
