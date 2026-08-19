@props([
    'data' => [],   // [['label' => 'س', 'value' => 45], ...] القيمة بالدقائق
])

@php
    $maxValue = max(1, collect($data)->max('value') ?? 1);
@endphp

{{-- رسم أعمدة بـCSS فقط — بلا مكتبة رسوم بيانية خارجية --}}
<div class="flex items-end justify-between gap-2 sm:gap-3" style="height: 140px;">
    @foreach ($data as $point)
        @php $heightPercent = $point['value'] > 0 ? max(6, round(($point['value'] / $maxValue) * 100)) : 0; @endphp
        <div class="flex h-full flex-1 flex-col items-center gap-2">
            <div class="flex w-full flex-1 items-end" role="img" aria-label="{{ $point['label'] }}: {{ $point['value'] }}">
                <div class="w-full rounded-t-md bg-accent/80 transition-[height] duration-500" style="height: {{ $heightPercent }}%"></div>
            </div>
            <span class="text-micro text-stone">{{ $point['label'] }}</span>
        </div>
    @endforeach
</div>
