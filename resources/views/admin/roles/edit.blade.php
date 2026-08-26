@php
    $roleLabels = ['student' => 'طالب', 'teacher' => 'معلّم', 'admin' => 'أدمن'];
    $roleLabel = $roleLabels[$role->name] ?? $role->name;

    $permissionLabels = [
        'view-sliders' => 'عرض الشرائح',
        'create-sliders' => 'إنشاء شريحة',
        'edit-sliders' => 'تعديل شريحة',
        'delete-sliders' => 'حذف شريحة',
        'manage-settings' => 'إدارة الإعدادات',
        'manage-roles' => 'إدارة الأدوار والصلاحيات',
    ];
@endphp

<x-layouts.admin :title="'صلاحيات ' . $roleLabel" breadcrumbs="الإعدادات / الأدوار / تعديل">

    <x-admin.page-head
        :title="'صلاحيات دور: ' . $roleLabel"
        lede="التبديل يُحفظ فوراً. زرّ الحفظ للحالات التي يكون فيها JavaScript معطّلاً.">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.roles.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع للقائمة
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    {{--
        🔴 النموذج يعمل بلا JavaScript إطلاقاً: خانات اختيار داخل <form> وزرّ
        حفظ يزامنها دفعة واحدة. الـJS يرقّي هذا إلى تبديل فوري لكل مفتاح على
        حدة ولا يخلق الوظيفة — إطفاء JS يُبطئ الشاشة ولا يعطّلها.

        ⚠️ ليس Alpine: Livewire 3 غير مثبّت وAlpine يأتي مضمّناً معه، فأي
        x-data هنا لن يعمل. vanilla عبر سمات data-* كبقية المشروع.
    --}}
    <form method="POST"
          action="{{ route('admin.roles.update', $role) }}"
          data-role-permissions
          data-toggle-url="{{ route('admin.roles.permissions.toggle', $role) }}"
          class="mt-6 max-w-3xl">
        @csrf
        @method('PUT')

        <div class="divide-y divide-hairline rounded-xl border border-hairline bg-canvas">
            @foreach ($permissions as $permission)
                @php
                    $isLocked = in_array($permission->name, $locked, true);
                    $isOn = in_array($permission->name, $assigned, true);
                @endphp

                <div class="flex items-center justify-between gap-4 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-ui text-ink">{{ $permissionLabels[$permission->name] ?? $permission->name }}</p>
                        <p class="mt-0.5 text-micro text-stone" dir="ltr">{{ $permission->name }}</p>

                        @if ($isLocked)
                            <p class="mt-1.5 inline-flex items-center gap-1.5 text-micro text-warn-deep">
                                <x-icon name="lock" class="size-3.5" />
                                مقفلة — نزعها يُغلق هذه الشاشة على كل أدمن
                            </p>
                        @endif
                    </div>

                    {{--
                        مفتاح تبديل — مكتوب خاماً لا عبر ui/toggle: نحتاج value
                        و data-permission و disabled، وثلاثتها ليست في @props
                        المكوّن فكانت ستتسرّب إلى الغلاف بلا أثر (فخّ مسجَّل).
                    --}}
                    <label @class([
                        'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition',
                        'cursor-pointer bg-hairline-strong has-checked:bg-accent' => ! $isLocked,
                        'cursor-not-allowed bg-accent/40' => $isLocked,
                    ])>
                        <span class="sr-only">{{ $permissionLabels[$permission->name] ?? $permission->name }}</span>

                        <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                               data-permission="{{ $permission->name }}"
                               @checked($isOn || $isLocked) @disabled($isLocked)
                               class="peer sr-only">

                        {{-- الانزلاق عبر start المنطقية لا translate: تنعكس مع
                             dir تلقائياً بلا صنف rtl إضافي. --}}
                        <span class="absolute inset-y-0.5 start-0.5 size-5 rounded-full bg-canvas shadow-subtle
                                     transition-[inset-inline-start] peer-checked:start-[1.375rem]"></span>
                    </label>

                    {{-- المفتاح المعطّل لا يُرسِل قيمته، فالحفظ الجماعي كان سينزع
                         المقفلة. حقل مخفي يحملها. (الخادم يفرضها ثانيةً.) --}}
                    @if ($isLocked)
                        <input type="hidden" name="permissions[]" value="{{ $permission->name }}">
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <x-admin.btn variant="primary">حفظ كل الصلاحيات</x-admin.btn>
        </div>
    </form>

</x-layouts.admin>
