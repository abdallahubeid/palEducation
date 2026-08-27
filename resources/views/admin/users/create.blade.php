<x-layouts.admin title="مستخدم جديد" breadcrumbs="النظام / المستخدمون / إضافة">

    <x-admin.page-head title="مستخدم جديد" lede="النوع يُختار الآن ولا يُغيَّر بعد الإنشاء.">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.users.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.users._form', [
        'user' => $user ?? new App\Models\User(['is_active' => true]),
        'types' => $types,
        'branches' => $branches,
        'action' => route('admin.users.store'),
        'method' => 'POST',
    ])

</x-layouts.admin>
