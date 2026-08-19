<x-layouts.centered :title="__('auth.forgot_title')" width="narrow">

    <div class="rounded-xl bg-canvas p-6 shadow-subtle sm:p-8" data-forgot>

        {{-- ══ حالة النموذج ══ --}}
        <div data-forgot-form>
            <div class="text-center">
                <h1 class="text-h3 font-bold text-ink">{{ __('auth.forgot_title') }}</h1>
                <p class="mt-1.5 text-ui text-steel">{{ __('auth.forgot_subtitle') }}</p>
            </div>

            <form data-forgot-submit class="mt-6 flex flex-col gap-4">
                <x-ui.input
                    name="email"
                    type="email"
                    :label="__('auth.email_label')"
                    :placeholder="__('auth.email_placeholder')"
                    required />

                <x-ui.button type="submit" size="md" class="mt-2 w-full">
                    {{ __('auth.forgot_submit') }}
                </x-ui.button>
            </form>
        </div>

        {{--
            ══ حالة النجاح ══
            تحلّ محلّ النموذج ولا تُكدَّس تحته — الشاشة تعرض حالة واحدة.
            الصياغة لا تكشف إن كان البريد مسجّلاً فعلاً (تعداد الحسابات).
        --}}
        <div data-forgot-sent hidden>
            <x-ui.empty-state
                icon="mail"
                :title="__('auth.forgot_sent_title')"
                :body="__('auth.forgot_sent_body')" />

            <button type="button" data-forgot-resend
                    class="mt-1 w-full rounded-md py-2 text-caption font-medium text-accent-deep transition hover:underline">
                {{ __('auth.forgot_resend') }}
            </button>
        </div>
    </div>

    <x-slot:footer>
        <a href="{{ route('auth.login') }}" class="font-semibold text-accent-deep transition hover:underline">
            {{ __('auth.back_to_login') }}
        </a>
    </x-slot:footer>

</x-layouts.centered>
