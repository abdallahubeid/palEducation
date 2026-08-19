<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description ?? __('common.meta_description') }}">

    <title>{{ isset($title) ? $title . ' — ' . __('common.brand') : __('common.brand') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ground text-charcoal antialiased">

    {{--
        قشرة تركيز — بلا شريط جانبي ولا شريط علوي معقّد.
        تُستخدم لأي شاشة تتطلّب تركيزاً كاملاً (الكويز، ونتيجته لاحقاً).
    --}}
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-hairline bg-canvas px-4 lg:px-8">
            <p class="min-w-0 truncate text-ui font-semibold text-ink">{{ $heading ?? '' }}</p>

            <a href="{{ $exitHref ?? '#' }}"
               class="flex shrink-0 items-center gap-1.5 rounded-full px-3 py-2 text-ui font-medium text-steel transition hover:bg-surface hover:text-ink">
                {{ __('student.quiz_exit') }}
                <x-icon name="close" class="size-4" />
            </a>
        </header>

        <main class="flex flex-1 justify-center px-4 py-8 lg:py-12">
            <div class="w-full max-w-2xl">
                {{ $slot }}
            </div>
        </main>
    </div>

</body>
</html>
