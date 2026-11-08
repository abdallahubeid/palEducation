<x-layouts.admin title="رسائل التواصل" breadcrumbs="النظام / رسائل التواصل">

    <x-admin.page-head
        title="رسائل التواصل"
        lede="الرسائل الواردة من نموذج «تواصل معنا» في الموقع العام." />

    {{--
        ═══ Status tabs ═══

        🔴 Read and replied are SEPARATE states, and «بانتظار الردّ» exists
        because of it: a message can be opened and left unanswered, which is
        exactly what an inbox has to surface. Collapsing the two would hide
        the only queue that matters.
    --}}
    @php
        $tabs = [
            'all' => 'الكل',
            'unread' => 'غير مقروءة',
            'awaiting' => 'بانتظار الردّ',
            'replied' => 'تمّ الردّ',
        ];
    @endphp

    <div class="mt-6 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.messages.index', $key === 'all' ? [] : ['status' => $key]) }}"
               @class([
                   'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-caption font-semibold transition',
                   'bg-accent text-on-primary' => $activeStatus === $key,
                   'bg-surface text-steel hover:bg-hairline' => $activeStatus !== $key,
               ])>
                @if ($key === 'unread' && $counts['unread'] > 0 && $activeStatus !== $key)
                    <span class="size-1.5 shrink-0 rounded-full bg-warn"></span>
                @endif

                {{ $label }}
                <span class="tabular-nums">({{ $counts[$key] }})</span>
            </a>
        @endforeach
    </div>

    @if ($messages->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="mail" class="size-5" />
            </span>
            {{-- The empty state names the FILTER, not the table. --}}
            @if ($activeStatus === 'unread')
                <p class="mt-4 text-ui font-semibold text-ink">لا رسائل غير مقروءة</p>
                <p class="mt-1 text-caption text-stone">كل الرسائل الواردة تمّت قراءتها.</p>
            @elseif ($activeStatus === 'awaiting')
                <p class="mt-4 text-ui font-semibold text-ink">لا شيء بانتظار الردّ</p>
                <p class="mt-1 text-caption text-stone">تمّ الردّ على كل الرسائل.</p>
            @elseif ($activeStatus === 'replied')
                <p class="mt-4 text-ui font-semibold text-ink">لم يُرَدّ على أي رسالة بعد</p>
            @else
                <p class="mt-4 text-ui font-semibold text-ink">لا رسائل بعد</p>
                <p class="mt-1 text-caption text-stone">ستظهر هنا الرسائل المرسَلة من صفحة «تواصل معنا».</p>
            @endif
        </div>
    @else
        @php $offset = $messages->firstItem() - 1; @endphp

        <x-admin.table class="mt-6" :headers="[
            ['label' => 'المرسِل'],
            ['label' => 'الموضوع', 'width' => 'w-32'],
            ['label' => 'الرسالة'],
            ['label' => 'الحالة', 'width' => 'w-40'],
            ['label' => 'وصلت', 'width' => 'w-32'],
            ['label' => 'إجراءات', 'align' => 'end'],
        ]">
            @foreach ($messages as $message)
                {{-- Unread rows carry a tint: an inbox where new and handled
                     look identical is one nobody can scan. --}}
                <tr @class([
                    'transition hover:bg-surface/60',
                    'bg-warn/5' => ! $message->is_read,
                ])>

                    <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                    <td class="px-4 py-3">
                        <p @class(['text-ink', 'font-bold' => ! $message->is_read, 'font-semibold' => $message->is_read])>
                            {{ $message->name }}
                        </p>
                        {{-- The address is latin inside Arabic text, so it is
                             isolated — an un-isolated email reorders in RTL. --}}
                        <p class="mt-0.5 text-micro text-stone"><bdi dir="ltr">{{ $message->email }}</bdi></p>
                    </td>

                    <td class="px-4 py-3">
                        <span class="rounded-md bg-surface px-2 py-1 text-micro text-stone">
                            {{ $message->subject_type->label() }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <p class="line-clamp-2 max-w-md text-caption text-steel">{{ $message->message }}</p>
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex flex-wrap items-center gap-1.5">
                            @if ($message->isReplied())
                                <x-admin.status-badge status="active" label="تمّ الردّ" />
                            @elseif ($message->is_read)
                                <x-admin.status-badge status="draft" label="بانتظار الردّ" />
                            @else
                                <x-admin.status-badge status="danger" label="غير مقروءة" />
                            @endif
                        </div>
                    </td>

                    {{-- Dates stay LTR even on an RTL page. --}}
                    <td class="px-4 py-3">
                        <span class="text-caption tabular-nums text-stone" dir="ltr">
                            {{ $message->created_at->format('Y-m-d') }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-admin.btn :href="route('admin.messages.show', $message)" variant="secondary">
                                عرض والردّ
                            </x-admin.btn>

                            @can('delete-messages')
                                {{-- 🔴 POST, never a link: a GET route fires on
                                     browser prefetch or a passing crawler. --}}
                                <form method="POST" action="{{ route('admin.messages.destroy', $message) }}"
                                      data-confirm-title="حذف رسالة «{{ $message->name }}»؟"
                                      data-confirm="تنتقل إلى سلّة المحذوفات ويمكن استرجاعها."
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

        <div class="mt-4">{{ $messages->links() }}</div>
    @endif

</x-layouts.admin>
