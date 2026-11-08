<x-layouts.admin title="تعديل الرأي" breadcrumbs="محتوى الصفحة الرئيسية / آراء الطلبة / تعديل">

    <x-admin.page-head title="تعديل الرأي" :lede="$testimonial->displayName()">
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
        'action' => route('admin.testimonials.update', $testimonial),
        'method' => 'PUT',
    ])

</x-layouts.admin>
