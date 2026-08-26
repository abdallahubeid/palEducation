<x-layouts.centered :title="__('auth.login_title')" width="narrow">

    <div class="rounded-xl bg-canvas p-6 shadow-subtle sm:p-8">

        <div class="text-center">
            <h1 class="text-h3 font-bold text-ink">{{ __('auth.login_title') }}</h1>
            <p class="mt-1.5 text-ui text-steel">{{ __('auth.login_subtitle') }}</p>
        </div>

        {{--
            الخطأ على مستوى النموذج لا الحقل: أمنياً لا يُكشف أيّ الحقلين
            كان الخطأ، فوسم حقل بعينه بالأحمر يسرّب المعلومة بصرياً.
            الكنترولر يرمي الخطأ على مفتاح email دائماً لهذا السبب.
        --}}
        @if ($errors->any())
            <x-ui.form-errors class="mt-6" :title="__('auth.login_failed_title')">
                {{ $errors->first() }}
            </x-ui.form-errors>
        @endif

        {{-- جوجل أولاً: أسرع مسار دخول، وأكثره استخداماً لدى طلبة الثانوية --}}
        <div class="mt-6">
            <x-ui.google-button href="{{ route('auth.google.redirect') }}" />
        </div>

        {{-- فاصل «أو» — الخط يمرّ خلف النص لا بجانبه --}}
        <div class="my-5 flex items-center gap-3" role="separator">
            <span class="h-px flex-1 bg-hairline"></span>
            <span class="text-caption text-stone">{{ __('auth.or_divider') }}</span>
            <span class="h-px flex-1 bg-hairline"></span>
        </div>

        <form method="POST" action="{{ route('auth.login.store') }}" class="flex flex-col gap-4">
            @csrf

            <x-ui.input
                name="email"
                type="email"
                autocomplete="email"
                :value="old('email')"
                :label="__('auth.email_label')"
                :placeholder="__('auth.email_placeholder')"
                required />

            <div class="flex flex-col gap-1.5">
                <x-ui.password-input
                    name="password"
                    autocomplete="current-password"
                    :label="__('auth.password_label')"
                    required />

                <a href="{{ route('auth.forgot') }}"
                   class="self-end text-caption font-medium text-accent-deep transition hover:underline">
                    {{ __('auth.login_forgot') }}
                </a>
            </div>

            <x-ui.checkbox name="remember" value="1" :label="__('auth.login_remember')" />

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
