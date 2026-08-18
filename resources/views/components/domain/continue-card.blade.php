@props([
    'image'        => null,
    'subjectName'  => '',
    'lectureTitle' => '',
    'percent'      => 0,
    'href'         => '#',
])

<div class="tile overflow-hidden lg:flex lg:items-stretch">
    <div class="overflow-hidden lg:w-72 lg:shrink-0">
        <x-ui.media-slot :src="$image" :alt="$lectureTitle" ratio="16/9" tone="accent" class="lg:size-full" />
    </div>

    <div class="flex flex-1 flex-col justify-center gap-4 p-6">
        <x-ui.rule-label>{{ __('student.continue_eyebrow') }}</x-ui.rule-label>

        <div>
            <p class="text-caption text-stone">{{ $subjectName }}</p>
            <h3 class="mt-1 text-h4 font-semibold text-ink">{{ $lectureTitle }}</h3>
        </div>

        <x-ui.progress-bar :percent="$percent" />

        <x-ui.button variant="primary" size="md" :href="$href" class="w-fit">
            {{ __('student.continue_cta') }}
            <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
        </x-ui.button>
    </div>
</div>
