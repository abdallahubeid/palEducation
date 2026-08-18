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

    <div data-shell class="lg:flex">
        <x-layout.sidebar />

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <x-layout.topbar
                :student-name="$studentName ?? ''"
                :student-avatar="$studentAvatar ?? null"
                :subscription-state="$subscriptionState ?? 'active'"
                :unread-count="$unreadCount ?? 0" />

            <main id="main" class="flex-1 px-4 py-6 lg:px-8 lg:py-8">
                {{ $slot }}
            </main>
        </div>
    </div>

</body>
</html>
