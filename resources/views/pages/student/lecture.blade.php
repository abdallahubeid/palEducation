@php
    // بيانات عرض — تُستبدل باستعلامات عند بناء نموذج المجال + مزوّد الفيديو (م-5)
    $lecture = [
        'number'      => 4,
        'title'       => 'قواعد الاشتقاق',
        'subjectName' => 'الرياضيات',
        'subjectSlug' => 'math',
        'teacher'     => 'أ. سامر خليل',
        'duration'    => '20:00',
        'uploadDate'  => '2026-08-01',
        'description' => 'نتعرّف في هذه المحاضرة على القواعد الأساسية للاشتقاق: قاعدة القوة، قاعدة الضرب، وقاعدة القسمة، مع أمثلة محلولة خطوة بخطوة على كل قاعدة. المحاضرة أساسية لفهم تطبيقات المشتقة في المحاضرات القادمة، ويُنصح بمراجعة محاضرة "المشتقة الأولى" قبلها إن لم تكن قد شاهدتها.',
    ];

    // كويز هذه المحاضرة — مضمّن في نفس الصفحة الآن (نمط Sprints.ai)، بدل صفحة مستقلة.
    // نفس أسئلة student/quiz.blade.php تماماً — نفس المحاضرة، عرضان لنفس الكويز.
    $quizQuestions = [
        [
            'text' => 'ما ناتج اشتقاق الدالة <bdi dir="ltr">f(x) = x²</bdi>؟',
            'options' => ['<bdi dir="ltr">2x</bdi>', '<bdi dir="ltr">x</bdi>', '<bdi dir="ltr">2</bdi>', '<bdi dir="ltr">x²</bdi>'],
            'correct' => 0,
        ],
        [
            'text' => 'أي القواعد التالية تُستخدم لاشتقاق حاصل ضرب دالتين؟',
            'options' => ['قاعدة القوة', 'قاعدة الضرب', 'قاعدة القسمة', 'قاعدة السلسلة'],
            'correct' => 1,
        ],
        [
            'text' => 'مشتقة الثابت <bdi dir="ltr">c</bdi> بالنسبة إلى <bdi dir="ltr">x</bdi> تساوي:',
            'options' => ['<bdi dir="ltr">0</bdi>', '<bdi dir="ltr">1</bdi>', '<bdi dir="ltr">c</bdi>', '<bdi dir="ltr">x</bdi>'],
            'correct' => 0,
        ],
        [
            'text' => 'ما ناتج اشتقاق الدالة <bdi dir="ltr">f(x) = 5x³</bdi>؟',
            'options' => ['<bdi dir="ltr">15x²</bdi>', '<bdi dir="ltr">5x²</bdi>', '<bdi dir="ltr">15x</bdi>', '<bdi dir="ltr">3x²</bdi>'],
            'correct' => 0,
        ],
        [
            'text' => 'قاعدة القسمة تُستخدم عندما تكون الدالة على صورة:',
            'options' => ['مجموع دالتين', 'حاصل ضرب دالتين', 'خارج قسمة دالتين', 'دالة مركّبة'],
            'correct' => 2,
        ],
    ];

    // نمط Sprints.ai — نفس تجميع صفحة المادة، معروض هنا كمخطط تنقّل أثناء المشاهدة
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

    $currentModuleNumber = collect($modules)
        ->first(fn ($m) => collect($m['lectures'])->contains('number', $lecture['number']))['number'] ?? 1;

    // محاضرة سابقة/تالية — حساب فعلي من تسلسل الوحدات، لا روابط عرضية بعد الآن
    $allLectures = collect($modules)->flatMap(fn ($m) => $m['lectures']);
    $currentIndex = $allLectures->search(fn ($l) => $l['number'] === $lecture['number']);
    $prevLectureNumber = $currentIndex !== false && $currentIndex > 0 ? $allLectures[$currentIndex - 1]['number'] : null;
    $nextLectureNumber = $currentIndex !== false && $currentIndex < $allLectures->count() - 1 ? $allLectures[$currentIndex + 1]['number'] : null;

    $breadcrumb = [
        ['label' => __('student.breadcrumb_subjects'), 'href' => Route::has('student.subjects.index') ? route('student.subjects.index') : '#'],
        ['label' => $lecture['subjectName'], 'href' => route('student.subjects.show', $lecture['subjectSlug'])],
        ['label' => $lecture['title']],
    ];
