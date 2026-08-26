<x-layouts.admin title="تعديل الشريحة" breadcrumbs="محتوى الصفحة الرئيسية / الهيرو / تعديل">

    <x-admin.page-head title="تعديل الشريحة" :lede="$slider->title">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.sliders.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.sliders._form', [
        'slider' => $slider,
        'action' => route('admin.sliders.update', $slider),
        'method' => 'PUT',
    ])

</x-layouts.admin>
