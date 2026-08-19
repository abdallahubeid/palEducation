@props([
    'variant' => 'warn',   // warn | error | accent
    'icon'    => null,
    'role'    => 'status', // status | alert — الأخطاء تستحق alert ليعلنها قارئ الشاشة فوراً
])

@php
    // النص بدرجة -deep دائماً: warn/error الأساسيان أسطح لا نصوص
    // (3.14:1 و3.73:1 — يفشلان AA). راجع DESIGN.md § Contrast Rule.
    $variants = [
        'warn'   => 'bg-warn/10 text-warn-deep ring-1 ring-warn/20',
        'error'  => 'bg-error/8 text-error-deep ring-1 ring-error/20',
        'accent' => 'bg-accent-soft text-accent-deep ring-1 ring-accent/20',
    ];

    $icons = [
        'warn'   => 'clock',
        'error'  => 'alert',
        'accent' => 'check-circle',
    ];

    $useIcon = $icon ?? ($icons[$variant] ?? 'circle');
@endphp

<div {{ $attributes->merge([
    'class' => 'flex flex-col items-start gap-3 rounded-lg p-4 sm:flex-row sm:items-center '
               . ($variants[$variant] ?? $variants['warn']),
]) }} role="{{ $role }}">
    <x-icon :name="$useIcon" class="mt-0.5 size-5 shrink-0 sm:mt-0" />
    <div class="flex-1 text-ui">{{ $slot }}</div>
</div>