@endphp

<x-layouts.student
    :title="$lecture['title']"
    :student-name="'محمد أبو عودة'"
    :subscription-state="'active'"
    :unread-count="2">

    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <x-ui.breadcrumb :items="$breadcrumb" />

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">

            {{-- العمود الرئيسي — عرضان يتبادلان الظهور بلا انتقال صفحة: فيديو ⇄ كويز --}}
            <div class="flex min-w-0 flex-col">

                {{-- عرض الفيديو — الافتراضي --}}
                <div data-lecture-view="video" class="flex flex-col gap-6">
                    <x-domain.video-player :title="$lecture['title']" />

                    {{-- أدوات عرض تجريبية — بديل مؤقّت لمشغّل حقيقي بانتظار م-5 --}}
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-dashed border-hairline-strong bg-surface-soft px-4 py-3">
                        <p class="text-caption text-stone">{{ __('student.demo_tools_label') }}</p>
                        <button type="button" data-simulate-lecture-end
                                class="shrink-0 rounded-md bg-canvas px-3 py-1.5 text-caption font-semibold text-steel ring-1 ring-hairline-strong transition hover:text-ink">
                            {{ __('student.simulate_lecture_end') }}
                        </button>
                    </div>

                    <div>
                        <h1 class="text-h3 font-bold text-ink">{{ $lecture['title'] }}</h1>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <x-ui.badge variant="neutral">{{ $lecture['teacher'] }}</x-ui.badge>
                            <x-ui.badge variant="duration">{{ $lecture['duration'] }}</x-ui.badge>
                            <span class="num text-caption text-stone">{{ $lecture['uploadDate'] }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        @if ($prevLectureNumber)
                            <x-ui.button variant="secondary" size="md" :href="route('student.lectures.show', $prevLectureNumber)">
                                <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                                {{ __('student.lecture_prev') }}
                            </x-ui.button>
                        @else
                            <span></span>
                        @endif

                        @if ($nextLectureNumber)
                            <x-ui.button variant="secondary" size="md" :href="route('student.lectures.show', $nextLectureNumber)">
                                {{ __('student.lecture_next') }}
                                <x-icon name="arrow" class="size-4 -scale-x-100 rtl:scale-x-100" />
                            </x-ui.button>
                        @endif
                    </div>

                    <details id="lecture-description" class="tile group overflow-hidden scroll-mt-24">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 p-5 text-ui font-semibold text-ink marker:hidden">
                            {{ __('student.lecture_description_title') }}
                            <x-icon name="chevron-down" class="size-4 shrink-0 text-steel transition duration-300 group-open:rotate-180" />
                        </summary>
                        <p class="border-t border-hairline-soft p-5 text-body leading-relaxed text-steel">
                            {{ $lecture['description'] }}
                        </p>
                    </details>
                </div>

                {{-- عرض الكويز — مضمّن هنا بدل صفحة مستقلة، مخفي افتراضياً --}}
                <div data-lecture-view="quiz" hidden
                     data-quiz-runner data-quiz-id="{{ $lecture['number'] }}"
                     class="flex flex-col gap-6">

                    <h2 class="text-h4 font-bold text-ink">{{ __('student.topic_quiz_label') }}</h2>

                    <div>
                        <p class="text-ui font-medium text-steel">
                            {!! str_replace([':current', ':total'], ['<span data-quiz-current>1</span>', (string) count($quizQuestions)], __('student.quiz_question_label')) !!}
                        </p>
                        <div data-quiz-progress class="mt-3">
                            <x-ui.progress-bar :percent="round(100 / count($quizQuestions))" />
                        </div>
                    </div>

                    @foreach ($quizQuestions as $i => $question)
                        <section data-quiz-question data-index="{{ $i }}" @if ($i !== 0) hidden @endif class="flex flex-col gap-5">
                            <p class="text-h5 leading-[1.8] font-semibold text-ink">{!! $question['text'] !!}</p>

                            <div role="radiogroup" class="flex flex-col gap-3">
                                @foreach ($question['options'] as $j => $option)
                                    <x-domain.quiz-option name="embedded-q{{ $i }}" value="{{ $j }}" :label="$option" />
                                @endforeach
                            </div>
                        </section>
                    @endforeach

                    <div class="flex items-center justify-between gap-3 border-t border-hairline-soft pt-6">
                        <button type="button" data-quiz-prev disabled
                                class="inline-flex h-11 items-center gap-1.5 rounded-full px-4 text-ui font-semibold text-steel
                                       transition hover:bg-surface hover:text-ink disabled:pointer-events-none disabled:opacity-40">
                            <x-icon name="arrow" class="size-4 -scale-x-100 rtl:scale-x-100" />
                            {{ __('student.quiz_prev') }}
                        </button>

                        <button type="button" data-quiz-next
                                class="inline-flex h-11 items-center gap-1.5 rounded-full bg-accent px-5 text-ui font-semibold
                                       text-on-primary transition hover:bg-amber-deep">
                            {{ __('student.quiz_next') }}
                            <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                        </button>

                        <a data-quiz-finish hidden
                           href="{{ Route::has('student.lectures.quiz-result') ? route('student.lectures.quiz-result', $lecture['number']) : '#' }}"
                           class="inline-flex h-11 items-center gap-1.5 rounded-full bg-accent px-5 text-ui font-semibold
                                  text-on-primary transition hover:bg-amber-deep">
                            {{ __('student.quiz_finish') }}
                            <x-icon name="check" class="size-4" />
                        </a>
                    </div>
                </div>

                {{-- عرض النتيجة — مضمّن أيضاً؛ يُحسب فعلياً من الإجابات المحفوظة عند الإنهاء --}}
                <div data-lecture-view="result" hidden
                     data-your-answer-label="{{ __('student.quiz_result_your_answer') }}"
                     data-correct-answer-label="{{ __('student.quiz_result_correct_answer') }}"
                     class="flex flex-col gap-6">

                    <h2 class="text-h4 font-bold text-ink">{{ __('student.quiz_result_title') }}</h2>

                    <x-domain.quiz-result-card :correct="0" :total="count($quizQuestions)" />

                    <div class="flex flex-col gap-3">
                        <h3 class="text-h5 font-semibold text-ink">{{ __('student.quiz_result_review_title') }}</h3>
                        @foreach ($quizQuestions as $i => $question)
                            <x-domain.answer-review-row
                                :index="$i"
                                :question="$question['text']"
                                :options="$question['options']"
                                :correct="$question['correct']" />
                        @endforeach
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        @if ($nextLectureNumber)
                            <x-ui.button variant="secondary" size="md" :href="route('student.lectures.show', $nextLectureNumber)" class="flex-1">
                                {{ __('student.lecture_next') }}
                            </x-ui.button>
                        @endif

                        <button type="button" data-quiz-retry
                                class="inline-flex h-11 flex-1 items-center justify-center rounded-full bg-accent px-5
                                       text-ui font-semibold text-on-primary transition hover:bg-amber-deep">
                            {{ __('student.quiz_result_retry') }}
                        </button>
                        <p data-quiz-retry-unavailable hidden
                           class="flex flex-1 items-center justify-center rounded-full bg-surface px-5 py-2.5 text-center text-caption text-stone">
                            {{ __('student.quiz_result_retry_unavailable') }}
                        </p>

                        <x-ui.button variant="secondary" size="md" :href="route('student.subjects.show', $lecture['subjectSlug'])" class="flex-1">
                            {{ __('student.quiz_result_back_to_subject') }}
                        </x-ui.button>
                    </div>
                </div>
            </div>

            {{-- الشريط الجانبي — مخطط المادة، يبقى ظاهراً حتى في وضع التركيز --}}
            <div class="lg:sticky lg:top-24">
                <div class="tile flex flex-col gap-3 p-3 sm:p-4">
                    <h2 class="px-2 pt-2 text-h5 font-semibold text-ink">{{ __('student.course_outline_title') }}</h2>

                    @foreach ($modules as $module)
                        <x-domain.module-accordion
                            :number="$module['number']"
                            :title="$module['title']"
                            :lecture-count="count($module['lectures'])"
                            :total-duration="$module['totalDuration']"
                            :completed-count="collect($module['lectures'])->where('status', 'completed')->count()"
                            :open="$module['number'] === $currentModuleNumber">
                            @foreach ($module['lectures'] as $moduleLecture)
                                <x-domain.topic-accordion
                                    :number="$moduleLecture['number']"
                                    :title="$moduleLecture['title']"
                                    :duration="$moduleLecture['duration']"
                                    :status="$moduleLecture['status']"
                                    :current="$moduleLecture['number'] === $lecture['number']"
                                    :open="$moduleLecture['number'] === $lecture['number']">
                                    <x-domain.topic-item type="text" :label="__('student.topic_text_label')"
                                        :done="$moduleLecture['textDone']"
                                        :href="route('student.lectures.show', $moduleLecture['number']) . '#lecture-description'" />
                                    <x-domain.topic-item type="video" :label="__('student.topic_video_label')"
                                        :meta="$moduleLecture['duration']" :done="$moduleLecture['videoDone']"
                                        :href="route('student.lectures.show', $moduleLecture['number'])"
                                        :embed-target="$moduleLecture['number'] === $lecture['number'] ? 'video' : null" />
                                    <x-domain.topic-item type="quiz" :label="__('student.topic_quiz_label')"
                                        :meta="$moduleLecture['quizScore']" :done="$moduleLecture['quizScore'] !== null"
                                        :href="route('student.lectures.quiz', $moduleLecture['number'])"
                                        :embed-target="$moduleLecture['number'] === $lecture['number'] ? 'quiz' : null" />
                                </x-domain.topic-accordion>
                            @endforeach
                        </x-domain.module-accordion>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- مودال «جاهز للاختبار؟» — يفتح تلقائياً عند حدث lecture:ended، بلا زر يدوي --}}
    <x-ui.modal id="quiz-ready-modal" size="sm" open-on="lecture:ended" labelledby="quiz-ready-title">
        <div class="flex flex-col items-center gap-4 text-center">
            <span class="grid size-16 place-items-center rounded-full bg-accent/14 text-accent-deep">
                <x-icon name="check-circle" class="size-8" />
            </span>

            <div>
                <h2 id="quiz-ready-title" class="text-h4 font-bold text-ink">{{ __('student.quiz_ready_title') }}</h2>
                <p class="mt-2 text-ui text-steel">{{ __('student.quiz_ready_body') }}</p>
            </div>

            <button type="button" data-embed-target="quiz" data-modal-close
                    class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-accent px-6
                           text-ui font-semibold text-on-primary transition hover:bg-amber-deep">
                {{ __('student.quiz_ready_cta') }}
                <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
            </button>

            <button type="button" data-modal-close class="text-caption font-semibold text-stone transition hover:text-ink">
                {{ __('student.quiz_ready_later') }}
            </button>
        </div>
    </x-ui.modal>

    {{-- تأكيد الإنهاء لو فيه أسئلة بلا إجابة — نفس منطق student/quiz.blade.php --}}
    <x-ui.confirm-dialog
        id="quiz-unanswered-confirm"
        variant="primary"
        :title="__('student.quiz_unanswered_title')"
        :body="str_replace([':answered', ':total'], ['<span data-answered-count>0</span>', (string) count($quizQuestions)], __('student.quiz_unanswered_body'))"
        :confirm-label="__('student.quiz_unanswered_confirm')"
        :confirm-href="Route::has('student.lectures.quiz-result') ? route('student.lectures.quiz-result', $lecture['number']) : '#'"
        :cancel-label="__('student.quiz_unanswered_cancel')" />

</x-layouts.student>
