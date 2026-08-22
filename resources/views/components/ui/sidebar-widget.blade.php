@props([
    'title' => null,
    'icon'  => null,
    'href'  => null,        // optional "see all" link target
    'link'  => null,        // optional "see all" link label
])

{{--
    Titled card for editorial sidebar rails.

    Built as a generic wrapper rather than inline markup because the same shape
    recurs: a ruled header, an optional trailing link, and a body slot. The
    teacher and admin areas will want the same container.

    The header uses flex + justify-between, which mirrors with direction on its
    own - no rtl: variant.
--}}

<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl bg-canvas ring-1 ring-hairline']) }}>
    @if ($title)
        {{--
            py-2 rather than py-4: the "see all" link carries min-h-11 to stay a valid
            touch target, and it - not the heading - sets the row height. The result is
            the same 60px header either way, but the link is now tappable. A widget
            header link is standalone navigation, so the WCAG 2.5.8 inline-link
            exemption that covers breadcrumbs does not apply to it.
        --}}
        <div class="flex items-center justify-between gap-3 border-b border-hairline px-5 py-2">
            <h2 class="flex items-center gap-2.5 text-h5 font-semibold text-ink">
                @if ($icon)
                    <x-icon :name="$icon" class="size-4 shrink-0 text-accent-deep" />
                @endif
                {{ $title }}
            </h2>

            @if ($href && $link)
                <a href="{{ $href }}"
                   class="-me-2 inline-flex min-h-11 shrink-0 items-center px-2 text-caption font-semibold
                          text-accent-deep transition hover:underline">
                    {{ $link }}
                </a>
            @endif
        </div>
    @endif

    <div class="p-5">
        {{ $slot }}
    </div>
</section>
