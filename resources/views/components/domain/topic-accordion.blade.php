@props([
    'number'   => 1,
    'title'    => '',
    'duration' => '',
    'status'   => 'new',   // completed | in_progress | new
    'current'  => false,
    'open'     => false,
])

@php
    $statusConfig = [
        'completed'   => ['variant' => 'accent', 'label' => __('student.lecture_status_completed')],
        'in_progress' => ['variant' => 'warn', 'label' => __('student.lecture_status_in_progress')],
        'new'         => ['variant' => 'neutral', 'label' => __('student.lecture_status_new')],
    ];
    $s = $statusConfig[$status] ?? $statusConfig['new'];
@endphp

{{-- الوحدة (Module) تحوي عدّة مواضيع، وكل موضوع = محاضرة تنطوي على 3 عناصر ثابتة الترتيب --}}
<details @if ($open) open @endif @class([
    'group overflow-hidden rounded-lg transition',
    'ring-2 ring-accent' => $current,
])>
    <summary class="flex cursor-pointer list-none items-center gap-3 rounded-lg p-3 marker:hidden transition hover:bg-surface">
        <span @class([
            'num grid size-9 shrink-0 place-items-center rounded-full text-ui font-bold',
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
            <h4 class="truncate text-ui font-semibold text-ink">{{ $title }}</h4>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <x-ui.badge variant="duration">{{ $duration }}</x-ui.badge>
                <x-ui.badge :variant="$s['variant']">{{ $s['label'] }}</x-ui.badge>
            </div>
        </div>

        <x-icon name="chevron-down" class="size-4 shrink-0 text-steel transition duration-300 group-open:rotate-180" />
    </summary>

    <div class="flex flex-col gap-1 border-t border-hairline-soft p-2 ps-5">
        {{ $slot }}
    </div>
</details>
