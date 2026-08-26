<x-layouts.centered :title="__('auth.verify_title')" width="narrow">

    <div class="rounded-xl bg-canvas p-6 shadow-subtle sm:p-8">

        <div class="text-center">
            {{-- أيقونة حالة لا تحذير: التوثيق خطوة عادية لا خطأ ارتكبه الطالب --}}
            <span class="mx-auto grid size-12 place-items-center rounded-full bg-accent/14" aria-hidden="true">
                <x-icon name="mail" class="size-6 text-accent-deep" />
            </span>

            <h1 class="mt-4 text-h3 font-bold text-ink">{{ __('auth.verify_title') }}</h1>

            {{-- 🔴 البريد مقطع لاتيني داخل جملة عربية — يُلَفّ بـ bdi وإلا انقلب ترتيبه بصرياً --}}
            <p class="mt-2 text-ui leading-[1.8] text-steel">
                {!! __('auth.verify_subtitle', ['email' => '<bdi class="font-semibold text-ink">'.e(auth()->user()->email).'</bdi>']) !!}
            </p>
        </div>

        <p class="mt-5 rounded-lg bg-surface-2 p-4 text-caption leading-[1.8] text-steel">
            {{ __('auth.verify_body') }}
        </p>

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <x-ui.button type="submit" size="md" class="w-full">
                {{ __('auth.verify_resend') }}
            </x-ui.button>
        </form>

        <form method="POST" action="{{ route('auth.logout') }}" class="mt-3">
            @csrf
            <button type="submit"
                    class="w-full text-center text-caption font-medium text-stone transition hover:text-ink hover:underline">
                {{ __('auth.logout') }}
            </button>
        </form>
    </div>

</x-layouts.centered>
