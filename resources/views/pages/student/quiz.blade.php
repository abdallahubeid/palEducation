@php
    // بيانات عرض — تُستبدل باستعلامات عند بناء نموذج المجال (Quiz/Question/Option)
    $lecture = [
        'id'    => 4,
        'title' => 'قواعد الاشتقاق',
    ];

    $questions = [
        [
            'text' => 'ما ناتج اشتقاق الدالة <bdi dir="ltr">f(x) = x²</bdi>؟',
            'options' => ['<bdi dir="ltr">2x</bdi>', '<bdi dir="ltr">x</bdi>', '<bdi dir="ltr">2</bdi>', '<bdi dir="ltr">x²</bdi>'],
        ],
        [
            'text' => 'أي القواعد التالية تُستخدم لاشتقاق حاصل ضرب دالتين؟',
            'options' => ['قاعدة القوة', 'قاعدة الضرب', 'قاعدة القسمة', 'قاعدة السلسلة'],
        ],
        [
            'text' => 'مشتقة الثابت <bdi dir="ltr">c</bdi> بالنسبة إلى <bdi dir="ltr">x</bdi> تساوي:',
            'options' => ['<bdi dir="ltr">0</bdi>', '<bdi dir="ltr">1</bdi>', '<bdi dir="ltr">c</bdi>', '<bdi dir="ltr">x</bdi>'],
        ],
        [
            'text' => 'ما ناتج اشتقاق الدالة <bdi dir="ltr">f(x) = 5x³</bdi>؟',
            'options' => ['<bdi dir="ltr">15x²</bdi>', '<bdi dir="ltr">5x²</bdi>', '<bdi dir="ltr">15x</bdi>', '<bdi dir="ltr">3x²</bdi>'],
        ],
        [
            'text' => 'قاعدة القسمة تُستخدم عندما تكون الدالة على صورة:',
            'options' => ['مجموع دالتين', 'حاصل ضرب دالتين', 'خارج قسمة دالتين', 'دالة مركّبة'],
        ],
    ];

    $unansweredCount = count($questions);
@endphp

<x-layouts.focus
    :title="__('student.tab_lectures') . ' — ' . $lecture['title']"
    :heading="$lecture['title']"
    :exit-href="route('student.lectures.show', $lecture['id'])">

    <div data-quiz-runner data-quiz-id="{{ $lecture['id'] }}" class="flex flex-col gap-8">

        <div>
            <p class="text-ui font-medium text-steel">
                {!! str_replace([':current', ':total'], ['<span data-quiz-current>1</span>', (string) count($questions)], __('student.quiz_question_label')) !!}
            </p>
            <div data-quiz-progress class="mt-3">
                <x-ui.progress-bar :percent="round(100 / count($questions))" />
            </div>
        </div>

        @foreach ($questions as $i => $question)
            <section data-quiz-question data-index="{{ $i }}" @if ($i !== 0) hidden @endif class="flex flex-col gap-5">
                <p class="text-h5 leading-[1.8] font-semibold text-ink">{!! $question['text'] !!}</p>

                <div role="radiogroup" class="flex flex-col gap-3">
                    @foreach ($question['options'] as $j => $option)
                        <x-domain.quiz-option name="q{{ $i }}" value="{{ $j }}" :label="$option" />
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
               href="{{ Route::has('student.lectures.quiz-result') ? route('student.lectures.quiz-result', $lecture['id']) : '#' }}"
               class="inline-flex h-11 items-center gap-1.5 rounded-full bg-accent px-5 text-ui font-semibold
                      text-on-primary transition hover:bg-amber-deep">
                {{ __('student.quiz_finish') }}
                <x-icon name="check" class="size-4" />
            </a>
        </div>
    </div>

    <x-ui.confirm-dialog
        id="quiz-unanswered-confirm"
        variant="primary"
        :title="__('student.quiz_unanswered_title')"
        :body="str_replace([':answered', ':total'], ['<span data-answered-count>0</span>', (string) count($questions)], __('student.quiz_unanswered_body'))"
        :confirm-label="__('student.quiz_unanswered_confirm')"
        :confirm-href="Route::has('student.lectures.quiz-result') ? route('student.lectures.quiz-result', $lecture['id']) : '#'"
        :cancel-label="__('student.quiz_unanswered_cancel')" />

</x-layouts.focus>
