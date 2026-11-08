<x-layouts.admin title="المميزات" breadcrumbs="محتوى الصفحة الرئيسية / المميزات">

    <x-admin.page-head
        title="المميزات"
        lede="بطاقات قسم «ما يميّزنا» في الصفحة الرئيسية، بالترتيب.">
        <x-slot:actions>
            @can('create-features')
                <x-admin.btn :href="route('admin.features.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    ميزة جديدة
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    @if ($features->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="sparkle" class="size-5" />
            </span>
            <p class="mt-4 text-ui font-semibold text-ink">لا توجد مميزات بعد</p>
            <p class="mt-1 text-caption text-stone">أضف أول بطاقة لتظهر في قسم «ما يميّزنا».</p>
        </div>
    @else
        @php
            // $loop->iteration restarts at 1 on every page; the offset keeps
            // the numbering continuous across pages.
            $offset = $features->firstItem() - 1;
        @endphp

        <x-admin.table class="mt-6" :headers="[
            ['label' => 'الميزة'],
            ['label' => 'الترتيب', 'width' => 'w-20'],
            ['label' => 'الحالة', 'width' => 'w-28'],
            ['label' => 'إجراءات', 'align' => 'end'],
        ]">
            @foreach ($features as $feature)
                <tr class="transition hover:bg-surface/60">

                    <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                    <td class="px-4 py-3">
                        <div class="flex items-start gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface text-steel">
                                <x-icon :name="$feature->icon ?? 'sparkle'" class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $feature->title }}</p>
                                @if ($feature->description)
                                    <p class="mt-0.5 line-clamp-2 text-micro text-stone">{{ $feature->description }}</p>
                                @endif
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-3 tabular-nums text-stone">{{ $feature->display_order }}</td>

                    <td class="px-4 py-3">
                        @can('edit-features')
                            <form method="POST" action="{{ route('admin.features.toggle', $feature) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="تبديل الظهور"
                                        class="cursor-pointer rounded-md transition hover:opacity-70">
                                    <x-admin.status-badge :status="$feature->is_active ? 'active' : 'hidden'" />
                                </button>
                            </form>
                        @else
                            <x-admin.status-badge :status="$feature->is_active ? 'active' : 'hidden'" />
                        @endcan
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @can('edit-features')
                                <x-admin.btn :href="route('admin.features.edit', $feature)" variant="secondary">
                                    تعديل
                                </x-admin.btn>
                            @endcan

                            @can('delete-features')
                                {{-- 🔴 POST, never a link: a GET route fires on
                                     browser prefetch or a passing crawler. --}}
                                <form method="POST" action="{{ route('admin.features.destroy', $feature) }}"
                                      data-confirm-title="حذف «{{ $feature->title }}»؟"
                                      data-confirm="تنتقل البطاقة إلى سلّة المحذوفات ويمكن استرجاعها."
                                      data-confirm-action="نعم، احذفها">
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

        <div class="mt-4">{{ $features->links() }}</div>
    @endif

</x-layouts.admin>
