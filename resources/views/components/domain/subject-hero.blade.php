@props([
    'name'    => '',
    'teacher' => '',
    'icon'    => 'book',
    'percent' => 0,
    'href'    => '#',
])

<div class="tile flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-4">
        <span class="grid size-16 shrink-0 place-items-center rounded-xl bg-accent/14 text-accent-deep">
            <x-icon :name="$icon" class="size-8" />
        </span>

        <div class="min-w-0">
            <h1 class="truncate text-h3 font-bold text-ink">{{ $name }}</h1>

            <div class="mt-1.5 flex items-center gap-2">
                <x-ui.avatar :name="$teacher" size="sm" />
                <span class="truncate text-ui text-steel">{{ $teacher }}</span>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:w-56 sm:shrink-0">
        <div class="flex items-center justify-between text-caption text-steel">
            <span>{{ __('student.subject_overall_progress') }}</span>
            <span class="num font-semibold text-ink">{{ (int) $percent }}%</span>
        </div>
        <x-ui.progress-bar :percent="$percent" />

        <x-ui.button variant="primary" size="md" :href="$href" class="w-full">
            {{ $percent > 0 ? __('student.continue_cta') : __('student.start_subject_cta') }}
            <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
        </x-ui.button>
    </div>
</div>
