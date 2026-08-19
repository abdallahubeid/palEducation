@php
    // بيانات عرض — ملفات فرعه عبر كل المواد، تُستبدل باستعلام حقيقي لاحقاً
    $files = [
        ['name' => 'ورقة عمل — المشتقات',        'subject' => 'الرياضيات',        'type' => 'pdf', 'size' => '2.4MB', 'date' => '2026-08-10'],
        ['name' => 'ملخص الفصل الأول',            'subject' => 'الرياضيات',        'type' => 'pdf', 'size' => '1.1MB', 'date' => '2026-08-05'],
        ['name' => 'امتحان نصفي سابق',            'subject' => 'الرياضيات',        'type' => 'pdf', 'size' => '3.0MB', 'date' => '2026-07-28'],
        ['name' => 'ورقة تمارين — قواعد الاشتقاق', 'subject' => 'الرياضيات',        'type' => 'doc', 'size' => '860KB', 'date' => '2026-08-01'],
        ['name' => 'ملخص قوانين نيوتن',           'subject' => 'الفيزياء',         'type' => 'pdf', 'size' => '1.8MB', 'date' => '2026-08-09'],
        ['name' => 'أوراق تجارب معملية',          'subject' => 'الفيزياء',         'type' => 'doc', 'size' => '2.1MB', 'date' => '2026-07-30'],
        ['name' => 'جدول العناصر الدوري',         'subject' => 'الكيمياء',         'type' => 'pdf', 'size' => '640KB', 'date' => '2026-08-02'],
        ['name' => 'ملخص التفاعلات الكيميائية',   'subject' => 'الكيمياء',         'type' => 'pdf', 'size' => '1.4MB', 'date' => '2026-07-25'],
        ['name' => 'Grammar Rules Summary',        'subject' => 'اللغة الإنجليزية', 'type' => 'pdf', 'size' => '980KB', 'date' => '2026-08-07'],
        ['name' => 'نصوص محفوظة — الأدب',         'subject' => 'اللغة العربية',    'type' => 'doc', 'size' => '1.2MB', 'date' => '2026-07-20'],
    ];

    $subjectNames = collect($files)->pluck('subject')->unique()->values();
@endphp

<x-layouts.student
    :title="__('student.library_title')"
    :student-name="'محمد أبو عودة'"
    :subscription-state="'active'"
    :unread-count="2">

    <div class="mx-auto flex max-w-6xl flex-col gap-6" data-library>
        <h1 class="text-h2 font-bold text-ink">{{ __('student.library_title') }}</h1>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="relative flex flex-1 items-center">
                <x-icon name="search" class="pointer-events-none absolute start-3 size-4 text-muted" />
                <input type="search"
                       data-library-search
                       placeholder="{{ __('student.library_search_placeholder') }}"
                       class="h-11 w-full rounded-full border border-hairline bg-surface-soft ps-9 pe-4 text-ui text-ink
                              placeholder:text-muted transition focus-visible:border-accent focus-visible:bg-canvas
                              focus-visible:outline-none">
            </label>

            <select data-library-filter-subject
                    class="h-11 rounded-full border border-hairline bg-canvas px-4 text-ui text-ink transition
                           hover:border-hairline-strong focus-visible:border-accent focus-visible:outline-none">
                <option value="all">{{ __('student.library_filter_subject_all') }}</option>
                @foreach ($subjectNames as $subjectName)
                    <option value="{{ $subjectName }}">{{ $subjectName }}</option>
                @endforeach
            </select>

            <select data-library-filter-type
                    class="h-11 rounded-full border border-hairline bg-canvas px-4 text-ui text-ink transition
                           hover:border-hairline-strong focus-visible:border-accent focus-visible:outline-none">
                <option value="all">{{ __('student.library_filter_type_all') }}</option>
                <option value="pdf">{{ __('student.library_filter_type_pdf') }}</option>
                <option value="doc">{{ __('student.library_filter_type_doc') }}</option>
            </select>
        </div>

        <div class="tile p-3 sm:p-6">
            @if (count($files))
                <div data-library-list class="divide-y divide-hairline-soft">
                    @foreach ($files as $file)
                        <div data-library-row data-file-subject="{{ $file['subject'] }}" data-file-type="{{ $file['type'] }}" data-file-search="{{ $file['name'] . ' ' . $file['subject'] }}">
                            <x-domain.file-row
                                :name="$file['name']"
                                :subject="$file['subject']"
                                :size="$file['size']"
                                :date="$file['date']"
                                href="#"
                                :previewable="true" />
                        </div>
                    @endforeach
                </div>

                <div data-library-no-results hidden>
                    <x-ui.empty-state icon="search" :title="__('student.library_no_results_title')" :body="__('student.library_no_results_body')" class="py-8" />
                </div>

                <div class="mt-5">
                    <x-ui.pagination :current-page="1" :last-page="2" />
                </div>
            @else
                <x-ui.empty-state icon="folder" :title="__('student.library_empty_title')" :body="__('student.library_empty_body')" class="py-8" />
            @endif
        </div>
    </div>

    {{-- مودال معاينة عام — واحد لكل الملفات، JS يملأه عند النقر --}}
    <x-ui.modal id="file-preview-modal" size="md" labelledby="file-preview-title">
        <div class="flex flex-col items-center gap-4 py-4 text-center">
            <span class="grid size-16 place-items-center rounded-full bg-tag/12 text-tag">
                <x-icon name="folder" class="size-8" />
            </span>

            <div>
                <h2 id="file-preview-title" data-preview-name class="text-h5 font-bold text-ink"></h2>
                <p data-preview-meta class="mt-1 num text-caption text-stone"></p>
            </div>

            <p class="max-w-sm text-ui text-steel">{{ __('student.library_preview_unavailable') }}</p>

            <a data-preview-download href="#" class="inline-flex h-11 items-center gap-2 rounded-full bg-accent px-6
                      text-ui font-semibold text-on-primary transition hover:bg-amber-deep">
                <x-icon name="download" class="size-4" />
                {{ __('student.download') }}
            </a>
        </div>
    </x-ui.modal>

</x-layouts.student>
