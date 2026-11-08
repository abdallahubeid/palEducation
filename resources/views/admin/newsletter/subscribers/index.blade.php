<x-layouts.admin title="مشتركو النشرة" breadcrumbs="النشرة البريدية / المشتركين">

    <x-admin.page-head
        title="مشتركو النشرة البريدية"
        lede="العناوين المسجَّلة من نموذج النشرة في تذييل الموقع.">
        <x-slot:actions>
            @can('send-newsletter')
                <x-admin.btn :href="route('admin.newsletter.send')" variant="primary">إرسال نشرة</x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    @php
        $tabs = ['all' => 'الكل', 'active' => 'النشطين', 'unsubscribed' => 'ملغى الاشتراك'];
    @endphp

    {{--
        ═══ Search and filters ═══

        🔴 One GET form around BOTH. Separate forms would drop the search term
        the moment a status tab is clicked, and drop the status the moment a
        search is run — the two have to travel together.
    --}}
    <form method="GET" action="{{ route('admin.newsletter.subscribers.index') }}"
          class="mt-6 flex flex-wrap items-center gap-3">

        <input type="hidden" name="status" value="{{ $activeStatus }}">

        <div class="min-w-0 flex-1 sm:max-w-xs">
            <label for="subscriber-search" class="sr-only">بحث بالبريد</label>
            <input id="subscriber-search" type="search" name="q" value="{{ $search }}"
                   dir="ltr" placeholder="ابحث بالبريد…"
                   class="h-10 w-full rounded-lg border border-hairline bg-canvas px-3 font-latin text-ui
                          text-ink transition placeholder:text-muted focus:border-accent
                          focus:outline-none focus:ring-2 focus:ring-accent/25">
        </div>

        <x-admin.btn variant="secondary">بحث</x-admin.btn>

        @if (filled($search))
            {{-- Clearing keeps the status tab — only the term is dropped. --}}
            <x-admin.btn :href="route('admin.newsletter.subscribers.index', ['status' => $activeStatus])"
                         variant="ghost">مسح البحث</x-admin.btn>
        @endif

        {{-- Exports what the FILTER selects, not the page, so the query travels. --}}
        <x-admin.btn class="ms-auto"
                     :href="route('admin.newsletter.subscribers.export', array_filter([
                         'status' => $activeStatus === 'all' ? null : $activeStatus,
                         'q' => $search,
                     ]))"
                     variant="secondary">تصدير CSV</x-admin.btn>
    </form>

    <div class="mt-4 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.newsletter.subscribers.index', array_filter([
                   'status' => $key === 'all' ? null : $key,
                   'q' => $search,
               ])) }}"
               @class([
                   'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-caption font-semibold transition',
                   'bg-accent text-on-primary' => $activeStatus === $key,
                   'bg-surface text-steel hover:bg-hairline' => $activeStatus !== $key,
               ])>
                {{ $label }}
                <span class="tabular-nums">({{ $counts[$key] }})</span>
            </a>
        @endforeach
    </div>

    @if ($subscribers->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="mail" class="size-5" />
            </span>
            {{-- The empty state names what is actually empty. --}}
            @if (filled($search))
                <p class="mt-4 text-ui font-semibold text-ink">لا نتائج لهذا البحث</p>
                <p class="mt-1 text-caption text-stone">لا مشترك بريده يطابق «{{ $search }}».</p>
            @elseif ($activeStatus === 'active')
                <p class="mt-4 text-ui font-semibold text-ink">لا مشتركين نشطين</p>
            @elseif ($activeStatus === 'unsubscribed')
                <p class="mt-4 text-ui font-semibold text-ink">لم يُلغِ أحد اشتراكه</p>
            @else
                <p class="mt-4 text-ui font-semibold text-ink">لا مشتركين بعد</p>
                <p class="mt-1 text-caption text-stone">ستظهر هنا العناوين المسجَّلة من تذييل الموقع.</p>
            @endif
        </div>
    @else
        @php $offset = $subscribers->firstItem() - 1; @endphp

        {{--
            ═══ Bulk selection ═══

            The table lives inside the bulk-delete form, so a checked row IS a
            posted field — nothing mirrors selection into hidden inputs, which
            is where selection UIs normally drift out of sync with the boxes.
        --}}
        <form method="POST" action="{{ route('admin.newsletter.subscribers.bulk-destroy') }}"
              x-data="subscriberSelection"
              data-confirm-title="حذف المشتركين المحدَّدين؟"
              data-confirm="ينتقلون إلى سلّة المحذوفات ويمكن استرجاعهم."
              data-confirm-action="نعم، احذفهم">
            @csrf
            @method('DELETE')

            @can('delete-newsletter')
                <div class="mt-6 flex flex-wrap items-center gap-3 rounded-xl border border-hairline
                            bg-canvas px-4 py-3">
                    <label class="flex cursor-pointer items-center gap-2 text-caption font-semibold text-steel">
                        {{-- 🔴 Selects only THIS page's rows, which is all it
                             can honestly do: the boxes are the posted fields,
                             and rows on page 2 are not in this document. The
                             label says "in this page" rather than implying it
                             reaches the whole filtered list. --}}
                        <input type="checkbox" @change="toggleAll($event.target.checked)"
                               :checked="allChecked" :indeterminate="count > 0 && ! allChecked"
                               class="size-4 cursor-pointer rounded border-hairline-strong text-accent
                                      focus:ring-2 focus:ring-accent/25">
                        تحديد كل الصفحة
                    </label>

                    {{-- The count and the action appear only with a selection:
                         a permanently visible disabled button reads as broken. --}}
                    <p x-show="count > 0" x-cloak class="text-caption text-stone">
                        محدَّد: <span class="tabular-nums font-semibold text-ink" x-text="count"></span>
                    </p>

                    <div x-show="count > 0" x-cloak class="ms-auto">
                        <x-admin.btn variant="danger">حذف المحدَّد</x-admin.btn>
                    </div>
                </div>
            @endcan

            <x-admin.table class="mt-4" :headers="[
                ['label' => '', 'width' => 'w-10'],
                ['label' => 'البريد'],
                ['label' => 'الحالة', 'width' => 'w-36'],
                ['label' => 'تاريخ الاشتراك', 'width' => 'w-40'],
                ['label' => 'إجراءات', 'align' => 'end'],
            ]">
                @foreach ($subscribers as $subscriber)
                    <tr class="transition hover:bg-surface/60">
                        <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                        <td class="px-4 py-3">
                            @can('delete-newsletter')
                                <input type="checkbox" name="ids[]" value="{{ $subscriber->id }}"
                                       data-row-check @change="recount"
                                       aria-label="تحديد {{ $subscriber->email }}"
                                       class="size-4 cursor-pointer rounded border-hairline-strong text-accent
                                              focus:ring-2 focus:ring-accent/25">
                            @endcan
                        </td>

                        {{-- Latin inside an RTL page: isolated, or it reorders. --}}
                        <td class="px-4 py-3">
                            <bdi dir="ltr" class="font-latin text-ui text-ink">{{ $subscriber->email }}</bdi>
                        </td>

                        <td class="px-4 py-3">
                            <x-admin.status-badge :status="$subscriber->status->badge()"
                                                  :label="$subscriber->status->label()" />
                        </td>

                        <td class="px-4 py-3">
                            <span class="text-caption tabular-nums text-stone" dir="ltr">
                                {{ $subscriber->subscribed_at?->format('Y-m-d') ?? '—' }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                {{-- 🔴 Each row action posts its own form, and
                                     those forms sit OUTSIDE this one: HTML
                                     forbids nested forms and browsers drop the
                                     inner one silently. The buttons reach them
                                     through the `form` attribute. --}}
                                <x-admin.btn variant="secondary" type="submit"
                                             form="toggle-{{ $subscriber->id }}">
                                    {{ $subscriber->isActive() ? 'إلغاء الاشتراك' : 'إعادة التفعيل' }}
                                </x-admin.btn>

                                @can('delete-newsletter')
                                    <x-admin.btn variant="danger" type="submit"
                                                 form="delete-{{ $subscriber->id }}">حذف</x-admin.btn>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>
        </form>

        {{-- The per-row forms, outside the bulk form, linked by id. --}}
        @foreach ($subscribers as $subscriber)
            <form id="toggle-{{ $subscriber->id }}" method="POST" class="hidden"
                  action="{{ route('admin.newsletter.subscribers.toggle', $subscriber) }}"
                  data-confirm-title="{{ $subscriber->isActive() ? 'إلغاء اشتراك هذا البريد؟' : 'إعادة تفعيل الاشتراك؟' }}"
                  data-confirm="{{ $subscriber->isActive()
                      ? 'لن تصله أي نشرة بعد الآن، ويبقى في القائمة كي لا يُراسَل مجدداً.'
                      : 'سيعود لاستقبال النشرات القادمة.' }}"
                  data-confirm-action="تأكيد">
                @csrf
                @method('PATCH')
            </form>

            @can('delete-newsletter')
                <form id="delete-{{ $subscriber->id }}" method="POST" class="hidden"
                      action="{{ route('admin.newsletter.subscribers.destroy', $subscriber) }}"
                      data-confirm-title="حذف «{{ $subscriber->email }}»؟"
                      data-confirm="ينتقل إلى سلّة المحذوفات. للتوقّف عن مراسلته فقط استخدم «إلغاء الاشتراك» — الحذف يسمح بإعادة تسجيله لاحقاً."
                      data-confirm-action="نعم، احذفه">
                    @csrf
                    @method('DELETE')
                </form>
            @endcan
        @endforeach

        <div class="mt-4">{{ $subscribers->links() }}</div>
    @endif

</x-layouts.admin>
