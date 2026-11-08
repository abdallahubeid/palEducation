<x-layouts.admin title="الفروع" breadcrumbs="المحتوى الأكاديمي / الفروع">

    <x-admin.page-head
        title="الفروع"
        lede="الفروع الدراسية التي يختار الطالب واحداً منها، وتظهر بطاقاتها في الصفحة الرئيسية.">
        <x-slot:actions>
            @can('create-branches')
                <x-admin.btn :href="route('admin.branches.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    فرع جديد
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    @if ($branches->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="book" class="size-5" />
            </span>
            <p class="mt-4 text-ui font-semibold text-ink">لا توجد فروع بعد</p>
            <p class="mt-1 text-caption text-stone">أضف أول فرع ليتمكّن الطلبة من اختيار مسارهم.</p>
        </div>
    @else
        @php
            // $loop->iteration restarts at 1 on every page; the offset keeps
            // the numbering continuous across pages.
            $offset = $branches->firstItem() - 1;
        @endphp

        <x-admin.table class="mt-6" :headers="[
            ['label' => 'الفرع'],
            ['label' => 'المواد', 'width' => 'w-20'],
            ['label' => 'المعلّمون', 'width' => 'w-24'],
            ['label' => 'الترتيب', 'width' => 'w-20'],
            ['label' => 'الحالة', 'width' => 'w-28'],
            ['label' => 'إجراءات', 'align' => 'end'],
        ]">
            @foreach ($branches as $branch)
                <tr class="transition hover:bg-surface/60">

                    <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                    <td class="px-4 py-3">
                        <div class="flex items-start gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface text-steel">
                                <x-icon :name="$branch->icon ?? 'book'" class="size-4" />
                            </span>
                            <div>
                                <p class="font-semibold text-ink">{{ $branch->name }}</p>
                                {{-- The slug is the route key — latin, so it
                                     is isolated with bdi inside Arabic text. --}}
                                <p class="mt-0.5 text-micro text-stone"><bdi dir="ltr">{{ $branch->slug }}</bdi></p>
                            </div>
                        </div>
                    </td>

                    {{-- Derived at query time by withCardCounts(), never stored. --}}
                    <td class="px-4 py-3 tabular-nums text-stone">{{ $branch->subjects_count }}</td>
                    <td class="px-4 py-3 tabular-nums text-stone">{{ $branch->teachers_count }}</td>

                    <td class="px-4 py-3 tabular-nums text-stone">{{ $branch->display_order }}</td>

                    <td class="px-4 py-3">
                        @can('edit-branches')
                            <form method="POST" action="{{ route('admin.branches.toggle', $branch) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="تبديل الظهور"
                                        class="cursor-pointer rounded-md transition hover:opacity-70">
                                    <x-admin.status-badge :status="$branch->is_active ? 'active' : 'hidden'" />
                                </button>
                            </form>
                        @else
                            <x-admin.status-badge :status="$branch->is_active ? 'active' : 'hidden'" />
                        @endcan
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @can('edit-branches')
                                <x-admin.btn :href="route('admin.branches.edit', $branch)" variant="secondary">
                                    تعديل
                                </x-admin.btn>
                            @endcan

                            @can('delete-branches')
                                {{-- 🔴 POST, never a link: a GET route fires on
                                     browser prefetch or a passing crawler. --}}
                                <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}"
                                      data-confirm-title="حذف «{{ $branch->name }}»؟"
                                      data-confirm="ينتقل إلى سلّة المحذوفات ويمكن استرجاعه."
                                      data-confirm-action="نعم، احذفه">
                                    @csrf
                                    @method('DELETE')
                                    <x-admin.btn variant="danger">حذف</x-admin.btn>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        <div class="mt-4">{{ $branches->links() }}</div>
    @endif

</x-layouts.admin>
