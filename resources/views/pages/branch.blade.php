@php
    // بيانات عرض ثابتة — تُستبدل باستعلام الفرع عند بناء الخادم.
    // الفرع الحالي يُحدَّد من {slug} المسار؛ نستعمل العلمي كنموذج.
    $slug = request()->route('branch') ?? 'scientific';

    $catalog = [
        'scientific' => [
            'name' => 'الفرع العلمي', 'icon' => 'beaker', 'tone' => 'accent',
            'summary' => 'الفرع الذي يفتح أبواب كليات الطب والهندسة والعلوم. مواده تقوم على الفهم لا الحفظ — ولهذا كل محاضرة هنا يتبعها كويز فوري يقيس فهمك لا ذاكرتك.',
            'subjects' => [
                ['name' => 'الرياضيات', 'teacher' => 'أ. سامر خليل', 'icon' => 'beaker',  'tone' => 'accent', 'lectures' => 12, 'files' => 8],
                ['name' => 'الفيزياء',  'teacher' => 'أ. رنا عوض',   'icon' => 'compass', 'tone' => 'tag',    'lectures' => 9,  'files' => 5],
                ['name' => 'الكيمياء',  'teacher' => 'أ. وليد حمد',  'icon' => 'beaker',  'tone' => 'amber',  'lectures' => 10, 'files' => 6],
                ['name' => 'الأحياء',   'teacher' => 'أ. هدى ناصر',  'icon' => 'book',    'tone' => 'warn',   'lectures' => 11, 'files' => 7],
                ['name' => 'اللغة العربية', 'teacher' => 'أ. مازن سالم', 'icon' => 'book', 'tone' => 'tag',   'lectures' => 8,  'files' => 4],
                ['name' => 'اللغة الإنجليزية', 'teacher' => 'أ. لينا فرح', 'icon' => 'book', 'tone' => 'accent', 'lectures' => 8, 'files' => 5],
                ['name' => 'التربية الإسلامية', 'teacher' => 'أ. أحمد بشير', 'icon' => 'book', 'tone' => 'amber', 'lectures' => 6, 'files' => 3],
            ],
            'teachers' => [
                ['name' => 'أ. سامر خليل', 'subject' => 'الرياضيات', 'initials' => 'س خ', 'lectures' => 12, 'tone' => 'accent'],
                ['name' => 'أ. رنا عوض',   'subject' => 'الفيزياء',  'initials' => 'ر ع', 'lectures' => 9,  'tone' => 'tag'],
                ['name' => 'أ. وليد حمد',  'subject' => 'الكيمياء',  'initials' => 'و ح', 'lectures' => 10, 'tone' => 'amber'],
                ['name' => 'أ. هدى ناصر',  'subject' => 'الأحياء',   'initials' => 'ه ن', 'lectures' => 11, 'tone' => 'warn'],
            ],
        ],
    ];

    $branch = $catalog[$slug] ?? $catalog['scientific'];

    $totalLectures = array_sum(array_column($branch['subjects'], 'lectures'));
    $totalFiles = array_sum(array_column($branch['subjects'], 'files'));

    $heroTones = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag'    => 'bg-tag/12 text-tag',
        'amber'  => 'bg-amber/12 text-amber-deep',
        'warn'   => 'bg-warn/14 text-warn-deep',
    ];

    $stats = [
        ['value' => count($branch['subjects']), 'label' => __('public.branch_stat_subjects')],
        ['value' => count($branch['teachers']), 'label' => __('public.branch_stat_teachers')],
        ['value' => $totalLectures,             'label' => __('public.branch_stat_lectures')],
        ['value' => $totalFiles,                'label' => __('public.branch_stat_files')],
    ];
@endphp

