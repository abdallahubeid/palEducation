<x-layouts.admin title="ميزة جديدة" breadcrumbs="محتوى الصفحة الرئيسية / المميزات / إضافة">

    <x-admin.page-head
        title="ميزة جديدة"
        lede="ستظهر ضمن بطاقات «ما يميّزنا» حسب ترتيبها.">
        <x-slot:actions>
            {{-- Back sits in the page head: a clear exit before any input,
                 not buried under a long form. --}}
            <x-admin.btn :href="route('admin.features.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.features._form', [
        'feature' => $feature,
        'action' => route('admin.features.store'),
        'method' => 'POST',
    ])

</x-layouts.admin>
