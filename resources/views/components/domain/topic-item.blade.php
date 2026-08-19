@props([
    'type'        => 'text',   // text | video | quiz
    'label'       => '',
    'meta'        => null,     // مثال: مدة الفيديو
    'done'        => false,
    'href'        => '#',
    'embedTarget' => null,     // فيديو/كويز المحاضرة الحالية فقط — يبدّل العرض بلا انتقال صفحة
])

@php
    $icons = ['text' => 'book', 'video' => 'play', 'quiz' => 'clipboard'];
@endphp

{{-- href يبقى رابطاً حقيقياً دائماً (دون JS أو تنقّل من محاضرة أخرى) —
     data-embed-target طبقة تحسين تعترض النقر لمحاضرة الصفحة الحالية فقط --}}
<a href="{{ $href }}"
   @if ($embedTarget) data-embed-target="{{ $embedTarget }}" @endif
   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-ui transition hover:bg-surface">
    <span @class([
        'grid size-8 shrink-0 place-items-center rounded-full',
        'bg-accent text-on-primary' => $done,
        'bg-surface text-steel' => !$done,
    ])>
        <x-icon :name="$done ? 'check' : ($icons[$type] ?? 'circle')" class="size-4" />
    </span>

    <span @class(['flex-1 truncate', 'text-steel' => $done, 'text-ink' => !$done])>{{ $label }}</span>

    @if ($meta)
        <span class="num shrink-0 text-caption text-stone">{{ $meta }}</span>
    @endif
</a>
