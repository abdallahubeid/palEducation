@props([
    'name' => '',
    'size' => '',
    'date' => '',
    'href' => '#',
])

<div class="flex items-center gap-4 border-b border-hairline-soft py-4 last:border-0">
    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-tag/12 text-tag">
        <x-icon name="folder" class="size-5" />
    </span>

    <div class="min-w-0 flex-1">
        <p class="truncate text-ui font-medium text-ink">{{ $name }}</p>
        <p class="num mt-0.5 text-caption text-stone">{{ $size }} · {{ $date }}</p>
    </div>

    <a href="{{ $href }}"
       class="grid size-9 shrink-0 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink"
       aria-label="{{ __('student.download') }}">
        <x-icon name="download" class="size-5" />
    </a>
</div>
