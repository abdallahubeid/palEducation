<x-layouts.centered :title="__('system.notfound_title')" width="narrow">

    {{-- نفس هيكل 403 بالضبط — أيقونة ونصّ مختلفان فقط. حالة نظام واحدة
         بثلاث تهيئات، لا ثلاثة تصاميم. --}}
    <div class="flex flex-col items-center rounded-xl bg-canvas px-6 py-12 text-center shadow-subtle sm:px-10">

        <span class="grid size-16 place-items-center rounded-full bg-surface text-stone">
            <x-icon name="compass" class="size-7" />
        </span>

        <p class="num mt-5 text-micro font-semibold text-muted">{{ __('system.notfound_code') }}</p>

        <h1 class="mt-1.5 text-h3 font-bold text-ink">{{ __('system.notfound_title') }}</h1>
        <p class="measure mt-2.5 text-ui text-steel">{{ __('system.notfound_body') }}</p>

        <div class="mt-7 flex w-full flex-col items-center gap-2 sm:w-auto">
            <x-ui.button size="md"
                         :href="Route::has('student.dashboard') ? route('student.dashboard') : url('/')"
                         class="w-full sm:w-auto sm:min-w-56">
                {{ __('system.notfound_cta') }}
            </x-ui.button>

            <a href="{{ url('/') }}"
               class="rounded-md px-3 py-2 text-caption text-stone transition hover:text-ink">
                {{ __('system.notfound_home') }}
            </a>
        </div>
    </div>

</x-layouts.centered>
