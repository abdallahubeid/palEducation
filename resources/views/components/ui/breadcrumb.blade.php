@props([
    'items' => [],   // [['label' => '...', 'href' => '...'], ..., ['label' => 'current']]
])

<nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-caption text-stone">
    @foreach ($items as $item)
        @if (!$loop->last)
            <a href="{{ $item['href'] }}" class="transition hover:text-accent-deep">{{ $item['label'] }}</a>
            <x-icon name="arrow" class="size-3 shrink-0 text-muted rtl:-scale-x-100" />
        @else
            <span class="font-medium text-ink" aria-current="page">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
