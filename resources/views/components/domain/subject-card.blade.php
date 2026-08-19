@props([
    'name'          => '',
    'teacher'       => '',
    'icon'          => 'book',
    'percent'       => 0,
    'lecturesCount' => 0,
    'filesCount'    => null,       // اختياري — يظهر بجانب عدد المحاضرات إن مُرِّر
    'tone'          => 'accent',   // accent | tag | amber | warn
    'href'          => '#',
])

@php
    $tones = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag'    => 'bg-tag/12 text-tag',
        'amber'  => 'bg-amber/12 text-amber-deep',
        'warn'   => 'bg-warn/14 text-warn-deep',
    ];
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'tile group flex flex-col gap-4 p-5']) }}>
    <div class="flex items-center gap-3">
        <span class="grid size-11 shrink-0 place-items-center rounded-lg {{ $tones[$tone] ?? $tones['accent'] }}">
            <x-icon :name="$icon" class="size-5" />
        </span>

        <div class="min-w-0">
            <h3 class="truncate text-ui font-semibold text-ink">{{ $name }}</h3>
            <p class="truncate text-caption text-stone">{{ $teacher }}</p>
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between text-caption text-steel">
            <span>{{ __('student.subject_progress_label') }}</span>
            <span class="num font-semibold text-ink">{{ (int) $percent }}%</span>
        </div>
        <x-ui.progress-bar :percent="$percent" size="sm" class="mt-2" />
    </div>

    <p class="num text-caption text-stone">
        {{ $lecturesCount }} {{ __('student.lectures_unit') }}
        @if ($filesCount !== null)
            · {{ $filesCount }} {{ __('student.files_unit') }}
        @endif
    </p>
</a>
