@props([
    'index'    => 0,
    'question' => '',
    'options'  => [],
    'correct'  => 0,
])

{{--
    data-options يحمل نصوص الخيارات (تشمل وسوم <bdi> للمعادلات) كي تبني
    JS سطري «إجابتك» و«الإجابة الصحيحة» دون تكرار المحتوى هنا سيرفرياً —
    القيم الفعلية غير معروفة حتى ينهي الطالب الكويز.
--}}
<div data-review-row data-index="{{ $index }}" data-correct-index="{{ $correct }}"
     data-options="{{ json_encode($options) }}"
     class="tile p-5">
    <p class="flex gap-2 text-ui font-semibold text-ink">
        <span class="num shrink-0 text-stone">{{ $index + 1 }}.</span>
        <span>{!! $question !!}</span>
    </p>

    <div data-review-answers class="mt-3 flex flex-col gap-2 ps-6 text-ui"></div>
</div>
