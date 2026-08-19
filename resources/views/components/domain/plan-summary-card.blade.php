@props([
    'planName'  => '',
    'status'    => 'active',   // active | expiring | expired
    'price'     => '',
    'period'    => '',
    'renewDate' => '',
    'daysLeft'  => 0,
])

@php
    $statusConfig = [
        'active'   => ['variant' => 'accent', 'label' => __('student.subscription_status_active')],
        'expiring' => ['variant' => 'warn', 'label' => __('student.subscription_warning_title')],
        'expired'  => ['variant' => 'error', 'label' => __('student.subscription_expired_title')],
    ];
    $s = $statusConfig[$status] ?? $statusConfig['active'];
@endphp

<div class="tile flex flex-col gap-5 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-caption text-stone">{{ __('student.subscription_current_plan') }}</p>
            <h2 class="mt-1 text-h3 font-bold text-ink">{{ $planName }}</h2>
        </div>
        <x-ui.badge :variant="$s['variant']">{{ $s['label'] }}</x-ui.badge>
    </div>

    <div class="flex items-baseline gap-2">
        <span class="num text-h2 font-bold text-ink">{{ $price }}</span>
        <span class="text-caption text-stone">{{ $period }}</span>
    </div>

    <dl class="grid grid-cols-2 gap-4 border-t border-hairline-soft pt-4">
        <div>
            <dt class="text-caption text-stone">{{ __('student.subscription_renew_date') }}</dt>
            <dd class="num mt-1 text-ui font-semibold text-ink">{{ $renewDate }}</dd>
        </div>
        <div>
            <dt class="text-caption text-stone">{{ __('student.subscription_days_left') }}</dt>
            <dd class="num mt-1 text-ui font-semibold text-ink">{{ $daysLeft }}</dd>
        </div>
    </dl>
</div>
