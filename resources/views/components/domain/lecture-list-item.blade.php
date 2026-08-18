@props([
    'number'   => 1,
    'title'    => '',
    'duration' => '',
    'status'   => 'new',   // completed | in_progress | new
    'score'    => null,
    'current'  => false,   // نقطة المتابعة الحالية — أيقونة تشغيل بدل الرقم
    'href'     => '#',
])

@php
    $statusConfig = [
        'completed'   => ['variant' => 'accent', 'label' => __('student.lecture_status_completed')],
        'in_progress' => ['variant' => 'warn', 'label' => __('student.lecture_status_in_progress')],
        'new'         => ['variant' => 'neutral', 'label' => __('student.lecture_status_new')],
    ];
    $s = $statusConfig[$status] ?? $statusConfig['new'];
@endphp

<a href="{{ $href }}" @class([
    'tile flex items-center gap-4 p-4 sm:p-5',
    'ring-2 ring-accent' => $current,
])>
    <span @class([
        'num grid size-11 shrink-0 place-items-center rounded-full text-ui font-bold',
        'bg-accent text-on-primary' => $status === 'completed' || $current,
        'bg-surface text-steel' => $status !== 'completed' && !$current,
    ])>
        @if ($current)
            <x-icon name="play" class="size-4" />
        @elseif ($status === 'completed')
            <x-icon name="check" class="size-4" />
        @else
            {{ $number }}
        @endif
    </span>

    <div class="min-w-0 flex-1">
        <h3 class="truncate text-ui font-semibold text-ink">{{ $title }}</h3>
        <div class="mt-1.5 flex flex-wrap items-center gap-2">
            <x-ui.badge variant="duration">{{ $duration }}</x-ui.badge>
            <x-ui.badge :variant="$s['variant']">{{ $s['label'] }}</x-ui.badge>
            @if ($score !== null)
                <span class="num text-caption font-semibold text-accent-deep">{{ $score }}</span>
            @endif
        </div>
    </div>

    <x-icon name="arrow" class="size-4 shrink-0 text-muted rtl:-scale-x-100" />
</a>
