@props([
    'name'    => '',
    'value'   => '',
    'label'   => '',
    'hint'    => null,
    'checked' => false,
])

@php
    $id = $attributes->get('id') ?: 'radio-' . $name . '-' . $value;
@endphp

{{--
    🔴 أصناف Tailwind خام حصراً — لا صنف مخصّص على العنصر الحامل للحالة.
    النقطة الداخلية تُتحكَّم عبر [&>span]: من الدائرة الشقيقة، لأن
    peer-checked: تطابق الأشقّاء (~) لا الأحفاد.

    الاستخدام: عدّة عناصر بنفس name داخل <fieldset> له <legend>.
--}}
{{-- py-2.5 حتى نقطة lg: مساحة اللمس ≥44px على اللمسي، وتتقلّص على المؤشّر --}}
<label for="{{ $id }}" class="group flex cursor-pointer items-start gap-3 py-2.5 lg:py-1.5">
    <input type="radio"
           id="{{ $id }}"
           name="{{ $name }}"
           value="{{ $value }}"
           @checked($checked)
           class="peer sr-only">

    <span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full border border-hairline-strong bg-canvas transition
                 group-hover:border-accent
                 peer-checked:border-accent peer-checked:[&>span]:scale-100
                 peer-focus-visible:shadow-[0_0_0_3px_rgb(82_95_225_/_0.32)]">
        <span class="size-2.5 scale-0 rounded-full bg-accent transition-transform"></span>
    </span>

    <span class="min-w-0 flex-1">
        <span class="block text-ui text-ink">{{ $label }}</span>
        @if ($hint)
            <span class="mt-0.5 block text-caption text-stone">{{ $hint }}</span>
        @endif
        {{ $slot }}
    </span>
</label>
