<x-layouts.admin title="الأسئلة الشائعة" breadcrumbs="محتوى الصفحة الرئيسية / الأسئلة الشائعة">

    <x-admin.page-head
        title="الأسئلة الشائعة"
        lede="الأسئلة المعروضة في قسم «أسئلة تدور في ذهن كل طالب» على الصفحة الرئيسية.">
        <x-slot:actions>
            @can('create-faqs')
                <x-admin.btn :href="route('admin.faqs.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    سؤال جديد
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    @if ($faqs->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="alert" class="size-5" />
            </span>
            <p class="mt-4 text-ui font-semibold text-ink">لا توجد أسئلة بعد</p>
            <p class="mt-1 text-caption text-stone">أضف أول سؤال ليظهر في الصفحة الرئيسية.</p>
        </div>
    @else
        @php
            // $loop->iteration restarts at 1 on every page; the offset keeps
            // the numbering continuous across pages.
            $offset = $faqs->firstItem() - 1;
        @endphp

        <x-admin.table class="mt-6" :headers="[
            ['label' => 'السؤال'],
            ['label' => 'الإجابة'],
            ['label' => 'الترتيب', 'width' => 'w-20'],
            ['label' => 'الحالة', 'width' => 'w-28'],
            ['label' => 'إجراءات', 'align' => 'end'],
        ]">
            @foreach ($faqs as $faq)
                <tr class="transition hover:bg-surface/60">

                    <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                    <td class="px-4 py-3">
                        <p class="max-w-sm font-semibold text-ink">{{ $faq->question }}</p>
                    </td>

                    <td class="px-4 py-3">
                        {{-- Clamped: a full answer is a paragraph and would
                             make every row a different height. --}}
                        <p class="line-clamp-2 max-w-md text-caption text-steel">{{ $faq->answer }}</p>
                    </td>

                    <td class="px-4 py-3 tabular-nums text-stone">{{ $faq->display_order }}</td>

                    <td class="px-4 py-3">
                        @can('edit-faqs')
                            <form method="POST" action="{{ route('admin.faqs.toggle', $faq) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="تبديل الظهور"
                                        class="cursor-pointer rounded-md transition hover:opacity-70">
                                    <x-admin.status-badge :status="$faq->is_active ? 'active' : 'hidden'" />
                                </button>
                            </form>
                        @else
                            <x-admin.status-badge :status="$faq->is_active ? 'active' : 'hidden'" />
                        @endcan
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @can('edit-faqs')
                                <x-admin.btn :href="route('admin.faqs.edit', $faq)" variant="secondary">
                                    تعديل
                                </x-admin.btn>
                            @endcan

                            @can('delete-faqs')
                                {{-- 🔴 POST, never a link: a GET route fires on
                                     browser prefetch or a passing crawler. --}}
                                <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}"
                                      data-confirm-title="حذف هذا السؤال؟"
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

        <div class="mt-4">{{ $faqs->links() }}</div>
    @endif

</x-layouts.admin>
