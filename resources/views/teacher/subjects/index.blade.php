@php
    /*
     * 🔴 كل رقم هنا مشتقّ من جدول موجود فعلاً.
     *
     * الشاشة الثابتة التي حلّت محلّها كانت تعرض «متوسط النتائج» لكل مادة
     * (82% · 74% · 69%) — ولا يوجد في المشروع جدول كويزات ولا محاولات ولا
     * تقدّم بعد، فالرقم كان بلا مصدر. حُذف شريط التقدّم بدل تلفيقه: معلّم
     * يتصرّف بناءً على متوسط مخترَع أسوأ حالاً ممّن لا يرى شيئاً.
     *
     * ⚠️ وأصناف النغمات مكتوبة حرفياً كاملة — فاحص Tailwind لا ينفّذ PHP.
     */
    $toneChip = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag' => 'bg-tag/12 text-tag',
        'amber' => 'bg-amber/12 text-amber-deep',
        'warn' => 'bg-warn/14 text-warn-deep',
    ];
@endphp

<x-layouts.teacher :title="__('teacher.subjects_title')" :teacher-name="auth()->user()->displayName()">

    <div>
        <h1 class="text-h2 font-bold text-ink">{{ __('teacher.subjects_title') }}</h1>
        <p class="mt-1 text-ui text-steel">{{ __('teacher.subjects_subtitle') }}</p>
    </div>

    @forelse ($subjects as $subject)
        @if ($loop->first)
            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @endif

        <article class="tile flex flex-col gap-4 p-5">

            <div class="flex items-start gap-3">
                @if ($subject->cover_url)
                    <img src="{{ $subject->cover_url }}" alt="" loading="lazy"
                         class="size-11 shrink-0 rounded-lg object-cover">
                @else
                    {{-- بديل الغلاف أيقونة المادة بنغمتها — لا مربّع فارغ. --}}
                    <span class="grid size-11 shrink-0 place-items-center rounded-lg
                                 {{ $toneChip[$subject->accent_token] ?? $toneChip['accent'] }}">
                        <x-icon :name="$subject->icon ?? 'book'" class="size-5" />
                    </span>
                @endif

                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-h5 font-semibold text-ink">{{ $subject->name }}</h2>
                    <p class="truncate text-caption text-stone">{{ $subject->branch?->name ?? '—' }}</p>
                </div>

                {{-- ⚠️ عدد المحاضرات غير المنشورة لا مجرّد «مسودّة»: الرقم هو
                     ما يجعل الشارة قابلة للتصرّف. --}}
                @if ($subject->draft_lectures_count > 0)
                    <x-ui.badge variant="warn" class="shrink-0">
                        <span class="num">{{ $subject->draft_lectures_count }}</span>
                        {{ __('teacher.status_draft') }}
                    </x-ui.badge>
                @endif
            </div>

            {{-- ثلاث قيم رقمية — الشبكة تنعكس تلقائياً مع الاتجاه --}}
            <dl class="grid grid-cols-3 gap-2 border-y border-hairline-soft py-3">
                <div>
                    {{-- 🔴 «طلبة الفرع» لا «طلبة المادة»: لا جدول تسجيل في
                         المشروع — الطالب ينتمي لفرع ويرى كل مواده المنشورة.
                         تسمية العمود «طالب» فوق عدد فرعٍ كانت ستعني شيئاً
                         آخر بصمت. --}}
                    <dt class="text-caption text-stone">{{ __('teacher.subject_branch_students') }}</dt>
                    <dd class="num mt-0.5 text-h5 font-bold text-ink">
                        {{ $branchStudentCounts[$subject->branch_id] ?? 0 }}
                    </dd>
                </div>
                <div>
                    <dt class="text-caption text-stone">{{ __('teacher.subject_lectures') }}</dt>
                    <dd class="num mt-0.5 text-h5 font-bold text-ink">{{ $subject->lectures_count }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-stone">{{ __('teacher.subject_files') }}</dt>
                    <dd class="num mt-0.5 text-h5 font-bold text-ink">{{ $subject->library_files_count }}</dd>
                </div>
            </dl>

            @if (filled($subject->description))
                <p class="line-clamp-2 text-caption leading-relaxed text-steel">{{ $subject->description }}</p>
            @endif

            <div class="mt-auto flex gap-2 pt-1">
                {{-- ⚠️ subject_id يُمرَّر ليصل المعلّم إلى محاضرات هذه المادة
                     وحدها. شاشة المحاضرات ما زالت معاينة ثابتة لا تقرأه بعد،
                     فالرابط صحيح اليوم ويصير مُفلتِراً حين تُبنى. --}}
                <x-ui.button variant="secondary" size="sm"
                             :href="Route::has('teacher.lectures')
                                 ? route('teacher.lectures', ['subject_id' => $subject->id])
                                 : '#'"
                             class="flex-1">
                    {{ __('teacher.subject_manage') }}
                </x-ui.button>

                {{-- ⚠️ كان يشير إلى teacher.lectures.create — مسار حُذف حين صار
                     الرفع نافذة داخل شاشة المحاضرات، فصار الزرّ يُرسَم '#'
                     ولا يفعل شيئاً. الحارس منع الانهيار وأخفى العطل معاً. --}}
                <x-ui.button size="sm"
                             :href="route('teacher.lectures', ['subject_id' => $subject->id])"
                             class="shrink-0"
                             :aria-label="__('teacher.upload_lecture')">
                    <x-icon name="plus" class="size-4" />
                </x-ui.button>
            </div>
        </article>

        @if ($loop->last)
            </div>
        @endif
    @empty
        {{-- حالة فراغ مهذّبة: المعلّم لا يملك ما يفعله هنا، والرسالة تقول
             لمن يتوجّه بدل أن تتركه أمام شبكة فارغة. --}}
        <div class="tile mt-6">
            <x-ui.empty-state icon="book"
                              :title="__('teacher.subjects_empty_title')"
                              :body="__('teacher.subjects_empty_body')" />
        </div>
    @endforelse

</x-layouts.teacher>
