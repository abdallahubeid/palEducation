@php
    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
@endphp

<x-layouts.admin title="دور جديد" breadcrumbs="النظام / الأدوار / إضافة">

    <x-admin.page-head title="دور جديد" lede="اسم برمجي وصلاحيات. يمكن تعديل الصلاحيات لاحقاً في أي وقت.">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.roles.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    <form method="POST" action="{{ route('admin.roles.store') }}" class="mt-6 space-y-6">
        @csrf

        <div class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6">
            <label for="name" class="{{ $labelClass }}">
                اسم الدور <span class="text-error-deep">*</span>
            </label>
            {{-- dir=ltr على الحقل وحده: اسم الدور مفتاح نظام لاتيني --}}
            <input type="text" id="name" name="name" dir="ltr" required
                   placeholder="content-editor"
                   value="{{ old('name') }}" class="{{ $inputClass }} max-w-sm">
            <p class="mt-1.5 text-micro text-stone">
                بالإنجليزية الصغيرة والشرطات فقط. لا يظهر للمستخدم — هو مفتاح نظام.
            </p>
        </div>

        {{-- وضع النموذج: لا مسار حفظ فوري بعد (الدور لم يُنشأ)، فالخانات
             تُرسَل مع النموذج. --}}
        <x-admin.permission-grid :groups="$groups" :assigned="old('permissions', [])" />

        <div class="flex items-center justify-end gap-3">
            <x-admin.btn :href="route('admin.roles.index')" variant="ghost">إلغاء</x-admin.btn>
            <x-admin.btn variant="primary">إنشاء الدور</x-admin.btn>
        </div>
    </form>

</x-layouts.admin>
