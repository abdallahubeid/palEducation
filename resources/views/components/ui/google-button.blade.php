@props(['label' => null])

{{--
    زر «المتابعة عبر جوجل».

    الشعار مرسوم هنا مباشرة لا في x-icon: مجموعة الأيقونات خطّية أحادية
    اللون بسماكة 1.75، وشعار جوجل علامة تجارية متعدّدة الألوان بمسارات
    مصمتة. إدخاله هناك كان سيكسر اتّساق المجموعة، وتلوينه بلون واحد
    مخالفة لإرشادات جوجل للعلامة.

    🔴 الشعار لا ينعكس مع RTL — العلامات التجارية لا تُعكس أبداً
    (rtl-bilingual.md §2). ولهذا لا يحمل rtl:-scale-x-100.
--}}

<a {{ $attributes->merge([
        'class' => 'inline-flex h-10 w-full items-center justify-center gap-2.5 rounded-md
                    border border-hairline-strong bg-canvas px-4 font-semibold text-ink
                    transition hover:bg-surface-2
                    focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary/20',
   ]) }}>

    <svg class="size-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path fill="#4285F4" d="M23.52 12.27c0-.85-.08-1.67-.22-2.45H12v4.63h6.46a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.58-5.17 3.58-8.8z"/>
        <path fill="#34A853" d="M12 24c3.24 0 5.96-1.08 7.94-2.91l-3.88-3.01c-1.08.72-2.45 1.15-4.06 1.15-3.13 0-5.78-2.11-6.73-4.95H1.26v3.11A12 12 0 0 0 12 24z"/>
        <path fill="#FBBC05" d="M5.27 14.28a7.2 7.2 0 0 1 0-4.56v-3.1H1.26a12 12 0 0 0 0 10.77l4.01-3.11z"/>
        <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.44-3.44C17.95 1.19 15.23 0 12 0A12 12 0 0 0 1.26 6.62l4.01 3.1C6.22 6.87 8.87 4.75 12 4.75z"/>
    </svg>

    <span>{{ $label ?? __('auth.google_continue') }}</span>
</a>
