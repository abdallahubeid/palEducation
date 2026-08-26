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

    /*
     | يقبل ['a','b'] أو ['a' => 'أ'] — الأولى تُطبَّع للثانية.
     |
     | 🔴 الفحص بـ array_is_list لا بـ is_int على كل مفتاح. الثاني كان
     | يعتبر أي مصفوفة بمفاتيح رقمية «قائمة بسيطة» فيستخدم النص كقيمة —
     | وهذا يكسر بصمت كل قائمة تأتي من قاعدة البيانات (pluck('name','id')
     | مفاتيحها أعداد صحيحة)، فتُرسَل «علمي» بدل 1 ولا يظهر أي خطأ.
     | array_is_list يميّز [0,1,2,…] المتتالية عن [3 => 'علمي'] فعلياً.
     */
    $optionsArray = $options instanceof \Illuminate\Support\Collection
        ? $options->all()
        : (array) $options;

    $normalized = array_is_list($optionsArray)
        ? array_combine($optionsArray, $optionsArray)
        : $optionsArray;
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
