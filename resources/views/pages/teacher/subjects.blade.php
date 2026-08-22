@php
    // 🔴 نطاق المعلم: المواد المسندة له فقط. هذه المصفوفة تحاكي نتيجة استعلام
    // مُنطاق بـ teacher_id — لا قائمة كل مواد الفرع.
    $subjects = [
        [
            'name' => 'الرياضيات', 'branch' => 'الفرع العلمي', 'icon' => 'beaker', 'tone' => 'accent',
            'students' => 84, 'lectures' => 12, 'files' => 8, 'avg' => 82, 'drafts' => 1,
        ],
        [
            'name' => 'الفيزياء', 'branch' => 'الفرع العلمي', 'icon' => 'compass', 'tone' => 'tag',
            'students' => 67, 'lectures' => 9, 'files' => 5, 'avg' => 74, 'drafts' => 0,
        ],
        [
            'name' => 'رياضيات تجارية', 'branch' => 'الفرع التجاري', 'icon' => 'briefcase', 'tone' => 'amber',
            'students' => 35, 'lectures' => 3, 'files' => 2, 'avg' => 69, 'drafts' => 1,
        ],
    ];

    $toneChip = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag'    => 'bg-tag/12 text-tag',
        'amber'  => 'bg-amber/12 text-amber-deep',
        'warn'   => 'bg-warn/14 text-warn-deep',
    ];
@endphp

<x-layouts.teacher :title="__('teacher.subjects_title')">

    <div>
        <h1 class="text-h2 font-bold text-ink">{{ __('teacher.subjects_title') }}</h1>
        <p class="mt-1 text-ui text-steel">{{ __('teacher.subjects_subtitle') }}</p>
    </div>

    @if (empty($subjects))
        <div class="tile mt-6">
            <x-ui.empty-state icon="book"
                              :title="__('teacher.subjects_empty_title')"
                              :body="__('teacher.subjects_empty_body')" />
        </div>
    @else
        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($subjects as $subject)
                <article class="tile flex flex-col gap-4 p-5">

                    <div class="flex items-start gap-3">
                        <span class="grid size-11 shrink-0 place-items-center rounded-lg {{ $toneChip[$subject['tone']] }}">
                            <x-icon :name="$subject['icon']" class="size-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-h5 font-semibold text-ink">{{ $subject['name'] }}</h2>
                            <p class="truncate text-caption text-stone">{{ $subject['branch'] }}</p>
                        </div>

                        @if ($subject['drafts'] > 0)
                            <x-ui.badge variant="warn" class="shrink-0">
                                <span class="num">{{ $subject['drafts'] }}</span> {{ __('teacher.status_draft') }}
                            </x-ui.badge>
                        @endif
                    </div>

                    {{-- ثلاث قيم رقمية — الشبكة تنعكس تلقائياً مع الاتجاه --}}
                    <dl class="grid grid-cols-3 gap-2 border-y border-hairline-soft py-3">
                        <div>
                            <dt class="text-caption text-stone">{{ __('teacher.subject_students') }}</dt>
                            <dd class="num mt-0.5 text-h5 font-bold text-ink">{{ $subject['students'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-caption text-stone">{{ __('teacher.subject_lectures') }}</dt>
                            <dd class="num mt-0.5 text-h5 font-bold text-ink">{{ $subject['lectures'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-caption text-stone">{{ __('teacher.subject_files') }}</dt>
                            <dd class="num mt-0.5 text-h5 font-bold text-ink">{{ $subject['files'] }}</dd>
                        </div>
                    </dl>

                    <div>
                        <div class="flex items-center justify-between text-caption text-steel">
                            <span>{{ __('teacher.subject_avg') }}</span>
                            <span class="num font-semibold text-ink">{{ $subject['avg'] }}%</span>
                        </div>
                        <x-ui.progress-bar :percent="$subject['avg']" size="sm" class="mt-2" />
                    </div>

                    <div class="mt-auto flex gap-2 pt-1">
                        <x-ui.button variant="secondary" size="sm"
                                     :href="Route::has('teacher.lectures') ? route('teacher.lectures') : '#'"
                                     class="flex-1">
                            {{ __('teacher.subject_manage') }}
                        </x-ui.button>

                        <x-ui.button size="sm"
                                     :href="Route::has('teacher.lectures.create') ? route('teacher.lectures.create') : '#'"
                                     class="shrink-0"
                                     :aria-label="__('teacher.upload_lecture')">
                            <x-icon name="plus" class="size-4" />
                        </x-ui.button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

</x-layouts.teacher>
