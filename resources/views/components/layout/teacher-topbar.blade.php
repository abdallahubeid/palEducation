@props([
    'teacherName'   => '',
    'teacherAvatar' => null,
    'subjectLabel'  => null,
    'unreadCount'   => 0,
])

@php
    // معاينة سريعة — أول 3 عناصر من pages/teacher/notifications.blade.php،
    // مكرَّرة هنا بنفس نمط بيانات العرض الذاتية المعتمد في كل الصفحات.
    $previewNotifications = [
        ['icon' => 'users', 'tone' => 'accent', 'title' => 'انضمام طالب جديد', 'body' => 'انضم طالب جديد لدرس الرياضيات.', 'time' => '11:05 ص', 'unread' => true],
        ['icon' => 'clipboard', 'tone' => 'accent', 'title' => 'تسليم كويز', 'body' => 'أتم الطالب إجابة كويز «قواعد الاشتقاق».', 'time' => '10:20 ص', 'unread' => true],
        ['icon' => 'compass', 'tone' => 'tag', 'title' => 'استفسار جديد', 'body' => 'تم إضافة سؤال جديد في مناقشة المادة.', 'time' => '09:40 ص', 'unread' => true],
    ];

    $notificationsHref = Route::has('teacher.notifications') ? route('teacher.notifications') : '#';
@endphp

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

        {{-- قائمة الإشعارات المنسدلة — نفس بنية شريط الطالب حرفياً،
             فتعمل مع initNotificationDropdown الموجودة بلا سطر JS إضافي --}}
        <div class="relative" data-notif-dropdown>
            <button type="button" data-notif-toggle
                    class="relative grid size-11 shrink-0 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink lg:size-10"
                    aria-label="{{ __('teacher.notifications_title') }}"
                    aria-expanded="false"
                    aria-haspopup="true">
                <x-icon name="bell" class="size-5" />
                @if ($unreadCount > 0)
                    <span data-notif-dot class="absolute end-2 top-2 size-2 rounded-full bg-error" aria-hidden="true"></span>
                @endif
            </button>

            {{-- على الجوال: ورقة ثابتة بهوامش جانبية (زر الجرس صغير ولا يصلح
                 مرتكزاً). من sm فأعلى: منسدلة مرتكزة على نهاية الزر. --}}
            <div data-notif-panel hidden
                 class="fixed inset-x-4 top-16 z-50 rounded-2xl bg-canvas p-2 shadow-xl
                        sm:absolute sm:inset-x-auto sm:top-full sm:end-0 sm:mt-2 sm:w-96">

                <div class="flex items-center justify-between px-3 py-2">
                    <p class="text-ui font-semibold text-ink">{{ __('teacher.notifications_title') }}</p>
                    <button type="button" data-notif-mark-all
                            @if ($unreadCount === 0) hidden @endif
                            class="inline-flex min-h-11 items-center rounded-md px-2 text-caption font-semibold text-accent-deep transition hover:underline lg:min-h-0 lg:px-0">
                        {{ __('teacher.notifications_mark_all_read') }}
                    </button>
                </div>

                <div class="flex flex-col gap-1">
                    @foreach ($previewNotifications as $item)
                        <x-domain.notification-item
                            :icon="$item['icon']"
                            :tone="$item['tone']"
                            :title="$item['title']"
                            :body="$item['body']"
                            :time="$item['time']"
                            :unread="$item['unread']"
                            :href="$notificationsHref" />
                    @endforeach
                </div>

                <a href="{{ $notificationsHref }}"
                   class="mt-1 flex min-h-11 items-center justify-center rounded-lg py-2.5 text-ui font-semibold text-accent-deep
                          transition hover:bg-accent-soft">
                    {{ __('student.notifications_view_all') }}
                </a>
            </div>
        </div>

        <a href="{{ Route::has('teacher.profile') ? route('teacher.profile') : '#' }}"
           class="flex min-h-11 shrink-0 items-center gap-2 rounded-full py-1 ps-1 pe-3 transition hover:bg-surface lg:min-h-0">
            <x-ui.avatar :src="$teacherAvatar" :name="$teacherName" size="sm" />
            <span class="hidden text-ui font-medium text-ink lg:inline">{{ $teacherName }}</span>
        </a>
    </div>
</header>
