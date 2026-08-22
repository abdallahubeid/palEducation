@props([
    'src'   => null,      // مسار الصورة الحقيقية عند توفّرها
    'alt'   => '',
    'ratio' => '4/3',
    'tone'  => 'accent',    // accent | tag | amber | warn
    'icon'  => 'play',      // أيقونة البديل المصمّم — 'play' افتراضياً لأصله في معاينة المحاضرة
    'loading' => 'lazy',    // lazy | eager - above-the-fold covers load eager (DESIGN.md, Performance)
])

@php
    $tones = [
        'accent'   => 'from-accent/16 to-accent/4 text-accent-deep',
        'tag'    => 'from-tag/14 to-tag/4 text-tag',
        'amber' => 'from-amber/14 to-amber/4 text-amber-deep',
        'warn'   => 'from-warn/16 to-warn/4 text-warn-deep',
    ];
    $t = $tones[$tone] ?? $tones['accent'];
@endphp

<div {{ $attributes->merge([
        'class' => 'overflow-hidden bg-canvas'
     ]) }}
     style="aspect-ratio: {{ $ratio }}">

    @if ($src)
        <img src="{{ $src }}"
             alt="{{ $alt }}"
             loading="{{ $loading }}"
             decoding="async"
             class="size-full object-cover">
    @else
        {{-- بديل مصمّم — يبدو مقصوداً لا ناقصاً --}}
        <div class="grid size-full place-items-center bg-linear-to-bl {{ $t }}">
            <div class="flex flex-col items-center gap-3 px-6 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-canvas/70">
                    <x-icon :name="$icon" class="size-6" />
                </span>
                <span class="text-caption font-medium opacity-70">{{ $alt ?: __('home.image_placeholder') }}</span>
            </div>
        </div>
    @endif
</div>
