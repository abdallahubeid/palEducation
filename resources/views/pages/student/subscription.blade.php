@php
    // بيانات عرض — بانتظار حسم م-1 (نطاق الاشتراك) وم-4 (آلية الدفع)
    $plan = [
        'name'      => 'خطة الفصل الدراسي — الفرع العلمي',
        'status'    => 'expiring',   // active | expiring | expired
        'price'     => '١٢٠ ₪',
        'period'    => '/ فصل دراسي',
        'renewDate' => '2026-09-15',
        'daysLeft'  => 28,
    ];

    $features = [
        'وصول كامل لمحاضرات فرعك العلمي',
        'كويز بعد كل محاضرة بلا حدّ للمحاولات المسموحة',
        'مكتبة الملفات كاملة — أوراق عمل وامتحانات سابقة',
        'دعم فني عبر واتساب خلال ساعات الدوام',
    ];

    $history = [
        ['date' => '2026-06-15', 'amount' => '١٢٠ ₪', 'method' => 'تحويل بنكي', 'status' => 'paid'],
        ['date' => '2026-03-15', 'amount' => '١٢٠ ₪', 'method' => 'محفظة جوّال', 'status' => 'paid'],
        ['date' => '2025-12-15', 'amount' => '١٢٠ ₪', 'method' => 'تحويل بنكي', 'status' => 'paid'],
    ];
@endphp

<x-layouts.student
    :title="__('student.subscription_title')"
    :student-name="'محمد أبو عودة'"
    :subscription-state="$plan['status']"
    :unread-count="2">

    <div class="mx-auto flex max-w-4xl flex-col gap-6">
        <h1 class="text-h2 font-bold text-ink">{{ __('student.subscription_title') }}</h1>

        <x-domain.plan-summary-card
            :plan-name="$plan['name']"
            :status="$plan['status']"
            :price="$plan['price']"
            :period="$plan['period']"
            :renew-date="$plan['renewDate']"
            :days-left="$plan['daysLeft']" />

        @if (in_array($plan['status'], ['expiring', 'expired']))
            <x-ui.alert :variant="$plan['status'] === 'expired' ? 'error' : 'warn'">
                <p class="font-semibold text-ink">
                    {{ $plan['status'] === 'expired' ? __('student.subscription_expired_title') : __('student.subscription_warning_title') }}
                </p>
                <p class="mt-0.5">
                    {{ $plan['status'] === 'expired'
                        ? __('student.subscription_expired_body')
                        : __('student.subscription_warning_body', ['count' => $plan['daysLeft']]) }}
                </p>
            </x-ui.alert>
        @endif

        <x-ui.button variant="primary" size="lg" :href="Route::has('pricing') ? route('pricing') : '#'" class="w-full sm:w-fit">
            {{ __('student.subscription_renew_cta') }}
            <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
        </x-ui.button>

        <div class="tile p-6">
            <h2 class="text-h5 font-semibold text-ink">{{ __('student.subscription_features_title') }}</h2>
            <ul class="mt-4 flex flex-col gap-3">
                @foreach ($features as $feature)
                    <li class="flex items-start gap-2.5 text-ui text-slate">
                        <x-icon name="check" class="mt-1 size-4 shrink-0 text-accent-deep" />
                        <span>{{ $feature }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="tile p-3 sm:p-6">
            <h2 class="px-2 text-h5 font-semibold text-ink sm:px-0">{{ __('student.subscription_history_title') }}</h2>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-start text-ui">
                    <thead>
                        <tr class="border-b border-hairline text-caption text-stone">
                            <th class="py-2 text-start font-medium">{{ __('student.subscription_history_date') }}</th>
                            <th class="py-2 text-start font-medium">{{ __('student.subscription_history_amount') }}</th>
                            <th class="py-2 text-start font-medium">{{ __('student.subscription_history_method') }}</th>
                            <th class="py-2 text-start font-medium">{{ __('student.subscription_history_status') }}</th>
                            <th class="py-2 text-start font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($history as $payment)
                            <tr class="border-b border-hairline-soft last:border-0">
                                <td class="num py-3 text-steel">{{ $payment['date'] }}</td>
                                <td class="num py-3 font-semibold text-ink">{{ $payment['amount'] }}</td>
                                <td class="py-3 text-steel">{{ $payment['method'] }}</td>
                                <td class="py-3">
                                    <x-ui.badge variant="accent">{{ __('student.subscription_history_status_paid') }}</x-ui.badge>
                                </td>
                                <td class="py-3">
                                    <a href="#" class="text-caption font-semibold text-accent-deep hover:underline">
                                        {{ __('student.subscription_receipt') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-layouts.student>
