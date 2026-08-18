@props([
    'src'  => null,
    'name' => '',
    'size' => 'md',   // sm | md | lg
])

@php
    $sizes = [
        'sm' => 'size-8 text-caption',
        'md' => 'size-10 text-ui',
        'lg' => 'size-14 text-h5',
    ];
    $initial = mb_substr(trim($name), 0, 1) ?: '؟';
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-grid shrink-0 place-items-center overflow-hidden rounded-full bg-accent-soft
                font-semibold text-accent-deep ' . ($sizes[$size] ?? $sizes['md']),
]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="size-full object-cover">
    @else
        {{ $initial }}
    @endif
</span>
