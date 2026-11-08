@props([
    'variant' => 'primary',   // primary | secondary | ghost | danger | success
    'href' => null,
    'type' => 'submit',
])

@php
    /*
     * زرّ لوحة التحكّم — منقول عن أسلوب أزرار veyra:
     * rounded-xl، حشوة px-4 py-2، نصّ semibold صغير، والأساسي بوهج accent.
     *
     * منفصل عن ui/button العام عمداً: ذاك حبّي (rounded-full) بارتفاع 44px+
     * لواجهة الطالب اللمسية؛ لوحة التحكّم سطح أداتي أكثف.
     */
    $base = 'inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl px-4 py-2
             text-caption font-semibold transition disabled:pointer-events-none disabled:opacity-50';

    $variants = [
        'primary' => 'bg-accent text-on-primary shadow-accent-glow hover:bg-accent-deep',
        'secondary' => 'border border-hairline bg-canvas text-steel hover:border-hairline-strong hover:text-ink',
        'ghost' => 'text-steel hover:bg-surface hover:text-ink',
        'danger' => 'border border-hairline bg-canvas text-error-deep hover:border-error/40 hover:bg-error/8',

        /*
         * 🔴 Same shape as `danger`, opposite meaning. Both are OUTLINE
         * buttons — identical height, padding, radius and border to
         * `secondary` — differing only in text colour, which is how this
         * panel already distinguishes a destructive action from a neutral
         * one without breaking the row's alignment.
         *
         * Added so "approve" can sit beside "edit" and "delete" at exactly
         * their dimensions. The filled `primary` it replaced was a
         * different height class of object in a row of outline controls.
         */
        'success' => 'border border-hairline bg-canvas text-accent-deep hover:border-accent/40 hover:bg-accent/8',
    ];

    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
