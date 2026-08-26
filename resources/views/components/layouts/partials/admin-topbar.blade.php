@props([
    'title' => null,
    'breadcrumbs' => null,
])

@php
    $user = auth()->user();

    // 🔴 name وسيط على User يقرأ full_name من سجلّ المجال — لا عمود name
    // في users. avatar() تُرجع المرفوعة أولاً ثم صورة جوجل.
    $userName = $user?->name;
    $avatarUrl = $user?->avatar();
    $initial = mb_substr($userName ?: '؟', 0, 1);
@endphp

<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-3
               border-b border-hairline bg-ground/80 px-4 backdrop-blur-md sm:px-6">

    <div class="flex min-w-0 items-center gap-3">
        <button type="button" data-admin-open
                class="cursor-pointer rounded-lg p-1.5 text-stone transition hover:bg-surface lg:hidden"
                aria-label="فتح القائمة">
            <x-icon name="menu" class="size-6" />
        </button>

        <div class="min-w-0">
            <h1 class="truncate text-h5 font-bold text-ink">{{ $title ?? __('admin.panel') }}</h1>
            @if ($breadcrumbs)
                <p class="hidden truncate text-micro text-stone sm:block">{{ $breadcrumbs }}</p>
            @endif
        </div>
    </div>

    <div class="flex items-center gap-2">
        {{-- رابط الموقع العام — الأدمن يتحقّق من أثر تعديلاته باستمرار --}}
        <a href="{{ route('home') }}" target="_blank" rel="noopener"
           class="hidden items-center gap-2 rounded-lg border border-hairline bg-canvas px-3 py-2
                  text-caption font-medium text-steel transition hover:border-hairline-strong hover:text-ink sm:inline-flex">
            <x-icon name="eye" class="size-4" />
            عرض الموقع
        </a>

        {{--
            قائمة الحساب — <details> أصيل لا JavaScript:
            يفتح ويغلق ويستجيب للوحة المفاتيح بلا سطر واحد، وAlpine غير متوفّر
            في هذا المشروع (Livewire 3 غير مثبّت).

            🔴 الاسم والصورة فقط — بلا بريد إلكتروني في المُشغِّل ولا في جسم
            القائمة. البريد معرّف دخول، وعرضه دائماً على شاشة قد تُشارَك أو
            تُصوَّر يكشفه بلا داعٍ؛ ومكانه الصحيح صفحة الملف الشخصي.
        --}}
        <details data-admin-menu class="relative">
            <summary class="flex cursor-pointer list-none items-center gap-2.5 rounded-lg p-1
                            transition hover:bg-surface [&::-webkit-details-marker]:hidden">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $userName }}"
                         class="size-9 shrink-0 rounded-full object-cover ring-1 ring-hairline">
                @else
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-accent-soft
                                 text-ui font-bold text-accent-deep">{{ $initial }}</span>
                @endif

                <span class="hidden truncate text-caption font-semibold text-ink sm:block">{{ $userName }}</span>
                <x-icon name="chevron-down" class="hidden size-4 shrink-0 text-stone sm:block" />
            </summary>

            <div class="absolute end-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-hairline
                        bg-canvas py-1 shadow-card">

                {{-- هوية الحساب: الاسم والصورة فقط --}}
                <div class="flex items-center gap-3 border-b border-hairline px-4 py-3">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $userName }}"
                             class="size-9 shrink-0 rounded-full object-cover ring-1 ring-hairline">
                    @else
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-accent-soft
                                     text-ui font-bold text-accent-deep">{{ $initial }}</span>
                    @endif
                    <p class="min-w-0 truncate text-caption font-semibold text-ink">{{ $userName }}</p>
                </div>

                <a href="{{ route('admin.profile.edit') }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-ui text-steel transition
                          hover:bg-surface hover:text-ink">
                    <x-icon name="user" class="size-4" />
                    الملف الشخصي
                </a>

                <form method="POST" action="{{ route('auth.logout') }}"
                      class="border-t border-hairline">
                    @csrf
                    <button type="submit"
                            class="flex w-full cursor-pointer items-center gap-2.5 px-4 py-2.5 text-start
                                   text-ui text-error-deep transition hover:bg-error/8">
                        <x-icon name="logout" class="size-4 rtl:-scale-x-100" />
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </details>
    </div>
</header>
