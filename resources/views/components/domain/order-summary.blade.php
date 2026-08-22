@props([
    'model'     => 'branch',      // branch | subjects
    'branch'    => '',
    'subjects'  => [],            // list of subject names (per-subject model)
    'period'    => null,
    'subtotal'  => 0,
    'discount'  => 0,             // positive number; rendered as a negative line
    'total'     => 0,
    'editHref'  => null,
    'compact'   => false,         // drops the edit link and the subject list
])

{{--
    Order summary, shared by checkout (8) and the pending screen (10) so the
    figures a student approves are rendered by exactly one piece of markup.

    All amounts run through `.num` (Inter + tabular-nums) with Western numerals -
    Eastern Arabic-Indic digits are banned system-wide, and tabular figures keep
    the currency column aligned down the rows.
--}}

@php
    $cur = __('subscription.summary_currency');
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl bg-canvas p-6 ring-1 ring-hairline']) }}>
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-h5 font-semibold text-ink">{{ __('subscription.summary_title') }}</h2>

        @if ($editHref && ! $compact)
            <a href="{{ $editHref }}"
               class="-me-2 inline-flex min-h-11 items-center px-2 text-caption font-semibold text-accent-deep
                      transition hover:underline">
                {{ __('subscription.summary_edit') }}
            </a>
        @endif
    </div>

    <dl class="mt-5 flex flex-col gap-3 border-t border-hairline pt-5 text-ui">
        <div class="flex items-start justify-between gap-4">
            <dt class="text-stone">{{ __('subscription.summary_plan') }}</dt>
            <dd class="text-end font-semibold text-ink">
                {{ $model === 'branch' ? __('subscription.model_branch') : __('subscription.model_per_subject') }}
            </dd>
        </div>

        <div class="flex items-start justify-between gap-4">
            <dt class="text-stone">{{ __('subscription.summary_branch') }}</dt>
            <dd class="text-end font-semibold text-ink">{{ $branch }}</dd>
        </div>

        <div class="flex items-start justify-between gap-4">
            <dt class="shrink-0 text-stone">{{ __('subscription.summary_subjects') }}</dt>
            <dd class="text-end font-semibold text-ink">
                @if ($model === 'branch')
                    {{ __('subscription.calc_all_subjects') }}
                    <span class="num text-caption font-normal text-stone">({{ count($subjects) }})</span>
                @else
                    <span class="num">{{ count($subjects) }}</span> {{ __('subscription.calc_subject_count') }}
                @endif
            </dd>
        </div>

        @if (! $compact && $model === 'subjects' && ! empty($subjects))
            <div>
                <ul class="mt-1 flex flex-wrap justify-end gap-1.5">
                    @foreach ($subjects as $s)
                        <li>
                            <x-ui.badge variant="neutral">{{ $s }}</x-ui.badge>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($period)
            <div class="flex items-start justify-between gap-4">
                <dt class="text-stone">{{ __('subscription.summary_period') }}</dt>
                <dd class="text-end font-semibold text-ink">{{ $period }}</dd>
            </div>
        @endif
    </dl>

    <dl class="mt-5 flex flex-col gap-3 border-t border-hairline pt-5 text-ui">
        <div class="flex items-center justify-between gap-4">
            <dt class="text-stone">{{ __('subscription.summary_subtotal') }}</dt>
            <dd class="num font-semibold text-ink">{{ $subtotal }} {{ $cur }}</dd>
        </div>

        @if ($discount > 0)
            <div class="flex items-center justify-between gap-4">
                <dt class="text-accent-deep">{{ __('subscription.summary_discount') }}</dt>
                {{-- The minus sign is forced LTR so it never re-orders against the numeral --}}
                <dd class="num font-semibold text-accent-deep"><bdi dir="ltr">−{{ $discount }}</bdi> {{ $cur }}</dd>
            </div>
        @endif
    </dl>

    <div class="mt-5 flex items-baseline justify-between gap-4 border-t border-hairline pt-5">
        <span class="text-h5 font-semibold text-ink">{{ __('subscription.summary_total') }}</span>
        <span class="num text-h3 font-bold text-ink">{{ $total }} {{ $cur }}</span>
    </div>

    {{ $slot }}
</div>
