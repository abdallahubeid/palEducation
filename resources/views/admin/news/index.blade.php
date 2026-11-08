<x-layouts.admin title="الأخبار" breadcrumbs="المحتوى / الأخبار">

    <x-admin.page-head
        title="الأخبار"
        lede="ما يظهر في صفحة «أخبارنا» العامة.">
        <x-slot:actions>
            @can('create-news')
                <x-admin.btn :href="route('admin.news.create')" variant="primary">إضافة خبر</x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    {{--
        ═══ Status tabs ═══

        🔴 «مجدول» is its own state, not a flavour of published. An article
        can be switched ON and dated next week — published to the editor and
        invisible to the visitor. Without this tab those articles look
        published in the list and are nowhere on the site.
    --}}
    @php
        $tabs = ['all' => 'الكل', 'published' => 'منشورة', 'scheduled' => 'مجدولة', 'draft' => 'مسودّة'];
    @endphp

    <div class="mt-6 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.news.index', $key === 'all' ? [] : ['status' => $key]) }}"
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

    @if ($news->isEmpty())
        <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                <x-icon name="clipboard" class="size-5" />
            </span>
            @if ($activeStatus !== 'all')
                <p class="mt-4 text-ui font-semibold text-ink">لا أخبار بهذه الحالة</p>
            @else
                <p class="mt-4 text-ui font-semibold text-ink">لا أخبار بعد</p>
                <p class="mt-1 text-caption text-stone">أضف أول خبر ليظهر في صفحة «أخبارنا».</p>
            @endif
        </div>
    @else
        @php $offset = $news->firstItem() - 1; @endphp

        <x-admin.table class="mt-6" :headers="[
            ['label' => 'الصورة', 'width' => 'w-24'],
            ['label' => 'العنوان'],
            ['label' => 'الكاتب', 'width' => 'w-40'],
            ['label' => 'الحالة', 'width' => 'w-36'],
            ['label' => 'تاريخ النشر', 'width' => 'w-36'],
            ['label' => 'إجراءات', 'align' => 'end'],
        ]">
            @foreach ($news as $item)
                <tr class="transition hover:bg-surface/60">
                    <td class="px-4 py-3 text-caption tabular-nums text-stone">{{ $offset + $loop->iteration }}</td>

                    <td class="px-4 py-3">
                        @if ($item->cover_url)
                            {{-- Fixed ratio: rows of mixed-height thumbnails
                                 make a table impossible to scan. --}}
                            <img src="{{ $item->cover_url }}" alt=""
                                 class="h-12 w-20 rounded-lg object-cover" loading="lazy">
                        @else
                            <span class="grid h-12 w-20 place-items-center rounded-lg bg-surface text-stone">
                                <x-icon name="clipboard" class="size-4" />
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <p class="font-semibold text-ink">{{ $item->title }}</p>
                        @if (filled($item->summary))
                            <p class="mt-0.5 line-clamp-1 max-w-md text-micro text-stone">{{ $item->summary }}</p>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-caption text-steel">{{ $item->authorName() }}</td>

                    <td class="px-4 py-3">
                        @if (! $item->is_published)
                            <x-admin.status-badge status="draft" label="مسودّة" />
                        @elseif (! $item->isVisible())
                            {{-- Switched on but not due — says so plainly
                                 rather than claiming to be live. --}}
                            <x-admin.status-badge status="hidden" label="مجدول" />
                        @else
                            <x-admin.status-badge status="active" label="منشور" />
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <span class="text-caption tabular-nums text-stone" dir="ltr">
                            {{ $item->published_at?->format('Y-m-d') ?? '—' }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @can('edit-news')
                                <x-admin.btn variant="secondary" type="submit" form="toggle-{{ $item->id }}">
                                    {{ $item->is_published ? 'إخفاء' : 'نشر' }}
                                </x-admin.btn>

                                <x-admin.btn :href="route('admin.news.edit', $item)" variant="secondary">تعديل</x-admin.btn>
                            @endcan

                            @can('delete-news')
                                <form method="POST" action="{{ route('admin.news.destroy', $item) }}"
                                      data-confirm-title="حذف خبر «{{ $item->title }}»؟"
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

        {{-- The toggle forms sit outside the table's rows' own forms: HTML
             forbids nested forms and browsers drop the inner one silently. --}}
        @can('edit-news')
            @foreach ($news as $item)
                <form id="toggle-{{ $item->id }}" method="POST" class="hidden"
                      action="{{ route('admin.news.toggle', $item) }}">
                    @csrf
                    @method('PATCH')
                </form>
            @endforeach
        @endcan

        <div class="mt-4">{{ $news->links() }}</div>
    @endif

</x-layouts.admin>
