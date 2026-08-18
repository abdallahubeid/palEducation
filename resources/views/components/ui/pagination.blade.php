@props([
    'currentPage' => 1,
    'lastPage'    => 1,
])

@if ($lastPage > 1)
    <nav aria-label="Pagination" class="flex items-center justify-center gap-1.5">
        <button type="button"
                @disabled($currentPage <= 1)
                class="grid size-9 shrink-0 place-items-center rounded-md text-steel transition hover:bg-surface disabled:pointer-events-none disabled:opacity-40">
            <x-icon name="arrow" class="size-4 -scale-x-100 rtl:scale-x-100" />
        </button>

        @for ($p = 1; $p <= $lastPage; $p++)
            <button type="button"
                    @class([
                        'num grid size-9 shrink-0 place-items-center rounded-md text-ui font-medium transition',
                        'bg-accent text-on-primary' => $p === $currentPage,
                        'text-steel hover:bg-surface' => $p !== $currentPage,
                    ])>
                {{ $p }}
            </button>
        @endfor

        <button type="button"
                @disabled($currentPage >= $lastPage)
                class="grid size-9 shrink-0 place-items-center rounded-md text-steel transition hover:bg-surface disabled:pointer-events-none disabled:opacity-40">
            <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
        </button>
    </nav>
@endif
