@props([
    'correct' => 0,
    'total'   => 0,
])

@php
    $total = max(0, (int) $total);
    $correct = max(0, min($total, (int) $correct));
    $incorrect = $total - $correct;
    $percent = $total > 0 ? round(($correct / $total) * 100) : 0;
@endphp

{{--
    data-score-* عامّة عبر JS لتحديثها بنتيجة حقيقية عند إنهاء الكويز —
    القيم الأولية هنا احتياطية فقط (SSR-friendly)، لا تُعرض للطالب فعلياً
    قبل أن يُنهي الكويز.
--}}
<div data-quiz-result-summary
     data-msg-excellent="{{ __('student.quiz_result_excellent') }}"
     data-msg-good="{{ __('student.quiz_result_good') }}"
     data-msg-practice="{{ __('student.quiz_result_practice_more') }}"
     class="tile flex flex-col items-center gap-4 p-8 text-center">

    <x-ui.score-ring :percent="$percent" />

    <p data-score-fraction class="num text-ui font-semibold text-steel">{{ $correct }} / {{ $total }}</p>
    <p data-score-message class="max-w-sm text-ui text-steel">
        {{ $percent >= 80 ? __('student.quiz_result_excellent') : ($percent >= 50 ? __('student.quiz_result_good') : __('student.quiz_result_practice_more')) }}
    </p>

    <div class="mt-2 grid w-full grid-cols-3 gap-3 border-t border-hairline-soft pt-5">
        <div>
            <p data-score-correct class="num text-h4 font-bold text-accent-deep">{{ $correct }}</p>
            <p class="text-caption text-stone">{{ __('student.quiz_result_correct_label') }}</p>
        </div>
        <div>
            <p data-score-incorrect class="num text-h4 font-bold text-error-deep">{{ $incorrect }}</p>
            <p class="text-caption text-stone">{{ __('student.quiz_result_incorrect_label') }}</p>
        </div>
        <div>
            <p data-score-percent-mini class="num text-h4 font-bold text-ink">{{ $percent }}%</p>
            <p class="text-caption text-stone">{{ __('student.quiz_result_percent_label') }}</p>
        </div>
    </div>
</div>
