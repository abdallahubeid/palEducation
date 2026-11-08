@php
    /*
     | Screen 7 - Pricing. Unblocked by decision M-1 (resolved 2026-08-22):
     | two subscription models coexist, per-subject and a discounted branch pass.
     |
     | Pricing is mock display data. The numbers are chosen so the break-even
     | lands at 4 subjects (4 x 35 = 140 > 120), which is what makes the
     | calculator's "switch to the branch pass" nudge meaningful rather than
     | decorative.
     */
    $unitPrice   = 35;    // per subject, per term
    $branchPrice = 120;   // full branch pass, per term

    $branches = [
        'scientific' => ['name' => 'الفرع العلمي', 'subjects' => [
            'الرياضيات', 'الفيزياء', 'الكيمياء', 'الأحياء',
            'اللغة العربية', 'اللغة الإنجليزية', 'التربية الإسلامية',
        ]],
        'literary' => ['name' => 'الفرع الأدبي', 'subjects' => [
            'اللغة العربية', 'التاريخ', 'الجغرافيا', 'التربية الإسلامية',
            'اللغة الإنجليزية', 'الثقافة العلمية',
        ]],
        'commercial' => ['name' => 'الفرع التجاري', 'subjects' => [
            'المحاسبة', 'الاقتصاد', 'إدارة الأعمال', 'الرياضيات التجارية',
            'اللغة العربية', 'اللغة الإنجليزية',
        ]],
    ];

    // Headline saving, quoted against the largest branch.
    $branchSubjectCount = count($branches['scientific']['subjects']);
    $branchListPrice    = $branchSubjectCount * $unitPrice;
    $branchSaving       = $branchListPrice - $branchPrice;

    /*
     | Comparison rows. `subjects` / `branch` are either true, false, or a short
     | string when the feature exists in both but with a different scope.
     */
    $comparison = [
        ['label' => __('subscription.compare_f_videos'),        'subjects' => __('subscription.compare_scope_selected'), 'branch' => __('subscription.compare_scope_all')],
        ['label' => __('subscription.compare_f_quiz'),          'subjects' => true,  'branch' => true],
        ['label' => __('subscription.compare_f_library'),       'subjects' => __('subscription.compare_scope_selected'), 'branch' => __('subscription.compare_scope_all')],
        ['label' => __('subscription.compare_f_progress'),      'subjects' => true,  'branch' => true],
        ['label' => __('subscription.compare_f_qa'),            'subjects' => true,  'branch' => true],
        ['label' => __('subscription.compare_f_all_subjects'),  'subjects' => false, 'branch' => true],
        ['label' => __('subscription.compare_f_new_subjects'),  'subjects' => false, 'branch' => true],
        ['label' => __('subscription.compare_f_priority'),      'subjects' => false, 'branch' => true],
    ];

    $faqs = [
        ['q' => __('subscription.faq_q1'), 'a' => __('subscription.faq_a1')],
        ['q' => __('subscription.faq_q2'), 'a' => __('subscription.faq_a2')],
        ['q' => __('subscription.faq_q3'), 'a' => __('subscription.faq_a3')],
        ['q' => __('subscription.faq_q4'), 'a' => __('subscription.faq_a4')],
        ['q' => __('subscription.faq_q5'), 'a' => __('subscription.faq_a5')],
        ['q' => __('subscription.faq_q6'), 'a' => __('subscription.faq_a6')],
    ];

    $checkoutHref = Route::has('student.checkout') ? route('student.checkout') : '#';
@endphp

