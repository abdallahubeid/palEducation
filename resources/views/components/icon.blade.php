@props(['name' => 'circle'])

{{--
    أيقونات خطية موحّدة السماكة (1.75) — لا emoji إطلاقاً.
    الأيقونات الاتجاهية تنعكس مع RTL عبر rtl:-scale-x-100 عند الاستدعاء.
--}}

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
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }}
     viewBox="0 0 24 24"
     fill="none"
     stroke="currentColor"
     stroke-width="1.75"
     stroke-linecap="round"
     stroke-linejoin="round"
     aria-hidden="true">
    {!! $paths[$name] ?? $paths['circle'] !!}
</svg>
