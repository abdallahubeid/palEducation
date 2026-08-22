@php
    $branches = [
        ['slug' => 'scientific', 'name' => 'الفرع العلمي',  'icon' => 'beaker',    'tone' => 'accent',
         'summary' => 'رياضيات · فيزياء · كيمياء · أحياء — للطلبة المتجهين لكليات الطب والهندسة والعلوم.',
         'subjects' => 7, 'teachers' => 12,
         'keywords' => 'رياضيات فيزياء كيمياء أحياء علمي طب هندسة'],

        ['slug' => 'literary', 'name' => 'الفرع الأدبي', 'icon' => 'book', 'tone' => 'tag',
         'summary' => 'لغة عربية · تاريخ · جغرافيا · لغة إنجليزية — للمتجهين للحقوق والإعلام والآداب.',
         'subjects' => 6, 'teachers' => 9,
         'keywords' => 'لغة عربية تاريخ جغرافيا إنجليزي أدبي حقوق إعلام'],

        ['slug' => 'commercial', 'name' => 'الفرع التجاري', 'icon' => 'briefcase', 'tone' => 'amber',
         'summary' => 'محاسبة · اقتصاد وإدارة · رياضيات تجارية — للمتجهين لإدارة الأعمال والمحاسبة.',
         'subjects' => 6, 'teachers' => 7,
         'keywords' => 'محاسبة اقتصاد إدارة تجاري أعمال'],

        ['slug' => 'industrial', 'name' => 'الفرع الصناعي', 'icon' => 'wrench', 'tone' => 'warn',
         'summary' => 'رسم صناعي · تقنية إنتاج · إلكترونيات — للمتجهين للكليات التقنية والمهن الصناعية.',
         'subjects' => 5, 'teachers' => 5,
         'keywords' => 'رسم صناعي تقنية إنتاج إلكترونيات صناعي تقني'],
    ];
@endphp

<x-layouts.public :title="__('public.branches_title')">

    {{-- رأس الصفحة --}}
    <section class="py-14 lg:py-20">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="max-w-3xl">
                <x-ui.rule-label>{{ __('nav.branches') }}</x-ui.rule-label>
                <h1 class="mt-5 text-h1 font-bold text-ink">{{ __('public.branches_title') }}</h1>
                <p class="measure mt-4 text-lead text-steel">{{ __('public.branches_subtitle') }}</p>
            </div>

            {{-- بحث على العميل — لا خادم بعد --}}
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
        </div>
    </section>

    {{-- شبكة الفروع --}}
    <section class="pb-16 lg:pb-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div data-branch-grid class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($branches as $branch)
                    <div data-branch-item data-search="{{ $branch['name'] }} {{ $branch['keywords'] }}">
                        <x-domain.branch-card
                            :name="$branch['name']"
                            :summary="$branch['summary']"
                            :subjects="$branch['subjects']"
                            :teachers="$branch['teachers']"
                            :icon="$branch['icon']"
                            :tone="$branch['tone']"
                            :href="route('branches.show', $branch['slug'])" />
                    </div>
                @endforeach
            </div>

            <div data-branch-empty hidden class="tile mt-6">
                <x-ui.empty-state icon="search"
                                  :title="__('public.branches_no_results_title')"
                                  :body="__('public.branches_no_results_body')" />
            </div>
        </div>
    </section>

    {{-- شريط دعوة التسجيل --}}
    <section class="pb-20 lg:pb-28">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="overflow-hidden rounded-xxl bg-linear-to-bl from-deep-from to-deep-to px-6 py-12 text-center sm:px-12 lg:py-16">
                <h2 class="text-h2 font-bold text-on-dark">{{ __('public.branches_cta_title') }}</h2>
                <p class="measure mx-auto mt-3 text-lead text-on-dark-muted">{{ __('public.branches_cta_body') }}</p>

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <x-ui.button variant="on-dark" size="lg"
                                 :href="Route::has('auth.register') ? route('auth.register') : '#'"
                                 class="w-full sm:w-auto">
                        {{ __('public.branches_cta_action') }}
                    </x-ui.button>

                    <a href="{{ route('news.index') }}"
                       class="inline-flex min-h-11 items-center rounded-full px-5 text-ui font-semibold text-accent-on-dark transition hover:text-on-dark">
                        {{ __('public.branches_cta_secondary') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

</x-layouts.public>
