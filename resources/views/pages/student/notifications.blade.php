@php
    // بيانات عرض — تُستبدل بجدول إشعارات حقيقي لاحقاً. الروابط تشير
    // لشاشات مبنية فعلاً كي يعمل "الانتقال المباشر لمصدره" بصدق.
    $today = [
        ['icon' => 'play', 'tone' => 'accent', 'title' => 'محاضرة جديدة في الرياضيات', 'body' => 'أُضيفت محاضرة «التكامل غير المحدد» لمادتك.', 'time' => '10:20 ص', 'unread' => true, 'href' => route('student.lectures.show', 6)],
        ['icon' => 'clipboard', 'tone' => 'accent', 'title' => 'نتيجة كويز جاهزة', 'body' => 'أنهيت كويز «قواعد الاشتقاق» — 8 من 10.', 'time' => '09:05 ص', 'unread' => true, 'href' => route('student.lectures.quiz', 4)],
    ];

    $yesterday = [
        ['icon' => 'clock', 'tone' => 'warn', 'title' => 'اشتراكك يوشك على الانتهاء', 'body' => 'باقي 5 أيام على انتهاء اشتراكك — جدّده لتستمر بلا انقطاع.', 'time' => '4:40 م', 'unread' => false, 'href' => route('student.subscription')],
    ];

    $earlier = [
        ['icon' => 'check-circle', 'tone' => 'accent', 'title' => 'محاضرة جديدة في الفيزياء', 'body' => 'أُضيفت محاضرة جديدة لمادة الفيزياء.', 'time' => '2026-08-12', 'unread' => false, 'href' => route('student.subjects.show', 'physics')],
        ['icon' => 'compass', 'tone' => 'tag', 'title' => 'خبر من المنصة', 'body' => 'مواعيد الامتحانات التجريبية للفصل الثاني أُعلنت.', 'time' => '2026-08-10', 'unread' => false, 'href' => '#'],
    ];

    $allGroups = [
        ['label' => __('student.notifications_today'), 'items' => $today],
        ['label' => __('student.notifications_yesterday'), 'items' => $yesterday],
        ['label' => __('student.notifications_earlier'), 'items' => $earlier],
    ];

    $hasAny = collect($allGroups)->sum(fn ($g) => count($g['items'])) > 0;
@endphp

<x-layouts.student
    :title="__('student.notifications_title')"
    :student-name="'محمد أبو عودة'"
    :subscription-state="'active'"
    :unread-count="2">

    <div class="mx-auto flex max-w-2xl flex-col gap-6" data-notifications>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-h2 font-bold text-ink">{{ __('student.notifications_title') }}</h1>
            <button type="button" data-mark-all-read
                    class="text-caption font-semibold text-accent-deep transition hover:underline">
                {{ __('student.notifications_mark_all_read') }}
            </button>
        </div>

        @if ($hasAny)
            @foreach ($allGroups as $group)
                @if (count($group['items']))
                    <div>
                        <p class="px-1 text-caption font-semibold text-stone">{{ $group['label'] }}</p>
                        <div class="tile mt-3 flex flex-col divide-y divide-hairline-soft p-2">
                            @foreach ($group['items'] as $item)
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
                    </div>
                @endif
            @endforeach
        @else
            <x-ui.empty-state icon="bell" :title="__('student.notifications_empty_title')" :body="__('student.notifications_empty_body')" />
        @endif
    </div>

</x-layouts.student>
