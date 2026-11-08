<x-layouts.admin title="سجل الحملات" breadcrumbs="النشرة البريدية / سجل الحملات">

    <x-admin.page-head
        title="سجل الحملات"
        lede="كل نشرة أُرسلت، بنصّها ومَن أرسلها وعدد مستلميها.">
        <x-slot:actions>
            @can('send-newsletter')
                <x-admin.btn :href="route('admin.newsletter.send')" variant="primary">إرسال نشرة</x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    @php
        $tabs = [
            'all' => 'الكل',
            'processing' => 'قيد الإرسال',
            'completed' => 'مكتملة',
            'failed' => 'فشلت',
        ];
    @endphp

    <div class="mt-6 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.newsletter.campaigns.index', $key === 'all' ? [] : ['status' => $key]) }}"
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

    @if ($campaigns->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="mail" class="size-5" />
            </span>
            {{-- The empty state names the FILTER, not the table. --}}
            @if ($activeStatus !== 'all')
                <p class="mt-4 text-ui font-semibold text-ink">لا حملات بهذه الحالة</p>
            @else
                <p class="mt-4 text-ui font-semibold text-ink">لم تُرسَل أي نشرة بعد</p>
                <p class="mt-1 text-caption text-stone">سيظهر هنا سجلّ كل نشرة تُرسَل من المنصّة.</p>
            @endif
        </div>
    @else
        @php $offset = $campaigns->firstItem() - 1; @endphp

        {{--
            One Alpine root over the table AND the modal.

            🔴 A single modal fed by whichever row was clicked, not one modal
            per row: a history page holds ten campaigns per page, and ten
            copies of a full newsletter body would be rendered into the
            document on every visit for content nobody may open.
        --}}
        <div x-data="campaignPreview">

            <x-admin.table class="mt-6" :headers="[
                ['label' => 'العنوان'],
                ['label' => 'المُرسِل', 'width' => 'w-40'],
                ['label' => 'المستلمون', 'width' => 'w-28'],
                ['label' => 'الحالة', 'width' => 'w-36'],
                ['label' => 'تاريخ الإرسال', 'width' => 'w-48'],
                ['label' => 'إجراءات', 'align' => 'end'],
            ]">
                @foreach ($campaigns as $campaign)
                    <tr class="transition hover:bg-surface/60">
                        <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                        <td class="px-4 py-3">
                            <p class="font-semibold text-ink">{{ $campaign->subject }}</p>
                            <p class="mt-0.5 line-clamp-1 max-w-md text-micro text-stone">
                                {{ Str::limit($campaign->body, 70) }}
                            </p>
                        </td>

                        <td class="px-4 py-3 text-caption text-steel">{{ $campaign->senderName() }}</td>

                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-md bg-surface px-2 py-1
                                         text-micro font-semibold tabular-nums text-steel">
                                {{ $campaign->recipients_count }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <x-admin.status-badge :status="$campaign->status->badge()"
                                                  :label="$campaign->status->label()" />
                        </td>

                        <td class="px-4 py-3">
                            @if ($campaign->sent_at)
                                {{-- 🔴 The absolute date is the title, not the
                                     visible text: "منذ 3 أيام" is what an admin
                                     scans for, but an exact timestamp is what
                                     they need when reconciling a complaint. --}}
                                <span class="text-caption text-stone"
                                      title="{{ $campaign->sent_at->format('Y-m-d H:i') }}">
                                    {{ $campaign->sent_at->diffForHumans() }}
                                </span>
                            @else
                                {{-- An unfinished campaign has no honest date. --}}
                                <span class="text-caption text-stone">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                {{-- The body is handed over as data attributes
                                     rather than inlined into an onclick: Blade
                                     escapes them, so a newsletter containing
                                     quotes cannot break the markup. --}}
                                <x-admin.btn type="button" variant="secondary"
                                             data-campaign-subject="{{ $campaign->subject }}"
                                             data-campaign-body="{{ $campaign->body }}"
                                             data-campaign-sender="{{ $campaign->senderName() }}"
                                             data-campaign-count="{{ $campaign->recipients_count }}"
                                             data-campaign-date="{{ $campaign->sent_at?->format('Y-m-d H:i') ?? '—' }}"
                                             @click="open($event.currentTarget)">
                                    معاينة الرسالة
                                </x-admin.btn>

                                @can('send-newsletter')
                                    {{-- ⚠️ A link to a PREFILLED FORM, never a
                                         send. A GET that dispatched a campaign
                                         would fire on a browser prefetch. --}}
                                    <x-admin.btn variant="secondary"
                                                 :href="route('admin.newsletter.send', ['from' => $campaign->id])">
                                        نسخ كحملة جديدة
                                    </x-admin.btn>
                                @endcan

                                @can('delete-campaigns')
                                    <form method="POST" action="{{ route('admin.newsletter.campaigns.destroy', $campaign) }}"
                                          data-confirm-title="حذف حملة «{{ $campaign->subject }}»؟"
                                          data-confirm="تنتقل إلى سلّة المحذوفات. الرسائل المُرسَلة فعلاً لا تتأثّر."
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

            {{-- ═══ Preview modal ═══ --}}
            <div x-show="showing" x-cloak
                 @keydown.escape.window="close()"
                 class="fixed inset-0 z-50 grid place-items-center p-4"
                 role="dialog" aria-modal="true" aria-labelledby="campaign-preview-title">

                <div @click="close()" class="absolute inset-0 bg-ink/50"></div>

                <div class="relative flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden
                            rounded-2xl border border-hairline bg-canvas shadow-xl">

                    <div class="flex items-start gap-3 border-b border-hairline px-5 py-4 sm:px-6">
                        <div class="min-w-0 flex-1">
                            <h2 id="campaign-preview-title"
                                class="truncate text-ui font-bold text-ink" x-text="subject"></h2>
                            <p class="mt-1 text-micro text-stone">
                                <span x-text="sender"></span>
                                ·
                                <span class="tabular-nums" x-text="count"></span> مستلم
                                ·
                                <span class="tabular-nums" dir="ltr" x-text="date"></span>
                            </p>
                        </div>

                        <button type="button" @click="close()" x-ref="closer"
                                class="shrink-0 cursor-pointer rounded-lg p-1.5 text-stone transition hover:bg-surface"
                                aria-label="إغلاق">
                            <x-icon name="close" class="size-5" />
                        </button>
                    </div>

                    {{-- whitespace-pre-line so the sender's own paragraph
                         breaks survive; x-text escapes the value. --}}
                    <div class="overflow-y-auto px-5 py-5 sm:px-6">
                        <p class="whitespace-pre-line text-body leading-[1.9] text-steel" x-text="body"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">{{ $campaigns->links() }}</div>
    @endif

</x-layouts.admin>
