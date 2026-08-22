@props([
    'items'  => [],   // ['lectures' => 'محاضرات', 'files' => 'ملفات']
    'active' => null,
])

@php
    $active = $active ?? array_key_first($items);
@endphp

<div data-tabs {{ $attributes }}>
    <div role="tablist" class="inline-flex items-center gap-1 rounded-lg bg-surface p-1">
        @foreach ($items as $key => $label)
            <button type="button"
                    data-tab-trigger="{{ $key }}"
                    role="tab"
                    aria-selected="{{ $key === $active ? 'true' : 'false' }}"
                    @class([
                        'tab-trigger cursor-pointer rounded-md px-4 py-2.5 text-ui font-semibold text-stone transition hover:text-ink lg:py-2',
                        'is-active' => $key === $active,
                    ])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{ $slot }}
</div>
