@props([
    'label' => '',
    'value' => '',
    'hint' => null,
    'icon' => 'chart',
    'accent' => false,
    'href' => null,
])

@php
    /*
     * بطاقة مؤشّر — منقولة عن admin/stat-card في لوحة veyra.
     * accent يميّز المؤشّر الذي يتطلّب إجراءً اليوم (لا أكثر من واحد في الصفحة).
     */
    $base = 'relative overflow-hidden rounded-xl border p-4 transition duration-200 ease-out';

    $surface = $accent
        ? 'border-accent/40 bg-canvas ring-1 ring-accent/25'
        : 'border-hairline bg-canvas hover:border-hairline-strong';

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([$base, $surface, 'block hover:shadow-subtle' => (bool) $href]) }}>

    @if ($accent)
        {{-- الوهج داخل الحاوية بالكامل: إزاحة سالبة داخل overflow-hidden تُقصّ --}}
        <div class="pointer-events-none absolute -top-6 -end-6 size-20 rounded-full bg-accent/20 blur-2xl"></div>
    @endif

    <div class="relative flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-caption font-medium text-stone">{{ $label }}</p>
            {{-- tabular-nums يمنع رقص الأرقام بين البطاقات --}}
            <p class="mt-2 text-h3 font-bold tabular-nums text-ink">{{ $value }}</p>
        </div>

        <span @class([
            'grid size-10 shrink-0 place-items-center rounded-xl',
            'bg-accent/15 text-accent' => $accent,
            'bg-surface text-stone' => ! $accent,
        ])>
            <x-icon :name="$icon" class="size-5" />
        </span>
    </div>

    @if ($hint)
        <p class="relative mt-3 text-micro text-stone">{{ $hint }}</p>
    @endif
</{{ $tag }}>
