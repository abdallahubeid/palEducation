@php
    /*
     * 🔴 كل شيء هنا يأتي من الكنترولر. كان هذا الملف يحمل «كتالوجاً» ثابتاً
     * لفرع واحد (العلمي) ويعرضه لأي slug — أي أن /branches/literary كانت
     * تعرض مواد الفرع العلمي وأساتذته تحت اسم الفرع الأدبي.
     *
     * الوحيد الباقي هنا خريطة النغمات: قيمة accent_token اسم توكن، وربطها
     * بصنف Tailwind يجب أن يمرّ عبر مصفوفة ثابتة تُكتب فيها الأصناف كاملة
     * حرفياً — الفاحص لا ينفّذ PHP.
     */
    $heroTones = [
        'accent' => 'bg-accent/14 text-accent-deep',
        'tag' => 'bg-tag/12 text-tag',
        'amber' => 'bg-amber/12 text-amber-deep',
        'warn' => 'bg-warn/14 text-warn-deep',
    ];

    $heroTone = $heroTones[$branch->accent_token] ?? $heroTones['accent'];

    $statCards = [
        ['value' => $stats['subjects'], 'label' => __('public.branch_stat_subjects')],
        ['value' => $stats['teachers'], 'label' => __('public.branch_stat_teachers')],
        ['value' => $stats['lectures'], 'label' => __('public.branch_stat_lectures')],
        ['value' => $stats['files'], 'label' => __('public.branch_stat_files')],
    ];

    // زرّ التسجيل: مسار حقيقي أو لا زرّ. Route::has يمنع 500 لو لم يُسجَّل بعد.
    $enrollHref = Route::has('auth.register') ? route('auth.register') : '#';
@endphp

<x-layouts.public :title="$branch->name">

    {{-- هيرو الفرع --}}
    <section class="border-b border-hairline bg-canvas py-10 lg:py-14">
        <div class="mx-auto max-w-[1280px] px-6">
            <x-ui.breadcrumb :items="[
                ['label' => __('nav.home'), 'href' => route('home')],
                ['label' => __('public.branch_all_branches'), 'href' => route('branches.index')],
                ['label' => $branch->name],
            ]" />

            <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">
                    <div class="flex items-center gap-4">
                        <span class="grid size-16 shrink-0 place-items-center rounded-xl {{ $heroTone }}">
                            <x-icon :name="$branch->icon ?? 'book'" class="size-8" />
                        </span>

                        <div class="min-w-0">
                            <p class="text-micro font-semibold text-stone">{{ __('public.branch_eyebrow') }}</p>
                            <h1 class="mt-1 text-h1 font-bold text-ink">{{ $branch->name }}</h1>
                        </div>
                    </div>

                    {{-- نص يُقرأ فعلاً: 16px · 1.75 · بعرض قراءة محدود.
                         يُسقَط كاملاً لو كان الوصف فارغاً بدل ترك فراغ. --}}
                    @if (filled($branch->description))
                        <p class="measure mt-5 text-body leading-[1.75] text-steel">{{ $branch->description }}</p>
                    @endif
                </div>

                {{-- ⚠️ زرّ الفرع من قاعدة البيانات إن وُجد نصّه ورابطه معاً
                     (hasAction)، وإلا فزرّ التسجيل العام: نصّ بلا رابط يُنتج
                     زرّاً ميّتاً. --}}
                <x-ui.button size="lg"
                             :href="$branch->hasAction() ? \App\Models\Setting::safeHref($branch->btn_link) : $enrollHref"
                             class="w-full shrink-0 sm:w-auto">
                    {{ $branch->hasAction() ? $branch->btn_text : __('public.branch_enroll_action') }}
                </x-ui.button>
            </div>

            {{-- أربع قيم رقمية — تُحسب من الصفوف المحمّلة لا تُكتب يدوياً --}}
            <dl class="mt-10 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-hairline lg:grid-cols-4">
                @foreach ($statCards as $stat)
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

            @if ($branch->subjects->isEmpty())
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
                    @foreach ($branch->subjects as $subject)
                        <x-domain.subject-card
                            :name="$subject->name"
                            {{-- أول أستاذ مُسنَد، أو لا شيء: اسم أستاذ مخترع
                                 أسوأ من خانة فارغة. --}}
                            :teacher="$subject->teachers->first()?->full_name"
                            :icon="$subject->icon ?? 'book'"
                            :tone="$subject->accent_token ?? 'accent'"
                            :lectures-count="$subject->lectures_count"
                            :files-count="$subject->library_files_count"
                            :href="$enrollHref" />
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

            @if ($teachers->isEmpty())
                <div class="tile mt-8">
                    <x-ui.empty-state icon="user"
                                      :title="__('public.branch_teachers_empty_title')"
                                      :body="__('public.branch_teachers_empty_body')" />
                </div>
            @else
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($teachers as $teacher)
                        <x-domain.teacher-card
                            :name="$teacher->full_name"
                            {{-- المادة التي وضعته على هذه الصفحة تحديداً —
                                 تُلتقط في الكنترولر أثناء التسطيح، لا أول
                                 مادة له في المنصّة كلها. --}}
                            :subject="$teacher->branch_subject_name"
                            :branch="$branch->name"
                            :photo="$teacher->avatarUrl()"
                            {{-- بديل الصورة حين لا صورة — الحرفان الأولان من
                                 الاسم بعد نزع اللقب. --}}
                            :initials="$teacher->initials()"
                            :lectures="$teacher->lectures_count ?? 0"
                            :tone="$branch->accent_token ?? 'accent'" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- 🔴 المكوّن المشترك بدل شريط ثابت — النصّ من إعدادات الأدمن. --}}
    <x-site.cta-final
        variant="deep"
        :secondary-text="__('public.branch_all_branches')"
        :secondary-link="route('branches.index')" />

</x-layouts.public>
