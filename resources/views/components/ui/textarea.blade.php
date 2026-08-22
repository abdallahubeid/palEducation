@props([
    'label'       => null,
    'name'        => '',
    'value'       => null,
    'placeholder' => null,
    'hint'        => null,
    'error'       => null,
    'rows'        => 4,
    'required'    => false,
])

@php
    $id = $attributes->get('id') ?: 'field-' . $name;
@endphp

{{--
    يطابق ui/input.blade.php في الحدود والنصف والحالات — الفرق الوحيد
    الارتفاع المتغيّر. ارتفاع السطر هنا 1.75 لأن هذا نص يُقرأ فعلاً
    (وصف محاضرة، شرح إجابة) لا سطر واجهة.
--}}
<div {{ $attributes->except('id')->merge(['class' => 'flex flex-col gap-1.5']) }}>
    @if ($label)
        <label for="{{ $id }}" class="text-ui text-steel">
            {{ $label }}
            @if ($required)<span class="text-error-deep">*</span>@endif
        </label>
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @class([
            'w-full resize-y rounded-md border bg-canvas px-4 py-3 text-body leading-[1.75] text-ink transition
             placeholder:text-muted focus-visible:border-accent focus-visible:outline-none
             disabled:pointer-events-none disabled:bg-surface disabled:opacity-50',
            'border-error bg-error/8' => $error,
            'border-hairline hover:border-hairline-strong' => ! $error,
        ])>{{ $value }}</textarea>

    @if ($hint && ! $error)
        <p class="text-caption text-stone">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="text-caption text-error-deep">{{ $error }}</p>
    @endif
</div>
