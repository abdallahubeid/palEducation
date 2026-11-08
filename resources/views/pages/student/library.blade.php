@php
    use Illuminate\Support\Facades\URL;

    $iconFor = fn (?string $mime): string => match (true) {
        $mime === 'application/pdf' => 'clipboard',
        str_contains((string) $mime, 'word') => 'book',
        str_contains((string) $mime, 'presentation') => 'play',
        str_contains((string) $mime, 'zip') => 'folder',
        default => 'folder',
    };
@endphp

<x-layouts.student
    :title="__('student.library_title')"
    :student-name="auth()->user()->displayName()"
    :student-avatar="auth()->user()->avatar_url">

    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <div>
            <h1 class="text-h2 font-bold text-ink">{{ __('student.library_title') }}</h1>
            <p class="mt-1 text-ui text-steel">ملفات فرعك المنشورة عبر كل موادك.</p>
        </div>

        <form method="GET" action="{{ route('student.library') }}"
              class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="relative flex flex-1 items-center">
                <x-icon name="search" class="pointer-events-none absolute start-3 size-4 text-muted" />
                <input type="search" name="search" value="{{ $filters['search'] }}"
                       placeholder="{{ __('student.library_search_placeholder') }}"
                       class="h-11 w-full rounded-full border border-hairline bg-surface-soft ps-9 pe-4 text-ui text-ink
                              placeholder:text-muted transition focus-visible:border-accent focus-visible:bg-canvas
                              focus-visible:outline-none">
            </label>

            @if ($subjects->count() > 1)
                <select name="subject"
                        class="h-11 rounded-full border border-hairline bg-canvas px-4 text-ui text-ink transition
                               focus-visible:border-accent focus-visible:outline-none">
                    <option value="">{{ __('student.library_filter_subject_all') }}</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected($filters['subject'] === $subject->id)>
                            {{ $subject->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <x-ui.button type="submit" variant="secondary" size="sm">تصفية</x-ui.button>

            @if (array_filter($filters, fn ($v) => filled($v)))
                <x-ui.button variant="ghost" size="sm" :href="route('student.library')">مسح</x-ui.button>
            @endif
        </form>

        <div class="tile p-3 sm:p-6">
            @if ($files->isEmpty())
                <x-ui.empty-state icon="folder"
                                  :title="array_filter($filters, fn ($v) => filled($v))
                                      ? __('student.library_no_results_title')
                                      : __('student.library_empty_title')"
                                  :body="array_filter($filters, fn ($v) => filled($v))
                                      ? __('student.library_no_results_body')
                                      : __('student.library_empty_body')" />
            @else
                <ul class="divide-y divide-hairline-soft">
                    @foreach ($files as $file)
                        @php
                            $downloadUrl = URL::temporarySignedRoute(
                                'student.library.download', now()->addMinutes(10), ['file' => $file->id]
                            );
                            $accessed = in_array($file->id, $accessedIds, true);
                        @endphp
                        <li class="flex items-center gap-4 py-4">
                            <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-surface text-stone">
                                <x-icon :name="$iconFor($file->media?->mime_type)" class="size-5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-ui font-semibold text-ink">{{ $file->title }}</p>
                                <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-micro text-stone">
                                    <span>{{ $file->subject?->name }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span class="rounded {{ $file->category->badge() }} px-1.5 py-0.5 font-semibold">
                                        {{ $file->category->label() }}
                                    </span>
                                    <span aria-hidden="true">·</span>
                                    <span class="num" dir="ltr">{{ $file->humanSize() }}</span>
                                    @if ($accessed)
                                        <span aria-hidden="true">·</span>
                                        <span class="text-accent-deep">نُزّل</span>
                                    @endif
                                </p>
                            </div>

                            {{-- ⚠️ رابط موقَّع قصير — لا مسار ملف مباشر. target=_blank
                                 ليفتح الملف بلا مغادرة القائمة. --}}
                            <x-ui.button variant="secondary" size="sm" :href="$downloadUrl"
                                         target="_blank" rel="noopener">
                                <x-icon name="folder" class="size-4" />
                                تنزيل
                            </x-ui.button>
                        </li>
                    @endforeach
                </ul>

                @if ($files->hasPages())
                    <div class="mt-5">{{ $files->links() }}</div>
                @endif
            @endif
        </div>
    </div>

</x-layouts.student>
