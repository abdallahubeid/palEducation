@php
    $lectures = [
        ['id' => 1,  'title' => 'التكامل غير المحدد',      'subject' => 'الرياضيات',      'status' => 'published', 'views' => 128, 'quiz' => true,  'date' => '2026-08-18'],
        ['id' => 2,  'title' => 'قواعد الاشتقاق',           'subject' => 'الرياضيات',      'status' => 'published', 'views' => 96,  'quiz' => true,  'date' => '2026-08-14'],
        ['id' => 3,  'title' => 'المتتاليات الحسابية',       'subject' => 'الرياضيات',      'status' => 'draft',     'views' => 0,   'quiz' => false, 'date' => '2026-08-12'],
        ['id' => 4,  'title' => 'النهايات والاتصال',         'subject' => 'الرياضيات',      'status' => 'published', 'views' => 152, 'quiz' => true,  'date' => '2026-08-09'],
        ['id' => 5,  'title' => 'قوانين نيوتن',              'subject' => 'الفيزياء',       'status' => 'published', 'views' => 74,  'quiz' => true,  'date' => '2026-08-07'],
        ['id' => 6,  'title' => 'الحركة الدائرية',           'subject' => 'الفيزياء',       'status' => 'published', 'views' => 61,  'quiz' => true,  'date' => '2026-08-03'],
        ['id' => 7,  'title' => 'الشغل والطاقة',             'subject' => 'الفيزياء',       'status' => 'draft',     'views' => 0,   'quiz' => false, 'date' => '2026-08-01'],
        ['id' => 8,  'title' => 'الفائدة المركّبة',          'subject' => 'رياضيات تجارية', 'status' => 'published', 'views' => 44,  'quiz' => true,  'date' => '2026-07-28'],
        ['id' => 9,  'title' => 'الأقساط والاستهلاك',        'subject' => 'رياضيات تجارية', 'status' => 'published', 'views' => 38,  'quiz' => true,  'date' => '2026-07-24'],
        ['id' => 10, 'title' => 'المصفوفات وتطبيقاتها',      'subject' => 'الرياضيات',      'status' => 'published', 'views' => 87,  'quiz' => true,  'date' => '2026-07-20'],
        ['id' => 11, 'title' => 'الاحتمالات الشرطية',        'subject' => 'الرياضيات',      'status' => 'published', 'views' => 71,  'quiz' => true,  'date' => '2026-07-16'],
    ];

    $subjectOptions = ['الرياضيات' => 'الرياضيات', 'الفيزياء' => 'الفيزياء', 'رياضيات تجارية' => 'رياضيات تجارية'];

    $columns = [
        ['key' => 'title',   'label' => __('teacher.col_lecture'), 'sortable' => true,  'primary' => true],
        ['key' => 'subject', 'label' => __('teacher.col_subject'), 'sortable' => true,  'hideBelow' => 'md'],
        ['key' => 'status',  'label' => __('teacher.col_status'),  'sortable' => true],
        ['key' => 'views',   'label' => __('teacher.col_views'),   'sortable' => true,  'numeric' => true, 'align' => 'end', 'hideBelow' => 'sm'],
        ['key' => 'quiz',    'label' => __('teacher.col_quiz'),    'sortable' => false, 'hideBelow' => 'lg'],
        ['key' => 'date',    'label' => __('teacher.col_date'),    'sortable' => true,  'numeric' => true, 'hideBelow' => 'lg'],
        ['key' => 'actions', 'label' => __('teacher.col_actions'), 'sortable' => false, 'align' => 'end'],
    ];
@endphp

