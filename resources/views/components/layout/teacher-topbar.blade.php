@props([
    'teacherName'   => '',
    'teacherAvatar' => null,
    'subjectLabel'  => null,
    'unreadCount'   => 0,
])

{{-- أقصر من شريط الطالب (h-15 مقابل h-18) — سطح أداتي لا سطح قراءة --}}
<header class="shadow-subtle sticky top-0 z-20 flex h-16 items-center gap-3 bg-canvas/90 px-4
               backdrop-blur lg:h-[3.75rem] lg:px-6">

    <button type="button" data-sidebar-open
            class="grid size-11 shrink-0 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink lg:hidden"
            aria-label="{{ __('student.menu_open') }}">
        <x-icon name="menu" class="size-5" />
    </button>

    <label class="relative hidden max-w-sm flex-1 items-center sm:flex">
        <x-icon name="search" class="pointer-events-none absolute start-3 size-4 text-muted" />
        <input type="search"
               placeholder="{{ __('teacher.search_placeholder') }}"
               class="h-10 w-full rounded-full border border-hairline bg-surface-soft ps-9 pe-4 text-ui text-ink
                      placeholder:text-muted transition focus-visible:border-accent focus-visible:bg-canvas
                      focus-visible:outline-none">
    </label>

    <div class="ms-auto flex items-center gap-2">
        @if ($subjectLabel)
            <x-ui.badge variant="branch" class="hidden sm:inline-flex">{{ $subjectLabel }}</x-ui.badge>
        @endif

        {{--
            ═══ جرس الإشعارات ═══

            🔴 يستطلع كل ١٥ ثانية ويتوقّف حين يُخفى التبويب — نفس مكوّن
            bellFeed المستعمل في شريط الأدمن. المسارات تُمرَّر من Blade لا
            تُبنى في JS.

            ⚠️ بلا JS تبقى الشارة الابتدائية مرسومة من الخادم ورابط «عرض
            الكل» يعمل — الجرس ترقية لا شرط.
        --}}
        <div x-data="bellFeed({
                 index: '{{ route('teacher.notifications.feed') }}',
                 read: '{{ route('teacher.notifications.read', ['id' => '__ID__']) }}',
                 readAll: '{{ route('teacher.notifications.read-all') }}',
             })"
             @click.outside="open = false"
             @keydown.escape.window="open = false"
             class="relative">

            <button type="button" @click="open = ! open"
                    :aria-expanded="open.toString()"
                    aria-haspopup="true"
                    class="relative grid size-11 shrink-0 place-items-center rounded-full text-steel transition
                           hover:bg-surface hover:text-ink lg:size-10"
                    aria-label="{{ __('teacher.notifications_title') }}">
                <x-icon name="bell" class="size-5" />

                {{-- الشارة الابتدائية من الخادم، ثم يتولّاها Alpine. تختفي عند الصفر. --}}
                <span x-show="unread > 0"
                      @if ($unreadCount === 0) style="display:none" @endif
                      class="absolute end-1.5 top-1.5 grid min-w-4 place-items-center rounded-full
                             bg-error px-1 text-[10px] font-bold leading-4 text-on-dark">
                    <span x-text="unread > 9 ? '9+' : unread">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                </span>
            </button>

            {{-- على الجوال: ورقة ثابتة بهوامش جانبية. من sm فأعلى: منسدلة مرتكزة على نهاية الزر. --}}
            <div x-show="open" x-cloak
                 class="fixed inset-x-4 top-16 z-50 flex max-h-[70vh] flex-col overflow-hidden rounded-2xl
                        border border-hairline bg-canvas shadow-xl
                        sm:absolute sm:inset-x-auto sm:top-full sm:end-0 sm:mt-2 sm:w-96 sm:max-w-[85vw]">

                <div class="flex items-center justify-between gap-2 border-b border-hairline px-4 py-3">
                    <p class="text-ui font-semibold text-ink">{{ __('teacher.notifications_title') }}</p>

                    <div class="flex items-center gap-1">
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
                            {{ __('teacher.notifications_mark_all_read') }}
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <template x-if="loading">
                        <p class="px-4 py-10 text-center text-caption text-stone">جارٍ التحميل…</p>
                    </template>

                    <template x-if="! loading && items.length === 0">
                        <p class="px-4 py-10 text-center text-caption text-stone">{{ __('teacher.notifications_empty_title') }}</p>
                    </template>

                    <template x-for="item in items" :key="item.id">
                        <button type="button" @click="visit(item)"
                                class="flex w-full cursor-pointer items-start gap-3 border-b border-hairline
                                       px-4 py-3 text-start transition last:border-b-0 hover:bg-surface/70"
                                :class="! item.read && 'bg-accent/5'">

                            <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-lg bg-surface text-stone">
                                {{-- أسماء x-icon لا تُبنى ديناميكياً من JS: تُرسَم كلها ويُظهَر المطابق. --}}
                                <span x-show="item.icon === 'book'"><x-icon name="book" class="size-3.5" /></span>
                                <span x-show="item.icon === 'folder'"><x-icon name="folder" class="size-3.5" /></span>
                                <span x-show="item.icon === 'sparkle'"><x-icon name="sparkle" class="size-3.5" /></span>
                                <span x-show="item.icon === 'alert'"><x-icon name="alert" class="size-3.5" /></span>
                                <span x-show="item.icon === 'user'"><x-icon name="user" class="size-3.5" /></span>
                                <span x-show="! ['book','folder','sparkle','alert','user'].includes(item.icon)">
                                    <x-icon name="bell" class="size-3.5" />
                                </span>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-caption font-semibold text-ink" x-text="item.title"></span>
                                <span class="mt-0.5 block text-micro leading-relaxed text-stone" x-text="item.message"></span>
                                <span class="mt-1 block text-micro text-muted" x-text="item.time"></span>
                            </span>

                            <span x-show="! item.read" class="mt-1.5 size-1.5 shrink-0 rounded-full bg-accent"></span>
                        </button>
                    </template>
                </div>

                <a href="{{ route('teacher.notifications') }}"
                   class="shrink-0 border-t border-hairline px-4 py-3 text-center text-caption
                          font-semibold text-accent-deep transition hover:bg-accent/8">
                    {{ __('student.notifications_view_all') }}
                </a>
            </div>
        </div>

        {{--
            ═══ قائمة الحساب ═══

            🔴 البيانات من الحساب المسجَّل حين يوجد، ومن الخصائص المُمرَّرة
            حين لا يوجد. بقية صفحات بوابة المعلّم ما زالت معاينات ثابتة
            تُعرض بلا مصادقة، فالقراءة المباشرة من auth() كانت ستكسرها
            جميعاً بـnull.
        --}}
        @php
            $currentUser = auth()->user();
            $isTeacherSession = $currentUser?->isTeacher() ?? false;

            $displayName = $isTeacherSession ? $currentUser->displayName() : $teacherName;
            $displayAvatar = $isTeacherSession ? $currentUser->avatar_url : $teacherAvatar;
        @endphp

        <div x-data="{ open: false }"
             @click.outside="open = false"
             @keydown.escape.window="open = false"
             class="relative shrink-0">

            <button type="button" @click="open = ! open"
                    :aria-expanded="open.toString()"
                    aria-haspopup="true"
                    class="flex min-h-11 cursor-pointer items-center gap-2 rounded-full py-1 ps-1 pe-3
                           transition hover:bg-surface lg:min-h-0">
                <x-ui.avatar :src="$displayAvatar" :name="$displayName" size="sm" />
                <span class="hidden text-ui font-medium text-ink lg:inline">{{ $displayName }}</span>
                {{-- سهم رأسي: يدور ولا ينعكس، فلا يحتاج معالجة اتجاه. --}}
                <x-icon name="chevron-down" class="hidden size-4 shrink-0 text-stone transition lg:block"
                        ::class="open && 'rotate-180'" />
            </button>

            <div x-show="open" x-cloak
                 class="absolute end-0 z-50 mt-2 w-52 overflow-hidden rounded-xl border border-hairline
                        bg-canvas py-1 shadow-card">

                {{-- 🔴 الاسم والصورة فقط — بلا بريد إلكتروني. البريد معرّف
                     دخول، وعرضه على شاشة قد تُشارَك أو تُصوَّر يكشفه بلا
                     داعٍ؛ مكانه صفحة الملف الشخصي. نفس قاعدة شريط الأدمن. --}}
                <div class="flex items-center gap-2.5 border-b border-hairline px-4 py-3">
                    <x-ui.avatar :src="$displayAvatar" :name="$displayName" size="sm" />
                    <p class="min-w-0 truncate text-caption font-semibold text-ink">{{ $displayName }}</p>
                </div>

                <a href="{{ route('teacher.profile.edit') }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-ui text-steel transition
                          hover:bg-surface hover:text-ink">
                    <x-icon name="user" class="size-4" />
                    الملف الشخصي
                </a>

                <form method="POST" action="{{ route('auth.logout') }}" class="border-t border-hairline">
                    @csrf
                    <button type="submit"
                            class="flex w-full cursor-pointer items-center gap-2.5 px-4 py-2.5 text-start
                                   text-ui text-error-deep transition hover:bg-error/8">
                        {{-- أيقونة اتجاهية: تنعكس مع اتجاه الكتابة. --}}
                        <x-icon name="logout" class="size-4 rtl:-scale-x-100" />
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
