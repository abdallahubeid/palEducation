@php
    // بيانات عرض — نفس مواد لوحة الطالب + مادتان إضافيتان لتغطية حالتي
    // "مكتملة" و"لم تبدأ بعد" في الفلاتر. تُستبدل باستعلام حقيقي لاحقاً.
    $subjects = [
        ['name' => 'الرياضيات',        'teacher' => 'أ. سامر خليل', 'icon' => 'compass', 'percent' => 62,  'lecturesCount' => 24, 'filesCount' => 9, 'tone' => 'accent', 'slug' => 'math'],
        ['name' => 'الفيزياء',         'teacher' => 'أ. رنا عوض',   'icon' => 'beaker',  'percent' => 40,  'lecturesCount' => 20, 'filesCount' => 7, 'tone' => 'tag',    'slug' => 'physics'],
        ['name' => 'الكيمياء',         'teacher' => 'أ. وليد حمد',  'icon' => 'beaker',  'percent' => 18,  'lecturesCount' => 18, 'filesCount' => 6, 'tone' => 'amber',  'slug' => 'chemistry'],
        ['name' => 'اللغة الإنجليزية', 'teacher' => 'أ. لينا فرح',  'icon' => 'book',    'percent' => 75,  'lecturesCount' => 16, 'filesCount' => 5, 'tone' => 'warn',   'slug' => 'english'],
        ['name' => 'اللغة العربية',    'teacher' => 'أ. مازن سالم', 'icon' => 'book',    'percent' => 100, 'lecturesCount' => 14, 'filesCount' => 4, 'tone' => 'tag',    'slug' => 'arabic'],
        ['name' => 'التربية الإسلامية', 'teacher' => 'أ. هدى ناصر', 'icon' => 'compass', 'percent' => 0,   'lecturesCount' => 10, 'filesCount' => 3, 'tone' => 'accent', 'slug' => 'islamic'],
    ];
@endphp

<x-layouts.student
    :title="__('student.my_subjects_title')"
    :student-name="'محمد أبو عودة'"
    :subscription-state="'active'"
    :unread-count="2">

    <div class="mx-auto flex max-w-6xl flex-col gap-6" data-subject-filter>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-h2 font-bold text-ink">{{ __('student.my_subjects_title') }}</h1>

            <label class="relative flex w-full max-w-xs items-center sm:w-64">
                <x-icon name="search" class="pointer-events-none absolute start-3 size-4 text-muted" />
                <input type="search"
                       data-subject-search
                       placeholder="{{ __('student.my_subjects_search_placeholder') }}"
                       class="h-10 w-full rounded-full border border-hairline bg-surface-soft ps-9 pe-4 text-ui text-ink
                              placeholder:text-muted transition focus-visible:border-accent focus-visible:bg-canvas
                              focus-visible:outline-none">
            </label>
        </div>

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

        @if (count($subjects))
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
                            :lectures-count="$subject['lecturesCount']"
                            :files-count="$subject['filesCount']"
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
            <x-ui.empty-state icon="book" :title="__('student.my_subjects_empty_title')" />
        @endif
    </div>

</x-layouts.student>
