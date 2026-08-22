@php
    /*
     | Screen 10 - Pending review.
     |
     | Tone note, same principle as the 402 paywall: this is a SUCCESS state, not
     | a holding pen. The student has done everything asked of them, so the page
     | uses accent (achievement) - never warn or error - and leads with what will
     | happen next rather than with what is missing.
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

    $request = [
        'ref'       => 'PE-2026-04871',
        'submitted' => '22 آب 2026، 14:35',
        'receipt'   => 'receipt-04871.jpg',
    ];

    $nextSteps = [
        __('subscription.pending_next_1'),
        __('subscription.pending_next_2'),
        __('subscription.pending_next_3'),
    ];
@endphp

<x-layouts.student
    :title="__('subscription.pending_title')"
    :student-name="'محمد أبو عودة'"
    subscription-state="expired"
    :unread-count="2">

    <div class="mx-auto max-w-[880px]">

        {{--
            Status banner. Accent, not warn: the student completed the task and
            this is confirmation. A yellow "pending" bar would read as a problem
            they still have to solve.
        --}}
        <section class="rounded-xl bg-accent-soft p-6 text-center ring-1 ring-accent/20 sm:p-10">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-accent text-on-primary">
                <x-icon name="check" class="size-8" />
            </span>

            <h1 class="mt-5 text-h2 font-bold text-ink">{{ __('subscription.pending_title') }}</h1>

            <p class="measure mx-auto mt-3 text-body leading-[1.75] text-slate">
                {{ __('subscription.pending_body') }}
            </p>

            <p class="mt-5 inline-flex flex-wrap items-center justify-center gap-2 rounded-full bg-canvas px-4 py-2">
                <span class="text-caption text-stone">{{ __('subscription.pending_ref_label') }}</span>
                <bdi dir="ltr" class="num text-ui font-bold text-ink">{{ $request['ref'] }}</bdi>
            </p>
        </section>

        <div class="mt-8 grid gap-6 lg:grid-cols-2 lg:items-start">

            {{-- Request details --}}
            <section class="rounded-xl bg-canvas p-6 ring-1 ring-hairline">
                <h2 class="text-h5 font-semibold text-ink">{{ __('subscription.summary_title') }}</h2>

                <dl class="mt-5 flex flex-col divide-y divide-hairline-soft text-ui">
                    <div class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0">
                        <dt class="text-stone">{{ __('subscription.pending_status') }}</dt>
                        <dd><x-ui.badge variant="accent">{{ __('subscription.pending_status_value') }}</x-ui.badge></dd>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <dt class="text-stone">{{ __('subscription.pending_submitted_at') }}</dt>
                        <dd class="num font-semibold text-ink">{{ $request['submitted'] }}</dd>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <dt class="text-stone">{{ __('subscription.pending_expected') }}</dt>
                        <dd class="font-semibold text-ink">{{ __('subscription.pending_expected_value') }}</dd>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <dt class="text-stone">{{ __('subscription.summary_total') }}</dt>
                        <dd class="num font-bold text-ink">{{ $total }} {{ __('subscription.summary_currency') }}</dd>
                    </div>

                    {{-- Uploaded receipt. Filename is Latin, so it is isolated with <bdi>. --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-3">
                        <dt class="text-stone">{{ __('subscription.pending_receipt') }}</dt>
                        <dd class="flex items-center gap-2 font-semibold text-ink">
                            <x-icon name="clipboard" class="size-4 shrink-0 text-accent-deep" />
                            <bdi dir="ltr">{{ $request['receipt'] }}</bdi>
                        </dd>
                    </div>
                </dl>
            </section>

            {{-- What happens next --}}
            <section class="rounded-xl bg-canvas p-6 ring-1 ring-hairline">
                <h2 class="text-h5 font-semibold text-ink">{{ __('subscription.pending_what_next') }}</h2>

                {{-- Numbered, because these are sequential stages, not a checklist --}}
                <ol class="mt-5 flex flex-col gap-4">
                    @foreach ($nextSteps as $step)
                        <li class="flex gap-3">
                            <span class="num mt-0.5 grid size-6 shrink-0 place-items-center rounded-full
                                         bg-accent/12 text-micro font-semibold text-accent-deep"
                                  aria-hidden="true">{{ $loop->iteration }}</span>
                            <span class="text-body leading-[1.75] text-steel">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-5 border-t border-hairline pt-4 text-caption text-stone">
                    {{ __('subscription.pending_meanwhile') }}
                </p>
            </section>
        </div>

        {{-- Order recap, compact: the figures already approved, without the edit link --}}
        <div class="mt-6">
            <x-domain.order-summary
                compact
                :model="$order['model']"
                :branch="$order['branch']"
                :subjects="$order['subjects']"
                :period="$order['period']"
                :subtotal="$subtotal"
                :discount="$discount"
                :total="$total" />
        </div>

        <div class="mt-8 flex flex-col-reverse gap-4 sm:flex-row sm:items-center sm:justify-between">
            <x-ui.button variant="secondary"
                         size="md"
                         href="{{ Route::has('student.profile') ? route('student.profile') : '#' }}"
                         class="w-full sm:w-auto">
                <x-icon name="mail" class="size-4" />
                {{ __('subscription.pending_support') }}
            </x-ui.button>

            <x-ui.button variant="primary"
                         size="md"
                         :href="route('student.dashboard')"
                         class="w-full sm:w-auto">
                {{ __('subscription.pending_dashboard') }}
            </x-ui.button>
        </div>
    </div>

</x-layouts.student>
