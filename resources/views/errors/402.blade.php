@php
    // يُمرَّر من الـController عند الحجب الفعلي؛ القيم هنا احتياط للعرض المباشر
    $subject = $subject ?? 'الرياضيات';
    $lectureCount = $lectureCount ?? 12;
@endphp

<x-layouts.centered :title="__('system.paywall_title', ['subject' => $subject, 'count' => $lectureCount])" width="full">

    {{--
        🔴 صفحة بيع لا صفحة رفض. الطالب يصلها في ذروة الرغبة بالمحتوى —
        أفضل لحظة تحويل في المنصة. بلا لغة خطأ، بلا أيقونة قفل حمراء،
        بلا نبرة error. النبرة accent: ما ينتظرك، لا ما مُنعت منه.
    --}}
    <div class="text-center">
        <x-ui.badge variant="accent" class="mb-4">
            <x-icon name="sparkle" class="size-3.5" />
            {{ __('system.paywall_eyebrow') }}
        </x-ui.badge>

        <h1 class="text-h2 font-bold text-ink">
            {{ __('system.paywall_title', ['subject' => $subject, 'count' => $lectureCount]) }}
        </h1>

        <p class="measure mx-auto mt-3 text-lead text-steel">{{ __('system.paywall_body') }}</p>
    </div>

    {{--
        البطاقة هنا عرض بيع لا ملخّص اشتراك: بلا شارة «انتهى» الحمراء وبلا
        تاريخ تجديد. الشارة الحمراء كانت تُدخل نبرة الرفض على أنجح لحظة
        تحويل في المنصة — وهو تحديداً ما تمنعه القاعدة.
    --}}
    <div class="mx-auto mt-9 max-w-md">
        <x-domain.plan-summary-card
            :eyebrow="__('system.paywall_plan_title')"
            plan-name="اشتراك الفرع — سنوي"
            :status="null"
            price="180₪"
            period="للسنة الدراسية كاملة" />
    </div>

    <div class="mt-8 flex flex-col items-center gap-3">
        <x-ui.button size="lg"
                     :href="Route::has('student.subscription') ? route('student.subscription') : '#'"
                     class="w-full sm:w-auto sm:min-w-72">
            {{ __('system.paywall_cta') }}
        </x-ui.button>

        {{-- رابط رجوع باهت عمداً — لا ينافس زر التحويل --}}
        <a href="{{ Route::has('student.dashboard') ? route('student.dashboard') : url('/') }}"
           class="rounded-md px-3 py-2 text-caption text-stone transition hover:text-ink">
            {{ __('system.paywall_back') }}
        </a>
    </div>

</x-layouts.centered>
