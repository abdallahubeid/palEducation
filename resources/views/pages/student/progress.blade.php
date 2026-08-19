@php
    // بيانات عرض — تُستبدل باستعلامات إحصائية حقيقية لاحقاً
    $stats = [
        ['icon' => 'check-circle', 'value' => 42,    'label' => __('student.stat_completed_lectures'), 'tone' => 'accent'],
        ['icon' => 'trending-up',  'value' => '84%',  'label' => __('student.stat_average_score'),      'tone' => 'amber'],
        ['icon' => 'compass',      'value' => 6,      'label' => __('student.progress_stat_streak_days'), 'tone' => 'tag'],
        ['icon' => 'clock',        'value' => '18 س', 'label' => __('student.progress_stat_total_hours'), 'tone' => 'warn'],
    ];

    $streakDays = [
        ['label' => 'س', 'active' => true],
        ['label' => 'ح', 'active' => true],
        ['label' => 'ن', 'active' => true],
        ['label' => 'ث', 'active' => true],
        ['label' => 'ر', 'active' => true],
        ['label' => 'خ', 'active' => true],
        ['label' => 'ج', 'active' => false],
    ];

    $weeklyActivity = [
        ['label' => 'س', 'value' => 35],
        ['label' => 'ح', 'value' => 50],
        ['label' => 'ن', 'value' => 20],
        ['label' => 'ث', 'value' => 65],
        ['label' => 'ر', 'value' => 40],
        ['label' => 'خ', 'value' => 55],
        ['label' => 'ج', 'value' => 0],
    ];

    // أصناف Tailwind كاملة وثابتة إلزامياً — الماسح الساكن لا يُنفّذ PHP،
    // فأي تجميع نصّي وقت التشغيل (bg-{$tone}/14) لن يُولَّد في CSS المبني.
    $subjectTones = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag'    => 'bg-tag/12 text-tag',
        'amber'  => 'bg-amber/12 text-amber-deep',
        'warn'   => 'bg-warn/14 text-warn-deep',
    ];

    $subjectsProgress = [
        ['name' => 'الرياضيات',        'icon' => 'compass', 'percent' => 62,  'tone' => 'accent'],
        ['name' => 'الفيزياء',         'icon' => 'beaker',  'percent' => 40,  'tone' => 'tag'],
        ['name' => 'الكيمياء',         'icon' => 'beaker',  'percent' => 18,  'tone' => 'amber'],
        ['name' => 'اللغة الإنجليزية', 'icon' => 'book',    'percent' => 75,  'tone' => 'warn'],
        ['name' => 'اللغة العربية',    'icon' => 'book',    'percent' => 100, 'tone' => 'tag'],
    ];

    $recentQuizzes = [
        ['lecture' => 'المعادلات التفاضلية — الدرس 2', 'subject' => 'الرياضيات', 'score' => '9 / 10',  'date' => '2026-08-14'],
        ['lecture' => 'قوانين نيوتن — الدرس 4',         'subject' => 'الفيزياء',  'score' => '7 / 10',  'date' => '2026-08-11'],
        ['lecture' => 'التفاعلات الكيميائية — الدرس 1', 'subject' => 'الكيمياء',  'score' => '10 / 10', 'date' => '2026-08-08'],
    ];
@endphp

<x-layouts.student
    :title="__('student.progress_title')"
    :student-name="'محمد أبو عودة'"
    :subscription-state="'active'"
    :unread-count="2">

    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <h1 class="text-h2 font-bold text-ink">{{ __('student.progress_title') }}</h1>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $stat)
                <x-domain.stat-card :icon="$stat['icon']" :value="$stat['value']" :label="$stat['label']" :tone="$stat['tone']" />
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="tile p-6">
                <h2 class="text-h5 font-semibold text-ink">{{ __('student.progress_streak_title') }}</h2>
                <div class="mt-5">
                    <x-domain.streak-tracker :days="$streakDays" />
                </div>
            </div>

            <div class="tile p-6">
                <h2 class="text-h5 font-semibold text-ink">{{ __('student.progress_weekly_activity_title') }}</h2>
                <div class="mt-5">
                    <x-domain.study-bar-chart :data="$weeklyActivity" />
                </div>
            </div>
        </div>

        <div class="tile p-6">
            <h2 class="text-h5 font-semibold text-ink">{{ __('student.progress_subjects_title') }}</h2>
            <div class="mt-5 flex flex-col gap-5">
                @foreach ($subjectsProgress as $subject)
                    <div class="flex items-center gap-4">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg {{ $subjectTones[$subject['tone']] ?? $subjectTones['accent'] }}">
                            <x-icon :name="$subject['icon']" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between text-ui">
                                <span class="truncate font-medium text-ink">{{ $subject['name'] }}</span>
                                <span class="num shrink-0 font-semibold text-ink">{{ $subject['percent'] }}%</span>
                            </div>
                            <x-ui.progress-bar :percent="$subject['percent']" size="sm" class="mt-2" />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="tile p-3 sm:p-6">
            <h2 class="px-2 text-h5 font-semibold text-ink sm:px-0">{{ __('student.progress_quiz_history_title') }}</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-start text-ui">
                    <thead>
                        <tr class="border-b border-hairline text-caption text-stone">
                            <th class="py-2 text-start font-medium">{{ __('student.results_lecture') }}</th>
                            <th class="py-2 text-start font-medium">{{ __('student.about_subject_title') }}</th>
                            <th class="py-2 text-start font-medium">{{ __('student.results_score') }}</th>
                            <th class="py-2 text-start font-medium">{{ __('student.results_date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentQuizzes as $quiz)
                            <tr class="border-b border-hairline-soft last:border-0">
                                <td class="py-3 text-ink">{{ $quiz['lecture'] }}</td>
                                <td class="py-3 text-steel">{{ $quiz['subject'] }}</td>
                                <td class="num py-3 font-semibold text-accent-deep">{{ $quiz['score'] }}</td>
                                <td class="num py-3 text-steel">{{ $quiz['date'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-layouts.student>
