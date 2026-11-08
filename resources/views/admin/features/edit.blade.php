<x-layouts.admin title="تعديل الميزة" breadcrumbs="محتوى الصفحة الرئيسية / المميزات / تعديل">

    <x-admin.page-head title="تعديل الميزة" :lede="$feature->title">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.features.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.features._form', [
        'feature' => $feature,
        'action' => route('admin.features.update', $feature),
        'method' => 'PUT',
    ])

</x-layouts.admin>
