<x-layouts.admin title="شريحة جديدة" breadcrumbs="محتوى الصفحة الرئيسية / الهيرو / إضافة">

    <x-admin.page-head
        title="شريحة جديدة"
        lede="ستظهر في أعلى الصفحة الرئيسية حسب ترتيبها.">
        <x-slot:actions>
            {{-- زرّ الرجوع في ترويسة الصفحة: مخرج واضح قبل أي إدخال، لا
                 مدفوناً أسفل نموذج طويل. --}}
            <x-admin.btn :href="route('admin.sliders.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.sliders._form', [
        'slider' => $slider,
        'action' => route('admin.sliders.store'),
        'method' => 'POST',
    ])

</x-layouts.admin>
