@php
    $results = [
        ['student' => 'ليان عبد الله', 'lecture' => 'قواعد الاشتقاق',      'subject' => 'الرياضيات', 'score' => 9,  'total' => 10, 'attempts' => 1, 'completion' => 92, 'date' => '2026-08-19'],
        ['student' => 'محمد نصّار',    'lecture' => 'النهايات والاتصال',    'subject' => 'الرياضيات', 'score' => 7,  'total' => 10, 'attempts' => 2, 'completion' => 78, 'date' => '2026-08-19'],
        ['student' => 'سارة حمدان',    'lecture' => 'التكامل غير المحدد',   'subject' => 'الرياضيات', 'score' => 10, 'total' => 10, 'attempts' => 1, 'completion' => 100, 'date' => '2026-08-18'],
        ['student' => 'يوسف قاسم',     'lecture' => 'المتتاليات الحسابية',   'subject' => 'الرياضيات', 'score' => 4,  'total' => 10, 'attempts' => 2, 'completion' => 45, 'date' => '2026-08-18'],
        ['student' => 'رنا صالح',      'lecture' => 'قوانين نيوتن',          'subject' => 'الفيزياء',  'score' => 8,  'total' => 10, 'attempts' => 1, 'completion' => 84, 'date' => '2026-08-17'],
        ['student' => 'أحمد بركات',    'lecture' => 'الحركة الدائرية',       'subject' => 'الفيزياء',  'score' => 3,  'total' => 10, 'attempts' => 2, 'completion' => 38, 'date' => '2026-08-17'],
        ['student' => 'هدى مصطفى',     'lecture' => 'قواعد الاشتقاق',        'subject' => 'الرياضيات', 'score' => 9,  'total' => 10, 'attempts' => 1, 'completion' => 90, 'date' => '2026-08-16'],
        ['student' => 'كريم عوض',      'lecture' => 'الفائدة المركّبة',      'subject' => 'رياضيات تجارية', 'score' => 6, 'total' => 10, 'attempts' => 1, 'completion' => 62, 'date' => '2026-08-16'],
        ['student' => 'دانا شاهين',    'lecture' => 'النهايات والاتصال',     'subject' => 'الرياضيات', 'score' => 8,  'total' => 10, 'attempts' => 1, 'completion' => 81, 'date' => '2026-08-15'],
        ['student' => 'عمر زيدان',     'lecture' => 'قوانين نيوتن',          'subject' => 'الفيزياء',  'score' => 5,  'total' => 10, 'attempts' => 2, 'completion' => 52, 'date' => '2026-08-15'],
    ];

    // توزيع الدرجات — يُحسب من البيانات نفسها لا يُكتب يدوياً
    $high = count(array_filter($results, fn ($r) => $r['score'] >= 8));
    $mid  = count(array_filter($results, fn ($r) => $r['score'] >= 5 && $r['score'] < 8));
    $low  = count(array_filter($results, fn ($r) => $r['score'] < 5));
    $totalRows = count($results);
    $passRate = $totalRows ? round((($high + $mid) / $totalRows) * 100) : 0;

    $bands = [
        ['label' => __('teacher.score_band_high'), 'count' => $high, 'bar' => 'bg-accent',      'text' => 'text-accent-deep'],
        ['label' => __('teacher.score_band_mid'),  'count' => $mid,  'bar' => 'bg-amber',       'text' => 'text-amber-deep'],
        ['label' => __('teacher.score_band_low'),  'count' => $low,  'bar' => 'bg-error',       'text' => 'text-error-deep'],
    ];

    $lectureOptions = array_unique(array_column($results, 'lecture'));
    $lectureOptions = array_combine($lectureOptions, $lectureOptions);

    $columns = [
        ['key' => 'student',    'label' => __('teacher.col_student'),    'sortable' => true, 'primary' => true],
        ['key' => 'lecture',    'label' => __('teacher.col_lecture'),    'sortable' => true, 'hideBelow' => 'md'],
        ['key' => 'score',      'label' => __('teacher.col_score'),      'sortable' => true, 'numeric' => true],
        ['key' => 'attempts',   'label' => __('teacher.col_attempts'),   'sortable' => true, 'numeric' => true, 'align' => 'end', 'hideBelow' => 'lg'],
        ['key' => 'completion', 'label' => __('teacher.col_completion'), 'sortable' => true, 'numeric' => true, 'hideBelow' => 'sm'],
        ['key' => 'date',       'label' => __('teacher.col_date'),       'sortable' => true, 'numeric' => true, 'hideBelow' => 'lg'],
    ];
@endphp

