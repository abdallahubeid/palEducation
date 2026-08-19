@props([
    'label'       => null,
    'name'        => 'password',
    'placeholder' => null,
    'hint'        => null,
    'error'       => null,
    'required'    => false,
])

@php
    $id = $attributes->get('id') ?: 'field-' . $name;
@endphp

{{--
    حقل كلمة المرور — نفس سلّم ui/input.blade.php بالضبط، مضافاً إليه
    زر كشف/إخفاء عند حافّة النهاية المنطقية. الزر type=button حتى لا
    يُرسل النموذج عند الضغط. التبديل الفعلي في app.js.
--}}
<div {{ $attributes->except('id')->merge(['class' => 'flex flex-col gap-1.5']) }}>
    @if ($label)
        <label for="{{ $id }}" class="text-ui text-steel">
            {{ $label }}
            @if ($required)<span class="text-error-deep">*</span>@endif
        </label>
    @endif

    <div class="relative" data-password-field>
        <input
            id="{{ $id }}"
            type="password"
            name="{{ $name }}"
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required @endif
            data-password-input
            @class([
                'h-12 w-full rounded-md border bg-canvas ps-4 pe-12 text-ui text-ink transition
                 placeholder:text-muted focus-visible:border-accent focus-visible:outline-none
                 disabled:pointer-events-none disabled:bg-surface disabled:opacity-50 lg:h-11',
                'border-error bg-error/8' => $error,
                'border-hairline hover:border-hairline-strong' => ! $error,
            ])>

        <button type="button"
                data-password-toggle
                data-show-label="{{ __('auth.password_show') }}"
                data-hide-label="{{ __('auth.password_hide') }}"
                aria-label="{{ __('auth.password_show') }}"
                aria-pressed="false"
                class="absolute end-1.5 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full
                       text-stone transition hover:bg-surface hover:text-ink">
            <x-icon name="eye" class="size-4.5" />
        </button>
    </div>

    @if ($hint && ! $error)
        <p class="text-caption text-stone">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="text-caption text-error-deep">{{ $error }}</p>
    @endif
</div>
