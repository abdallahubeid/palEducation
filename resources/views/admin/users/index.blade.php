<x-layouts.admin title="المستخدمون" breadcrumbs="النظام / المستخدمون">

    <x-admin.page-head
        title="المستخدمون"
        lede="حسابات المنصّة: طلبة ومعلّمون وأدمن.">
        <x-slot:actions>
            @can('create-users')
                <x-admin.btn :href="route('admin.users.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    مستخدم جديد
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    {{-- تبويبات النوع --}}
    <div class="mt-6 flex flex-wrap gap-2">
        <a href="{{ route('admin.users.index') }}"
           @class([
               'rounded-lg px-3 py-1.5 text-caption font-semibold transition',
               'bg-accent text-on-primary' => blank($activeType),
               'bg-surface text-steel hover:bg-hairline' => filled($activeType),
           ])>
            الكل <span class="tabular-nums">({{ array_sum($counts) }})</span>
        </a>

        @foreach ($types as $key => $type)
            <a href="{{ route('admin.users.index', ['type' => $key]) }}"
               @class([
                   'rounded-lg px-3 py-1.5 text-caption font-semibold transition',
                   'bg-accent text-on-primary' => $activeType === $key,
                   'bg-surface text-steel hover:bg-hairline' => $activeType !== $key,
               ])>
                {{ $type['label'] }} <span class="tabular-nums">({{ $counts[$key] ?? 0 }})</span>
            </a>
        @endforeach
    </div>

    @if ($users->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="users" class="size-5" />
            </span>
            <p class="mt-4 text-ui font-semibold text-ink">لا يوجد مستخدمون في هذا القسم</p>
        </div>
    @else
        @php
            // 🔴 $loop->iteration يبدأ من 1 في كل صفحة. الإزاحة تجعل الترقيم
            // متّصلاً عبر الصفحات.
            $offset = $users->firstItem() - 1;
        @endphp

        <x-admin.table class="mt-6" :headers="[
            ['label' => 'الاسم'],
            ['label' => 'البريد'],
            ['label' => 'النوع', 'width' => 'w-28'],
            ['label' => 'الحالة', 'width' => 'w-28'],
            ['label' => 'إجراءات', 'align' => 'end'],
        ]">
            @foreach ($users as $user)
                <tr class="transition hover:bg-surface/60">

                    <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-accent-soft
                                         text-caption font-bold text-accent-deep">
                                {{ mb_substr($user->name ?: '؟', 0, 1) }}
                            </span>
                            <p class="min-w-0 truncate font-semibold text-ink">{{ $user->name }}</p>
                        </div>
                    </td>

                    {{-- البريد LTR دائماً ولو كانت الصفحة RTL --}}
                    <td class="px-4 py-3">
                        <span class="text-caption text-stone" dir="ltr">{{ $user->email }}</span>
                    </td>

                    <td class="px-4 py-3">
                        <x-admin.status-badge status="neutral" :label="$types[$user->roleName()]['label'] ?? '—'" />
                    </td>

                    <td class="px-4 py-3">
                        @can('edit-users')
                            <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="تبديل التفعيل"
                                        class="cursor-pointer rounded-md transition hover:opacity-70">
                                    <x-admin.status-badge
                                        :status="$user->is_active ? 'active' : 'hidden'"
                                        :label="$user->is_active ? 'مفعّل' : 'معطّل'" />
                                </button>
                            </form>
                        @else
                            <x-admin.status-badge
                                :status="$user->is_active ? 'active' : 'hidden'"
                                :label="$user->is_active ? 'مفعّل' : 'معطّل'" />
                        @endcan
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @can('edit-users')
                                <x-admin.btn :href="route('admin.users.edit', $user)" variant="secondary">
                                    تعديل
                                </x-admin.btn>
                            @endcan

                            {{-- 🔴 لا زرّ حذف على حسابك: الحارس خادمي أيضاً،
                                 وهذا يمنع النقرة قبل أن تصل. --}}
                            @can('delete-users')
                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                          onsubmit="return confirm('حذف حساب «{{ $user->name }}»؟ ينتقل إلى سلّة المحذوفات.')">
                                        @csrf
                                        @method('DELETE')
                                        <x-admin.btn variant="danger">حذف</x-admin.btn>
                                    </form>
                                @endunless
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        <div class="mt-4">{{ $users->links() }}</div>
    @endif

</x-layouts.admin>
