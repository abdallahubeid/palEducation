@php
    /*
     * 🔴 نغمات النوع مكتوبة حرفياً كاملة — فاحص Tailwind لا ينفّذ PHP، فأي
     * "bg-{$type}/12" لا يولّد CSS إطلاقاً. نفس خريطة نغمات
     * domain/notification-item، فالشاشتان تُقرآن كنظام واحد.
     *
     * ⚠️ لم يُعَد استعمال x-domain.notification-item هنا: ذاك المكوّن يُخرج
     * <a> كاملاً، وهذه الصفحة تحتاج أزرار إجراءات داخل كل صفّ — والزرّ داخل
     * الرابط تركيب غير صالح يُسقطه المتصفّح بصمت.
     */
    $tones = [
        'message' => ['icon' => 'mail', 'chip' => 'bg-accent/14 text-accent-deep', 'label' => 'رسالة'],
        'testimonial' => ['icon' => 'sparkle', 'chip' => 'bg-tag/12 text-tag', 'label' => 'رأي'],
        'success' => ['icon' => 'check-circle', 'chip' => 'bg-accent/14 text-accent-deep', 'label' => 'حملة'],
        'account' => ['icon' => 'user', 'chip' => 'bg-tag/12 text-tag', 'label' => 'حساب'],
        'settings' => ['icon' => 'settings', 'chip' => 'bg-surface text-stone', 'label' => 'إعدادات'],
        'warning' => ['icon' => 'trash', 'chip' => 'bg-warn/14 text-warn-deep', 'label' => 'تنبيه'],
        'error' => ['icon' => 'alert', 'chip' => 'bg-error/12 text-error-deep', 'label' => 'خطأ'],
        'info' => ['icon' => 'bell', 'chip' => 'bg-surface text-stone', 'label' => 'إشعار'],
    ];

    $tabs = ['all' => 'الكل', 'unread' => 'غير مقروءة', 'read' => 'مقروءة'];
@endphp

