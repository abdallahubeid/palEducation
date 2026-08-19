@props(['name' => 'circle'])

{{--
    أيقونات خطية موحّدة السماكة (1.75) — لا emoji إطلاقاً.
    الأيقونات الاتجاهية تنعكس مع RTL عبر rtl:-scale-x-100 عند الاستدعاء.

    🔴 المقاس الافتراضي يُضاف فقط إن لم يمرّر المستدعي مقاساً.
    السبب: merge يدمج الصنفين معاً، وTailwind يرتّب size-5 بعد size-3/size-4
    في ملف الأنماط — فيفوز الافتراضي بترتيب المصدر عند تساوي الخصوصية،
    وتُعرض كل أيقونة أصغر من 5 بمقاس 20px خطأً. (نفس درس ترتيب المصدر
    المسجّل في doctrine.md — تكرّر هنا بصمت عبر 12 شاشة.)
--}}
@php
    $incomingClass = $attributes->get('class', '');
    $callerSetSize = (bool) preg_match('/(^|\s)(size|[hw])-/', $incomingClass);
    $defaultClass  = trim(($callerSetSize ? '' : 'size-5 ') . 'shrink-0');
@endphp

@php
    $paths = [
        // الفروع الدراسية
        'beaker'    => '<path d="M9 3h6M10 3v6.5L4.5 19a2 2 0 0 0 1.7 3h11.6a2 2 0 0 0 1.7-3L14 9.5V3"/><path d="M7.5 15h9"/>',
        'book'      => '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/><path d="M4 17.5A2.5 2.5 0 0 1 6.5 15H20"/>',
        'briefcase' => '<rect x="2.5" y="7" width="19" height="13" rx="2"/><path d="M8.5 7V5a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v2M2.5 12.5h19"/>',
        'wrench'    => '<path d="M14.7 6.3a4 4 0 0 0 5 5l-9.4 9.4a2.5 2.5 0 0 1-3.5-3.5z"/><path d="M14.7 6.3 18 3l3 3-3.3 3.3"/>',

        // المنصة
        'play'      => '<path d="M5.5 4.5v15l13-7.5z"/>',
        'clipboard' => '<rect x="7" y="4" width="10" height="4" rx="1"/><path d="M9 6H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3"/><path d="M8.5 13h7M8.5 16.5h4.5"/>',
        'folder'    => '<path d="M3 7.5a2 2 0 0 1 2-2h3.8a2 2 0 0 1 1.6.8l.9 1.2H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'compass'   => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5.5-5.5 2 2-5.5z"/>',
        'shield'    => '<path d="M12 2.5 4.5 5.5v6c0 4.8 3.2 8.6 7.5 10 4.3-1.4 7.5-5.2 7.5-10v-6z"/><path d="m9 12 2 2 4-4"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16.5 5.2a3.5 3.5 0 0 1 0 6.6M18 20a6.5 6.5 0 0 0-2.2-4.9"/>',

        // عامة
        'check'     => '<path d="m4.5 12.5 5 5 10-11"/>',
        'arrow'     => '<path d="M4 12h15M13 6l6 6-6 6"/>',
        'circle'    => '<circle cx="12" cy="12" r="9"/>',

        // قشرة الطالب
        'home'        => '<path d="M4 11.5 12 4l8 7.5"/><path d="M6 10v9a1 1 0 0 0 1 1h3v-6h4v6h3a1 1 0 0 0 1-1v-9"/>',
        'chart'       => '<path d="M4 20V4M4 20h16"/><rect x="7" y="12" width="3" height="6" rx=".5"/><rect x="12" y="8" width="3" height="10" rx=".5"/><rect x="17" y="15" width="3" height="3" rx=".5"/>',
        'card'        => '<rect x="2.5" y="5.5" width="19" height="13" rx="2"/><path d="M2.5 10h19"/><path d="M6 15h4"/>',
        'user'        => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
        'search'      => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m20 20-4.3-4.3"/>',
        'bell'        => '<path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 19a2 2 0 0 0 4 0"/>',
        'menu'        => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close'       => '<path d="M6 6l12 12M18 6 6 18"/>',
        'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.5 2.5L16 9.5"/>',
        'trending-up' => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 6h6v6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'download'     => '<path d="M12 4v11m0 0 4-4m-4 4-4-4"/><path d="M5 18h14"/>',

        // النماذج وحالات النظام
        'eye'       => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.75"/>',
        'eye-off'   => '<path d="M10.6 6.7A8 8 0 0 1 12 6.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-2.7 3.6"/><path d="M6.2 8.4A16.6 16.6 0 0 0 2.5 12s3.5 6.5 9.5 6.5a9 9 0 0 0 3.4-.7"/><path d="M10.1 10.1a2.75 2.75 0 0 0 3.8 3.8"/><path d="m3.5 3.5 17 17"/>',
        'lock'      => '<rect x="4.5" y="10" width="15" height="10.5" rx="2"/><path d="M8 10V7.5a4 4 0 0 1 8 0V10"/>',
        'mail'      => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="m3.5 6.5 8.5 6 8.5-6"/>',
        'alert'     => '<path d="M12 3.5 2.8 19.5h18.4z"/><path d="M12 9.5v4M12 16.8v.01"/>',
        'sparkle'   => '<path d="M12 3.5 13.9 9l5.6 1.9-5.6 1.9L12 18.5l-1.9-5.7L4.5 11 10.1 9z"/><path d="M18.5 4v3M20 5.5h-3"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $defaultClass]) }}
     viewBox="0 0 24 24"
     fill="none"
     stroke="currentColor"
     stroke-width="1.75"
     stroke-linecap="round"
     stroke-linejoin="round"
     aria-hidden="true">
    {!! $paths[$name] ?? $paths['circle'] !!}
</svg>
