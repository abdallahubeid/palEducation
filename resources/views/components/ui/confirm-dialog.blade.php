@props([
    'id'           => 'confirm-dialog',
    'title'        => '',
    'body'         => '',
    'confirmLabel' => '',
    'confirmHref'  => '#',
    'cancelLabel'  => null,
    'variant'      => 'danger',   // primary | danger
])

<x-ui.modal :id="$id" size="sm" :labelledby="$id . '-title'">
    <div class="flex flex-col items-center gap-5 text-center">
        <div>
            <h2 id="{{ $id }}-title" class="text-h4 font-bold text-ink">{{ $title }}</h2>
            <p class="mt-2 text-ui text-steel">{!! $body !!}</p>
        </div>

        <div class="flex w-full flex-col gap-2 sm:flex-row">
            <button type="button" data-modal-close
                    class="inline-flex h-11 flex-1 items-center justify-center rounded-full border border-hairline-strong
                           text-ui font-semibold text-ink transition hover:bg-surface">
                {{ $cancelLabel ?? __('common.cancel') }}
            </button>

            <x-ui.button :variant="$variant" size="md" :href="$confirmHref" data-confirm-accept class="flex-1">
                {{ $confirmLabel }}
            </x-ui.button>
        </div>
    </div>
</x-ui.modal>
