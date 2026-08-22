@props([
    'title'         => null,
    'teacherName'   => 'أ. سامر خليل',
    'teacherAvatar' => null,
    'subjectLabel'  => null,
    'unreadCount'   => 3,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ $title ? $title . ' — ' . __('common.brand') : __('common.brand') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ground text-charcoal antialiased">

    {{--
        قشرة المعلم — سطح أداتي (tool-like) لا سطح قراءة:
        شريط جانبي أضيق (60 مقابل 64)، شريط علوي أقصر، ومساحات داخلية أقل
        من قشرة الطالب. الطالب يقرأ هنا ساعات؛ المعلم يمسح ويتصرّف.

        data-shell هو هدف أصناف الحالة (is-collapsed) — نفس عقد قشرة الطالب.
    --}}
    <div data-shell class="lg:flex">
        <x-layout.teacher-sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-layout.teacher-topbar
                :teacher-name="$teacherName"
                :teacher-avatar="$teacherAvatar"
                :subject-label="$subjectLabel"
                :unread-count="$unreadCount" />

            <main class="flex-1 px-4 py-6 lg:px-6 lg:py-8">
                {{ $slot }}
            </main>
        </div>
    </div>

</body>
</html>
