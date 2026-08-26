@props([
    'tabs' => [],   // [['label' => '…', 'route' => '…', 'pattern' => '…', 'badge' => null]]
])

{{--
    شريط تبويبات أفقي — نمط شريط تبويبات إعدادات veyra.

    ينزلق أفقياً على الشاشات الضيّقة بدل أن يلتفّ أو يُقصّ:
    `overflow-x-auto` على الشريط و`shrink-0` على كل تبويب.

    🔴 المؤشّر النشط حدّ سفلي بسماكة 2 لا خلفية ممتلئة: التبويب النشط
    يجب أن يُقرأ جزءاً من الشريط لا زرّاً منفصلاً عنه.

    ⚠️ `-mb-px` يجعل حدّ التبويب النشط يجلس **فوق** حدّ الشريط تماماً،
    فلا يظهر خطّان متجاوران بسماكة مضاعفة.
--}}
<div {{ $attributes->class(['border-b border-hairline']) }}>
    <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="أقسام الإعدادات">
        @foreach ($tabs as $tab)
            @php $active = request()->routeIs($tab['pattern'] ?? $tab['route']); @endphp

            <a href="{{ route($tab['route']) }}"
               @if ($active) aria-current="page" @endif
               @class([
                   'flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-ui font-medium transition',
                   'border-accent text-accent-deep' => $active,
                   'border-transparent text-steel hover:border-hairline-strong hover:text-ink' => ! $active,
               ])>
                @isset($tab['icon'])
                    <x-icon :name="$tab['icon']" class="size-4 shrink-0" />
                @endisset

                {{ $tab['label'] }}

                @isset($tab['badge'])
                    <span @class([
                        'rounded-md px-1.5 py-0.5 text-micro font-semibold tabular-nums',
                        'bg-accent/12 text-accent-deep' => $active,
                        'bg-surface text-stone' => ! $active,
                    ])>{{ $tab['badge'] }}</span>
                @endisset
            </a>
        @endforeach
    </nav>
</div>
