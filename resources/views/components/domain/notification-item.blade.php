@props([
    'icon'   => 'bell',
    'tone'   => 'accent',   // accent | tag | warn | error
    'title'  => '',
    'body'   => '',
    'time'   => '',
    'unread' => false,
    'href'   => '#',
])

@php
    $tones = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag'    => 'bg-tag/12 text-tag',
        'warn'   => 'bg-warn/14 text-warn-deep',
        'error'  => 'bg-error/12 text-error-deep',
    ];
@endphp

<a href="{{ $href }}" data-notification-item @class([
    'flex items-start gap-3 rounded-lg p-4 transition hover:bg-surface',
    'bg-accent-soft/50' => $unread,
])>
    <span class="grid size-10 shrink-0 place-items-center rounded-full {{ $tones[$tone] ?? $tones['accent'] }}">
        <x-icon :name="$icon" class="size-5" />
    </span>

    <div class="min-w-0 flex-1">
        <p @class(['text-ui text-ink', 'font-semibold' => $unread, 'font-medium' => !$unread])>{{ $title }}</p>
        <p class="mt-0.5 text-caption text-steel">{{ $body }}</p>
        <p class="num mt-1 text-caption text-stone">{{ $time }}</p>
    </div>

    @if ($unread)
        <span data-unread-dot class="mt-1.5 size-2 shrink-0 rounded-full bg-accent" aria-hidden="true"></span>
    @endif
</a>