<x-layouts.public :title="$branch['name']">

    {{-- هيرو الفرع --}}
    <section class="border-b border-hairline bg-canvas py-10 lg:py-14">
        <div class="mx-auto max-w-[1280px] px-6">
            <x-ui.breadcrumb :items="[
                ['label' => __('nav.home'), 'href' => route('home')],
                ['label' => __('public.branch_all_branches'), 'href' => route('branches.index')],
                ['label' => $branch['name']],
            ]" />

            <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">
                    <div class="flex items-center gap-4">
                        <span class="grid size-16 shrink-0 place-items-center rounded-xl {{ $heroTones[$branch['tone']] }}">
                            <x-icon :name="$branch['icon']" class="size-8" />
                        </span>

                        <div class="min-w-0">
                            <p class="text-micro font-semibold text-stone">{{ __('public.branch_eyebrow') }}</p>
                            <h1 class="mt-1 text-h1 font-bold text-ink">{{ $branch['name'] }}</h1>
                        </div>
                    </div>

                    {{-- نص يُقرأ فعلاً: 16px · 1.75 · بعرض قراءة محدود --}}
                    <p class="measure mt-5 text-body leading-[1.75] text-steel">{{ $branch['summary'] }}</p>
                </div>

                <x-ui.button size="lg"
                             :href="Route::has('auth.register') ? route('auth.register') : '#'"
                             class="w-full shrink-0 sm:w-auto">
                    {{ __('public.branch_enroll_action') }}
                </x-ui.button>
            </div>

            {{-- أربع قيم رقمية — تُبنى من البيانات لا تُكتب يدوياً --}}
            <dl class="mt-10 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-hairline lg:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="bg-canvas px-5 py-6 text-center">
                        <dt class="order-2 mt-1 text-caption text-stone">{{ $stat['label'] }}</dt>
                        <dd class="num text-h2 font-bold text-ink">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- مواد الفرع --}}
    <section class="py-14 lg:py-20">
        <div class="mx-auto max-w-[1280px] px-6">
            <x-ui.section-head
                :eyebrow="__('nav.branches')"
                :title="__('public.branch_subjects_title')"
                :lede="__('public.branch_subjects_subtitle')" />

            @if (empty($branch['subjects']))
                <div class="tile mt-8">
                    <x-ui.empty-state icon="book"
                                      :title="__('public.branch_subjects_empty_title')"
                                      :body="__('public.branch_subjects_empty_body')" />
                </div>
            @else
                {{--
                    🔴 سياق الزائر: بلا شريط تقدّم. الزائر لا يملك تقدّماً،
                    وعرض «0%» له يُقرأ إخفاقاً لا حياداً — لذلك percent
                    لا يُمرَّر إطلاقاً (الافتراضي null يُسقط الكتلة).
                --}}
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($branch['subjects'] as $subject)
                        <x-domain.subject-card
                            :name="$subject['name']"
                            :teacher="$subject['teacher']"
                            :icon="$subject['icon']"
                            :tone="$subject['tone']"
                            :lectures-count="$subject['lectures']"
                            :files-count="$subject['files']"
                            :href="Route::has('auth.register') ? route('auth.register') : '#'" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- أساتذة الفرع --}}
    <section class="bg-canvas py-14 lg:py-20">
        <div class="mx-auto max-w-[1280px] px-6">
            <x-ui.section-head
                :eyebrow="__('home.teachers_eyebrow')"
                :title="__('public.branch_teachers_title')"
                :lede="__('public.branch_teachers_subtitle')" />

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($branch['teachers'] as $teacher)
                    <x-domain.teacher-card
                        :name="$teacher['name']"
                        :subject="$teacher['subject']"
                        :branch="$branch['name']"
                        :initials="$teacher['initials']"
                        :lectures="$teacher['lectures']"
                        :tone="$teacher['tone']" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- دعوة التسجيل --}}
    <section class="py-16 lg:py-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="overflow-hidden rounded-xxl bg-linear-to-bl from-deep-from to-deep-to px-6 py-12 text-center sm:px-12 lg:py-16">
                <h2 class="text-h2 font-bold text-on-dark">
                    {{ __('public.branch_enroll_title', ['branch' => $branch['name']]) }}
                </h2>
                <p class="measure mx-auto mt-3 text-lead text-on-dark-muted">{{ __('public.branch_enroll_body') }}</p>

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <x-ui.button variant="on-dark" size="lg"
                                 :href="Route::has('auth.register') ? route('auth.register') : '#'"
                                 class="w-full sm:w-auto">
                        {{ __('public.branch_enroll_action') }}
                    </x-ui.button>

                    <a href="{{ route('branches.index') }}"
                       class="inline-flex min-h-11 items-center rounded-full px-5 text-ui font-semibold text-accent-on-dark transition hover:text-on-dark">
                        {{ __('public.branch_all_branches') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

</x-layouts.public>