<x-layouts.admin title="الإشعارات" breadcrumbs="الحساب / الإشعارات">

    <div x-data="notificationsPage({
             toggle: '{{ route('admin.notifications.toggle', ['id' => '__ID__']) }}',
             read: '{{ route('admin.notifications.read', ['id' => '__ID__']) }}',
             destroy: '{{ route('admin.notifications.destroy', ['id' => '__ID__']) }}',
             readAll: '{{ route('admin.notifications.read-all') }}',
             clearRead: '{{ route('admin.notifications.clear-read') }}',
         }, {{ $counts['unread'] }}, {{ $counts['read'] }})">

        <x-admin.page-head
            title="الإشعارات والتنبيهات"
            lede="سجلّ ما حدث في المنصّة، وما لم تقرأه بعد.">
            <x-slot:actions>
                {{-- ⚠️ يظهران فقط حين يكون لهما أثر: زرّ «تحديد الكل» على صفر
                     غير مقروء، أو «حذف المقروءة» على لا شيء مقروء، ضوابط لا
                     تفعل شيئاً وتُقرأ معطّلة. --}}
                <span x-show="unread > 0" x-cloak>
                    <x-admin.btn type="button" variant="secondary" @click="markAll()" ::disabled="busy">
                        تحديد الكل كمقروء
                    </x-admin.btn>
                </span>

                <span x-show="read > 0" x-cloak>
                    <x-admin.btn type="button" variant="danger" @click="clearRead()" ::disabled="busy">
                        حذف الإشعارات المقروءة
                    </x-admin.btn>
                </span>
            </x-slot:actions>
        </x-admin.page-head>

        {{-- ═══ إحصاءات سريعة ═══ --}}
        <div class="mt-6 grid grid-cols-3 gap-3 sm:max-w-lg">
            <div class="rounded-xl border border-hairline bg-canvas px-4 py-3">
                <p class="text-micro text-stone">الإجمالي</p>
                <p class="mt-1 text-h5 font-bold tabular-nums text-ink" x-text="total">{{ $counts['all'] }}</p>
            </div>

            <div class="rounded-xl border border-hairline bg-canvas px-4 py-3">
                <p class="text-micro text-stone">غير مقروءة</p>
                <p class="mt-1 text-h5 font-bold tabular-nums text-accent-deep" x-text="unread">{{ $counts['unread'] }}</p>
            </div>

            <div class="rounded-xl border border-hairline bg-canvas px-4 py-3">
                <p class="text-micro text-stone">مقروءة</p>
                <p class="mt-1 text-h5 font-bold tabular-nums text-steel" x-text="read">{{ $counts['read'] }}</p>
            </div>
        </div>

        {{-- ═══ تبويبات التصفية ═══ --}}
        <div class="mt-6 flex flex-wrap gap-2">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.notifications.index', $key === 'all' ? [] : ['filter' => $key]) }}"
                   @class([
                       'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-caption font-semibold transition',
                       'bg-accent text-on-primary' => $activeFilter === $key,
                       'bg-surface text-steel hover:bg-hairline' => $activeFilter !== $key,
                   ])>
                    {{ $label }}
                    <span class="tabular-nums">({{ $counts[$key] }})</span>
                </a>
            @endforeach
        </div>

        @if ($notifications->isEmpty())
            <div class="mt-6 rounded-xl border border-hairline bg-canvas px-6 py-16 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-surface text-stone">
                    <x-icon name="bell" class="size-5" />
                </span>
                {{-- الحالة الفارغة تسمّي التبويب لا الجدول. --}}
                @if ($activeFilter === 'unread')
                    <p class="mt-4 text-ui font-semibold text-ink">لا إشعارات غير مقروءة</p>
                    <p class="mt-1 text-caption text-stone">قرأت كل شيء.</p>
                @elseif ($activeFilter === 'read')
                    <p class="mt-4 text-ui font-semibold text-ink">لا إشعارات مقروءة</p>
                @else
                    <p class="mt-4 text-ui font-semibold text-ink">لا إشعارات بعد</p>
                    <p class="mt-1 text-caption text-stone">سيظهر هنا كل ما يحدث في المنصّة.</p>
                @endif
            </div>
        @else
            <ul class="mt-6 divide-y divide-hairline overflow-hidden rounded-xl border border-hairline bg-canvas">
                @foreach ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $tone = $tones[$data['type'] ?? 'info'] ?? $tones['info'];
                        $isUnread = $notification->read_at === null;
                    @endphp

                    <li x-data="{ read: {{ $isUnread ? 'false' : 'true' }}, gone: false }"
                        x-show="! gone"
                        :class="! read && 'bg-accent/5'"
                        @class(['flex items-start gap-3.5 p-4 transition', 'bg-accent/5' => $isUnread])>

                        <span class="grid size-10 shrink-0 place-items-center rounded-full {{ $tone['chip'] }}">
                            <x-icon :name="$tone['icon']" class="size-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-ui text-ink" :class="read ? 'font-medium' : 'font-semibold'">
                                    {{ $data['title'] ?? '' }}
                                </p>

                                <span class="rounded-md bg-surface px-1.5 py-0.5 text-micro text-stone">
                                    {{ $tone['label'] }}
                                </span>

                                <span x-show="! read" x-cloak
                                      class="size-1.5 shrink-0 rounded-full bg-accent"></span>
                            </div>

                            <p class="mt-1 text-caption leading-relaxed text-steel">{{ $data['message'] ?? '' }}</p>

                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                {{-- الوقت النسبي مُنسَّق على الخادم — العربية لها
                                     ستّ صيغ جمع لا يملكها المتصفّح. --}}
                                <span class="text-micro text-stone">{{ $notification->created_at->diffForHumans() }}</span>

                                @if (filled($data['url'] ?? null))
                                    {{-- ⚠️ الانتقال يُعلّم الإشعار مقروءاً أولاً:
                                         رابط أصيل كان سينتقل قبل وصول الكتابة. --}}
                                    <a href="{{ $data['url'] }}"
                                       @click.prevent="visit('{{ $notification->id }}', '{{ $data['url'] }}')"
                                       class="text-micro font-semibold text-accent-deep transition hover:underline">
                                        فتح العنصر
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <button type="button"
                                    @click="toggle('{{ $notification->id }}').then(n => { if (n) read = n.read })"
                                    :disabled="busy"
                                    class="cursor-pointer rounded-lg p-1.5 text-stone transition
                                           hover:bg-surface hover:text-ink disabled:opacity-50"
                                    :title="read ? 'تحديد كغير مقروء' : 'تحديد كمقروء'">
                                <span x-show="! read"><x-icon name="check" class="size-4" /></span>
                                <span x-show="read" x-cloak><x-icon name="eye-off" class="size-4" /></span>
                            </button>

                            <button type="button"
                                    @click="remove('{{ $notification->id }}').then(ok => { if (ok) gone = true })"
                                    :disabled="busy"
                                    class="cursor-pointer rounded-lg p-1.5 text-stone transition
                                           hover:bg-error/8 hover:text-error-deep disabled:opacity-50"
                                    title="حذف">
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4">{{ $notifications->links() }}</div>
        @endif
    </div>

</x-layouts.admin>
