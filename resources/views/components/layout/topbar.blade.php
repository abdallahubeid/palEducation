@props([
    'studentName'       => '',
    'studentAvatar'     => null,
    'subscriptionState' => 'active',   // active | expiring | expired
    'unreadCount'       => 0,
])

@php
    $badge = match ($subscriptionState) {
        'expiring' => ['variant' => 'warn', 'label' => __('student.subscription_warning_title')],
        'expired'  => ['variant' => 'error', 'label' => __('student.subscription_expired_title')],
        default    => ['variant' => 'accent', 'label' => __('student.stat_subscription_days')],
    };

    // معاينة سريعة — نفس أول 3 عناصر من student/notifications.blade.php،
    // مكرَّرة هنا عمداً بنفس نمط بيانات العرض الذاتية المعتمد بكل الصفحات.
    $previewNotifications = [
        ['icon' => 'play', 'tone' => 'accent', 'title' => 'محاضرة جديدة في الرياضيات', 'body' => 'أُضيفت محاضرة «التكامل غير المحدد» لمادتك.', 'time' => '10:20 ص', 'unread' => true, 'href' => route('student.lectures.show', 6)],
        ['icon' => 'clipboard', 'tone' => 'accent', 'title' => 'نتيجة كويز جاهزة', 'body' => 'أنهيت كويز «قواعد الاشتقاق» — 8 من 10.', 'time' => '09:05 ص', 'unread' => true, 'href' => route('student.lectures.quiz', 4)],
        ['icon' => 'clock', 'tone' => 'warn', 'title' => 'اشتراكك يوشك على الانتهاء', 'body' => 'باقي 5 أيام على انتهاء اشتراكك.', 'time' => 'أمس', 'unread' => false, 'href' => Route::has('student.subscription') ? route('student.subscription') : '#'],
    ];
@endphp

{{-- بلا border-b — فصلها عن المحتوى عبر ظل خفيف بدل خطّ صلب --}}
<header class="shadow-subtle sticky top-0 z-20 flex h-16 items-center gap-3 bg-canvas/90 px-4
               backdrop-blur lg:h-18 lg:px-8">

    <button type="button" data-sidebar-open
            class="grid size-10 shrink-0 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink lg:hidden"
            aria-label="{{ __('student.menu_open') }}">
        <x-icon name="menu" class="size-5" />
    </button>

    <label class="relative hidden max-w-sm flex-1 items-center sm:flex">
        <x-icon name="search" class="pointer-events-none absolute start-3 size-4 text-muted" />
        <input type="search"
               placeholder="{{ __('student.search_placeholder') }}"
               class="h-10 w-full rounded-full border border-hairline bg-surface-soft ps-9 pe-4 text-ui text-ink
                      placeholder:text-muted transition focus-visible:border-accent focus-visible:bg-canvas
                      focus-visible:outline-none">
    </label>

    <div class="ms-auto flex items-center gap-2">
        <x-ui.badge :variant="$badge['variant']" class="hidden sm:inline-flex">{{ $badge['label'] }}</x-ui.badge>

        {{-- قائمة الإشعارات المنسدلة — نمط TAQAT: معاينة سريعة + رؤية الكل --}}
        <div class="relative" data-notif-dropdown>
            <button type="button" data-notif-toggle
                    class="relative grid size-10 shrink-0 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink"
                    aria-label="{{ __('student.notifications') }}"
                    aria-expanded="false"
                    aria-haspopup="true">
                <x-icon name="bell" class="size-5" />
                @if ($unreadCount > 0)
                    <span data-notif-dot class="absolute end-2 top-2 size-2 rounded-full bg-error" aria-hidden="true"></span>
                @endif
            </button>

            {{--
                على الجوال: ورقة ثابتة بهوامش جانبية (لا ترتبط بموضع
                زر الجرس الصغير فتفيض خارج الشاشة). من sm فأعلى: قائمة
                منسدلة عادية مرتكزة على نهاية الزر.
            --}}
            <div data-notif-panel hidden
                 class="fixed inset-x-4 top-16 z-50 rounded-2xl bg-canvas p-2 shadow-xl
                        sm:absolute sm:inset-x-auto sm:top-full sm:end-0 sm:mt-2 sm:w-96">
                <div class="flex items-center justify-between px-3 py-2">
                    <p class="text-ui font-semibold text-ink">{{ __('student.notifications_title') }}</p>
                    <button type="button" data-notif-mark-all
                            @if ($unreadCount === 0) hidden @endif
                            class="text-caption font-semibold text-accent-deep transition hover:underline">
                        {{ __('student.notifications_mark_all_read') }}
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
                            :href="$item['href']" />
                    @endforeach
                </div>

                <a href="{{ Route::has('student.notifications') ? route('student.notifications') : '#' }}"
                   class="mt-1 flex items-center justify-center rounded-lg py-2.5 text-ui font-semibold text-accent-deep
                          transition hover:bg-accent-soft">
                    {{ __('student.notifications_view_all') }}
                </a>
            </div>
        </div>

        <a href="{{ Route::has('student.profile') ? route('student.profile') : '#' }}"
           class="flex shrink-0 items-center gap-2 rounded-full py-1 ps-1 pe-3 transition hover:bg-surface">
            <x-ui.avatar :src="$studentAvatar" :name="$studentName" size="sm" />
            <span class="hidden text-ui font-medium text-ink lg:inline">{{ $studentName }}</span>
        </a>
    </div>
</header>