<x-layouts.teacher :title="__('teacher.performance_title')">

    <div>
        <h1 class="text-h2 font-bold text-ink">{{ __('teacher.performance_title') }}</h1>
        <p class="mt-1 text-ui text-steel">{{ __('teacher.performance_subtitle') }}</p>
    </div>

    {{-- إحصاء علوي + توزيع الدرجات --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-[repeat(3,minmax(0,1fr))_minmax(0,1.4fr)]">
        <x-domain.stat-card icon="clipboard" :value="(string) $totalRows" :label="__('teacher.stat_attempts_total')" tone="tag" />
        <x-domain.stat-card icon="trending-up" :value="$passRate . '%'" :label="__('teacher.stat_pass_rate')" tone="accent" />
        <x-domain.stat-card icon="alert" :value="(string) $low" :label="__('teacher.stat_needs_help')" tone="warn" />

        {{-- توزيع بسيط بثلاث فئات — يجيب «من يحتاج متابعة؟» بنظرة واحدة --}}
        <div class="tile flex flex-col justify-center gap-3 p-5">
            <p class="text-caption font-semibold text-stone">{{ __('teacher.distribution_title') }}</p>

            @foreach ($bands as $band)
                @php $pct = $totalRows ? round(($band['count'] / $totalRows) * 100) : 0; @endphp
                <div>
                    <div class="flex items-center justify-between text-caption">
                        <span class="{{ $band['text'] }} font-medium">{{ $band['label'] }}</span>
                        <span class="num text-stone">{{ $band['count'] }} · {{ $pct }}%</span>
                    </div>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-hairline-soft">
                        <div class="h-full rounded-full {{ $band['bar'] }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <x-ui.data-table
        class="mt-6"
        :columns="$columns"
        :search-placeholder="__('teacher.performance_search')"
        :per-page="8"
        empty-icon="chart"
        :empty-title="__('teacher.performance_empty_title')"
        :empty-body="__('teacher.performance_empty_body')"
        :no-results-title="__('teacher.table_no_results_title')"
        :no-results-body="__('teacher.table_no_results_body')">

        <x-slot:toolbar>
            <x-ui.select name="lecture_filter"
                         data-table-filter="lecture"
                         :placeholder="__('teacher.filter_all_lectures')"
                         :options="$lectureOptions"
                         class="w-full sm:w-56" />
        </x-slot:toolbar>

        <x-slot:actions>
            <x-ui.button variant="secondary" size="sm">
                <x-icon name="download" class="size-4" />
                {{ __('teacher.export') }}
            </x-ui.button>
        </x-slot:actions>

        @foreach ($results as $row)
            @php
                $pct = (int) round(($row['score'] / $row['total']) * 100);
                $scoreTone = $row['score'] >= 8 ? 'accent' : ($row['score'] >= 5 ? 'warn' : 'error');
            @endphp

            <tr data-row
                data-search="{{ $row['student'] }} {{ $row['lecture'] }}"
                data-filter-lecture="{{ $row['lecture'] }}"
                data-sort-student="{{ $row['student'] }}"
                data-sort-lecture="{{ $row['lecture'] }}"
                data-sort-score="{{ $row['score'] }}"
                data-sort-attempts="{{ $row['attempts'] }}"
                data-sort-completion="{{ $row['completion'] }}"
                data-sort-date="{{ $row['date'] }}"
                class="transition hover:bg-surface-soft">

                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="$row['student']" size="sm" />
                        <div class="min-w-0">
                            <p class="truncate text-body font-medium text-ink">{{ $row['student'] }}</p>
                            <p class="truncate text-caption text-stone md:hidden">{{ $row['lecture'] }}</p>
                        </div>
                    </div>
                </td>

                <td class="hidden px-4 py-3 md:table-cell">
                    <p class="truncate text-ui text-steel">{{ $row['lecture'] }}</p>
                    <p class="truncate text-caption text-muted">{{ $row['subject'] }}</p>
                </td>

                <td class="px-4 py-3">
                    {{-- الدرجة كسر — يبقى LTR دائماً --}}
                    <x-ui.badge :variant="$scoreTone">
                        <span class="num" dir="ltr">{{ $row['score'] }}/{{ $row['total'] }}</span>
                    </x-ui.badge>
                </td>

                <td class="num hidden px-4 py-3 text-end text-ui text-steel lg:table-cell">{{ $row['attempts'] }}</td>

                {{--
                    whitespace-nowrap + min-w على الخلية: بدونهما كان محتوى
                    الخلية (شريط 64px ثابت + النسبة) يتجاوز عرض العمود المخصَّص
                    له (171px داخل 153px)، والفائض ينسكب في RTL جهة اليسار —
                    أي فوق عمود التاريخ مباشرة. الشريط الآن مرن بحدّ أدنى
                    بدل عرض ثابت لا يقبل التقلّص.
                --}}
                <td class="hidden whitespace-nowrap px-4 py-3 sm:table-cell">
                    <div class="flex min-w-[7.5rem] items-center gap-2.5">
                        <x-ui.progress-bar :percent="$row['completion']" size="sm" class="min-w-10 flex-1" />
                        <span class="num shrink-0 text-caption tabular-nums text-stone">{{ $row['completion'] }}%</span>
                    </div>
                </td>

                <td class="hidden whitespace-nowrap px-4 py-3 lg:table-cell">
                    <time datetime="{{ $row['date'] }}" dir="ltr" class="text-caption text-stone">{{ $row['date'] }}</time>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

</x-layouts.teacher>
