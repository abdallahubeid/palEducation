@props([
    'name'        => '',
    'subject'     => null,   // اختياري — يظهر في المكتبة، لا في تبويب ملفات المادة
    'size'        => '',
    'date'        => '',
    'href'        => '#',
    'previewable' => false,  // يفتح مودال معاينة عامة عند النقر على الصف (لا الزر)
])

<div @class([
        'flex items-center gap-4 border-b border-hairline-soft py-4 last:border-0',
        'cursor-pointer' => $previewable,
    ])
    @if ($previewable)
        data-file-preview-trigger
        data-file-name="{{ $name }}"
        data-file-subject="{{ $subject }}"
        data-file-size="{{ $size }}"
        data-file-date="{{ $date }}"
        data-file-href="{{ $href }}"
        role="button"
        tabindex="0"
    @endif>
    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-tag/12 text-tag">
        <x-icon name="folder" class="size-5" />
    </span>

    <div class="min-w-0 flex-1">
        <p class="truncate text-ui font-medium text-ink">{{ $name }}</p>
        <p class="num mt-0.5 text-caption text-stone">
            @if ($subject)
                {{ $subject }} ·
            @endif
            {{ $size }} · {{ $date }}
        </p>
    </div>

    <a href="{{ $href }}"
       data-file-download
       class="grid size-9 shrink-0 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink"
       aria-label="{{ __('student.download') }}">
        <x-icon name="download" class="size-5" />
    </a>
</div>
