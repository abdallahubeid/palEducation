<x-layouts.admin title="آراء الطلبة" breadcrumbs="محتوى الصفحة الرئيسية / آراء الطلبة">

    <x-admin.page-head
        title="آراء الطلبة"
        lede="الآراء المعروضة في قسم «ماذا قال من جرّبها» على الصفحة الرئيسية.">
        <x-slot:actions>
            @can('create-testimonials')
                <x-admin.btn :href="route('admin.testimonials.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    رأي جديد
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    {{--
        ═══ Status tabs ═══

        🔴 Links, not buttons: the filter belongs in the URL so a pending
        queue can be bookmarked, shared and survive a refresh — and so the
        approve action can return the admin to the tab they were on.

        The pending count carries a warn dot even when that tab is not
        selected: work waiting is the one thing this screen exists to
        surface, and it must be visible from every tab.
    --}}
    @php
        $tabs = [
            'all' => ['label' => 'الكل', 'tone' => 'text-steel'],
            'active' => ['label' => 'الظاهرة', 'tone' => 'text-accent-deep'],
            'pending' => ['label' => 'بانتظار الموافقة', 'tone' => 'text-warn-deep'],
        ];
    @endphp

    <div class="mt-6 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $tab)
            <a href="{{ route('admin.testimonials.index', $key === 'all' ? [] : ['status' => $key]) }}"
               @class([
                   'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-caption font-semibold transition',
                   'bg-accent text-on-primary' => $activeStatus === $key,
                   'bg-surface text-steel hover:bg-hairline' => $activeStatus !== $key,
               ])>
                @if ($key === 'pending' && $counts['pending'] > 0 && $activeStatus !== $key)
                    <span class="size-1.5 shrink-0 rounded-full bg-warn"></span>
                @endif

                {{ $tab['label'] }}
                <span class="tabular-nums">({{ $counts[$key] }})</span>
            </a>
        @endforeach
    </div>

    {{-- Says what the queue means, so nobody wonders why a review the student
         swears they sent is not on the site. --}}
    @if ($counts['pending'] > 0)
        <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="alert" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">
                <span class="num font-semibold">{{ $counts['pending'] }}</span>
                رأياً بانتظار الموافقة. لا يظهر أي منها في الصفحة الرئيسية حتى تُفعّله.
            </p>
        </div>
    @endif

    @if ($testimonials->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="users" class="size-5" />
            </span>
            {{-- The empty state names the FILTER, not the table: "no reviews
                 yet" under the pending tab would read as "nothing is waiting"
                 when the real message is "nothing is waiting in this view". --}}
            @if ($activeStatus === 'pending')
                <p class="mt-4 text-ui font-semibold text-ink">لا شيء بانتظار الموافقة</p>
                <p class="mt-1 text-caption text-stone">كل الآراء المرسَلة تمّت مراجعتها.</p>
            @elseif ($activeStatus === 'active')
                <p class="mt-4 text-ui font-semibold text-ink">لا توجد آراء ظاهرة</p>
                <p class="mt-1 text-caption text-stone">فعّل رأياً من تبويب «بانتظار الموافقة» ليظهر هنا.</p>
            @else
                <p class="mt-4 text-ui font-semibold text-ink">لا توجد آراء بعد</p>
                <p class="mt-1 text-caption text-stone">أضف أول رأي ليظهر في الصفحة الرئيسية.</p>
            @endif
        </div>
    @else
        @php
            // $loop->iteration restarts at 1 on every page; the offset keeps
            // the numbering continuous across pages.
            $offset = $testimonials->firstItem() - 1;
        @endphp

        <x-admin.table class="mt-6" :headers="[
            ['label' => 'صاحب الرأي'],
            ['label' => 'التقييم', 'width' => 'w-28'],
            ['label' => 'الرأي'],
            ['label' => 'الترتيب', 'width' => 'w-20'],
            ['label' => 'الحالة', 'width' => 'w-28'],
            ['label' => 'إجراءات', 'align' => 'end'],
        ]">
            @foreach ($testimonials as $testimonial)
                <tr class="transition hover:bg-surface/60">

                    <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                    <td class="px-4 py-3">
                        <p class="font-semibold text-ink">{{ $testimonial->displayName() ?: '—' }}</p>
                        <p class="mt-0.5 text-micro text-stone">{{ $testimonial->displayMeta() ?: '—' }}</p>

                        {{-- Says plainly whether this row reads live from an
                             account or from its stored snapshot. --}}
                        <p class="mt-1 text-micro {{ $testimonial->student_id ? 'text-accent-deep' : 'text-muted' }}">
                            {{ $testimonial->student_id ? 'مرتبط بحساب طالب' : 'غير مرتبط بحساب' }}
                        </p>
                    </td>

                    <td class="px-4 py-3">
                        {{-- stars() clamps: the column accepts up to 255, and
                             a raw loop would print that many. --}}
                        <span class="text-accent" aria-label="{{ $testimonial->stars() }} من 5">
                            {{ str_repeat('★', $testimonial->stars()) }}<span
                                class="text-muted">{{ str_repeat('★', 5 - $testimonial->stars()) }}</span>
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <p class="line-clamp-2 max-w-md text-caption text-steel">{{ $testimonial->description }}</p>
                    </td>

                    <td class="px-4 py-3 tabular-nums text-stone">{{ $testimonial->display_order }}</td>

                    <td class="px-4 py-3">
                        @php
                            /*
                             * 🔴 Pending is its OWN state, not "hidden". An
                             * admin who deliberately unpublished a review and
                             * a student submission nobody has looked at yet
                             * are different situations, and a queue that
                             * shows them identically cannot be worked.
                             *
                             * Reuses the `draft` variant — already warn/orange
                             * — rather than adding a near-duplicate.
                             */
                            $badgeStatus = $testimonial->is_active ? 'active' : 'draft';
                            $badgeLabel = $testimonial->is_active ? 'ظاهرة' : 'بانتظار الموافقة';
                        @endphp

                        @can('edit-testimonials')
                            <form method="POST" action="{{ route('admin.testimonials.toggle', $testimonial) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        title="{{ $testimonial->is_active ? 'إخفاء من الصفحة' : 'موافقة ونشر' }}"
                                        class="cursor-pointer rounded-md transition hover:opacity-70">
                                    <x-admin.status-badge :status="$badgeStatus" :label="$badgeLabel" />
                                </button>
                            </form>
                        @else
                            <x-admin.status-badge :status="$badgeStatus" :label="$badgeLabel" />
                        @endcan
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @can('edit-testimonials')
                                {{-- 🔴 One click to publish, and it only exists
                                     while there is something to publish. The
                                     badge above toggles both ways; this is the
                                     primary action for the queue, so it says
                                     what it does instead of "toggle". --}}
                                @unless ($testimonial->is_active)
                                    <form method="POST" action="{{ route('admin.testimonials.toggle', $testimonial) }}">
                                        @csrf
                                        @method('PATCH')
                                        {{-- Carries the current tab back, so
                                             approving from the pending queue
                                             returns to the pending queue. --}}
                                        <input type="hidden" name="status" value="{{ $activeStatus }}">
                                        {{-- 🔴 No icon, deliberately. The base
                                             classes already matched «تعديل» and
                                             «حذف» exactly — padding, radius,
                                             border and text size all identical —
                                             but the icon changed the CONTENT box:
                                             an extra 16px child plus the gap made
                                             this button visibly bulkier than the
                                             two text-only buttons beside it.

                                             Text-only now, so the three differ in
                                             label length alone — exactly as «تعديل»
                                             and «حذف» already differ from each
                                             other — and in colour. --}}
                                        <x-admin.btn variant="success">موافقة ونشر</x-admin.btn>
                                    </form>
                                @endunless

                                <x-admin.btn :href="route('admin.testimonials.edit', $testimonial)" variant="secondary">
                                    تعديل
                                </x-admin.btn>
                            @endcan

                            @can('delete-testimonials')
                                {{-- 🔴 POST, never a link: a GET route fires on
                                     browser prefetch or a passing crawler. --}}
                                <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}"
                                      data-confirm-title="حذف رأي «{{ $testimonial->displayName() }}»؟"
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

        <div class="mt-4">{{ $testimonials->links() }}</div>
    @endif

</x-layouts.admin>
