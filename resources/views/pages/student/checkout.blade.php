@php
    /*
     | Screen 8 - Checkout. Unblocked by M-1 (two subscription models) and M-4
     | (manual payment: bank transfer / e-wallet / local agent / cash).
     |
     | Mock order: the per-subject model with 3 subjects, so the summary exercises
     | the subject-list branch of the component rather than the simpler pass.
     */
    $order = [
        'model'    => 'subjects',              // subjects | branch
        'branch'   => 'الفرع العلمي',
        'subjects' => ['الرياضيات', 'الفيزياء', 'الكيمياء'],
        'period'   => __('subscription.summary_term'),
    ];

    $unitPrice = 35;
    $subtotal  = count($order['subjects']) * $unitPrice;
    $discount  = 0;                            // branch pass only
    $total     = $subtotal - $discount;

    $student = [
        'name'   => 'محمد أبو عودة',
        'branch' => $order['branch'],
        'phone'  => '0599-123-456',
    ];

    $methods = [
        ['value' => 'bank',   'icon' => 'bank',   'title' => __('subscription.payment_bank'),   'desc' => __('subscription.payment_bank_desc')],
        ['value' => 'wallet', 'icon' => 'wallet', 'title' => __('subscription.payment_wallet'), 'desc' => __('subscription.payment_wallet_desc')],
        ['value' => 'agent',  'icon' => 'users',  'title' => __('subscription.payment_agent'),  'desc' => __('subscription.payment_agent_desc')],
        ['value' => 'cash',   'icon' => 'home',   'title' => __('subscription.payment_cash'),   'desc' => __('subscription.payment_cash_desc')],
    ];

    $steps = [
        ['label' => __('subscription.checkout_step_order'),  'state' => 'current'],
        ['label' => __('subscription.checkout_step_pay'),    'state' => 'next'],
        ['label' => __('subscription.checkout_step_review'), 'state' => 'next'],
    ];
@endphp

<x-layouts.student
    :title="__('subscription.checkout_title')"
    :student-name="$student['name']"
    subscription-state="expired"
    :unread-count="2">

    <div class="mx-auto max-w-[1100px]">

        <x-ui.breadcrumb :items="[
            ['label' => __('student.nav_dashboard'), 'href' => route('student.dashboard')],
            ['label' => __('subscription.pricing_eyebrow'), 'href' => route('pricing')],
            ['label' => __('subscription.checkout_title')],
        ]" />

        <h1 class="mt-5 text-h2 font-bold text-ink">{{ __('subscription.checkout_title') }}</h1>

        {{--
            Three-step progress. Built as an ordered list so the sequence is real
            structure, not a row of divs; aria-current marks the active step for
            assistive tech. The connector line is a flex-1 rule between items, so
            it stretches and mirrors with direction automatically.
        --}}
        <ol class="mt-6 flex items-center gap-2 sm:gap-3">
            @foreach ($steps as $i => $step)
                @php $isCurrent = $step['state'] === 'current'; @endphp

                <li class="flex shrink-0 items-center gap-2"
                    @if ($isCurrent) aria-current="step" @endif>
                    <span @class([
                        'num grid size-8 shrink-0 place-items-center rounded-full text-micro font-semibold',
                        'bg-accent text-on-primary' => $isCurrent,
                        'bg-surface text-stone' => ! $isCurrent,
                    ])>{{ $i + 1 }}</span>

                    <span @class([
                        'text-caption sm:text-ui',
                        'font-semibold text-ink' => $isCurrent,
                        'text-stone' => ! $isCurrent,
                    ])>{{ $step['label'] }}</span>
                </li>

                @if (! $loop->last)
                    <li aria-hidden="true" class="h-px min-w-4 flex-1 bg-hairline"></li>
                @endif
            @endforeach
        </ol>

        {{--
            Two columns from lg: the form stream on the inline start, the order
            summary sticky on the inline end. Below lg the summary drops BELOW the
            form - a student on a phone should reach the action, not scroll past a
            price block to get to it.
        --}}
        <div class="mt-8 lg:grid lg:grid-cols-12 lg:items-start lg:gap-8">

            {{-- ── Form stream ── --}}
            <div class="flex flex-col gap-6 lg:col-span-7 lg:col-start-1">

                {{-- Student details --}}
                <section class="rounded-xl bg-canvas p-6 ring-1 ring-hairline">
                    <h2 class="text-h5 font-semibold text-ink">{{ __('subscription.student_info_title') }}</h2>
                    <p class="mt-1.5 text-caption text-stone">{{ __('subscription.student_info_note') }}</p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <x-ui.input :label="__('subscription.student_name')"
                                    name="student_name"
                                    :value="$student['name']" />

                        <x-ui.input :label="__('subscription.student_branch')"
                                    name="student_branch"
                                    :value="$student['branch']"
                                    disabled />

                        {{-- dir=ltr: phone numbers never mirror, in any language --}}
                        <x-ui.input :label="__('subscription.student_phone')"
                                    name="student_phone"
                                    type="tel"
                                    dir="ltr"
                                    :value="$student['phone']"
                                    class="sm:col-span-2" />
                    </div>
                </section>

                {{-- Payment method --}}
                <section class="rounded-xl bg-canvas p-6 ring-1 ring-hairline">
                    <h2 class="text-h5 font-semibold text-ink">{{ __('subscription.payment_title') }}</h2>
                    <p class="mt-1.5 text-caption text-stone">{{ __('subscription.payment_note') }}</p>

                    {{--
                        selectable-card carries radio semantics and marks the chosen
                        state with three signals (ring, tint, check) rather than colour
                        alone. fieldset/legend makes the group a real group for
                        screen readers.
                    --}}
                    <fieldset class="mt-5">
                        <legend class="sr-only">{{ __('subscription.payment_title') }}</legend>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($methods as $m)
                                <x-ui.selectable-card
                                    name="payment_method"
                                    :value="$m['value']"
                                    :icon="$m['icon']"
                                    :title="$m['title']"
                                    :description="$m['desc']"
                                    :checked="$loop->first" />
                            @endforeach
                        </div>
                    </fieldset>
                </section>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <a href="{{ route('pricing') }}"
                       class="inline-flex min-h-11 items-center gap-2 text-ui font-semibold text-accent-deep transition hover:underline">
                        <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                        {{ __('subscription.checkout_back') }}
                    </a>

                    <x-ui.button variant="primary"
                                 size="md"
                                 :href="route('student.checkout.proof')"
                                 class="w-full sm:w-auto">
                        {{ __('subscription.checkout_cta') }}
                    </x-ui.button>
                </div>
            </div>

            {{-- ── Order summary ── --}}
            <aside class="mt-8 lg:col-span-5 lg:col-start-8 lg:mt-0">
                <div class="lg:sticky lg:top-24">
                    <x-domain.order-summary
                        :model="$order['model']"
                        :branch="$order['branch']"
                        :subjects="$order['subjects']"
                        :period="$order['period']"
                        :subtotal="$subtotal"
                        :discount="$discount"
                        :total="$total"
                        :edit-href="route('pricing')" />
                </div>
            </aside>
        </div>
    </div>

</x-layouts.student>
