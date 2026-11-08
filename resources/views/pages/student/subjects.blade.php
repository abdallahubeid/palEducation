<x-layouts.student
    :title="__('student.my_subjects_title')"
    :student-name="auth()->user()->displayName()"
    :student-avatar="auth()->user()->avatar_url">

    <div class="mx-auto flex max-w-6xl flex-col gap-6" data-subject-filter>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-h2 font-bold text-ink">{{ __('student.my_subjects_title') }}</h1>
                <p class="mt-1 text-ui text-steel">مواد فرعك المنشورة — تقدّمك محسوب من المحاضرات المكتملة.</p>
            </div>

            @if ($subjects->isNotEmpty())
                <label class="relative flex w-full max-w-xs items-center sm:w-64">
                    <x-icon name="search" class="pointer-events-none absolute start-3 size-4 text-muted" />
                    <input type="search"
                           data-subject-search
                           placeholder="{{ __('student.my_subjects_search_placeholder') }}"
                           class="h-10 w-full rounded-full border border-hairline bg-surface-soft ps-9 pe-4 text-ui text-ink
                                  placeholder:text-muted transition focus-visible:border-accent focus-visible:bg-canvas
                                  focus-visible:outline-none">
                </label>
            @endif
        </div>

        @if ($subjects->isNotEmpty())
            <div role="tablist" class="inline-flex w-fit items-center gap-1 rounded-lg bg-surface p-1">
                <button type="button" data-subject-filter-btn="all"
                        class="tab-trigger is-active cursor-pointer rounded-md px-4 py-2 text-ui font-semibold text-stone transition hover:text-ink">
                    {{ __('student.my_subjects_filter_all') }}
                </button>
                <button type="button" data-subject-filter-btn="current"
                        class="tab-trigger cursor-pointer rounded-md px-4 py-2 text-ui font-semibold text-stone transition hover:text-ink">
                    {{ __('student.my_subjects_filter_current') }}
                </button>
                <button type="button" data-subject-filter-btn="completed"
                        class="tab-trigger cursor-pointer rounded-md px-4 py-2 text-ui font-semibold text-stone transition hover:text-ink">
                    {{ __('student.my_subjects_filter_completed') }}
                </button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($subjects as $subject)
                    <div data-subject-card
                         data-subject-status="{{ $subject['percent'] >= 100 ? 'completed' : 'current' }}"
                         data-subject-name="{{ $subject['name'] . ' ' . $subject['teacher'] }}">
                        <x-domain.subject-card
                            :name="$subject['name']"
                            :teacher="$subject['teacher']"
                            :icon="$subject['icon']"
                            :percent="$subject['percent']"
                            :lectures-count="$subject['lectures_count']"
                            :tone="$subject['tone']"
                            :href="route('student.subjects.show', $subject['slug'])" />
                    </div>
                @endforeach
            </div>

            <div data-subject-no-results hidden>
                <x-ui.empty-state
                    icon="search"
                    :title="__('student.my_subjects_no_results_title')"
                    :body="__('student.my_subjects_no_results_body')" />
            </div>
        @else
            <div class="tile">
                <x-ui.empty-state icon="book"
                                  :title="__('student.my_subjects_empty_title')"
                                  body="لا مواد منشورة في فرعك بعد — ستظهر هنا فور نشرها." />
            </div>
        @endif
    </div>

</x-layouts.student>
