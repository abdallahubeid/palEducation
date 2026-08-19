@props([
    'number'        => 1,
    'title'         => '',
    'lectureCount'  => 0,
    'totalDuration' => '',
    'completedCount' => 0,
    'open'          => false,
])

{{-- نمط Sprints.ai: وحدة مرقّمة قابلة للطي، رأسها يلخّص العدد والمدة والتقدّم قبل الفتح --}}
<details @if ($open) open @endif class="tile group overflow-hidden">
    <summary class="flex cursor-pointer list-none items-center gap-4 p-4 sm:p-5 marker:hidden">
        <span class="num grid size-9 shrink-0 place-items-center rounded-full bg-accent/14 text-ui font-bold text-accent-deep">
            {{ $number }}
        </span>

        <div class="min-w-0 flex-1">
            <h3 class="truncate text-ui font-semibold text-ink">{{ $title }}</h3>
            <p class="num mt-0.5 text-caption text-stone">
                {{ $lectureCount }} {{ __('student.lectures_unit') }} · {{ $totalDuration }}
                @if ($completedCount > 0)
                    · {{ $completedCount }}/{{ $lectureCount }} {{ __('student.module_completed_suffix') }}
                @endif
            </p>
        </div>

        <x-icon name="chevron-down" class="size-4 shrink-0 text-steel transition duration-300 group-open:rotate-180" />
    </summary>

    <div class="flex flex-col gap-2 border-t border-hairline-soft p-3 sm:p-4">
        {{ $slot }}
    </div>
</details>
