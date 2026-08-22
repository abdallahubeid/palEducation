@props([
    'title'  => null,
    'points' => [],
])

{{--
    Key-points box - a standard pattern in modern news portals (BBC places one
    at the top of the article). Its value here is doubled: our readers are
    students under time pressure, and four lines ahead of 800 words respects
    that.

    Sits inside the reading column, not beside it - it is part of the article,
    not an annotation on it.

    Design notes (2026-08-22 upgrade):
    - `border-s-4 border-accent` is the prominent edge. Logical property, so it
      lands on the right in RTL and the left in LTR with no rtl: variant.
    - Markers are NUMBERED, not check-circles. Repeated ticks read as a
      completed checklist; numbers read as ranked takeaways and give the
      bullet hierarchy an explicit reading order.
    - Numerals carry `.num` (Inter + tabular-nums) so they align in a column
      and never render as Eastern Arabic-Indic digits.
--}}

@if (! empty($points))
    <aside {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border-s-4 border-accent bg-accent-soft/50']) }}>
        <h2 class="flex items-center gap-3 border-b border-accent/15 px-5 py-4 text-h5 font-semibold text-ink sm:px-6">
            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-accent/12 text-accent-deep">
                <x-icon name="sparkle" class="size-4" />
            </span>
            {{ $title ?? __('public.news_key_points') }}
        </h2>

        <ul class="flex flex-col gap-4 px-5 py-5 sm:px-6">
            @foreach ($points as $point)
                <li class="flex gap-3">
                    <span class="num mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-accent/12
                                 text-micro font-semibold text-accent-deep"
                          aria-hidden="true">{{ $loop->iteration }}</span>

                    <span class="text-body leading-[1.75] text-slate">{{ $point }}</span>
                </li>
            @endforeach
        </ul>
    </aside>
@endif
