<x-layouts.admin title="تعديل المستخدم" breadcrumbs="النظام / المستخدمون / تعديل">

    <x-admin.page-head title="تعديل المستخدم" :lede="$user->name">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.users.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    @include('admin.users._form', [
        'user' => $user,
        'types' => $types,
        'branches' => $branches,
        'action' => route('admin.users.update', $user),
        'method' => 'PUT',
    ])

</x-layouts.admin>
