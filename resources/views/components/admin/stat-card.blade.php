@props([
    'label' => '',
    'value' => '',
    'hint' => null,
    'icon' => 'chart',
    'accent' => false,
    'href' => null,

    /*
     * نسبة التغيّر مقابل الفترة السابقة.
     *
     * ⚠️ null تعني «لا أساس للمقارنة» لا «صفر». قفزة من 0 إلى 5 ليست
     * «+500%» — لا نسبة لها أصلاً، وطباعة واحدة تخترع أساساً لم يوجد.
     * القيمتان تُميَّزان، ولا تُدمجان في 0.
     */
    'trend' => null,
])

@php
    /*
     * بطاقة مؤشّر — منقولة عن admin/stat-card في لوحة veyra.
     * accent يميّز المؤشّر الذي يتطلّب إجراءً اليوم (لا أكثر من واحد في الصفحة).
     */
    $base = 'relative overflow-hidden rounded-xl border p-4 transition duration-200 ease-out';

    $surface = $accent
        ? 'border-accent/40 bg-canvas ring-1 ring-accent/25'
        : 'border-hairline bg-canvas hover:border-hairline-strong';

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([$base, $surface, 'block hover:shadow-subtle' => (bool) $href]) }}>

    @if ($accent)
        {{-- الوهج داخل الحاوية بالكامل: إزاحة سالبة داخل overflow-hidden تُقصّ --}}
        <div class="pointer-events-none absolute -top-6 -end-6 size-20 rounded-full bg-accent/20 blur-2xl"></div>
    @endif

    <div class="relative flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-caption font-medium text-stone">{{ $label }}</p>
            {{-- tabular-nums يمنع رقص الأرقام بين البطاقات --}}
            <p class="mt-2 text-h3 font-bold tabular-nums text-ink">{{ $value }}</p>
        </div>

        <span @class([
            'grid size-10 shrink-0 place-items-center rounded-xl',
            'bg-accent/15 text-accent' => $accent,
            'bg-surface text-stone' => ! $accent,
        ])>
            <x-icon :name="$icon" class="size-5" />
        </span>
    </div>

    @if ($trend !== null || $hint)
        <div class="relative mt-3 flex flex-wrap items-center gap-2">
            @if ($trend !== null)
                @php
                    /*
                     * 🔴 كل صنف مكتوب حرفياً كاملاً — فاحص Tailwind لا ينفّذ
                     * PHP، فـ"text-{$tone}-deep" لا يولّد أي CSS إطلاقاً.
                     */
                    $trendTones = [
                        'up' => 'bg-accent/10 text-accent-deep',
                        'down' => 'bg-error/10 text-error-deep',
                        'flat' => 'bg-surface text-stone',
                    ];

                    $direction = $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'flat');
                    $sign = $trend > 0 ? '+' : '';
                @endphp

                {{-- الرقم والنسبة يبقيان LTR داخل صفحة RTL: علامة الإشارة
                     أمام الرقم تنقلب بصرياً بدونه فتصير «‎12.5%+‎». --}}
                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-micro font-semibold
                             {{ $trendTones[$direction] }}">
                    <bdi dir="ltr" class="tabular-nums">{{ $sign }}{{ $trend }}%</bdi>
                </span>
            @endif

            @if ($hint)
                <p class="text-micro text-stone">{{ $hint }}</p>
            @endif
        </div>
    @endif
</{{ $tag }}>
