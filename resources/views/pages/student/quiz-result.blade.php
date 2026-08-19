@php
    // بيانات عرض — نفس أسئلة student/lecture.blade.php وquiz.blade.php لنفس المحاضرة
    $lecture = [
        'number'      => 4,
        'title'       => 'قواعد الاشتقاق',
        'subjectSlug' => 'math',
    ];

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

    $breadcrumb = [
        ['label' => __('student.breadcrumb_subjects'), 'href' => Route::has('student.subjects.index') ? route('student.subjects.index') : '#'],
        ['label' => $lecture['title'], 'href' => route('student.lectures.show', $lecture['number'])],
        ['label' => __('student.quiz_result_title')],
    ];
@endphp

<x-layouts.student
    :title="__('student.quiz_result_title')"
    :student-name="'محمد أبو عودة'"
    :subscription-state="'active'"
    :unread-count="2">

    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <x-ui.breadcrumb :items="$breadcrumb" />

        {{--
            مسار احتياطي: يُصَل إليه فقط لو أُنهي الكويز من الصفحة المستقلة
            (لا نتيجة مضمّنة هناك). نفس دالة renderQuizResult تُستدعى هنا
            فور التحميل بدل عند ضغط "إنهاء" — الإجابات محفوظة مسبقاً بنفس
            مفتاح localStorage من صفحة الكويز المستقلة.
        --}}
        <div data-quiz-result-standalone
             data-quiz-id="{{ $lecture['number'] }}"
             data-retake-href="{{ route('student.lectures.quiz', $lecture['number']) }}"
             data-your-answer-label="{{ __('student.quiz_result_your_answer') }}"
             data-correct-answer-label="{{ __('student.quiz_result_correct_answer') }}"
             class="flex flex-col gap-6">

            <x-domain.quiz-result-card :correct="0" :total="count($quizQuestions)" />

            <div class="flex flex-col gap-3">
                <h2 class="text-h5 font-semibold text-ink">{{ __('student.quiz_result_review_title') }}</h2>
                @foreach ($quizQuestions as $i => $question)
                    <x-domain.answer-review-row
                        :index="$i"
                        :question="$question['text']"
                        :options="$question['options']"
                        :correct="$question['correct']" />
                @endforeach
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <x-ui.button variant="secondary" size="md" :href="route('student.lectures.show', $lecture['number'])" class="flex-1">
                    {{ __('student.lecture_next') }}
                </x-ui.button>

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

</x-layouts.student>
