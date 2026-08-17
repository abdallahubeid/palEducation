@props([
    'question' => '',
    'open'     => false,
])

{{--
    مبني على <details> لا على JS:
    يفتح ويغلق ويُقرأ بقارئ الشاشة حتى لو تعطّل JavaScript تماماً.
--}}
<details {{ $open ? 'open' : '' }}
    {{ $attributes->merge(['class' => 'group tile overflow-hidden']) }}>

    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-6
                    text-h5 font-semibold text-ink marker:hidden">
        {{ $question }}

        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-surface text-steel
                     transition duration-300 group-open:rotate-45 group-open:bg-mint group-open:text-primary">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14"/>
            </svg>
        </span>
    </summary>

    <div class="border-t border-hairline-soft px-6 py-5 text-body text-steel">
        {{ $slot }}
    </div>
</details>
