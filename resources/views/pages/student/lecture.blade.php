@php
    /*
     * 🔴 صفحة مشاهدة حقيقية. كانت تحمل محاضرة مخترَعة كاملة — عنواناً
     * ومعلّماً ووصفاً وستّ محاضرات شقيقة وخمسة أسئلة كويز — وتعرضها لأي
     * مُعرِّف في المسار. فيديو حقيقي بجانب مخطّط وهمي أسوأ من صفحة فارغة:
     * القارئ لا يعرف أيّ نصفٍ يصدّق.
     *
     * ⚠️ قسم الكويز باقٍ ببنيته (التبديل فيديو ⇄ كويز) لكن بلا أسئلة: لا
     * جدول quizzes في المشروع بعد، وأسئلة ثابتة تُقرأ كويزاً حقيقياً.
     */
    $viewer = auth()->user();
    $subject = $lecture->subject;

    $subjectHref = $subject && Route::has('student.subjects.show')
        ? route('student.subjects.show', $subject->slug)
        : null;

    $breadcrumb = array_values(array_filter([
        Route::has('student.subjects.index')
            ? ['label' => __('student.breadcrumb_subjects'), 'href' => route('student.subjects.index')]
            : null,
        $subject ? ['label' => $subject->name, 'href' => $subjectHref] : null,
        ['label' => $lecture->title],
    ]));

    // السابق/التالي من الأشقّاء الحقيقيين — لا أرقام عرضية.
    $index = $siblings->search(fn ($item) => $item->id === $lecture->id);
    $previous = $index !== false && $index > 0 ? $siblings[$index - 1] : null;
    $next = $index !== false && $index < $siblings->count() - 1 ? $siblings[$index + 1] : null;

    // المدّة تُعرض فقط حين تكون معروفة: «00:00» تُقرأ محاضرة فارغة.
    $duration = $lecture->video_duration_seconds
        ? gmdate($lecture->video_duration_seconds >= 3600 ? 'H:i:s' : 'i:s', $lecture->video_duration_seconds)
        : null;
@endphp

