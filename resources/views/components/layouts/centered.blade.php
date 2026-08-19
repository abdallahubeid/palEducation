@props([
    'title'    => null,
    'width'    => 'narrow',   // narrow(440) | wide(560) | full(1024) — الأخيرة لاختيار الفرع وجدار 402
    'showLogo' => true,
])

@php
    $widths = [
        'narrow' => 'max-w-[27.5rem]',   // 440px — تسجيل دخول، نسيت كلمة المرور
        'wide'   => 'max-w-[35rem]',     // 560px — إنشاء حساب (حقول أكثر)
        'full'   => 'max-w-4xl',         // 1024px — اختيار الفرع، حالات النظام
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description ?? __('common.meta_description') }}">

    <title>{{ $title ? $title . ' — ' . __('common.brand') : __('common.brand') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ground text-charcoal antialiased">

    {{--
        قشرة مركزية واحدة تخدم 7 شاشات: الدخول · إنشاء حساب · نسيت كلمة
        المرور · اختيار الفرع · 402 · 403 · 404.
        بلا شريط جانبي ولا شريط علوي — لا شيء يشدّ الانتباه عن الفعل الوحيد
        المطلوب في الشاشة. العرض وحده يتغيّر عبر prop.
    --}}
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:py-14">

        @if ($showLogo)
            <a href="{{ route('home') }}" class="mb-7 inline-flex items-center gap-2">
                {{-- عنصر نائب مؤقّت للشعار — يُستبدل فور وصول الهوية البصرية --}}
                <span class="grid size-9 place-items-center rounded-md bg-accent text-h5 font-bold text-on-primary"
                      aria-hidden="true">p</span>
                <span class="text-h5 font-bold text-ink">{{ __('common.brand') }}</span>
            </a>
        @endif

        <main class="w-full {{ $widths[$width] ?? $widths['narrow'] }}">
            {{ $slot }}
        </main>

        @isset($footer)
            <div class="mt-6 text-center text-ui text-steel">
                {{ $footer }}
            </div>
        @endisset
    </div>

</body>
</html>
