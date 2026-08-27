@php
    use App\Support\PermissionCatalog;

    $labels = ['student' => 'طالب', 'teacher' => 'معلّم', 'admin' => 'أدمن'];
@endphp

<x-layouts.admin title="الأدوار والصلاحيات" breadcrumbs="النظام / الأدوار">

    <x-admin.page-head
        title="الأدوار والصلاحيات"
        lede="الأدوار الثلاثة النظامية ثابتة، ويمكن إنشاء أدوار إضافية وتخصيص صلاحياتها.">
        <x-slot:actions>
            @can('create-roles')
                <x-admin.btn :href="route('admin.roles.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    دور جديد
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    <x-admin.table class="mt-6" :headers="[
        ['label' => 'الدور'],
        ['label' => 'الصلاحيات', 'width' => 'w-48'],
        ['label' => 'المستخدمون', 'width' => 'w-28'],
        ['label' => 'إجراءات', 'align' => 'end'],
    ]">
        @foreach ($roles as $role)
            @php $isSystem = PermissionCatalog::isSystemRole($role->name); @endphp

            <tr class="transition hover:bg-surface/60">

                <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $loop->iteration }}</td>

                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-ink">{{ $labels[$role->name] ?? $role->name }}</p>
                        @if ($isSystem)
                            <x-admin.status-badge status="draft" label="نظامي" />
                        @endif
                    </div>
                    {{-- الاسم البرمجي LTR: مفاتيح النظام لاتينية دائماً --}}
                    <p class="mt-0.5 text-micro text-stone" dir="ltr">{{ $role->name }}</p>
                </td>

                <td class="px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        {{-- شريط تقدّم يُقرأ أسرع من رقمين --}}
                        <span class="h-1.5 w-20 overflow-hidden rounded-full bg-surface">
                            <span class="block h-full rounded-full bg-accent"
                                  style="width: {{ $permissionCount ? round($role->permissions_count / $permissionCount * 100) : 0 }}%"></span>
                        </span>
                        <span class="text-caption tabular-nums text-stone">
                            {{ $role->permissions_count }} / {{ $permissionCount }}
                        </span>
                    </div>
                </td>

                <td class="px-4 py-3 tabular-nums text-stone">{{ $role->users_count }}</td>

                <td class="px-4 py-3">
                    <div class="flex items-center justify-end gap-2">
                        @can('manage-roles')
                            <x-admin.btn :href="route('admin.roles.edit', $role)" variant="secondary">
                                الصلاحيات
                            </x-admin.btn>
                        @endcan

                        {{-- 🔴 الأدوار النظامية بلا زرّ حذف إطلاقاً: حذف
                             student أو teacher أو admin يقطع اشتقاق الدور من
                             userable_type ويترك كل حساب بلا صلاحيات. --}}
                        @if (! $isSystem)
                            @can('delete-roles')
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                      onsubmit="return confirm('حذف الدور «{{ $role->name }}»؟ لا يمكن التراجع.')">
                                    @csrf
                                    @method('DELETE')
                                    <x-admin.btn variant="danger">حذف</x-admin.btn>
                                </form>
                            @endcan
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

</x-layouts.admin>
