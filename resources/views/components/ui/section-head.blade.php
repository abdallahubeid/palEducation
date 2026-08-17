@props([
    'eyebrow' => null,
    'title'   => '',
    'lede'    => null,
    'align'   => 'start',   // start | center
    'on'      => 'light',   // light | dark
])

@php
    $titleInk = $on === 'dark' ? 'text-on-dark' : 'text-ink';
    $ledeInk  = $on === 'dark' ? 'text-on-dark/70' : 'text-steel';
@endphp

<div @class([
    'measure',
    'mx-auto text-center' => $align === 'center',
])>
    @if ($eyebrow)
        <x-ui.rule-label :on="$on">{{ $eyebrow }}</x-ui.rule-label>
    @endif

    <h2 class="mt-5 text-h1 font-bold {{ $titleInk }}">{{ $title }}</h2>

    @if ($lede)
        <p class="mt-4 text-lead {{ $ledeInk }}">{{ $lede }}</p>
    @endif

    {{ $slot }}
</div>
