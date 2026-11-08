<x-layouts.admin title="فرع جديد" breadcrumbs="المحتوى الأكاديمي / الفروع / إضافة">

    <x-admin.page-head
        title="فرع جديد"
        lede="سيظهر ضمن بطاقات الفروع في الصفحة الرئيسية حسب ترتيبه.">
        <x-slot:actions>
            {{-- Back sits in the page head: a clear exit before any input,
                 not buried under a long form. --}}
            <x-admin.btn :href="route('admin.branches.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.branches._form', [
        'branch' => $branch,
        'action' => route('admin.branches.store'),
        'method' => 'POST',
    ])

</x-layouts.admin>
