@php
    /*
     * نظرة عامة على الإعدادات — قائمة أقسام بعرض كامل لا شبكة بطاقات
     * معزولة. مصفوفة واحدة: إضافة قسم لاحقاً = صفّ هنا لا تخطيط جديد.
     */
    $sections = [
        [
            'label' => 'الشريط العلوي',
            'description' => 'روابط التواصل التي تظهر في أعلى كل صفحة عامة.',
            'route' => 'admin.settings.navbar',
            'icon' => 'link',
            'status' => $navbarFilled.' / '.$navbarTotal.' روابط',
            'filled' => $navbarFilled > 0,
        ],
    ];
@endphp

<x-layouts.admin title="الإعدادات" breadcrumbs="محتوى الصفحة الرئيسية / الإعدادات">

    <x-admin.page-head
        title="الإعدادات"
        lede="أقسام إعدادات محتوى الصفحة الرئيسية. اختر قسماً لتحريره." />

    <div class="mt-6 divide-y divide-hairline overflow-hidden rounded-xl border border-hairline bg-canvas">
        @foreach ($sections as $section)
            <a href="{{ route($section['route']) }}"
               class="group flex items-center gap-4 px-5 py-5 transition hover:bg-surface/60">

                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-accent/12 text-accent">
                    <x-icon :name="$section['icon']" class="size-5" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-ui font-bold text-ink">{{ $section['label'] }}</p>
                    <p class="mt-1 text-caption text-stone">{{ $section['description'] }}</p>
                </div>

                <x-admin.status-badge
                    :status="$section['filled'] ? 'active' : 'hidden'"
                    :label="$section['status']"
                    class="hidden shrink-0 sm:inline-flex" />

                {{-- سهم اتجاهي — ينعكس مع dir --}}
                <x-icon name="arrow"
                        class="size-4 shrink-0 text-stone transition group-hover:text-accent rtl:-scale-x-100" />
            </a>
        @endforeach
    </div>

</x-layouts.admin>
