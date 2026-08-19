@php
    // عرض تجريبي: ?error=1 يُظهر حالة فشل الدخول (بلا خادم بعد)
    $showError = request()->boolean('error');
@endphp

<x-layouts.centered :title="__('auth.login_title')" width="narrow">

    <div class="rounded-xl bg-canvas p-6 shadow-subtle sm:p-8">

        <div class="text-center">
            <h1 class="text-h3 font-bold text-ink">{{ __('auth.login_title') }}</h1>
            <p class="mt-1.5 text-ui text-steel">{{ __('auth.login_subtitle') }}</p>
        </div>

        {{--
            الخطأ على مستوى النموذج لا الحقل: أمنياً لا يُكشف أيّ الحقلين
            كان الخطأ، فوسم حقل بعينه بالأحمر يسرّب المعلومة بصرياً.
        --}}
        @if ($showError)
            <x-ui.form-errors class="mt-6" :title="__('auth.login_failed_title')">
                {{ __('auth.login_failed_body') }}
            </x-ui.form-errors>
        @endif

        {{--
            data-demo-submit: بلا خادم بعد — JS يمنع الإرسال الفعلي وينتقل
            للوجهة. عمداً لا method="GET" حتى لا تظهر كلمة المرور في الرابط.
        --}}
        <form data-demo-submit="{{ route('student.dashboard') }}" class="mt-6 flex flex-col gap-4">

            <x-ui.input
                name="email"
                type="email"
                :label="__('auth.email_label')"
                :placeholder="__('auth.email_placeholder')"
                required />

            <div class="flex flex-col gap-1.5">
                <x-ui.password-input
                    name="password"
                    :label="__('auth.password_label')"
                    required />

                <a href="{{ route('auth.forgot') }}"
                   class="self-end text-caption font-medium text-accent-deep transition hover:underline">
                    {{ __('auth.login_forgot') }}
                </a>
            </div>

            <x-ui.checkbox name="remember" :label="__('auth.login_remember')" />

            <x-ui.button type="submit" size="md" class="mt-2 w-full">
                {{ __('auth.login_submit') }}
            </x-ui.button>
        </form>
    </div>

    <x-slot:footer>
        {{ __('auth.login_no_account') }}
        <a href="{{ route('auth.register') }}" class="font-semibold text-accent-deep transition hover:underline">
            {{ __('auth.login_create_account') }}
        </a>
    </x-slot:footer>

</x-layouts.centered>
