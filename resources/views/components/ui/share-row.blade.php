@props([
    'title'  => '',          // content title, handed to the native share sheet
    'label'  => null,        // leading label; null drops it
    'layout' => 'row',       // row | rail - rail is the vertical sticky desktop variant
])

{{--
    Share row, deliberately without platform logos.

    Rationale: this icon system is stroke-only at 1.75 weight with fill="none".
    Facebook and WhatsApp marks are solid by nature, and dropping them in would
    break the whole icon language. The alternative is also the better one
    functionally: navigator.share opens the OS share sheet - which already
    contains WhatsApp, the channel that matters most for a Palestinian mobile
    audience. Desktop falls back to copy-link.

    The native button ships hidden and is revealed by JS only when the API
    exists, so we never show a button that cannot work.
--}}

@php
    $btn = 'inline-flex items-center justify-center gap-2 rounded-full border border-hairline bg-canvas
            text-ui font-medium text-steel transition hover:border-hairline-strong hover:text-ink';

    $shape = $layout === 'rail' ? 'size-11' : 'min-h-11 px-4';
@endphp

<div {{ $attributes->merge([
        'class' => $layout === 'rail'
            ? 'flex flex-col items-center gap-2'
            : 'flex flex-wrap items-center gap-2',
     ]) }}
     data-share
     data-share-title="{{ $title }}"
     data-copied-label="{{ __('public.news_link_copied') }}">

    @if ($label)
        <span class="me-1 text-caption text-stone">{{ $label }}</span>
    @endif

    {{-- Revealed by JS only where navigator.share is supported --}}
    <button type="button"
            data-share-native
            hidden
            aria-label="{{ __('public.news_share_action') }}"
            class="{{ $btn }} {{ $shape }}">
        <x-icon name="share" class="size-4" />
        @if ($layout !== 'rail')
            {{ __('public.news_share_action') }}
        @endif
    </button>

    <button type="button"
            data-share-copy
            aria-label="{{ __('public.news_copy_link') }}"
            class="{{ $btn }} {{ $shape }}">
        <x-icon name="link" class="size-4" />
        @if ($layout !== 'rail')
            <span data-share-copy-label>{{ __('public.news_copy_link') }}</span>
        @endif
    </button>

    {{-- Copy confirmation - announced to screen readers, shown visually in rail layout --}}
    <span data-share-feedback
          role="status"
          aria-live="polite"
          class="text-caption font-medium text-accent-deep {{ $layout === 'rail' ? 'text-center' : '' }}"></span>
</div>
