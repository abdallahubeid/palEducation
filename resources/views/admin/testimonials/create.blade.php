<x-layouts.admin title="رأي جديد" breadcrumbs="محتوى الصفحة الرئيسية / آراء الطلبة / إضافة">

    <x-admin.page-head title="رأي جديد" lede="سيظهر في قسم آراء الطلبة حسب ترتيبه.">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.testimonials.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.testimonials._form', [
        'testimonial' => $testimonial,
        'students' => $students,
        'action' => route('admin.testimonials.store'),
        'method' => 'POST',
    ])

</x-layouts.admin>
