@props([
    'variant' => 'primary',   // primary | mint | on-dark | secondary | ghost | danger
    'size'    => 'md',        // sm | md | lg
    'href'    => null,
])

@php
    // الأزرار حبّية دائماً — الزر المربّع يقرأ كأداة طرف ثالث
    $base = 'inline-flex items-center justify-center gap-2 font-semibold whitespace-nowrap
             transition duration-200 disabled:opacity-50 disabled:pointer-events-none';

    $variants = [
        'primary'   => 'bg-primary text-on-primary rounded-full hover:brightness-125',
        'mint'      => 'bg-mint text-primary rounded-full hover:brightness-105',
        'on-dark'   => 'bg-on-dark text-primary rounded-full hover:brightness-95',
        'secondary' => 'bg-transparent text-ink border border-hairline rounded-full hover:bg-surface',
        'ghost'     => 'bg-transparent text-steel rounded-md hover:bg-surface hover:text-ink',
        'danger'    => 'bg-error text-on-dark rounded-full hover:brightness-95',
    ];

    // ≥44px حتى نقطة lg — الأجهزة اللوحية لمسية أيضاً، فلا يصحّ التقلّص عند sm
    $sizes = [
        'sm' => 'text-caption h-11 lg:h-9 px-4',
        'md' => 'text-ui h-12 lg:h-11 px-6',
        'lg' => 'text-ui h-13 px-8',
    ];

    $classes = trim("{$base} {$variants[$variant]} {$sizes[$size]}");
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'button']) }}>
        {{ $slot }}
    </button>
@endif