<x-layouts.teacher :title="__('teacher.lectures_title')">

    <div>
        <h1 class="text-h2 font-bold text-ink">{{ __('teacher.lectures_title') }}</h1>
        <p class="mt-1 text-ui text-steel">{{ __('teacher.lectures_subtitle') }}</p>
    </div>

    <x-ui.data-table
        class="mt-6"
        :columns="$columns"
        :search-placeholder="__('teacher.lectures_search')"
        :per-page="8"
        empty-icon="play"
        :empty-title="__('teacher.lectures_empty_title')"
        :empty-body="__('teacher.lectures_empty_body')"
        :no-results-title="__('teacher.table_no_results_title')"
        :no-results-body="__('teacher.table_no_results_body')">

        <x-slot:toolbar>
            <x-ui.select name="subject_filter"
                         data-table-filter="subject"
                         :placeholder="__('teacher.filter_all_subjects')"
                         :options="$subjectOptions"
                         class="w-full sm:w-44" />

            <x-ui.select name="status_filter"
                         data-table-filter="status"
                         :placeholder="__('teacher.filter_all_statuses')"
                         :options="['published' => __('teacher.status_published'), 'draft' => __('teacher.status_draft')]"
                         class="w-full sm:w-40" />
        </x-slot:toolbar>

        <x-slot:actions>
            <x-ui.button size="sm"
                         :href="Route::has('teacher.lectures.create') ? route('teacher.lectures.create') : '#'">
                <x-icon name="plus" class="size-4" />
                {{ __('teacher.upload_lecture') }}
            </x-ui.button>
        </x-slot:actions>

        @foreach ($lectures as $lecture)
            <tr data-row
                data-search="{{ $lecture['title'] }} {{ $lecture['subject'] }}"
                data-filter-subject="{{ $lecture['subject'] }}"
                data-filter-status="{{ $lecture['status'] }}"
                data-sort-title="{{ $lecture['title'] }}"
                data-sort-subject="{{ $lecture['subject'] }}"
                data-sort-status="{{ $lecture['status'] }}"
                data-sort-views="{{ $lecture['views'] }}"
                data-sort-date="{{ $lecture['date'] }}"
                class="group transition hover:bg-surface-soft">

                {{-- العمود الأساسي: 16px — هو النص الذي يُقرأ فعلاً --}}
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface text-steel">
                            <x-icon name="play" class="size-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-body font-medium text-ink">{{ $lecture['title'] }}</p>
                            <p class="truncate text-caption text-stone md:hidden">{{ $lecture['subject'] }}</p>
                        </div>
                    </div>
                </td>

                <td class="hidden px-4 py-3 text-ui text-steel md:table-cell">{{ $lecture['subject'] }}</td>

                <td class="px-4 py-3">
                    <x-ui.badge :variant="$lecture['status'] === 'published' ? 'accent' : 'neutral'">
                        {{ $lecture['status'] === 'published' ? __('teacher.status_published') : __('teacher.status_draft') }}
                    </x-ui.badge>
                </td>

                <td class="num hidden px-4 py-3 text-end text-ui text-steel sm:table-cell">{{ $lecture['views'] }}</td>

                <td class="hidden px-4 py-3 lg:table-cell">
                    @if ($lecture['quiz'])
                        <span class="inline-flex items-center gap-1.5 text-caption text-accent-deep">
                            <x-icon name="check-circle" class="size-4" />
                            {{ __('teacher.quiz_ready') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-caption text-warn-deep">
                            <x-icon name="alert" class="size-4" />
                            {{ __('teacher.quiz_missing') }}
                        </span>
                    @endif
                </td>

                <td class="hidden px-4 py-3 lg:table-cell">
                    <time datetime="{{ $lecture['date'] }}" dir="ltr" class="text-caption text-stone">{{ $lecture['date'] }}</time>
                </td>

                {{--
                    الإجراءات ظاهرة دائماً على اللمس، وتُبرَز عند التحويم على
                    المؤشّر فقط. المرجع يوصي بإظهارها بالتحويم لتقليل الضجيج —
                    لكن التحويم غير موجود على الجوال، فالإخفاء هناك يعني تعطيلها.
                --}}
                <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-1 lg:opacity-60 lg:transition lg:group-hover:opacity-100">
                        <a href="{{ Route::has('student.lectures.show') ? route('student.lectures.show', $lecture['id']) : '#' }}"
                           class="grid size-11 place-items-center rounded-md text-steel transition hover:bg-surface hover:text-ink lg:size-9"
                           aria-label="{{ __('teacher.action_preview') }}" title="{{ __('teacher.action_preview') }}">
                            <x-icon name="eye" class="size-4" />
                        </a>

                        <a href="#"
                           class="grid size-11 place-items-center rounded-md text-steel transition hover:bg-surface hover:text-ink lg:size-9"
                           aria-label="{{ __('teacher.action_edit') }}" title="{{ __('teacher.action_edit') }}">
                            <x-icon name="edit" class="size-4" />
                        </a>

                        <button type="button"
                                data-modal-open="lecture-delete"
                                class="grid size-11 place-items-center rounded-md text-steel transition hover:bg-error/10 hover:text-error-deep lg:size-9"
                                aria-label="{{ __('teacher.action_delete') }}" title="{{ __('teacher.action_delete') }}">
                            <x-icon name="trash" class="size-4" />
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

    <x-ui.confirm-dialog
        id="lecture-delete"
        :title="__('teacher.delete_confirm_title')"
        :body="__('teacher.delete_confirm_body')"
        :confirm-label="__('teacher.delete_confirm_action')"
        variant="danger" />

</x-layouts.teacher>
