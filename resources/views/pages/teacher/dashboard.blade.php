@php
    // بيانات عرض ثابتة — تُستبدل باستعلام مُنطاق بمواد المعلم عند بناء الخادم
    $teacherName = 'أ. سامر خليل';
    $draftCount = 2;

    $stats = [
        ['icon' => 'book',  'value' => '3',   'label' => __('teacher.stat_subjects'),  'tone' => 'accent'],
        ['icon' => 'play',  'value' => '24',  'label' => __('teacher.stat_lectures'),  'tone' => 'tag'],
        ['icon' => 'users', 'value' => '186', 'label' => __('teacher.stat_students'),  'tone' => 'amber'],
        ['icon' => 'chart', 'value' => '78%', 'label' => __('teacher.stat_avg_score'), 'tone' => 'accent'],
    ];

    $quickActions = [
        ['icon' => 'plus',      'label' => __('teacher.quick_upload_lecture'),   'hint' => __('teacher.quick_upload_lecture_hint'),   'route' => 'teacher.lectures.create'],
        ['icon' => 'folder',    'label' => __('teacher.quick_upload_file'),      'hint' => __('teacher.quick_upload_file_hint'),      'route' => 'teacher.files.create'],
        ['icon' => 'chart',     'label' => __('teacher.quick_view_performance'), 'hint' => __('teacher.quick_view_performance_hint'), 'route' => 'teacher.performance'],
    ];

    $activity = [
        ['icon' => 'clipboard', 'tone' => 'accent', 'time' => 'قبل ساعة',
         'text' => '<strong>ليان عبد الله</strong> أنهت كويز «قواعد الاشتقاق» — 9 من 10'],
        ['icon' => 'play', 'tone' => 'tag', 'time' => 'قبل 3 ساعات',
         'text' => '<strong>14 طالباً</strong> شاهدوا «التكامل غير المحدد» اليوم'],
        ['icon' => 'alert', 'tone' => 'warn', 'time' => 'أمس',
         'text' => '<strong>6 طلاب</strong> حصلوا على أقل من 50% في «المتتاليات»'],
        ['icon' => 'clipboard', 'tone' => 'accent', 'time' => 'أمس',
         'text' => '<strong>محمد نصّار</strong> أنهى كويز «النهايات» — 7 من 10'],
    ];

    $recentLectures = [
        ['title' => 'التكامل غير المحدد', 'subject' => 'الرياضيات', 'status' => 'published', 'views' => 128],
        ['title' => 'قواعد الاشتقاق',      'subject' => 'الرياضيات', 'status' => 'published', 'views' => 96],
        ['title' => 'المتتاليات الحسابية',  'subject' => 'الرياضيات', 'status' => 'draft',     'views' => 0],
    ];

    $toneChip = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag'    => 'bg-tag/12 text-tag',
        'amber'  => 'bg-amber/12 text-amber-deep',
        'warn'   => 'bg-warn/14 text-warn-deep',
    ];
@endphp

<x-layouts.teacher :title="__('teacher.dashboard_title')" :teacher-name="$teacherName" subject-label="الفرع العلمي">

    {{-- ترحيب --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-h2 font-bold text-ink">{{ __('teacher.dashboard_welcome', ['name' => $teacherName]) }}</h1>
            <p class="mt-1 text-ui text-steel">{{ __('teacher.dashboard_subtitle') }}</p>
        </div>
    </div>

    {{-- تنبيه المسودات — شرطي، وهو أكثر ما يضيع على المعلم فعلياً --}}
    @if ($draftCount > 0)
        <x-ui.alert variant="warn" icon="alert" class="mt-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ __('teacher.drafts_title') }}</p>
                    <p class="mt-0.5 text-caption">{{ __('teacher.drafts_body', ['count' => $draftCount]) }}</p>
                </div>
                <x-ui.button variant="secondary" size="sm"
                             :href="Route::has('teacher.lectures') ? route('teacher.lectures') : '#'"
                             class="shrink-0">
                    {{ __('teacher.drafts_cta') }}
                </x-ui.button>
            </div>
        </x-ui.alert>
    @endif

    {{-- بطاقات الإحصاء --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <x-domain.stat-card
                :icon="$stat['icon']"
                :value="$stat['value']"
                :label="$stat['label']"
                :tone="$stat['tone']" />
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">

        {{-- العمود الرئيسي --}}
        <div class="flex flex-col gap-6">

            {{-- إجراءات سريعة --}}
            <section>
                <h2 class="text-h4 font-semibold text-ink">{{ __('teacher.quick_actions_title') }}</h2>

                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    @foreach ($quickActions as $action)
                        <a href="{{ Route::has($action['route']) ? route($action['route']) : '#' }}"
                           class="tile group flex flex-col gap-3 p-4">
                            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-accent/14 text-accent-deep
                                         transition group-hover:bg-accent group-hover:text-on-primary">
                                <x-icon :name="$action['icon']" class="size-5" />
                            </span>
                            <span>
                                <span class="block text-ui font-semibold text-ink">{{ $action['label'] }}</span>
                                <span class="mt-0.5 block text-caption text-stone">{{ $action['hint'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- آخر محاضراتك --}}
            <section>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-h4 font-semibold text-ink">{{ __('teacher.recent_lectures_title') }}</h2>
                    <a href="{{ Route::has('teacher.lectures') ? route('teacher.lectures') : '#' }}"
                       class="text-caption font-semibold text-accent-deep transition hover:underline">
                        {{ __('teacher.view_all') }}
                    </a>
                </div>

                <ul class="tile mt-3 divide-y divide-hairline-soft">
                    @foreach ($recentLectures as $lecture)
                        <li class="flex items-center gap-3 px-5 py-3.5">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface text-steel">
                                <x-icon name="play" class="size-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-body font-medium text-ink">{{ $lecture['title'] }}</p>
                                <p class="truncate text-caption text-stone">{{ $lecture['subject'] }}</p>
                            </div>

                            <x-ui.badge :variant="$lecture['status'] === 'published' ? 'accent' : 'neutral'" class="shrink-0">
                                {{ $lecture['status'] === 'published' ? __('teacher.status_published') : __('teacher.status_draft') }}
                            </x-ui.badge>

                            <span class="num hidden shrink-0 text-caption text-stone sm:inline">
                                {{ $lecture['views'] }} {{ __('teacher.col_views') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- سجلّ النشاط --}}
        <section>
            <h2 class="text-h4 font-semibold text-ink">{{ __('teacher.activity_title') }}</h2>

            <div class="tile mt-3 p-2">
                @if (empty($activity))
                    <x-ui.empty-state icon="chart"
                                      :title="__('teacher.activity_empty_title')"
                                      :body="__('teacher.activity_empty_body')" />
                @else
                    <ul class="flex flex-col">
                        @foreach ($activity as $item)
                            <li class="flex gap-3 rounded-lg p-3 transition hover:bg-surface-soft">
                                <span class="grid size-9 shrink-0 place-items-center rounded-full {{ $toneChip[$item['tone']] }}">
                                    <x-icon :name="$item['icon']" class="size-4" />
                                </span>

                                <div class="min-w-0 flex-1">
                                    {{-- النص يحمل <strong> لاسم الطالب — محتوى ثابت لا مدخل مستخدم --}}
                                    <p class="text-ui text-steel">{!! $item['text'] !!}</p>
                                    <p class="mt-0.5 text-caption text-muted">{{ $item['time'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    </div>

</x-layouts.teacher>
