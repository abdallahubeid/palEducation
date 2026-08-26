@php
    $roleLabels = ['student' => 'طالب', 'teacher' => 'معلّم', 'admin' => 'أدمن'];
@endphp

<x-layouts.admin title="الأدوار والصلاحيات" breadcrumbs="الإعدادات / الأدوار">

    <x-admin.page-head
        title="الأدوار والصلاحيات"
        lede="ثلاثة أدوار ثابتة تقابل أنواع الحسابات. ما يُدار هنا هو صلاحيات كل دور." />

    <x-admin.table class="mt-6" :headers="[
        ['label' => 'الدور'],
        ['label' => 'الصلاحيات', 'width' => 'w-48'],
        ['label' => 'إجراءات', 'align' => 'end'],
    ]">
        @foreach ($roles as $role)
            <tr class="transition hover:bg-surface/60">

                <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $loop->iteration }}</td>

                <td class="px-4 py-3">
                    <p class="font-semibold text-ink">{{ $roleLabels[$role->name] ?? $role->name }}</p>
                    {{-- الاسم البرمجي LTR: مفاتيح النظام لاتينية دائماً --}}
                    <p class="mt-0.5 text-micro text-stone" dir="ltr">{{ $role->name }}</p>
                </td>

                <td class="px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        {{-- شريط تقدّم صغير يُقرأ أسرع من رقمين --}}
                        <span class="h-1.5 w-20 overflow-hidden rounded-full bg-surface">
                            <span class="block h-full rounded-full bg-accent"
                                  style="width: {{ $permissionCount ? round($role->permissions_count / $permissionCount * 100) : 0 }}%"></span>
                        </span>
                        <span class="text-caption tabular-nums text-stone">
                            {{ $role->permissions_count }} / {{ $permissionCount }}
                        </span>
                    </div>
                </td>

                <td class="px-4 py-3">
                    <div class="flex items-center justify-end">
                        <x-admin.btn :href="route('admin.roles.edit', $role)" variant="secondary">
                            إدارة الصلاحيات
                        </x-admin.btn>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

</x-layouts.admin>
