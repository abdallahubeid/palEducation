<x-layouts.admin title="تعديل الخطة" breadcrumbs="محتوى الصفحة الرئيسية / الأسعار / تعديل">

    <x-admin.page-head title="تعديل الخطة" :lede="$plan->name">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.pricing.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.pricing._form', [
        'plan' => $plan,
        'action' => route('admin.pricing.update', $plan),
        'method' => 'PUT',
    ])

</x-layouts.admin>
