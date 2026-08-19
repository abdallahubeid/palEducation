@props([
    'days' => [],   // [['label' => 'س', 'active' => true], ...] سبعة عناصر
])

<div class="flex items-center justify-between gap-1.5 sm:gap-2">
    @foreach ($days as $day)
        <div class="flex flex-1 flex-col items-center gap-1.5">
            <span class="text-micro text-stone">{{ $day['label'] }}</span>
            <span @class([
                'grid size-8 place-items-center rounded-full text-ui font-bold sm:size-9',
                'bg-accent text-on-primary' => $day['active'],
                'bg-surface text-muted' => !$day['active'],
            ])>
                @if ($day['active'])
                    <x-icon name="check" class="size-4" />
                @endif
            </span>
        </div>
    @endforeach
</div>
