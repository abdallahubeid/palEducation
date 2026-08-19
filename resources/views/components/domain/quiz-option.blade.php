@props([
    'name'  => '',
    'value' => '',
    'label' => '',
])

{{--
    عمداً بلا صنف .tile — خلفيته المخصّصة في app.css تفوز دائماً على
    أصناف Tailwind الشرطية (has-checked:) لأنها خارج طبقات @layer.
    هنا التصميم كامل عبر Tailwind وحده كي تعمل حالة التحديد بشكل سليم.
--}}
<label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-xl bg-canvas p-4
              shadow-[0_0_30px_rgb(0_0_0/0.05)] ring-1 ring-transparent transition
              has-checked:bg-accent-soft has-checked:ring-2 has-checked:ring-accent hover:has-checked:bg-accent-soft">
    <input type="radio" name="{{ $name }}" value="{{ $value }}" class="peer sr-only" data-quiz-option>

    <span class="grid size-5 shrink-0 place-items-center rounded-full border-2 border-hairline-strong
                 transition peer-checked:border-accent peer-checked:bg-accent">
        <span class="size-2 rounded-full bg-canvas opacity-0 transition peer-checked:opacity-100"></span>
    </span>

    <span class="text-ui text-ink">{!! $label !!}</span>
</label>
