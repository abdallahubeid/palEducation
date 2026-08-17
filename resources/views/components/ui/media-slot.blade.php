@props([
    'src'   => null,      // مسار الصورة الحقيقية عند توفّرها
    'alt'   => '',
    'ratio' => '4/3',
    'tone'  => 'mint',    // mint | tag | orange | warn
])

@php
    $tones = [
        'mint'   => 'from-mint/16 to-mint/4 text-mint-deep',
        'tag'    => 'from-tag/14 to-tag/4 text-tag',
        'orange' => 'from-orange/14 to-orange/4 text-orange-deep',
        'warn'   => 'from-warn/16 to-warn/4 text-warn',
    ];
    $t = $tones[$tone] ?? $tones['mint'];
@endphp

<div {{ $attributes->merge([
        'class' => 'overflow-hidden bg-canvas'
     ]) }}
     style="aspect-ratio: {{ $ratio }}">

    @if ($src)
        <img src="{{ $src }}"
             alt="{{ $alt }}"
             loading="lazy"
             decoding="async"
             class="size-full object-cover">
    @else
        {{-- بديل مصمّم — يبدو مقصوداً لا ناقصاً --}}
        <div class="grid size-full place-items-center bg-linear-to-bl {{ $t }}">
            <div class="flex flex-col items-center gap-3 px-6 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-canvas/70">
                    <x-icon name="play" class="size-6" />
                </span>
                <span class="text-caption font-medium opacity-70">{{ $alt ?: __('home.image_placeholder') }}</span>
            </div>
        </div>
    @endif
</div>
