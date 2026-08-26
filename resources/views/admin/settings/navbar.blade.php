@php
    /*
     * إعدادات الشريط العلوي — داخل غلاف تبويبات، بنموذج عمودي بعرض كامل.
     *
     * 🔴 لا `max-w-*` ولا شبكة شرح/حقول: كلاهما كان يترك جزءاً كبيراً من
     * اللوح فارغاً. الحقول تملأ عرض البطاقة، والبطاقة تملأ عرض المحتوى.
     */
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';

    $fields = [
        ['key' => 'social_link1', 'label' => 'الرابط الأول', 'placeholder' => 'https://facebook.com/…'],
        ['key' => 'social_link2', 'label' => 'الرابط الثاني', 'placeholder' => 'https://instagram.com/…'],
        ['key' => 'social_link3', 'label' => 'الرابط الثالث', 'placeholder' => 'https://youtube.com/…'],
    ];
@endphp

<x-layouts.admin title="الإعدادات" breadcrumbs="محتوى الصفحة الرئيسية / الإعدادات">

    <x-admin.page-head
        title="الإعدادات"
        lede="إعدادات محتوى الصفحة الرئيسية.">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.settings.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    {{-- شريط التبويبات — قسم واحد اليوم، والمكوّن يحتمل الزيادة بلا تعديل --}}
    <x-admin.tabs class="mt-6" :tabs="[
        ['label' => 'الشريط العلوي', 'route' => 'admin.settings.navbar', 'icon' => 'link'],
    ]" />

    <form method="POST" action="{{ route('admin.settings.navbar.update') }}" class="mt-6">
        @csrf
        @method('PUT')

        <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

            <div>
                <h3 class="text-ui font-bold text-ink">روابط التواصل</h3>
                <p class="mt-1.5 text-micro text-stone">
                    تظهر في أعلى كل صفحة عامة. اتركه فارغاً لإخفاء الأيقونة.
                </p>
            </div>

            {{-- 🔴 تذكير دائم لا زخرفة: هذه القيم تُقرأ علناً، وجدول settings
                 لا يملك عمود is_public بعد يميّز العام من الخاص. --}}
            <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
                <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
                <p class="text-caption text-warn-deep">هذه الروابط تظهر لكل زائر، حتى غير المسجّل.</p>
            </div>

            @foreach ($fields as $field)
                <div>
                    <label for="{{ $field['key'] }}" class="{{ $labelClass }}">{{ $field['label'] }}</label>

                    {{-- dir=ltr على الحقل وحده: الرابط لاتيني، والتسمية عربية --}}
                    <input type="url"
                           id="{{ $field['key'] }}"
                           name="{{ $field['key'] }}"
                           dir="ltr"
                           placeholder="{{ $field['placeholder'] }}"
                           value="{{ old($field['key'], $settings['navbar.'.$field['key']] ?? null) }}"
                           class="{{ $inputClass }}">
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <x-admin.btn :href="route('admin.settings.index')" variant="ghost">إلغاء</x-admin.btn>
            <x-admin.btn variant="primary">حفظ التغييرات</x-admin.btn>
        </div>
    </form>

</x-layouts.admin>
