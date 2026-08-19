@props([
    'name'    => '',
    'value'   => '1',
    'label'   => '',
    'hint'    => null,
    'checked' => false,
    'error'   => null,
])

@php
    $id = $attributes->get('id') ?: 'check-' . $name;
@endphp

{{--
    🔴 أصناف Tailwind خام حصراً — بلا أي صنف CSS مخصّص على العنصر الحامل
    للحالة. الصنف المخصّص (كـ.tile) يخرج من نظام الطبقات فيهزم
    peer-checked: دائماً. نفس درس quiz-option.blade.php وtoggle.blade.php.

    المربّع أخ لاحق للـinput لتعمل peer-checked:، ومساحة النقر هي
    الـlabel كاملة (النص + المربّع) لا المربّع وحده.
--}}
<div {{ $attributes->except('id')->merge(['class' => 'flex flex-col gap-1.5']) }}>
    {{-- py-2.5 حتى نقطة lg: مساحة اللمس ≥44px على اللمسي، وتتقلّص على المؤشّر — نفس منطق مقاسات الأزرار --}}
    <label for="{{ $id }}" class="group flex cursor-pointer items-start gap-3 py-2.5 lg:py-1.5">
        <input type="checkbox"
               id="{{ $id }}"
               name="{{ $name }}"
               value="{{ $value }}"
               @checked($checked)
               class="peer sr-only">

        {{--
            علامة الصح تُتحكَّم من المربّع نفسه عبر [&>svg]: — لأن
            peer-checked: تطابق الأشقّاء فقط (~)، والأيقونة حفيدة لا شقيقة.
        --}}
        <span @class([
            'mt-0.5 grid size-5 shrink-0 place-items-center rounded-xs border bg-canvas transition
             peer-checked:border-accent peer-checked:bg-accent peer-checked:[&>svg]:opacity-100
             peer-focus-visible:shadow-[0_0_0_3px_rgb(82_95_225_/_0.32)]',
            'border-error' => $error,
            'border-hairline-strong group-hover:border-accent' => ! $error,
        ])>
            <x-icon name="check" class="size-3.5 text-on-primary opacity-0 transition-opacity" />
        </span>

        <span class="min-w-0 flex-1">
            <span class="block text-ui text-ink">{{ $label }}</span>
            @if ($hint)
                <span class="mt-0.5 block text-caption text-stone">{{ $hint }}</span>
            @endif
            {{ $slot }}
        </span>
    </label>

    @if ($error)
        <p class="text-caption text-error-deep">{{ $error }}</p>
    @endif
</div>
