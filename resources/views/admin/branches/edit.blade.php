<x-layouts.admin title="تعديل الفرع" breadcrumbs="المحتوى الأكاديمي / الفروع / تعديل">

    <x-admin.page-head title="تعديل الفرع" :lede="$branch->name">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.branches.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.branches._form', [
        'branch' => $branch,
        'action' => route('admin.branches.update', $branch),
        'method' => 'PUT',
    ])

</x-layouts.admin>
