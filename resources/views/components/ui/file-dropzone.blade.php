@props([
    'name'        => 'file',
    'accept'      => '',                 // ".pdf,.docx" أو "video/*"
    'multiple'    => false,
    'icon'        => 'folder',
    'title'       => null,
    'hint'        => null,
    'maxLabel'    => null,               // نص وصفي للحد الأقصى — لا تحقّق فعلي بلا خادم
    'simulate'    => false,              // يحاكي شريط تقدّم الرفع (بانتظار م-5)
])

@php
    $id = $attributes->get('id') ?: 'drop-' . $name;
@endphp

{{--
    منطقة رفع بالسحب والإفلات. الحاوية <label> فالنقر في أي مكان يفتح
    منتقي الملفات، والتركيز بلوحة المفاتيح يصل للحقل نفسه — لا حاجة
    لـtabindex يدوي ولا لـrole مخترع.

    ⚠️ بلا خادم بعد: شريط التقدّم محاكاة صريحة، والتحقّق من الحجم/النوع
    يعرض رسالة فقط ولا يمنع شيئاً فعلياً. يُستبدل عند حسم م-5.
--}}
<div {{ $attributes->except('id')->merge(['class' => 'flex flex-col gap-3']) }}
     data-dropzone
     @if ($simulate) data-dropzone-simulate @endif>

    <label for="{{ $id }}"
           data-dropzone-target
           class="flex cursor-pointer flex-col items-center gap-3 rounded-xl border-2 border-dashed border-hairline-strong
                  bg-surface-soft px-6 py-10 text-center transition
                  hover:border-accent hover:bg-accent-soft/40
                  focus-within:border-accent focus-within:bg-accent-soft/40">

        <input type="file"
               id="{{ $id }}"
               name="{{ $name }}{{ $multiple ? '[]' : '' }}"
               data-dropzone-input
               @if ($accept) accept="{{ $accept }}" @endif
               @if ($multiple) multiple @endif
               class="sr-only">

        <span class="grid size-12 place-items-center rounded-full bg-accent/14 text-accent-deep">
            <x-icon :name="$icon" class="size-6" />
        </span>

        <span class="block">
            <span class="block text-body font-semibold text-ink">{{ $title ?? __('teacher.dropzone_title') }}</span>
            @if ($hint)
                <span class="mt-1 block text-caption text-steel">{{ $hint }}</span>
            @endif
            @if ($maxLabel)
                <span class="mt-1 block text-caption text-muted">{{ $maxLabel }}</span>
            @endif
        </span>

        <span class="inline-flex h-11 items-center rounded-full border border-hairline-strong bg-canvas px-5
                     text-ui font-semibold text-ink transition hover:bg-surface lg:h-10">
            {{ __('teacher.dropzone_browse') }}
        </span>
    </label>

    {{-- قائمة الملفات المختارة — يبنيها JS --}}
    <ul data-dropzone-files class="flex flex-col gap-2 empty:hidden"></ul>

    {{-- قالب صف الملف — يُستنسخ بـJS، فلا تُبنى العناصر بسلاسل نصية --}}
    <template data-dropzone-file-template>
        <li class="flex items-center gap-3 rounded-lg border border-hairline bg-canvas p-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-md bg-surface text-steel">
                <x-icon name="folder" class="size-5" />
            </span>

            <div class="min-w-0 flex-1">
                <p data-file-name class="truncate text-ui font-medium text-ink"></p>
                <p data-file-size class="num text-caption text-stone" dir="ltr"></p>

                <div data-file-progress hidden class="mt-2">
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-hairline-soft">
                        <div data-file-bar class="h-full rounded-full bg-accent transition-[width] duration-200" style="width:0%"></div>
                    </div>
                </div>
            </div>

            <span data-file-done hidden class="shrink-0 text-accent-deep">
                <x-icon name="check-circle" class="size-5" />
            </span>

            <button type="button" data-file-remove
                    class="grid size-11 shrink-0 place-items-center rounded-md text-steel transition
                           hover:bg-error/10 hover:text-error-deep lg:size-9"
                    aria-label="{{ __('teacher.dropzone_remove') }}">
                <x-icon name="close" class="size-4" />
            </button>
        </li>
    </template>
</div>
