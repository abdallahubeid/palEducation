<x-layouts.admin title="سؤال جديد" breadcrumbs="محتوى الصفحة الرئيسية / الأسئلة الشائعة / إضافة">

    <x-admin.page-head title="سؤال جديد" lede="سيظهر في قسم الأسئلة الشائعة حسب ترتيبه.">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.faqs.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.faqs._form', [
        'faq' => $faq,
        'action' => route('admin.faqs.store'),
        'method' => 'POST',
    ])

</x-layouts.admin>
