@php
    /*
     | Screen 9 - Upload payment proof. The manual half of decision M-4: the
     | student transfers the money out of band, then files the receipt here.
     |
     | Account details are mock. Every one of them is a Latin/numeric string
     | inside an Arabic page, so each is wrapped in <bdi dir="ltr"> - without it
     | an IBAN reorders visually and becomes impossible to copy correctly.
     */
    $order = [
        'model'    => 'subjects',
        'branch'   => 'الفرع العلمي',
        'subjects' => ['الرياضيات', 'الفيزياء', 'الكيمياء'],
        'period'   => __('subscription.summary_term'),
    ];

    $subtotal = 105;
    $discount = 0;
    $total    = $subtotal - $discount;

    $account = [
        'name'   => 'pal education',
        'bank'   => 'بنك فلسطين',
        'number' => '0123-456789-01',
        'iban'   => 'PS92 PALS 0000 0000 0123 4567 8901',
    ];

    $steps = [
        ['label' => __('subscription.checkout_step_order'),  'state' => 'done'],
        ['label' => __('subscription.checkout_step_pay'),    'state' => 'current'],
        ['label' => __('subscription.checkout_step_review'), 'state' => 'next'],
    ];
@endphp

<x-layouts.student
    :title="__('subscription.proof_title')"
    :student-name="'محمد أبو عودة'"
    subscription-state="expired"
    :unread-count="2">

    <div class="mx-auto max-w-[1100px]">

        <x-ui.breadcrumb :items="[
            ['label' => __('student.nav_dashboard'), 'href' => route('student.dashboard')],
            ['label' => __('subscription.checkout_title'), 'href' => route('student.checkout')],
            ['label' => __('subscription.proof_title')],
        ]" />

        <h1 class="mt-5 text-h2 font-bold text-ink">{{ __('subscription.proof_title') }}</h1>
        <p class="measure mt-3 text-body leading-[1.75] text-steel">{{ __('subscription.proof_lede') }}</p>

        <ol class="mt-6 flex items-center gap-2 sm:gap-3">
            @foreach ($steps as $i => $step)
                @php
                    $isCurrent = $step['state'] === 'current';
                    $isDone    = $step['state'] === 'done';
                @endphp

                <li class="flex shrink-0 items-center gap-2" @if ($isCurrent) aria-current="step" @endif>
                    <span @class([
                        'num grid size-8 shrink-0 place-items-center rounded-full text-micro font-semibold',
                        'bg-accent text-on-primary' => $isCurrent || $isDone,
                        'bg-surface text-stone' => ! $isCurrent && ! $isDone,
                    ])>
                        @if ($isDone)
                            <x-icon name="check" class="size-4" />
                        @else
                            {{ $i + 1 }}
                        @endif
                    </span>

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

        <div class="mt-8 lg:grid lg:grid-cols-12 lg:items-start lg:gap-8">

            <div class="flex flex-col gap-6 lg:col-span-7 lg:col-start-1">

                {{-- Transfer instructions --}}
                <section class="rounded-xl bg-canvas p-6 ring-1 ring-hairline">
                    <h2 class="text-h5 font-semibold text-ink">{{ __('subscription.proof_instructions_title') }}</h2>

                    {{--
                        Every value is <bdi dir="ltr"> plus `.num`: account numbers and
                        IBANs are Latin/numeric strings inside Arabic prose, and without
                        isolation the bidi algorithm reorders them on screen. A student
                        copying a reordered IBAN sends money nowhere.
                    --}}
                    <dl class="mt-5 flex flex-col divide-y divide-hairline-soft">
                        @foreach ([
                            ['label' => __('subscription.proof_bank_name'),      'value' => $account['bank'],   'ltr' => false],
                            ['label' => __('subscription.proof_account_name'),   'value' => $account['name'],   'ltr' => true],
                            ['label' => __('subscription.proof_account_number'), 'value' => $account['number'], 'ltr' => true],
                            ['label' => __('subscription.proof_iban'),           'value' => $account['iban'],   'ltr' => true],
                        ] as $row)
                            <div class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                                <dt class="text-ui text-stone">{{ $row['label'] }}</dt>
                                <dd class="text-ui font-semibold text-ink">
                                    @if ($row['ltr'])
                                        <bdi dir="ltr" class="num">{{ $row['value'] }}</bdi>
                                    @else
                                        {{ $row['value'] }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-3">
                            <dt class="text-ui font-semibold text-ink">{{ __('subscription.proof_amount_due') }}</dt>
                            <dd class="num text-h4 font-bold text-accent-deep">
                                {{ $total }} {{ __('subscription.summary_currency') }}
                            </dd>
                        </div>
                    </dl>

                    <x-ui.alert variant="warn" class="mt-5">
                        {{ __('subscription.proof_warning') }}
                    </x-ui.alert>
                </section>

                {{-- Receipt form --}}
                <section class="rounded-xl bg-canvas p-6 ring-1 ring-hairline">
                    <h2 class="text-h5 font-semibold text-ink">{{ __('subscription.proof_form_title') }}</h2>

                    <div class="mt-5 flex flex-col gap-5">
                        <x-ui.file-dropzone
                            name="receipt"
                            accept=".jpg,.jpeg,.png,.pdf"
                            icon="folder"
                            simulate
                            :title="__('subscription.proof_upload_title')"
                            :hint="__('subscription.proof_upload_hint')"
                            :max-label="__('subscription.proof_upload_max')" />

                        <x-ui.input :label="__('subscription.proof_reference')"
                                    name="reference"
                                    dir="ltr"
                                    :hint="__('subscription.proof_reference_hint')"
                                    placeholder="TRX-000000" />

                        {{-- Native date/time inputs stay LTR: they render numerals either way --}}
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.input :label="__('subscription.proof_date')"
                                        name="transfer_date"
                                        type="date"
                                        dir="ltr" />

                            <x-ui.input :label="__('subscription.proof_time')"
                                        name="transfer_time"
                                        type="time"
                                        dir="ltr" />
                        </div>

                        <x-ui.textarea :label="__('subscription.proof_note')"
                                       name="note"
                                       rows="3"
                                       :placeholder="__('subscription.proof_note_placeholder')" />
                    </div>
                </section>

                <div class="flex flex-wrap items-center justify-between gap-4">
                    <a href="{{ route('student.checkout') }}"
                       class="inline-flex min-h-11 items-center gap-2 text-ui font-semibold text-accent-deep transition hover:underline">
                        <x-icon name="arrow" class="size-4 ltr:-scale-x-100" />
                        {{ __('subscription.proof_back') }}
                    </a>

                    <x-ui.button variant="primary"
                                 size="md"
                                 :href="route('student.checkout.pending')"
                                 class="w-full sm:w-auto">
                        {{ __('subscription.proof_cta') }}
                    </x-ui.button>
                </div>
            </div>

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
