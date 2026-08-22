@props([
    'columns'           => [],      // [['key','label','sortable'=>bool,'align'=>'start|end|center','primary'=>bool,'hideBelow'=>'sm|md|lg']]
    'searchable'        => true,
    'searchPlaceholder' => null,
    'perPage'           => 8,
    'density'           => 'regular', // condensed(40) | regular(48) | relaxed(56)
    'emptyIcon'         => 'folder',
    'emptyTitle'        => '',
    'emptyBody'         => null,
    'noResultsTitle'    => '',
    'noResultsBody'     => null,
])

@php
    // سلّم الكثافة من مرجع Pencil&Paper — الصف «المريح» 56px هو الافتراضي
    // هنا لا 48px، لأن النص العربي يحتاج هواءً رأسياً أكثر من اللاتيني.
    $rowPad = [
        'condensed' => 'px-4 py-2',
        'regular'   => 'px-4 py-3',
        'relaxed'   => 'px-4 py-4',
    ][$density] ?? 'px-4 py-3';

    $alignCls = ['start' => 'text-start', 'end' => 'text-end', 'center' => 'text-center'];

    // إخفاء أعمدة ثانوية على الشاشات الضيقة بدل ضغطها حتى تنكسر
    $hideCls = ['sm' => 'hidden sm:table-cell', 'md' => 'hidden md:table-cell', 'lg' => 'hidden lg:table-cell'];
@endphp

{{--
    جدول بيانات عام — يخدم محاضراتي وأداء الطلبة والسلة ولوحة الأدمن لاحقاً.
    الصفوف تُمرَّر عبر الـslot لأن كل شاشة تحتاج خلايا غنية (شارات، صور،
    إجراءات) لا نصاً مسطّحاً. المكوّن يوفّر القشرة: شريط أدوات، رأس قابل
    للفرز، ترقيم، وحالتين فارغتين.

    كل صف يحمل: data-row · data-search="..." · data-sort-{key}="..."
    والفلاتر الخارجية تُطابَق عبر data-filter-{name}="...".

    الاتجاه: الجدول يرث dir من الصفحة، وترتيب الأعمدة ينعكس تلقائياً في RTL.
    المحاذاة منطقية (text-start/end) لا فيزيائية.
--}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-4']) }}
     data-table
     data-per-page="{{ $perPage }}">

    {{-- ── شريط الأدوات ── --}}
    @if ($searchable || isset($toolbar) || isset($actions))
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            @if ($searchable)
                <label class="relative flex min-w-0 flex-1 items-center lg:max-w-xs">
                    <x-icon name="search" class="pointer-events-none absolute start-3 size-4 text-muted" />
                    <input type="search"
                           data-table-search
                           placeholder="{{ $searchPlaceholder ?? __('teacher.table_search') }}"
                           class="h-11 w-full rounded-md border border-hairline bg-canvas ps-9 pe-3 text-ui text-ink
                                  placeholder:text-muted transition hover:border-hairline-strong
                                  focus-visible:border-accent focus-visible:outline-none lg:h-10">
                </label>
            @endif

            @isset($toolbar)
                <div class="flex flex-wrap items-center gap-2">{{ $toolbar }}</div>
            @endisset

            @isset($actions)
                <div class="flex items-center gap-2 lg:ms-auto">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    {{-- ── الجدول ── --}}
    <div class="overflow-hidden rounded-xl bg-canvas shadow-subtle">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-hairline-soft bg-surface-soft">
                        @foreach ($columns as $col)
                            @php
                                $align = $alignCls[$col['align'] ?? 'start'] ?? 'text-start';
                                $hide  = isset($col['hideBelow']) ? ($hideCls[$col['hideBelow']] ?? '') : '';
                            @endphp
                            <th scope="col"
                                @if (! empty($col['sortable'])) aria-sort="none" data-sort-col="{{ $col['key'] }}" @endif
                                class="{{ $align }} {{ $hide }} {{ $rowPad }} text-micro font-semibold text-stone whitespace-nowrap">

                                @if (! empty($col['sortable']))
                                    {{-- الشيفرون داخل الزر فلا يزيح محاذاة العنوان عن محاذاة عمود المحتوى --}}
                                    {{--
                                        -my-3/py-3: يمدّ رقعة النقر رأسياً لتغطّي حشوة
                                        الخلية كاملة (≥44px) دون زيادة ارتفاع الصف —
                                        بدونها كان الزر 20px والنقر على الحشوة لا يفرز.
                                    --}}
                                    <button type="button"
                                            data-sort-key="{{ $col['key'] }}"
                                            @if (! empty($col['numeric'])) data-sort-type="number" @endif
                                            class="group -my-3 inline-flex items-center gap-1.5 rounded-sm py-3 transition hover:text-ink">
                                        {{ $col['label'] }}
                                        <x-icon name="chevron-down"
                                                class="size-3.5 shrink-0 text-muted transition group-hover:text-steel"
                                                data-sort-icon />
                                    </button>
                                @else
                                    {{ $col['label'] }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody data-table-body class="divide-y divide-hairline-soft">
                    {{ $slot }}
                </tbody>
            </table>
        </div>

        {{-- لا بيانات إطلاقاً --}}
        <div data-table-empty hidden>
            <x-ui.empty-state :icon="$emptyIcon" :title="$emptyTitle" :body="$emptyBody">
                @isset($emptyAction){{ $emptyAction }}@endisset
            </x-ui.empty-state>
        </div>

        {{-- بحث/فلترة بلا نتيجة — رسالة مختلفة عمداً عن «لا بيانات» --}}
        <div data-table-no-results hidden>
            <x-ui.empty-state icon="search" :title="$noResultsTitle" :body="$noResultsBody" />
        </div>
    </div>

    {{-- ── التذييل: العدّاد + الترقيم ── --}}
    <div data-table-footer class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-caption text-stone"
           data-table-count
           data-count-template="{{ __('teacher.table_showing', ['from' => ':from', 'to' => ':to', 'total' => ':total']) }}"
           aria-live="polite"></p>

        <nav data-table-pagination aria-label="{{ __('teacher.table_pagination') }}"
             class="flex items-center gap-1.5"></nav>
    </div>
</div>
