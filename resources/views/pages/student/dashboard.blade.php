@php
    // بيانات عرض — تُستبدل باستعلامات عند بناء نماذج المجال والمصادقة
    $student = [
        'name'   => 'محمد أبو عودة',
        'avatar' => null,
        'branch' => 'الفرع العلمي',
    ];

    $subscriptionState = 'expiring';   // active | expiring | expired
    $subscriptionDaysLeft = 5;

    $stats = [
        ['icon' => 'check-circle', 'value' => 42,   'label' => __('student.stat_completed_lectures'), 'tone' => 'accent'],
        ['icon' => 'trending-up',  'value' => '84%', 'label' => __('student.stat_average_score'),      'tone' => 'amber'],
        ['icon' => 'book',         'value' => 7,     'label' => __('student.stat_branch_subjects'),    'tone' => 'tag'],
        ['icon' => 'clock',        'value' => $subscriptionDaysLeft, 'label' => __('student.stat_subscription_days'), 'tone' => $subscriptionState === 'active' ? 'accent' : 'warn'],
    ];

    $continueLecture = [
        'subjectName'  => 'الرياضيات',
        'lectureTitle' => 'المعادلات التفاضلية — الدرس 3',
        'percent'      => 62,
        'image'        => null,
        'href'         => '#',
    ];

    $subjects = [
        ['name' => 'الرياضيات',           'teacher' => 'أ. سامر خليل', 'icon' => 'compass', 'percent' => 62, 'lecturesCount' => 24, 'tone' => 'accent', 'slug' => 'math'],
        ['name' => 'الفيزياء',            'teacher' => 'أ. رنا عوض',   'icon' => 'beaker',  'percent' => 40, 'lecturesCount' => 20, 'tone' => 'tag',    'slug' => 'physics'],
        ['name' => 'الكيمياء',            'teacher' => 'أ. وليد حمد',  'icon' => 'beaker',  'percent' => 18, 'lecturesCount' => 18, 'tone' => 'amber',  'slug' => 'chemistry'],
        ['name' => 'اللغة الإنجليزية',    'teacher' => 'أ. لينا فرح',  'icon' => 'book',    'percent' => 75, 'lecturesCount' => 16, 'tone' => 'warn',   'slug' => 'english'],
    ];

    $results = [
        ['lecture' => 'المعادلات التفاضلية — الدرس 2',   'score' => '9 / 10',  'date' => '2026-08-14'],
        ['lecture' => 'قوانين نيوتن — الدرس 4',           'score' => '7 / 10',  'date' => '2026-08-11'],
        ['lecture' => 'التفاعلات الكيميائية — الدرس 1',   'score' => '10 / 10', 'date' => '2026-08-08'],
    ];

    $news = [
        ['title' => 'مواعيد الامتحانات التجريبية للفصل الثاني', 'date' => '2026-08-15'],
        ['title' => 'محاضرات جديدة أُضيفت لمادة الفيزياء',       'date' => '2026-08-12'],
    ];
@endphp

<x-layouts.student
    :title="__('student.nav_dashboard')"
    :student-name="$student['name']"
    :student-avatar="$student['avatar']"
    :subscription-state="$subscriptionState"
    :unread-count="2">

    <div class="mx-auto flex max-w-6xl flex-col gap-8">

        @if (in_array($subscriptionState, ['expiring', 'expired']))
            <x-ui.alert :variant="$subscriptionState === 'expired' ? 'error' : 'warn'">
                <p class="font-semibold text-ink">
                    {{ $subscriptionState === 'expired' ? __('student.subscription_expired_title') : __('student.subscription_warning_title') }}
                </p>
                <p class="mt-0.5">
                    {{ $subscriptionState === 'expired'
                        ? __('student.subscription_expired_body')
                        : __('student.subscription_warning_body', ['count' => $subscriptionDaysLeft]) }}
                </p>
                <x-ui.button variant="primary" size="sm" href="#" class="mt-3">
                    {{ __('student.renew_now') }}
                </x-ui.button>
            </x-ui.alert>
        @endif

        <div class="reveal flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-h2 font-bold text-ink">{{ __('student.welcome', ['name' => $student['name']]) }}</h1>
                <p class="mt-1 text-ui text-steel">{{ __('student.welcome_sub') }}</p>
            </div>
            <x-ui.badge variant="branch">{{ $student['branch'] }}</x-ui.badge>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $stat)
                <x-domain.stat-card :icon="$stat['icon']" :value="$stat['value']" :label="$stat['label']" :tone="$stat['tone']" />
            @endforeach
        </div>

        <x-domain.continue-card
            :subject-name="$continueLecture['subjectName']"
            :lecture-title="$continueLecture['lectureTitle']"
            :percent="$continueLecture['percent']"
            :image="$continueLecture['image']"
            :href="$continueLecture['href']" />

        <div>
            <div class="flex items-center justify-between">
                <h2 class="text-h4 font-semibold text-ink">{{ __('student.subjects_eyebrow') }}</h2>
                <a href="{{ Route::has('student.subjects.index') ? route('student.subjects.index') : '#' }}"
                   class="text-caption font-semibold text-accent-deep hover:underline">
                    {{ __('student.subjects_view_all') }}
                </a>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($subjects as $subject)
                    <x-domain.subject-card
                        :name="$subject['name']"
                        :teacher="$subject['teacher']"
                        :icon="$subject['icon']"
                        :percent="$subject['percent']"
                        :lectures-count="$subject['lecturesCount']"
                        :tone="$subject['tone']"
                        :href="route('student.subjects.show', $subject['slug'])" />
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
            <div class="tile p-6">
                <h2 class="text-h4 font-semibold text-ink">{{ __('student.results_eyebrow') }}</h2>

                @if (count($results))
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-start text-ui">
                            <thead>
                                <tr class="border-b border-hairline text-caption text-stone">
                                    <th class="py-2 text-start font-medium">{{ __('student.results_lecture') }}</th>
                                    <th class="py-2 text-start font-medium">{{ __('student.results_score') }}</th>
                                    <th class="py-2 text-start font-medium">{{ __('student.results_date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($results as $result)
                                    <tr class="border-b border-hairline-soft last:border-0">
                                        <td class="py-3 text-ink">{{ $result['lecture'] }}</td>
                                        <td class="num py-3 font-semibold text-accent-deep">{{ $result['score'] }}</td>
                                        <td class="num py-3 text-steel">{{ $result['date'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state
                        icon="clipboard"
                        :title="__('student.results_empty_title')"
                        :body="__('student.results_empty_body')" />
                @endif
            </div>

            <div class="tile p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-h4 font-semibold text-ink">{{ __('student.news_eyebrow') }}</h2>
                    <a href="{{ Route::has('news') ? route('news') : '#' }}"
                       class="text-caption font-semibold text-accent-deep hover:underline">
                        {{ __('student.news_view_all') }}
                    </a>
                </div>

                <ul class="mt-4 flex flex-col gap-4">
                    @foreach ($news as $item)
                        <li class="border-b border-hairline-soft pb-4 last:border-0 last:pb-0">
                            <p class="text-ui font-medium text-ink">{{ $item['title'] }}</p>
                            <p class="num mt-1 text-caption text-stone">{{ $item['date'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

    </div>

</x-layouts.student>
