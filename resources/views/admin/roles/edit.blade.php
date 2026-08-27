@php
    $labels = ['student' => 'طالب', 'teacher' => 'معلّم', 'admin' => 'أدمن'];
    $roleLabel = $labels[$role->name] ?? $role->name;

    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25 disabled:bg-surface disabled:text-stone';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
@endphp

<x-layouts.admin :title="'صلاحيات ' . $roleLabel" breadcrumbs="النظام / الأدوار / تعديل">

    <x-admin.page-head :title="'صلاحيات دور: ' . $roleLabel">
        <x-slot:actions>
            <x-admin.btn :href="route('admin.roles.index')" variant="secondary">
                <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                رجوع
            </x-admin.btn>
        </x-slot:actions>
    </x-admin.page-head>

    {{-- ═══ اسم الدور ═══ --}}
    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="mt-6">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-hairline bg-canvas p-5 sm:p-6">
            <label for="name" class="{{ $labelClass }}">اسم الدور</label>

            <div class="flex flex-wrap items-start gap-3">
                {{-- 🔴 اسم الدور النظامي معطّل: User::roleName() تشتقّ الدور من
                     userable_type بالأسماء الثلاثة حرفياً، فتغيير الاسم يقطع
                     الاشتقاق ويترك كل حساب بلا صلاحيات بصمت. --}}
                <input type="text" id="name" name="name" dir="ltr"
                       value="{{ old('name', $role->name) }}"
                       @disabled($isSystem)
                       class="{{ $inputClass }} max-w-sm">

                @unless ($isSystem)
                    <x-admin.btn variant="secondary">حفظ الاسم</x-admin.btn>
                @endunless
            </div>

            @if ($isSystem)
                <p class="mt-2 inline-flex items-center gap-1.5 text-micro text-warn-deep">
                    <x-icon name="lock" class="size-3.5" />
                    دور نظامي — اسمه ثابت لأن اشتقاق الدور من نوع الحساب يعتمد عليه.
                </p>
            @endif
        </div>
    </form>

    {{-- ═══ الصلاحيات ═══ --}}
    <div class="mt-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h3 class="text-ui font-bold text-ink">الصلاحيات</h3>
        </div>

        {{-- حفظ فوري: كل تبديل يُرسَل في الخلفية بلا إعادة تحميل. --}}
        <x-admin.permission-grid
            :groups="$groups"
            :assigned="$assigned"
            :locked="$locked"
            :toggle-url="route('admin.roles.permissions.toggle', $role)"
            :group-url="route('admin.roles.permissions.group', $role)" />
    </div>

</x-layouts.admin>