<x-layouts.public :title="__('subscription.pricing_eyebrow')">

    {{-- ═══ Masthead ═══ --}}
    <section class="border-b border-hairline bg-canvas py-12 lg:py-16">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="measure">
                <x-ui.rule-label>{{ __('subscription.pricing_eyebrow') }}</x-ui.rule-label>
                <h1 class="mt-5 text-h1 font-bold text-ink">{{ __('subscription.pricing_title') }}</h1>
                <p class="mt-4 text-lead leading-[1.8] text-steel">{{ __('subscription.pricing_lede') }}</p>
            </div>
        </div>
    </section>

    {{--
        ═══ The two models, side by side ═══

        Grid coordinates only, so RTL/LTR mirror for free. The calculator is the
        wider half because it is the one the student interacts with; the branch
        pass is the narrower, highlighted "best value" card.
    --}}
    <section class="py-12 lg:py-16">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="grid gap-8 lg:grid-cols-12 lg:items-start">

                {{-- Option A - per-subject calculator --}}
                <div class="lg:col-span-7 lg:col-start-1">
                    <div class="mb-4">
                        <h2 class="text-h3 font-bold text-ink">{{ __('subscription.model_per_subject') }}</h2>
                        <p class="mt-2 text-body leading-[1.75] text-steel">{{ __('subscription.model_per_subject_summary') }}</p>
                    </div>

                    <x-domain.plan-calculator
                        :branches="$branches"
                        :unit-price="$unitPrice"
                        :branch-price="$branchPrice"
                        :checkout-href="$checkoutHref" />
                </div>

                {{-- Option B - full branch pass --}}
                <div class="lg:col-span-5 lg:col-start-8" data-branch-plan>
                    <div class="mb-4">
                        <h2 class="text-h3 font-bold text-ink">{{ __('subscription.model_branch') }}</h2>
                        <p class="mt-2 text-body leading-[1.75] text-steel">{{ __('subscription.model_branch_summary') }}</p>
                    </div>

                    <x-domain.plan-card
                        featured
                        :badge="__('subscription.model_branch_badge')"
                        :name="__('subscription.model_branch')"
                        :summary="__('subscription.model_branch_summary')"
                        :price="$branchPrice"
                        :old-price="$branchListPrice"
                        :period="__('home.plan_term_period')"
                        :note="__('subscription.calc_saving', ['amount' => $branchSaving])"
                        :cta="__('home.plan_term_cta')"
                        :href="$checkoutHref"
                        :features="[
                            __('subscription.compare_f_all_subjects'),
                            __('subscription.compare_f_quiz'),
                            __('subscription.compare_f_library'),
                            __('subscription.compare_f_new_subjects'),
                            __('subscription.compare_f_priority'),
                        ]" />
                </div>
            </div>

        </div>
    </section>

    {{-- ═══ Comparison table ═══ --}}
    <section class="border-y border-hairline bg-canvas py-14 lg:py-20">
        <div class="mx-auto max-w-[1280px] px-6">
            <x-ui.section-head
                :title="__('subscription.compare_title')"
                :lede="__('subscription.compare_lede')" />

            {{--
                The table scrolls inside its own container on narrow screens so the
                PAGE never scrolls sideways. The first column is the row label, and
                each state renders an icon plus text - never colour alone, which
                would be unreadable to anyone who cannot distinguish it.
            --}}
            <div class="mt-8 overflow-x-auto rounded-xl ring-1 ring-hairline">
                <table class="w-full min-w-[36rem] border-collapse text-start">
                    <caption class="sr-only">{{ __('subscription.compare_title') }}</caption>

                    <thead>
                        <tr class="border-b border-hairline bg-surface">
                            <th scope="col" class="px-5 py-4 text-start text-ui font-semibold text-ink">
                                {{ __('subscription.compare_feature') }}
                            </th>
                            <th scope="col" class="px-5 py-4 text-start text-ui font-semibold text-ink">
                                {{ __('subscription.model_per_subject') }}
                            </th>
                            <th scope="col" class="px-5 py-4 text-start text-ui font-semibold text-ink">
                                <span class="flex items-center gap-2">
                                    {{ __('subscription.model_branch') }}
                                    <x-ui.badge variant="accent">{{ __('subscription.model_branch_badge') }}</x-ui.badge>
                                </span>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($comparison as $row)
                            <tr class="border-b border-hairline-soft last:border-0">
                                <th scope="row" class="px-5 py-4 text-start text-body font-medium text-slate">
                                    {{ $row['label'] }}
                                </th>

                                @foreach (['subjects', 'branch'] as $col)
                                    <td class="px-5 py-4 text-ui">
                                        @if ($row[$col] === true)
                                            <span class="flex items-center gap-2 text-accent-deep">
                                                <x-icon name="check-circle" class="size-4 shrink-0" />
                                                {{ __('subscription.compare_included') }}
                                            </span>
                                        @elseif ($row[$col] === false)
                                            <span class="flex items-center gap-2 text-stone">
                                                <x-icon name="close" class="size-4 shrink-0" />
                                                {{ __('subscription.compare_excluded') }}
                                            </span>
                                        @else
                                            <span class="flex items-center gap-2 text-accent-deep">
                                                <x-icon name="check-circle" class="size-4 shrink-0" />
                                                {{ $row[$col] }}
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ═══ FAQ ═══ (id: the contact page deflection card links straight here) --}}
    <section id="faq" class="py-14 lg:py-20">
        <div class="mx-auto max-w-[1280px] px-6">
            <x-ui.section-head :title="__('subscription.faq_title')" />

            {{-- <details name> gives exclusive open/close with zero JavaScript --}}
            <div class="measure mt-8 flex flex-col gap-3">
                @foreach ($faqs as $i => $faq)
                    <x-ui.accordion-item :question="$faq['q']" group="pricing-faq" :open="$i === 0">
                        {{ $faq['a'] }}
                    </x-ui.accordion-item>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══ Closing CTA - the only primary button outside the plan cards ═══ --}}
    <section class="pb-16 lg:pb-24">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="tile flex flex-col items-start gap-6 p-8 sm:p-10 lg:flex-row lg:items-center lg:justify-between">
                <div class="measure">
                    <h2 class="text-h3 font-bold text-ink">{{ __('subscription.cta_title') }}</h2>
                    <p class="mt-3 text-body leading-[1.75] text-steel">{{ __('subscription.cta_body') }}</p>
                </div>

                <x-ui.button variant="secondary"
                             size="lg"
                             :href="Route::has('auth.register') ? route('auth.register') : '#'"
                             class="w-full shrink-0 sm:w-auto">
                    {{ __('subscription.cta_action') }}
                </x-ui.button>
            </div>
        </div>
    </section>

</x-layouts.public>
