@props([
    'id'         => 'modal',
    'size'       => 'md',   // sm(480px) confirm-style | md(560px) | lg(640px) content
    'openOn'     => null,   // اسم حدث مخصّص يفتح المودال تلقائياً — مثال: lecture:ended
    'labelledby' => null,
])

@php
    $widths = ['sm' => 'sm:max-w-[480px]', 'md' => 'sm:max-w-[560px]', 'lg' => 'sm:max-w-[640px]'];
@endphp

<div id="{{ $id }}"
     data-modal
     @if ($openOn) data-modal-open-on="{{ $openOn }}" @endif
     hidden
     class="fixed inset-0 z-[70] grid place-items-end bg-primary/45 p-4 sm:place-items-center"
     role="dialog"
     aria-modal="true"
     @if ($labelledby) aria-labelledby="{{ $labelledby }}" @endif>

    <div {{ $attributes->merge(['class' => "w-full rounded-xl bg-canvas p-6 shadow-mockup sm:p-8 {$widths[$size]}"]) }}>
        <div class="flex justify-end">
            <button type="button"
                    data-modal-close
                    aria-label="{{ __('common.close') }}"
                    class="-m-2 grid size-11 shrink-0 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink lg:size-9">
                <x-icon name="close" class="size-5" />
            </button>
        </div>

        <div class="-mt-4">
            {{ $slot }}
        </div>
    </div>
</div>
