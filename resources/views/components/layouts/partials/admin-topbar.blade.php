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
            ═══ جرس الإشعارات ═══

            🔴 يستطلع كل ١٥ ثانية ويتوقّف حين يُخفى التبويب — بدون ذلك كل
            تبويب لوحة متروك مفتوحاً يضرب الخادم أربع مرات في الدقيقة إلى
            الأبد، ويُبقي الجلسة حيّة معه.

            المسارات تُمرَّر من Blade لا تُبنى في JS: تغيير بادئة المسار في
            routes/admin.php يجب ألّا يترك سلسلة نصّية مكسورة في حزمة JS.
        --}}
        <div x-data="bellFeed({
                 index: '{{ route('admin.notifications.feed') }}',
                 read: '{{ route('admin.notifications.read', ['id' => '__ID__']) }}',
                 readAll: '{{ route('admin.notifications.read-all') }}',
             })"
             @click.outside="open = false"
             @keydown.escape.window="open = false"
             class="relative">

            <button type="button" @click="open = ! open"
                    :aria-expanded="open.toString()"
                    class="relative cursor-pointer rounded-lg p-2 text-stone transition hover:bg-surface hover:text-ink"
                    aria-label="الإشعارات">
                <x-icon name="bell" class="size-5" />

                {{-- الشارة تختفي عند الصفر: شارة تقول «٠» ضجيج يُدرّب العين
                     على تجاهلها. --}}
                <span x-show="unread > 0" x-cloak
                      class="absolute -top-0.5 -end-0.5 grid min-w-4 place-items-center rounded-full
                             bg-error px-1 text-[10px] font-bold leading-4 text-on-dark">
                    <span x-text="unread > 9 ? '9+' : unread"></span>
                </span>
            </button>

            <div x-show="open" x-cloak
                 class="absolute end-0 z-50 mt-2 flex max-h-[70vh] w-80 max-w-[85vw] flex-col
                        overflow-hidden rounded-xl border border-hairline bg-canvas shadow-card">

                <div class="flex items-center justify-between gap-2 border-b border-hairline px-4 py-3">
                    <h2 class="text-caption font-bold text-ink">الإشعارات</h2>

                    <div class="flex items-center gap-1">
                        {{-- ⚠️ النغمة مُطفأة افتراضياً: واجهة تُصدر صوتاً بلا
                             أن يُطلب منها هي أول ما يُعطَّل بالكامل. --}}
                        <button type="button" @click="toggleChime()"
                                class="cursor-pointer rounded-md p-1.5 text-stone transition hover:bg-surface"
                                :aria-pressed="chime.toString()"
                                :title="chime ? 'إيقاف النغمة' : 'تشغيل النغمة'">
                            <span x-show="chime"><x-icon name="bell" class="size-3.5" /></span>
                            <span x-show="! chime" x-cloak><x-icon name="eye-off" class="size-3.5" /></span>
                        </button>

                        <button type="button" @click="markAll()" x-show="unread > 0" x-cloak
                                class="cursor-pointer rounded-md px-2 py-1 text-micro font-semibold
                                       text-accent-deep transition hover:bg-accent/8">
                            تحديد الكل كمقروء
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <template x-if="loading">
                        <p class="px-4 py-10 text-center text-caption text-stone">جارٍ التحميل…</p>
                    </template>

                    <template x-if="! loading && items.length === 0">
                        <p class="px-4 py-10 text-center text-caption text-stone">لا إشعارات بعد.</p>
                    </template>

                    <template x-for="item in items" :key="item.id">
                        {{-- زرّ لا رابط: النقرة تُعلّم الإشعار مقروءاً أولاً ثم
                             تنتقل، والرابط الأصيل كان سينتقل قبل أن تصل
                             الكتابة. --}}
                        <button type="button" @click="visit(item)"
                                class="flex w-full cursor-pointer items-start gap-3 border-b border-hairline
                                       px-4 py-3 text-start transition last:border-b-0 hover:bg-surface/70"
                                :class="! item.read && 'bg-accent/5'">

                            <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-lg bg-surface text-stone">
                                {{-- الأيقونات كلها مرسومة هنا ويُظهَر منها
                                     المطابق: أسماء x-icon لا تُبنى ديناميكياً
                                     من JS. --}}
                                <span x-show="item.icon === 'mail'"><x-icon name="mail" class="size-3.5" /></span>
                                <span x-show="item.icon === 'sparkle'"><x-icon name="sparkle" class="size-3.5" /></span>
                                <span x-show="item.icon === 'trash'"><x-icon name="trash" class="size-3.5" /></span>
                                <span x-show="item.icon === 'user'"><x-icon name="user" class="size-3.5" /></span>
                                <span x-show="item.icon === 'settings'"><x-icon name="settings" class="size-3.5" /></span>
                                <span x-show="item.icon === 'alert'"><x-icon name="alert" class="size-3.5" /></span>
                                <span x-show="! ['mail','sparkle','trash','user','settings','alert'].includes(item.icon)">
                                    <x-icon name="bell" class="size-3.5" />
                                </span>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-caption font-semibold text-ink" x-text="item.title"></span>
                                <span class="mt-0.5 block text-micro leading-relaxed text-stone" x-text="item.message"></span>
                                {{-- الوقت النسبي يُنسَّق على الخادم: المتصفّح لا
                                     يملك صيغ الجمع العربية الستّ، وCarbon تملكها. --}}
                                <span class="mt-1 block text-micro text-muted" x-text="item.time"></span>
                            </span>

                            <span x-show="! item.read"
                                  class="mt-1.5 size-1.5 shrink-0 rounded-full bg-accent"></span>
                        </button>
                    </template>
                </div>

                {{-- ⚠️ خارج الحاوية القابلة للتمرير وبـshrink-0: داخلها كان
                     الرابط سينزلق مع القائمة ويختفي تحت الطيّة كلما امتلأت.
                     القائمة تعرض عشرة، وما بعدها لا يُوصَل إليه إلا من هنا. --}}
                <a href="{{ route('admin.notifications.index') }}"
                   class="shrink-0 border-t border-hairline px-4 py-3 text-center text-caption
                          font-semibold text-accent-deep transition hover:bg-accent/8">
                    عرض كل الإشعارات
                </a>
            </div>
        </div>

        {{--
            قائمة الحساب — <details> أصيل لا JavaScript:
            يفتح ويغلق ويستجيب للوحة المفاتيح بلا سطر واحد.

            🔁 التعليق القديم قال إن Alpine غير متوفّر — لم يعد صحيحاً (مثبَّت
            عبر npm)، لكن <details> يبقى الأصحّ هنا: سلوك أصيل بلا JS.

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