<x-layouts.student
    :title="$lecture->title"
    :student-name="$viewer?->displayName() ?: ''"
    subscription-state="active"
    :unread-count="0">

    <div class="mx-auto flex max-w-6xl flex-col gap-6">
        <x-ui.breadcrumb :items="$breadcrumb" />

        @if ($isPreview)
            {{-- ⚠️ يُقال صراحةً لمن يعاين: هذه شاشة الطالب نفسها، فما يراه
                 هنا هو ما سيراه طلبته — بما في ذلك محاضرة ما زالت مسودّة. --}}
            <div class="rounded-xl border border-accent/30 bg-accent/8 px-4 py-3">
                <p class="text-caption text-ink">
                    معاينة — هذه الشاشة نفسها التي يراها الطالب.
                    @if ($lecture->status !== \App\Enums\LectureStatus::Published)
                        هذه المحاضرة غير منشورة، فلا يراها أحد غيرك بعد.
                    @endif
                </p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">

            {{-- العمود الرئيسي — عرضان يتبادلان الظهور بلا انتقال صفحة --}}
            <div class="flex min-w-0 flex-col">

                <div data-lecture-view="video" class="flex flex-col gap-6">

                    {{--
                        🔴 المشغّل داخل المنصّة لا على يوتيوب: رابط خارجي
                        يأخذ الطالب إلى صفحة لا نتحكّم بها — باقتراحات فيديو
                        وإعلانات وتعليقات — ويُنهي الجلسة عندنا.

                        ⚠️ dir="ltr" على الحاوية: شريط الزمن يمشي يساراً←يميناً
                        في كل اللغات، وعكسه يربك كل مستخدم. استثناء معلن في
                        قواعد الاتجاه.
                    --}}
                    @if ($lecture->embed_url)
                        @php
                            /*
                             * 🔴 تتبّع التقدّم للطالب فقط (لا المعاينة): enablejsapi=1
                             * يفتح YT IFrame API، و initLectureProgress يرسل المقاطع
                             * المُشاهَدة نبضةً إثر نبضة. المعلّم يعاين بلا تتبّع.
                             */
                            $trackProgress = ! ($isPreview ?? false)
                                && auth()->user()?->isStudent();
                            // ⚠️ يبقى الرابط نظيفاً كما هو للمعاينة؛ enablejsapi=1
                            // فقط حين نتتبّع تقدّم الطالب.
                            $embedSrc = $lecture->embed_url
                                . ($trackProgress ? '?enablejsapi=1' : '');
                        @endphp
                        <div dir="ltr" class="aspect-video w-full overflow-hidden rounded-xl bg-canvas-dark"
                             @if ($trackProgress)
                                 data-lecture-progress
                                 data-lecture-progress-url="{{ route('student.lectures.progress', $lecture) }}"
                             @endif>
                            <iframe src="{{ $embedSrc }}"
                                    class="size-full"
                                    title="{{ $lecture->title }}"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen></iframe>
                        </div>
                    @else
                        {{-- محاضرة بلا فيديو حالة حقيقية (مسودّة قيد الإعداد). --}}
                        <div class="grid aspect-video w-full place-items-center rounded-xl border border-dashed
                                    border-hairline-strong bg-surface-soft text-center">
                            <div>
                                <x-icon name="play" class="mx-auto size-8 text-muted" />
                                <p class="mt-3 text-caption text-stone">لم يُضَف فيديو لهذه المحاضرة بعد.</p>
                            </div>
                        </div>
                    @endif

                    <div>
                        <h1 class="text-h3 font-bold text-ink">{{ $lecture->title }}</h1>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @if ($lecture->teacher)
                                <x-ui.badge variant="neutral">{{ $lecture->teacher->full_name }}</x-ui.badge>
                            @endif

                            @if ($duration)
                                <x-ui.badge variant="duration">
                                    <bdi dir="ltr" class="num">{{ $duration }}</bdi>
                                </x-ui.badge>
                            @endif

                            @if ($lecture->published_at)
                                <span class="num text-caption text-stone" dir="ltr">
                                    {{ $lecture->published_at->format('Y-m-d') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        @if ($previous)
                            <x-ui.button variant="secondary" size="md"
                                         :href="route('student.lectures.show', $previous)">
                                <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                                {{ __('student.lecture_prev') }}
                            </x-ui.button>
                        @else
                            <span></span>
                        @endif

                        @if ($next)
                            <x-ui.button variant="secondary" size="md"
                                         :href="route('student.lectures.show', $next)">
                                {{ __('student.lecture_next') }}
                                <x-icon name="arrow" class="size-4 -scale-x-100 rtl:scale-x-100" />
                            </x-ui.button>
                        @endif
                    </div>

                    @if (filled($lecture->description))
                        <details id="lecture-description" class="tile group overflow-hidden scroll-mt-24" open>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 p-5
                                            text-ui font-semibold text-ink marker:hidden">
                                {{ __('student.lecture_description_title') }}
                                <x-icon name="chevron-down"
                                        class="size-4 shrink-0 text-steel transition duration-300 group-open:rotate-180" />
                            </summary>
                            {{-- whitespace-pre-line: فقرات المعلّم تبقى كما كتبها، والقيمة مُهرَّبة. --}}
                            <p class="whitespace-pre-line border-t border-hairline-soft p-5 text-body
                                      leading-relaxed text-steel">{{ $lecture->description }}</p>
                        </details>
                    @endif
                </div>

                {{--
                    ═══ الكويز ═══

                    🔴 البنية باقية كما هي (data-lecture-view="quiz" والتبديل
                    الذي يعمل عليه app.js) لكن بلا أسئلة: لا جدول quizzes بعد،
                    وخمسة أسئلة ثابتة كانت تُقرأ كويزاً حقيقياً — وتُعطي طالباً
                    نتيجة لا تعني شيئاً.

                    ⚠️ لا تُحذف هذه الكتلة: وعد المنصّة الأساسي أن كل محاضرة
                    يتبعها كويز، وهذا مكانه المحجوز.
                --}}
                <div data-lecture-view="quiz" hidden class="flex flex-col gap-6">
                    <div class="tile flex flex-col items-center gap-3 px-6 py-14 text-center">
                        <span class="grid size-12 place-items-center rounded-full bg-surface text-stone">
                            <x-icon name="clipboard" class="size-5" />
                        </span>

                        <h2 class="text-h4 font-bold text-ink">كويز المحاضرة — قريباً</h2>
                        <p class="measure text-body leading-relaxed text-steel">
                            سيفتح الكويز تلقائياً بعد إتمام مشاهدة المحاضرة، ويظهر هنا فور إضافته.
                        </p>

                        <x-ui.button variant="secondary" size="md" data-lecture-view-to="video" class="mt-2">
                            العودة إلى الفيديو
                        </x-ui.button>
                    </div>
                </div>
            </div>

            {{--
                ═══ مخطط المادة — أكورديون الوحدات ═══

                🔴 الوحدة الحاملة للمحاضرة الجارية تُفتح وحدها، والباقي مطويّ.
                قائمة مسطّحة بعشرين محاضرة تجعل الطالب يبحث عن موضعه في كل
                تحميل؛ والأكورديون المغلق بالكامل يفعل الشيء نفسه — لذلك
                activeUnit يأتي من الخادم لا من قيمة ابتدائية ثابتة.

                ⚠️ x-show لا @if: الطيّ حالة عرض على العميل، وإخفاء الوحدات
                على الخادم كان سيمنع فتحها بلا إعادة تحميل.
            --}}
            <div class="lg:sticky lg:top-24"
                 x-data="{ activeUnit: @js($activeUnit) }">

                <div class="tile flex flex-col gap-1 p-3 sm:p-4">
                    <h2 class="px-2 pb-2 pt-2 text-h5 font-semibold text-ink">
                        {{ __('student.course_outline_title') }}
                    </h2>

                    @foreach ($lecturesByUnit as $unitName => $unitLectures)
                        @php
                            // الترقيم متّصل عبر الوحدات: رقم المحاضرة في
                            // المادة كلها لا داخل وحدتها.
                            $unitHasCurrent = $unitLectures->contains('id', $lecture->id);
                        @endphp

                        <div class="overflow-hidden rounded-lg border border-hairline-soft">

                            {{-- ترويسة الوحدة --}}
                            <button type="button"
                                    @click="activeUnit = (activeUnit === @js($unitName) ? null : @js($unitName))"
                                    :aria-expanded="(activeUnit === @js($unitName)).toString()"
                                    @class([
                                        'flex w-full cursor-pointer items-center gap-3 px-3 py-3 text-start transition',
                                        'bg-accent-soft/60' => $unitHasCurrent,
                                        'hover:bg-surface' => ! $unitHasCurrent,
                                    ])>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-ui font-semibold text-ink">{{ $unitName }}</span>
                                    {{-- العدّ بصيغة واحدة: العربية لها ستّ صيغ
                                         جمع، ورقم مجرّد يتجنّبها كلها. --}}
                                    <span class="num mt-0.5 block text-micro text-stone">
                                        {{ __('student.lecture_unit_count', ['count' => $unitLectures->count()]) }}
                                    </span>
                                </span>

                                {{-- سهم رأسي: يدور ولا ينعكس، فلا يحتاج معالجة اتجاه. --}}
                                <x-icon name="chevron-down"
                                        class="size-4 shrink-0 text-steel transition-transform duration-300"
                                        ::class="activeUnit === @js($unitName) && 'rotate-180'" />
                            </button>

                            {{-- محاضرات الوحدة --}}
                            <div x-show="activeUnit === @js($unitName)" x-cloak
                                 class="border-t border-hairline-soft">
                                @foreach ($unitLectures as $unitLecture)
                                    @php $isCurrent = $unitLecture->id === $lecture->id; @endphp

                                    <a href="{{ route('student.lectures.show', $unitLecture) }}"
                                       @class([
                                           'flex items-center gap-3 px-3 py-2.5 text-ui transition',
                                           // 🔴 المحاضرة الجارية تُميَّز بثلاث إشارات
                                           // معاً — خلفية وحدّ ونصّ صريح — لا باللون
                                           // وحده: اللون وحده لا يصل من لا يميّزه.
                                           'border-s-2 border-accent bg-accent-soft font-semibold text-accent-deep' => $isCurrent,
                                           'text-steel hover:bg-surface hover:text-ink' => ! $isCurrent,
                                       ])
                                       @if ($isCurrent) aria-current="page" @endif>

                                        <span @class([
                                            'grid size-6 shrink-0 place-items-center rounded-full',
                                            'bg-accent text-on-primary' => $isCurrent,
                                            'bg-surface text-stone' => ! $isCurrent,
                                        ])>
                                            <x-icon name="play" class="size-3" />
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate">{{ $unitLecture->title }}</span>

                                            @if ($isCurrent)
                                                <span class="mt-0.5 block text-micro font-medium text-accent-deep">
                                                    {{ __('student.lecture_now_playing') }}
                                                </span>
                                            @endif
                                        </span>

                                        @if ($unitLecture->status !== \App\Enums\LectureStatus::Published)
                                            {{-- تظهر للمعلّم وحده — الطالب لا يُحمَّل له غير المنشور. --}}
                                            <x-ui.badge variant="warn" class="shrink-0">مسودّة</x-ui.badge>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</x-layouts.student>
