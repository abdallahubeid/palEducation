@php
    // إشعارات المعلم — محورها نشاط الطلاب على محتواه، لا اشتراكه.
    // الروابط تشير لشاشات مبنية فعلاً كي يعمل الانتقال لمصدر الإشعار بصدق.
    $today = [
        ['icon' => 'users', 'tone' => 'accent', 'title' => 'انضمام طالب جديد', 'body' => 'انضم طالب جديد لدرس الرياضيات.', 'time' => '11:05 ص', 'unread' => true, 'href' => route('teacher.performance')],
        ['icon' => 'clipboard', 'tone' => 'accent', 'title' => 'تسليم كويز', 'body' => 'أتم الطالب إجابة كويز «قواعد الاشتقاق».', 'time' => '10:20 ص', 'unread' => true, 'href' => route('teacher.performance')],
        ['icon' => 'compass', 'tone' => 'tag', 'title' => 'استفسار جديد', 'body' => 'تم إضافة سؤال جديد في مناقشة المادة.', 'time' => '09:40 ص', 'unread' => true, 'href' => route('teacher.subjects')],
    ];

    $yesterday = [
        ['icon' => 'alert', 'tone' => 'warn', 'title' => '6 طلاب تحت 50% في «المتتاليات»', 'body' => 'قد تحتاج المحاضرة إعادة شرح أو كويزاً أسهل.', 'time' => '6:20 م', 'unread' => true, 'href' => route('teacher.performance')],
        ['icon' => 'play', 'tone' => 'tag', 'title' => '14 طالباً شاهدوا «التكامل غير المحدد»', 'body' => 'أعلى نسبة مشاهدة يومية لهذه المحاضرة حتى الآن.', 'time' => '2:10 م', 'unread' => false, 'href' => route('teacher.lectures')],
    ];

    $earlier = [
        ['icon' => 'alert', 'tone' => 'warn', 'title' => 'محاضرة بلا كويز', 'body' => '«الشغل والطاقة» محفوظة كمسودّة بلا كويز — لا يمكن نشرها هكذا.', 'time' => '2026-08-12', 'unread' => false, 'href' => route('teacher.lectures')],
        ['icon' => 'users', 'tone' => 'tag', 'title' => 'أُسندت لك مادة جديدة', 'body' => 'رياضيات تجارية — الفرع التجاري.', 'time' => '2026-08-08', 'unread' => false, 'href' => route('teacher.subjects')],
    ];

    $allGroups = [
        ['label' => __('teacher.notifications_today'), 'items' => $today],
        ['label' => __('teacher.notifications_yesterday'), 'items' => $yesterday],
        ['label' => __('teacher.notifications_earlier'), 'items' => $earlier],
    ];

    $hasAny = collect($allGroups)->sum(fn ($g) => count($g['items'])) > 0;
    $unreadCount = collect($allGroups)->flatMap(fn ($g) => $g['items'])->where('unread', true)->count();
@endphp

<x-layouts.teacher :title="__('teacher.notifications_title')" :unread-count="$unreadCount">

    {{-- نفس بنية صفحة إشعارات الطالب (عمود ضيّق · مجموعات زمنية ·
         notification-item · تعليم الكل كمقروء) مضافاً إليها تبويبا
         الفلترة المطلوبان: الكل / غير المقروءة. --}}
    <div class="mx-auto flex max-w-2xl flex-col gap-6" data-notifications data-notif-filter>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-h2 font-bold text-ink">{{ __('teacher.notifications_title') }}</h1>

            <button type="button" data-mark-all-read
                    @if ($unreadCount === 0) hidden @endif
                    class="inline-flex min-h-11 items-center rounded-md px-2 text-caption font-semibold text-accent-deep transition hover:underline lg:min-h-0 lg:px-0">
                {{ __('teacher.notifications_mark_all_read') }}
            </button>
        </div>

        {{-- تبويبا الفلترة --}}
        <div role="tablist" class="inline-flex w-fit items-center gap-1 rounded-lg bg-surface p-1">
            <button type="button" role="tab" data-notif-tab="all" aria-selected="true"
                    class="tab-trigger is-active cursor-pointer rounded-md px-4 py-2.5 text-ui font-semibold text-stone transition hover:text-ink lg:py-2">
                {{ __('teacher.notifications_filter_all') }}
            </button>

            <button type="button" role="tab" data-notif-tab="unread" aria-selected="false"
                    class="tab-trigger cursor-pointer rounded-md px-4 py-2.5 text-ui font-semibold text-stone transition hover:text-ink lg:py-2">
                {{ __('teacher.notifications_filter_unread') }}
                <span data-unread-count
                      class="num ms-1 inline-flex min-w-5 justify-center rounded-full bg-accent/14 px-1.5 text-micro text-accent-deep">
                    {{ $unreadCount }}
                </span>
            </button>
        </div>

        @if ($hasAny)
            @foreach ($allGroups as $group)
                @if (count($group['items']))
                    <div data-notif-group>
                        <p class="px-1 text-caption font-semibold text-stone">{{ $group['label'] }}</p>

                        <div class="tile mt-3 flex flex-col divide-y divide-hairline-soft p-2">
                            @foreach ($group['items'] as $item)
                                <div data-notif-row data-unread="{{ $item['unread'] ? '1' : '0' }}">
                                    <x-domain.notification-item
                                        :icon="$item['icon']"
                                        :tone="$item['tone']"
                                        :title="$item['title']"
                                        :body="$item['body']"
                                        :time="$item['time']"
                                        :unread="$item['unread']"
                                        :href="$item['href']" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- تظهر حين يُفلتَر على «غير المقروءة» ولا يوجد شيء --}}
            <div data-notif-none-unread hidden>
                <x-ui.empty-state icon="check-circle"
                                  :title="__('teacher.notifications_none_unread_title')"
                                  :body="__('teacher.notifications_none_unread_body')" />
            </div>
        @else
            <x-ui.empty-state icon="bell"
                              :title="__('teacher.notifications_empty_title')"
                              :body="__('teacher.notifications_empty_body')" />
        @endif
    </div>

</x-layouts.teacher>
