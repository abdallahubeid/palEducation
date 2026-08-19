<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ __('styleguide.title') }} — {{ __('common.brand') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ground text-charcoal antialiased">

    {{--
        سطح تطوير داخلي — ليس واجهة منتج. لا يستخدم PublicShell عمداً
        (لا رأس تسويقي ولا تذييل) كي يبقى المكوّن المعروض هو البطل.
    --}}
    <header class="sticky top-0 z-30 border-b border-hairline bg-canvas/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-3 px-4 py-3 lg:px-8">
            <div class="min-w-0 flex-1">
                <p class="truncate text-h5 font-bold text-ink">{{ __('styleguide.title') }}</p>
                <p class="truncate text-caption text-stone">{{ __('styleguide.subtitle') }}</p>
            </div>

            <button type="button"
                    data-dir-toggle
                    data-label-rtl="{{ __('styleguide.dir_switch_to_ltr') }}"
                    data-label-ltr="{{ __('styleguide.dir_switch_to_rtl') }}"
                    class="inline-flex h-11 shrink-0 items-center gap-2 rounded-full bg-accent px-5 text-ui font-semibold text-on-primary transition hover:bg-accent-deep lg:h-9">
                <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                <span data-dir-label>{{ __('styleguide.dir_switch_to_ltr') }}</span>
            </button>
        </div>

        <nav class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 pb-2 lg:px-8">
            @foreach ([
                'foundations' => __('styleguide.nav_foundations'),
                'buttons'     => __('styleguide.nav_buttons'),
                'forms'       => __('styleguide.nav_forms'),
                'selection'   => __('styleguide.nav_selection'),
                'feedback'    => __('styleguide.nav_feedback'),
                'data'        => __('styleguide.nav_data'),
            ] as $anchor => $label)
                <a href="#{{ $anchor }}"
                   class="shrink-0 rounded-full px-3 py-1.5 text-caption font-medium text-steel transition hover:bg-surface hover:text-ink">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-10 lg:px-8 lg:py-14">
        {{ $slot }}
    </main>

</body>
</html>
