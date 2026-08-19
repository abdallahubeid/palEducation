@props([
    'icon'  => 'circle',
    'value' => '',
    'label' => '',
    'tone'  => 'accent',   // accent | tag | amber | warn
])

@php
    $tones = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag'    => 'bg-tag/12 text-tag',
        'amber'  => 'bg-amber/12 text-amber-deep',
        'warn'   => 'bg-warn/14 text-warn-deep',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'tile flex items-center gap-4 p-5']) }}>
    <span class="grid size-12 shrink-0 place-items-center rounded-lg {{ $tones[$tone] ?? $tones['accent'] }}">
        <x-icon :name="$icon" class="size-6" />
    </span>

    <div class="min-w-0">
        <p class="num text-h3 font-bold text-ink">{{ $value }}</p>
        <p class="truncate text-caption text-steel">{{ $label }}</p>
    </div>
</div>
