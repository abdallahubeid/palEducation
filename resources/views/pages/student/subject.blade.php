@php
    // بيانات عرض — تُستبدل باستعلامات عند بناء نموذج المجال (Subject/Lecture/LibraryFile)
    $subject = [
        'name'          => 'الرياضيات',
        'teacher'       => 'أ. سامر خليل',
        'icon'          => 'compass',
        'percent'       => 62,
        'lecturesCount' => 24,
        'filesCount'    => 9,
    ];

    $currentLectureNumber = 4;

    // نمط Sprints.ai — محاضرات المادة مجمّعة في وحدات، لا قائمة مسطّحة
    $modules = [
        [
            'number' => 1,
            'title' => 'أساسيات التفاضل',
            'totalDuration' => 'ساعة و7 د',
            'lectures' => [
                ['number' => 1, 'title' => 'مقدمة في التفاضل',  'duration' => '12:30', 'status' => 'completed',   'textDone' => true,  'videoDone' => true,  'quizScore' => '9 / 10'],
                ['number' => 2, 'title' => 'نهايات الدوال',      'duration' => '15:10', 'status' => 'completed',   'textDone' => true,  'videoDone' => true,  'quizScore' => '10 / 10'],
                ['number' => 3, 'title' => 'المشتقة الأولى',      'duration' => '18:45', 'status' => 'completed',   'textDone' => true,  'videoDone' => true,  'quizScore' => '7 / 10'],
                ['number' => 4, 'title' => 'قواعد الاشتقاق',      'duration' => '20:00', 'status' => 'in_progress', 'textDone' => true,  'videoDone' => false, 'quizScore' => null],
            ],
        ],
        [
            'number' => 2,
            'title' => 'التطبيقات والتكامل',
            'totalDuration' => '38 د',
            'lectures' => [
                ['number' => 5, 'title' => 'تطبيقات المشتقة',    'duration' => '16:20', 'status' => 'new', 'textDone' => false, 'videoDone' => false, 'quizScore' => null],
                ['number' => 6, 'title' => 'التكامل غير المحدد', 'duration' => '22:15', 'status' => 'new', 'textDone' => false, 'videoDone' => false, 'quizScore' => null],
            ],
        ],
    ];

    // المحاضرة الحالية تحدد أي وحدة تُفتح افتراضياً
    $currentModuleNumber = collect($modules)
        ->first(fn ($m) => collect($m['lectures'])->contains('number', $currentLectureNumber))['number'] ?? 1;

    $files = [
        ['name' => 'ورقة عمل — المشتقات', 'size' => '2.4MB', 'date' => '2026-08-10'],
        ['name' => 'ملخص الفصل الأول',     'size' => '1.1MB', 'date' => '2026-08-05'],
        ['name' => 'امتحان نصفي سابق',     'size' => '3.0MB', 'date' => '2026-07-28'],
    ];

    $breadcrumb = [
        ['label' => __('student.breadcrumb_subjects'), 'href' => Route::has('student.subjects.index') ? route('student.subjects.index') : '#'],
        ['label' => $subject['name']],
    ];
@endphp

<x-layouts.student
    :title="$subject['name']"
    :student-name="'محمد أبو عودة'"
    :subscription-state="'active'"
    :unread-count="2">

    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <x-ui.breadcrumb :items="$breadcrumb" />

        <x-domain.subject-hero
            :name="$subject['name']"
            :teacher="$subject['teacher']"
            :icon="$subject['icon']"
            :percent="$subject['percent']"
            :href="route('student.lectures.show', $currentLectureNumber)" />

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">

            {{-- العمود الرئيسي — ~70٪ --}}
            <div class="flex min-w-0 flex-col gap-5">
                <x-ui.tabs :items="['lectures' => __('student.tab_lectures'), 'files' => __('student.tab_files')]" active="lectures">
                    <div data-tab-panel="lectures" class="mt-5 flex flex-col gap-3">
                        @foreach ($modules as $module)
                            <x-domain.module-accordion
                                :number="$module['number']"
                                :title="$module['title']"
                                :lecture-count="count($module['lectures'])"
                                :total-duration="$module['totalDuration']"
                                :completed-count="collect($module['lectures'])->where('status', 'completed')->count()"
                                :open="$module['number'] === $currentModuleNumber">
                                @foreach ($module['lectures'] as $lecture)
                                    <x-domain.topic-accordion
                                        :number="$lecture['number']"
                                        :title="$lecture['title']"
                                        :duration="$lecture['duration']"
                                        :status="$lecture['status']"
                                        :current="$lecture['number'] === $currentLectureNumber"
                                        :open="$lecture['number'] === $currentLectureNumber">
                                        <x-domain.topic-item type="text" :label="__('student.topic_text_label')"
                                            :done="$lecture['textDone']"
                                            :href="route('student.lectures.show', $lecture['number']) . '#lecture-description'" />
                                        <x-domain.topic-item type="video" :label="__('student.topic_video_label')"
                                            :meta="$lecture['duration']" :done="$lecture['videoDone']"
                                            :href="route('student.lectures.show', $lecture['number'])" />
                                        <x-domain.topic-item type="quiz" :label="__('student.topic_quiz_label')"
                                            :meta="$lecture['quizScore']" :done="$lecture['quizScore'] !== null"
                                            :href="route('student.lectures.quiz', $lecture['number'])" />
                                    </x-domain.topic-accordion>
                                @endforeach
                            </x-domain.module-accordion>
                        @endforeach
                    </div>

                    <div data-tab-panel="files" hidden class="mt-5">
                        <div class="tile p-3 sm:p-6">
                            @if (count($files))
                                <div class="divide-y divide-hairline-soft">
                                    @foreach ($files as $file)
                                        <x-domain.file-row :name="$file['name']" :size="$file['size']" :date="$file['date']" href="#" />
                                    @endforeach
                                </div>

                                <div class="mt-5">
                                    <x-ui.pagination :current-page="1" :last-page="2" />
                                </div>
                            @else
                                <x-ui.empty-state
                                    icon="folder"
                                    :title="__('student.files_empty_title')"
                                    :body="__('student.files_empty_body')" />
                            @endif
                        </div>
                    </div>
                </x-ui.tabs>
            </div>

            {{-- الشريط الجانبي — ~30٪، لاصق --}}
            <div class="lg:sticky lg:top-24">
                <div class="tile flex flex-col gap-5 p-6">
                    <h2 class="text-h5 font-semibold text-ink">{{ __('student.about_subject_title') }}</h2>

                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="$subject['teacher']" size="md" />
                        <p class="truncate text-ui font-medium text-ink">{{ $subject['teacher'] }}</p>
                    </div>

                    <dl class="flex flex-col gap-2.5 border-t border-hairline-soft pt-4 text-ui">
                        <div class="flex items-center justify-between">
                            <dt class="text-steel">{{ __('student.about_subject_lectures_count') }}</dt>
                            <dd class="num font-semibold text-ink">{{ $subject['lecturesCount'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-steel">{{ __('student.about_subject_files_count') }}</dt>
                            <dd class="num font-semibold text-ink">{{ $subject['filesCount'] }}</dd>
                        </div>
                    </dl>

                    <x-ui.button variant="secondary" size="md" href="#" class="w-full">
                        {{ __('student.start_subject_cta') }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>

</x-layouts.student>
