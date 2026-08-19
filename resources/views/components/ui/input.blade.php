@props([
    'label'       => null,
    'name'        => '',
    'type'        => 'text',
    'value'       => null,
    'placeholder' => null,
    'error'       => null,
    'required'    => false,
    'dir'         => null,   // يُطبَّق على الحقل وحده لا على الغلاف
])

@php
    $id = $attributes->get('id') ?: 'field-' . $name;
@endphp

{{--
    ينفّذ سلّم الحقول من DESIGN.md — "Inputs" كان موصوفاً هناك بلا بناء.
    class المُمرَّرة من المستدعي تتحكّم بعرض الحقل كاملاً (تسمية+حقل)،
    مطابقةً لنمط ui/tabs.blade.php الحالي — لا تمرير فرعي للـinput.

    🔴 dir استثناء مقصود: يُطبَّق على <input> وحده. لو وُضع على الغلاف
    (كبقية الأصناف) لورثته التسمية العربية أيضاً، فيصير محاذاتها start=يسار
    بينما كل تسميات النموذج يمين — حقل الجوال LTR، لا تسميته.
--}}
<div {{ $attributes->except('id')->merge(['class' => 'flex flex-col gap-1.5']) }}>
    @if ($label)
        <label for="{{ $id }}" class="text-ui text-steel">
            {{ $label }}
            @if ($required)<span class="text-error-deep">*</span>@endif
        </label>
    @endif

    <input
        id="{{ $id }}"
        type="{{ $type }}"
        name="{{ $name }}"
        @if ($value !== null) value="{{ $value }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($dir) dir="{{ $dir }}" @endif
        @if ($required) required @endif
        @class([
            'h-12 w-full rounded-md border bg-canvas px-4 text-ui text-ink transition
             placeholder:text-muted focus-visible:border-accent focus-visible:outline-none
             disabled:pointer-events-none disabled:bg-surface disabled:opacity-50 lg:h-11',
            'border-error bg-error/8' => $error,
            'border-hairline hover:border-hairline-strong' => !$error,
        ])>

    @if ($error)
        <p class="text-caption text-error-deep">{{ $error }}</p>
    @endif
</div>
