@props([
    'title' => '',
    'lede' => null,
])

{{-- ترويسة صفحة — نفس شكل لوحة veyra: عنوان وسطر شارح يسار/يمين الإجراءات --}}
<div {{ $attributes->class(['flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="min-w-0">
        <h2 class="text-h4 font-bold text-ink">{{ $title }}</h2>
        @if ($lede)
            <p class="mt-1 text-caption text-stone">{{ $lede }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
