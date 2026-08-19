@props([
    'label'       => null,
    'name'        => '',
    'options'     => [],      // ['value' => 'label'] أو قائمة نصوص بسيطة
    'value'       => null,
    'placeholder' => null,
    'error'       => null,
    'required'    => false,
])

@php
    $id = $attributes->get('id') ?: 'field-' . $name;

    // يقبل ['a','b'] أو ['a' => 'أ'] — الأولى تُطبَّع للثانية
    $normalized = [];
    foreach ($options as $key => $label_) {
        $normalized[is_int($key) ? $label_ : $key] = $label_;
    }
@endphp

{{--
    يطابق ui/input.blade.php حرفياً في الارتفاع والنصف والحدود، فالحقلان
    يقفان بجانب بعضهما بلا اختلاف بصري. السهم داخل الحقل عند حافّة
    النهاية المنطقية (end) فينعكس تلقائياً مع الاتجاه.
--}}
<div {{ $attributes->except('id')->merge(['class' => 'flex flex-col gap-1.5']) }}>
    @if ($label)
        <label for="{{ $id }}" class="text-ui text-steel">
            {{ $label }}
            @if ($required)<span class="text-error-deep">*</span>@endif
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($required) required @endif
            @class([
                'h-12 w-full appearance-none rounded-md border bg-canvas ps-4 pe-11 text-ui text-ink transition
                 focus-visible:border-accent focus-visible:outline-none
                 disabled:pointer-events-none disabled:bg-surface disabled:opacity-50 lg:h-11',
                'border-error bg-error/8' => $error,
                'border-hairline hover:border-hairline-strong' => ! $error,
                'text-muted' => $placeholder && ! $value,
            ])>

            @if ($placeholder)
                <option value="" disabled @selected(! $value)>{{ $placeholder }}</option>
            @endif

            @foreach ($normalized as $optValue => $optLabel)
                <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>

        <x-icon name="chevron-down"
                class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-stone" />
    </div>

    @if ($error)
        <p class="text-caption text-error-deep">{{ $error }}</p>
    @endif
</div>
