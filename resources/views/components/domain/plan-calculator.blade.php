@props([
    'branches'     => [],      // ['scientific' => ['name' => '...', 'subjects' => [...]], ...]
    'unitPrice'    => 0,       // price per subject, per term
    'branchPrice'  => 0,       // full-branch pass, per term
    'defaultBranch' => null,
    'checkoutHref' => '#',
])

@php
    $defaultBranch = $defaultBranch ?: array_key_first($branches);
    $cur = __('subscription.summary_currency');
@endphp

{{--
    Per-subject calculator - the interactive half of the M-1 pricing model.

    State lives in the DOM (checked checkboxes), not in a parallel JS object, so
    the same markup moves to Livewire later without redesign. Driven by
    `data-calc-*` hooks in app.js; there is no Alpine in this project.

    The subject lists for every branch are all rendered server-side and toggled
    with `hidden`, rather than being rebuilt in JS from a data blob. That keeps
    the branch content in Blade where it belongs and avoids building class names
    or markup from variables at runtime.

    The break-even nudge is the product point: at this pricing, 4 subjects cost
    more than the whole-branch pass, so the calculator says so out loud instead
    of letting a student overpay.
--}}

<div class="rounded-xl bg-canvas p-6 ring-1 ring-hairline sm:p-8"
     data-calc
     data-calc-unit="{{ $unitPrice }}"
     data-calc-branch-price="{{ $branchPrice }}"
     data-calc-currency="{{ $cur }}">

    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h3 class="text-h4 font-semibold text-ink">{{ __('subscription.calc_title') }}</h3>
        <p class="text-caption text-stone">
            {{ __('subscription.calc_per_subject_price', ['price' => $unitPrice]) }}
        </p>
    </div>

    {{--
        Branch selector. ui/select builds its options from the `options` prop
        (it has no slot), and merges extra attributes onto its WRAPPER div - so
        `data-calc-branch` marks the wrapper and app.js reaches the control with
        `[data-calc-branch] select`.
    --}}
    <div class="mt-6">
        <x-ui.select :label="__('subscription.calc_branch_label')"
                     name="calc_branch"
                     :options="collect($branches)->map(fn ($b) => $b['name'])->all()"
                     :value="$defaultBranch"
                     data-calc-branch />
    </div>

    {{-- Subject lists: one per branch, only the active one visible --}}
    <div class="mt-6">
        <p class="text-ui font-medium text-steel">{{ __('subscription.calc_subjects_label') }}</p>

        @foreach ($branches as $key => $branch)
            <div data-calc-group="{{ $key }}"
                 @if ($key !== $defaultBranch) hidden @endif
                 class="mt-3 grid gap-x-6 sm:grid-cols-2">
                {{--
                    No data hook on the checkboxes: ui/checkbox merges attributes onto
                    its wrapper too, so app.js reads the real inputs with
                    `group.querySelectorAll('input[type=checkbox]')` instead.
                --}}
                @foreach ($branch['subjects'] as $subject)
                    <x-ui.checkbox
                        name="subjects_{{ $key }}[]"
                        :value="$subject"
                        :label="$subject" />
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- Live total --}}
    <div class="mt-7 border-t border-hairline pt-6">
        <div class="flex items-baseline justify-between gap-4">
            <span class="text-ui text-stone">
                {{ __('subscription.calc_selected') }}
                <span class="num font-semibold text-ink" data-calc-count>0</span>
            </span>

            <span class="flex items-baseline gap-2">
                <span class="num text-h2 font-bold text-ink" data-calc-total>0</span>
                <span class="text-caption text-stone">{{ $cur }}</span>
            </span>
        </div>

        {{-- Shown only while nothing is selected --}}
        <p class="mt-3 text-caption text-stone" data-calc-empty>{{ __('subscription.calc_empty') }}</p>

        {{--
            Break-even nudge. Text is written by JS from a template string held in
            data-template, so the wording still comes from the lang file and is
            never hardcoded in JavaScript.
        --}}
        <div hidden
             data-calc-nudge
             data-template="{{ __('subscription.calc_switch_hint', ['count' => '%count%', 'total' => '%total%', 'branch' => $branchPrice]) }}"
             class="mt-4 flex flex-col gap-3 rounded-lg bg-accent-soft p-4 ring-1 ring-accent/20 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-ui text-accent-deep" data-calc-nudge-text></p>

            {{--
                Secondary, not primary: this sits directly above the calculator's own
                CTA, and two adjacent primary buttons destroy the hierarchy. The nudge
                is a helpful suggestion, not the main path forward.
            --}}
            <x-ui.button variant="secondary" size="sm" class="shrink-0" data-calc-switch>
                {{ __('subscription.calc_switch_action') }}
            </x-ui.button>
        </div>

        <x-ui.button variant="primary"
                     size="md"
                     :href="$checkoutHref"
                     class="mt-6 w-full"
                     data-calc-cta>
            {{ __('subscription.calc_cta') }}
        </x-ui.button>
    </div>
</div>
