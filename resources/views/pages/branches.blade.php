<x-layouts.public :title="__('public.branches_title')">

    {{-- رأس الصفحة --}}
    <section class="py-14 lg:py-20">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="max-w-3xl">
                <x-ui.rule-label>{{ __('nav.branches') }}</x-ui.rule-label>
                <h1 class="mt-5 text-h1 font-bold text-ink">{{ __('public.branches_title') }}</h1>
                <p class="measure mt-4 text-lead text-steel">{{ __('public.branches_subtitle') }}</p>
            </div>

            {{-- بحث على العميل: القائمة كلها في الصفحة أصلاً (أربعة فروع)،
                 فرحلة للخادم لكل حرف تكلفة بلا مقابل. --}}
            @if ($branches->isNotEmpty())
                <div class="mt-8 max-w-md" data-branch-filter>
                    <label class="relative flex items-center">
                        <x-icon name="search" class="pointer-events-none absolute start-4 size-4 text-muted" />
                        <input type="search"
                               data-branch-search
                               placeholder="{{ __('public.branches_search_placeholder') }}"
                               class="h-12 w-full rounded-full border border-hairline bg-canvas ps-11 pe-4 text-body text-ink
                                      placeholder:text-muted transition hover:border-hairline-strong
                                      focus-visible:border-accent focus-visible:outline-none">
                    </label>
                </div>
            @endif
        </div>
    </section>

    {{-- شبكة الفروع --}}
    <section class="pb-16 lg:pb-24">
        <div class="mx-auto max-w-[1280px] px-6">

            @if ($branches->isEmpty())
                {{-- 🔴 حالة فراغ حقيقية لا شبكة فارغة: قاعدة بيانات جديدة
                     أو كل الفروع مُعطَّلة تُنتجان صفحة بلا شيء، والصفحة
                     البيضاء تُقرأ عطلاً لا «لا يوجد بعد». --}}
                <div class="tile">
                    <x-ui.empty-state icon="book"
                                      :title="__('public.branches_empty_title')"
                                      :body="__('public.branches_empty_body')" />
                </div>
            @else
                <div data-branch-grid class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($branches as $branch)
                        {{-- نصّ البحث يُبنى من الفرع ومواده المنشورة: الزائر
                             يبحث بـ«فيزياء» لا باسم الفرع، والكلمات المفتاحية
                             لم تعد مصفوفة مكتوبة يدوياً في القالب. --}}
                        <div data-branch-item
                             data-search="{{ $branch->name }} {{ $branch->description }} {{ $branch->subjects->pluck('name')->implode(' ') }}">
                            <x-domain.branch-card
                                :name="$branch->name"
                                :summary="$branch->description"
                                :subjects="$branch->subjects_count"
                                :teachers="$branch->teachers_count"
                                :icon="$branch->icon ?? 'book'"
                                :tone="$branch->accent_token ?? 'accent'"
                                :href="route('branches.show', $branch)" />
                        </div>
                    @endforeach
                </div>

                <div data-branch-empty hidden class="tile mt-6">
                    <x-ui.empty-state icon="search"
                                      :title="__('public.branches_no_results_title')"
                                      :body="__('public.branches_no_results_body')" />
                </div>
            @endif
        </div>
    </section>

    {{-- 🔴 المكوّن المشترك بدل شريط ثابت: النصّ والزرّ يأتيان من إعدادات
         «الدعوة الختامية» التي يحرّرها الأدمن، فلا تبقى نسخة ثانية من
         النصّ في هذا الملف تتباعد عن الأصل. --}}
    <x-site.cta-final
        variant="deep"
        :secondary-text="__('public.branches_cta_secondary')"
        :secondary-link="route('news.index')" />

</x-layouts.public>
