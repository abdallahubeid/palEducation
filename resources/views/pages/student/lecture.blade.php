@php
    // بيانات عرض — تُستبدل باستعلامات عند بناء نموذج المجال + مزوّد الفيديو (م-5)
    $lecture = [
        'number'      => 4,
        'title'       => 'قواعد الاشتقاق',
        'subjectName' => 'الرياضيات',
        'subjectSlug' => 'math',
        'teacher'     => 'أ. سامر خليل',
        'duration'    => '20:00',
        'uploadDate'  => '2026-08-01',
        'description' => 'نتعرّف في هذه المحاضرة على القواعد الأساسية للاشتقاق: قاعدة القوة، قاعدة الضرب، وقاعدة القسمة، مع أمثلة محلولة خطوة بخطوة على كل قاعدة. المحاضرة أساسية لفهم تطبيقات المشتقة في المحاضرات القادمة، ويُنصح بمراجعة محاضرة "المشتقة الأولى" قبلها إن لم تكن قد شاهدتها.',
    ];

    $files = [
        ['name' => 'ورقة تمارين — قواعد الاشتقاق', 'size' => '860KB', 'date' => '2026-08-01'],
    ];

    $breadcrumb = [
        ['label' => __('student.breadcrumb_subjects'), 'href' => Route::has('student.subjects.index') ? route('student.subjects.index') : '#'],
        ['label' => $lecture['subjectName'], 'href' => route('student.subjects.show', $lecture['subjectSlug'])],
        ['label' => $lecture['title']],
    ];
@endphp

<x-layouts.student
    :title="$lecture['title']"
    :student-name="'محمد أبو عودة'"
    :subscription-state="'active'"
    :unread-count="2">

    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <x-ui.breadcrumb :items="$breadcrumb" />

        <x-domain.video-player :title="$lecture['title']" />

        {{-- أدوات عرض تجريبية — بديل مؤقّت لمشغّل حقيقي بانتظار م-5 --}}
        <div class="flex items-center justify-between gap-3 rounded-lg border border-dashed border-hairline-strong bg-surface-soft px-4 py-3">
            <p class="text-caption text-stone">{{ __('student.demo_tools_label') }}</p>
            <button type="button" data-simulate-lecture-end
                    class="shrink-0 rounded-md bg-canvas px-3 py-1.5 text-caption font-semibold text-steel ring-1 ring-hairline-strong transition hover:text-ink">
                {{ __('student.simulate_lecture_end') }}
            </button>
        </div>

        <div>
            <h1 class="text-h3 font-bold text-ink">{{ $lecture['title'] }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-ui.badge variant="neutral">{{ $lecture['teacher'] }}</x-ui.badge>
                <x-ui.badge variant="duration">{{ $lecture['duration'] }}</x-ui.badge>
                <span class="num text-caption text-stone">{{ $lecture['uploadDate'] }}</span>
            </div>
        </div>

        <details class="tile group overflow-hidden">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 p-5 text-ui font-semibold text-ink marker:hidden">
                {{ __('student.lecture_description_title') }}
                <x-icon name="chevron-down" class="size-4 shrink-0 text-steel transition duration-300 group-open:rotate-180" />
            </summary>
            <p class="border-t border-hairline-soft p-5 text-body leading-relaxed text-steel">
                {{ $lecture['description'] }}
            </p>
        </details>

        <div class="tile p-3 sm:p-6">
            <h2 class="px-2 text-h5 font-semibold text-ink sm:px-0">{{ __('student.lecture_files_title') }}</h2>

            @if (count($files))
                <div class="mt-3 divide-y divide-hairline-soft">
                    @foreach ($files as $file)
                        <x-domain.file-row :name="$file['name']" :size="$file['size']" :date="$file['date']" href="#" />
                    @endforeach
                </div>
            @else
                <x-ui.empty-state icon="folder" :title="__('student.lecture_files_empty')" class="py-8" />
            @endif
        </div>

        <div class="flex items-center justify-between gap-3">
            <x-ui.button variant="secondary" size="md" href="#">
                <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                {{ __('student.lecture_prev') }}
            </x-ui.button>

            <x-ui.button variant="secondary" size="md" href="#">
                {{ __('student.lecture_next') }}
                <x-icon name="arrow" class="size-4 -scale-x-100 rtl:scale-x-100" />
            </x-ui.button>
        </div>
    </div>

    {{-- مودال «جاهز للاختبار؟» — يفتح تلقائياً عند حدث lecture:ended، بلا زر يدوي --}}
    <x-ui.modal id="quiz-ready-modal" size="sm" open-on="lecture:ended" labelledby="quiz-ready-title">
        <div class="flex flex-col items-center gap-4 text-center">
            <span class="grid size-16 place-items-center rounded-full bg-accent/14 text-accent-deep">
                <x-icon name="check-circle" class="size-8" />
            </span>

            <div>
                <h2 id="quiz-ready-title" class="text-h4 font-bold text-ink">{{ __('student.quiz_ready_title') }}</h2>
                <p class="mt-2 text-ui text-steel">{{ __('student.quiz_ready_body') }}</p>
            </div>

            <x-ui.button variant="primary" size="md" href="#" class="w-full">
                {{ __('student.quiz_ready_cta') }}
                <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
            </x-ui.button>

            <button type="button" data-modal-close class="text-caption font-semibold text-stone transition hover:text-ink">
                {{ __('student.quiz_ready_later') }}
            </button>
        </div>
    </x-ui.modal>

</x-layouts.student>
