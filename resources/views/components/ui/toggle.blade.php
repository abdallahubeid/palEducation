@props([
    'name'    => '',
    'label'   => '',
    'checked' => false,
])

{{--
    عمداً بلا صنف .tile/مخصّص — Tailwind وحده هنا كي يعمل has-checked:
    بلا تنازع طبقات (نفس درس quiz-option.blade.php).
--}}
<label class="flex cursor-pointer items-center justify-between gap-4 py-2">
    <span class="text-ui text-ink">{{ $label }}</span>

    {{--
        الانزلاق عبر start (خاصية منطقية) لا transform: تفادياً لمشكلة
        تكديس المتغيّرات rtl:peer-checked: التي لم تُطبَّق فعلياً عند
        الاختبار — start-* يُصحَّح تلقائياً حسب الاتجاه بلا صنف rtl إضافي.
    --}}
    <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-hairline-strong transition has-checked:bg-accent">
        <input type="checkbox" name="{{ $name }}" @checked($checked) class="peer sr-only">
        <span class="absolute inset-y-0.5 start-0.5 size-5 rounded-full bg-canvas shadow-subtle transition-[inset-inline-start]
                     peer-checked:start-[1.375rem]"></span>
    </span>
</label>
