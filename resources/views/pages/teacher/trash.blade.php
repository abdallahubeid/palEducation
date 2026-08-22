@php
    $items = [
        ['title' => 'المتتاليات الهندسية',    'type' => 'lecture', 'subject' => 'الرياضيات',      'deleted' => '2026-08-18', 'daysLeft' => 28],
        ['title' => 'ورقة عمل — النهايات',     'type' => 'file',    'subject' => 'الرياضيات',      'deleted' => '2026-08-15', 'daysLeft' => 25],
        ['title' => 'الزخم والتصادم',          'type' => 'lecture', 'subject' => 'الفيزياء',       'deleted' => '2026-08-11', 'daysLeft' => 21],
        ['title' => 'ملخّص الفصل الأول',        'type' => 'file',    'subject' => 'رياضيات تجارية', 'deleted' => '2026-08-04', 'daysLeft' => 14],
        ['title' => 'امتحان تجريبي — تفاضل',    'type' => 'file',    'subject' => 'الرياضيات',      'deleted' => '2026-07-29', 'daysLeft' => 8],
        ['title' => 'مراجعة نهائية',            'type' => 'lecture', 'subject' => 'الفيزياء',       'deleted' => '2026-07-26', 'daysLeft' => 5],
    ];

    $columns = [
        ['key' => 'title',   'label' => __('teacher.col_item'),       'sortable' => true, 'primary' => true],
        ['key' => 'type',    'label' => __('teacher.col_type'),       'sortable' => true, 'hideBelow' => 'sm'],
        ['key' => 'deleted', 'label' => __('teacher.col_deleted_at'), 'sortable' => true, 'numeric' => true, 'hideBelow' => 'lg'],
        ['key' => 'days',    'label' => __('teacher.col_days_left'),  'sortable' => true, 'numeric' => true],
        ['key' => 'actions', 'label' => __('teacher.col_actions'),    'sortable' => false, 'align' => 'end'],
    ];
@endphp

<x-layouts.teacher :title="__('teacher.trash_title')">

    <div>
        <h1 class="text-h2 font-bold text-ink">{{ __('teacher.trash_title') }}</h1>
        <p class="measure mt-1 text-ui text-steel">{{ __('teacher.trash_subtitle') }}</p>
    </div>

    {{-- م-9: المعلم يسترجع فقط — الحذف النهائي للأدمن حصراً --}}
    <x-ui.alert variant="accent" icon="shield" class="mt-6">
        {{ __('teacher.trash_note') }}
    </x-ui.alert>

    <x-ui.data-table
        class="mt-6"
        :columns="$columns"
        :search-placeholder="__('teacher.trash_search')"
        :per-page="8"
        empty-icon="trash"
        :empty-title="__('teacher.trash_empty_title')"
        :empty-body="__('teacher.trash_empty_body')"
        :no-results-title="__('teacher.table_no_results_title')"
        :no-results-body="__('teacher.table_no_results_body')">

        <x-slot:toolbar>
            <x-ui.select name="type_filter"
                         data-table-filter="type"
                         :placeholder="__('teacher.filter_all_types')"
                         :options="['lecture' => __('teacher.type_lecture'), 'file' => __('teacher.type_file')]"
                         class="w-full sm:w-44" />
        </x-slot:toolbar>

        @foreach ($items as $item)
            {{-- ≤7 أيام: تحذير بصري — الوقت ينفد فعلاً --}}
            @php $urgent = $item['daysLeft'] <= 7; @endphp

            <tr data-row
                data-search="{{ $item['title'] }} {{ $item['subject'] }}"
                data-filter-type="{{ $item['type'] }}"
                data-sort-title="{{ $item['title'] }}"
                data-sort-type="{{ $item['type'] }}"
                data-sort-deleted="{{ $item['deleted'] }}"
                data-sort-days="{{ $item['daysLeft'] }}"
                class="group transition hover:bg-surface-soft">

                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface text-steel">
                            <x-icon :name="$item['type'] === 'lecture' ? 'play' : 'folder'" class="size-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-body font-medium text-ink">{{ $item['title'] }}</p>
                            <p class="truncate text-caption text-stone">{{ $item['subject'] }}</p>
                        </div>
                    </div>
                </td>

                <td class="hidden px-4 py-3 sm:table-cell">
                    <x-ui.badge variant="neutral">
                        {{ $item['type'] === 'lecture' ? __('teacher.type_lecture') : __('teacher.type_file') }}
                    </x-ui.badge>
                </td>

                <td class="hidden whitespace-nowrap px-4 py-3 lg:table-cell">
                    <time datetime="{{ $item['deleted'] }}" dir="ltr" class="text-caption text-stone">{{ $item['deleted'] }}</time>
                </td>

                <td class="whitespace-nowrap px-4 py-3">
                    <x-ui.badge :variant="$urgent ? 'warn' : 'neutral'">
                        <span class="num">{{ $item['daysLeft'] }}</span> {{ __('teacher.days_unit') }}
                    </x-ui.badge>
                </td>

                <td class="px-4 py-3">
                    <div class="flex items-center justify-end">
                        <button type="button"
                                data-modal-open="item-restore"
                                class="inline-flex h-11 items-center gap-2 rounded-full border border-hairline-strong
                                       bg-canvas px-4 text-caption font-semibold text-ink transition hover:bg-surface lg:h-9">
                            <x-icon name="undo" class="size-4" />
                            {{ __('teacher.action_restore') }}
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

    <x-ui.confirm-dialog
        id="item-restore"
        :title="__('teacher.restore_confirm_title')"
        :body="__('teacher.restore_confirm_body')"
        :confirm-label="__('teacher.restore_confirm_action')"
        variant="primary" />

</x-layouts.teacher>
