<x-layouts.admin title="خطة جديدة" breadcrumbs="محتوى الصفحة الرئيسية / الأسعار / إضافة">

    <x-admin.page-head
        title="خطة جديدة"
        lede="ستظهر ضمن بطاقات الأسعار في الصفحة الرئيسية حسب ترتيبها.">
        <x-slot:actions>
            {{-- Back sits in the page head: a clear exit before any input,
                 not buried under a long form. --}}
            <x-admin.btn :href="route('admin.pricing.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.pricing._form', [
        'plan' => $plan,
        'action' => route('admin.pricing.store'),
        'method' => 'POST',
    ])

</x-layouts.admin>
