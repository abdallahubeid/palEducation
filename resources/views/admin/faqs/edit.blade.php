<x-layouts.admin title="تعديل السؤال" breadcrumbs="محتوى الصفحة الرئيسية / الأسئلة الشائعة / تعديل">

    <x-admin.page-head title="تعديل السؤال" :lede="$faq->question">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.faqs.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.faqs._form', [
        'faq' => $faq,
        'action' => route('admin.faqs.update', $faq),
        'method' => 'PUT',
    ])

</x-layouts.admin>
