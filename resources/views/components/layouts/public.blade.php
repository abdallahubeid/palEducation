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

    <x-site.header />

    <main id="main">
        {{ $slot }}
    </main>

    <x-site.footer />

    {{-- العودة لأعلى الصفحة — تظهر بعد 600px تمرير.
         نعناعية لا سوداء: الفوتر أسود، والزر الأسود يختفي فوقه تماماً
         وهو أكثر موضع يحتاجه المستخدم فيه. --}}
    <button type="button"
            data-to-top
            aria-label="{{ __('common.back_to_top') }}"
            class="to-top fixed bottom-7 end-7 z-40 grid size-13 place-items-center rounded-lg
                   bg-mint text-primary shadow-card transition
                   hover:bg-primary hover:text-mint">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 19V5M5 12l7-7 7 7"/>
        </svg>
    </button>

</body>
</html>
