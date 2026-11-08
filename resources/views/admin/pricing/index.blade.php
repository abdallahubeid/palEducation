<x-layouts.admin title="خطط الأسعار" breadcrumbs="محتوى الصفحة الرئيسية / الأسعار">

    <x-admin.page-head
        title="خطط الأسعار"
        lede="بطاقات التسعير المعروضة في قسم الأسعار، بالترتيب.">
        <x-slot:actions>
            @can('create-pricings')
                <x-admin.btn :href="route('admin.pricing.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    خطة جديدة
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    {{-- 🔴 Standing reminder, not decoration. These rows are the offer on
         the page; what a student actually bought will live in
         `subscriptions` (phase 8). Editing a price here changes what is
         advertised, never what somebody already paid. --}}
    <div class="mt-6 flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
        <x-icon name="alert" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
        <p class="text-caption text-warn-deep">
            هذه أسعار معروضة للزوّار. تعديلها لا يمسّ أي اشتراك قائم.
        </p>
    </div>

    @if ($plans->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="wallet" class="size-5" />
            </span>
            <p class="mt-4 text-ui font-semibold text-ink">لا توجد خطط بعد</p>
            <p class="mt-1 text-caption text-stone">أضف أول خطة لتظهر في قسم الأسعار.</p>
        </div>
    @else
        @php
            // $loop->iteration restarts at 1 on every page; the offset keeps
            // the numbering continuous across pages.
            $offset = $plans->firstItem() - 1;
        @endphp

        <x-admin.table class="mt-6" :headers="[
            ['label' => 'الخطة'],
            ['label' => 'السعر', 'width' => 'w-28'],
            ['label' => 'المزايا', 'width' => 'w-20'],
            ['label' => 'الترتيب', 'width' => 'w-20'],
            ['label' => 'الحالة', 'width' => 'w-28'],
            ['label' => 'إجراءات', 'align' => 'end'],
        ]">
            @foreach ($plans as $plan)
                <tr class="transition hover:bg-surface/60">

                    <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                    <td class="px-4 py-3">
                        <div class="flex items-start gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $plan->name }}</p>
                                @if ($plan->badge)
                                    <p class="mt-0.5 text-micro text-stone">{{ $plan->badge }}</p>
                                @endif
                            </div>

                            @if ($plan->isHighlighted())
                                <span class="shrink-0 rounded-full bg-accent/10 px-2 py-0.5 text-micro font-semibold text-accent-deep">
                                    {{ $plan->highlight_badge }}
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Prices are always Western numerals with tabular-nums,
                         so figures line up down the column. --}}
                    <td class="px-4 py-3">
                        <span class="num font-semibold text-ink">{{ $plan->price }}</span>
                        <span class="text-micro text-stone">{{ $plan->period }}</span>
                    </td>

                    <td class="px-4 py-3 tabular-nums text-stone">{{ count($plan->featureList()) }}</td>

                    <td class="px-4 py-3 tabular-nums text-stone">{{ $plan->display_order }}</td>

                    <td class="px-4 py-3">
                        @can('edit-pricings')
                            <form method="POST" action="{{ route('admin.pricing.toggle', $plan) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="تبديل الظهور"
                                        class="cursor-pointer rounded-md transition hover:opacity-70">
                                    <x-admin.status-badge :status="$plan->is_active ? 'active' : 'hidden'" />
                                </button>
                            </form>
                        @else
                            <x-admin.status-badge :status="$plan->is_active ? 'active' : 'hidden'" />
                        @endcan
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @can('edit-pricings')
                                <x-admin.btn :href="route('admin.pricing.edit', $plan)" variant="secondary">
                                    تعديل
                                </x-admin.btn>
                            @endcan

                            @can('delete-pricings')
                                {{-- 🔴 POST, never a link: a GET route fires on
                                     browser prefetch or a passing crawler. --}}
                                <form method="POST" action="{{ route('admin.pricing.destroy', $plan) }}"
                                      data-confirm-title="حذف خطة «{{ $plan->name }}»؟"
                                      data-confirm="تنتقل الخطة إلى سلّة المحذوفات ويمكن استرجاعها. الاشتراكات القائمة لا تتأثّر."
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

        <div class="mt-4">{{ $plans->links() }}</div>
    @endif

</x-layouts.admin>
