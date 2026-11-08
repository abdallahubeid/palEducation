@php
    /*
     * لوحة التحكّم — أربعة أقسام بترتيب مقصود:
     *   ١) ما يحتاج إجراءً اليوم (يظهر أولاً، ويختفي كاملاً حين لا شيء ينتظر)
     *   ٢) المؤشّرات
     *   ٣) إجراءات سريعة
     *   ٤) آخر المحتوى وحالة النظام
     *
     * 🔴 «ما ينتظر» فوق المؤشّرات عمداً: الأرقام تُقرأ، والطوابير تُعالَج.
     * لوحة تفتح على أرقام جميلة وتخفي أربع رسائل غير مقروءة أسفل الصفحة
     * تخدم من يصمّمها لا من يستعملها.
     */
    $quickActions = [
        ['label' => 'خبر جديد', 'route' => 'admin.news.create', 'permission' => 'create-news', 'icon' => 'plus'],
        ['label' => 'إرسال نشرة', 'route' => 'admin.newsletter.send', 'permission' => 'send-newsletter', 'icon' => 'mail'],
        ['label' => 'شريحة جديدة', 'route' => 'admin.sliders.create', 'permission' => 'create-sliders', 'icon' => 'sparkle'],
        ['label' => 'فرع جديد', 'route' => 'admin.branches.create', 'permission' => 'create-branches', 'icon' => 'book'],
        ['label' => 'الإعدادات', 'route' => 'admin.settings.index', 'permission' => 'manage-settings', 'icon' => 'settings'],
        ['label' => 'سلّة المحذوفات', 'route' => 'admin.trash.index', 'permission' => null, 'icon' => 'trash'],
    ];

    // ⚠️ الحجم يُنسَّق هنا لا في الكنترولر: التنسيق شأن عرض، والكنترولر
    // يُرجع بايتات خاماً تصلح للاختبار والمقارنة.
    $formatBytes = function (int $bytes): string {
        if ($bytes < 1024) return $bytes.' بايت';
        if ($bytes < 1048576) return round($bytes / 1024, 1).' كيلوبايت';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1).' ميغابايت';

        return round($bytes / 1073741824, 2).' غيغابايت';
    };
@endphp

<x-layouts.admin title="لوحة التحكّم" breadcrumbs="نظرة عامة">

    <x-admin.page-head
        :title="'مرحباً، ' . auth()->user()->name"
        lede="نظرة سريعة على محتوى المنصّة وما يحتاج إجراءً اليوم.">
        <x-slot:actions>
            @can('create-news')
                <x-admin.btn :href="route('admin.news.create')" variant="primary">
                    <x-icon name="plus" class="size-4" />
                    خبر جديد
                </x-admin.btn>
            @endcan
        </x-slot:actions>
    </x-admin.page-head>

    {{-- ═══ ١ · ما ينتظر إجراءً ═══ --}}
    @php
        // يُرشَّح بالصلاحية هنا: الكنترولر يعدّ، والعرض يقرّر ما يراه هذا
        // المستخدم. صفّ يقود إلى شاشة محظورة أسوأ من غيابه.
        $visiblePending = collect($pending)->filter(fn ($item) => auth()->user()?->can($item['permission']));
    @endphp

    @if ($visiblePending->isNotEmpty())
        <div class="mt-6 rounded-xl border border-warn/30 bg-warn/8 p-4 sm:p-5">
            <div class="flex items-center gap-2.5">
                <x-icon name="bell" class="size-4 shrink-0 text-warn-deep" />
                <h2 class="text-ui font-bold text-ink">بانتظار إجراء</h2>
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($visiblePending as $item)
                    {{-- كل صفّ رابط مباشر إلى الشاشة التي تُفرغه: طابور لا
                         يُوصَل إليه بنقرة واحدة لا يُفرغه أحد. --}}
                    <a href="{{ $item['href'] }}"
                       class="flex items-center justify-between gap-3 rounded-lg border border-hairline
                              bg-canvas px-3.5 py-2.5 transition hover:border-warn/40 hover:shadow-subtle">
                        <span class="min-w-0 truncate text-caption text-steel">{{ $item['label'] }}</span>
                        <span class="shrink-0 rounded-md bg-warn/15 px-2 py-0.5 text-micro font-bold
                                     tabular-nums text-warn-deep">{{ $item['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ═══ ٢ · المؤشّرات ═══ --}}
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
        <x-admin.stat-card label="الطلبة" :value="$kpis['students']['value']"
                           :trend="$kpis['students']['trend']"
                           :hint="$kpis['students']['recent'] . ' جديد خلال ٣٠ يوماً'"
                           icon="users" :href="route('admin.users.index', ['type' => 'student'])" />

        <x-admin.stat-card label="المعلّمون" :value="$kpis['teachers']['value']"
                           :trend="$kpis['teachers']['trend']"
                           :hint="$kpis['teachers']['recent'] . ' جديد خلال ٣٠ يوماً'"
                           icon="user" :href="route('admin.users.index', ['type' => 'teacher'])" />

        <x-admin.stat-card label="المواد المنشورة" :value="$kpis['subjects']['value']"
                           :trend="$kpis['subjects']['trend']"
                           :hint="$kpis['subjects']['recent'] . ' جديدة خلال ٣٠ يوماً'"
                           icon="book" />

        <x-admin.stat-card label="الأخبار المنشورة" :value="$kpis['news']['value']"
                           :trend="$kpis['news']['trend']"
                           :hint="$kpis['news']['recent'] . ' جديد خلال ٣٠ يوماً'"
                           icon="clipboard" :href="route('admin.news.index')" />

        <x-admin.stat-card label="مشتركو النشرة" :value="$kpis['subscribers']['value']"
                           :trend="$kpis['subscribers']['trend']"
                           :hint="$kpis['subscribers']['recent'] . ' جديد خلال ٣٠ يوماً'"
                           icon="mail" :href="route('admin.newsletter.subscribers.index')" />
    </div>

    {{-- ═══ ٣ · إجراءات سريعة ═══ --}}
    <div class="mt-6 rounded-xl border border-hairline bg-canvas p-4 sm:p-5">
        <h2 class="text-ui font-bold text-ink">إجراءات سريعة</h2>

        <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ($quickActions as $action)
                {{-- ⚠️ الصلاحية null تعني «ظاهر للجميع» — سلّة المحذوفات
                     تحرسها صلاحية لكل نوع لا صلاحية واحدة، فلا مفتاح واحد
                     يصحّ فحصه هنا. المسار نفسه محروس على أي حال. --}}
                @if ($action['permission'] === null || auth()->user()?->can($action['permission']))
                    <a href="{{ route($action['route']) }}"
                       class="group flex flex-col items-center gap-2 rounded-lg border border-hairline
                              px-3 py-4 text-center transition hover:border-accent/40 hover:bg-accent/5">
                        <span class="grid size-9 place-items-center rounded-lg bg-surface text-stone
                                     transition group-hover:bg-accent/12 group-hover:text-accent-deep">
                            <x-icon :name="$action['icon']" class="size-4" />
                        </span>
                        <span class="text-caption font-medium text-steel group-hover:text-ink">{{ $action['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- ═══ ٤ · آخر الأخبار ═══ --}}
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-hairline bg-canvas">
                <div class="flex items-center justify-between gap-3 border-b border-hairline px-5 py-4">
                    <h3 class="text-ui font-bold text-ink">آخر الأخبار</h3>

                    @can('view-news')
                        <x-admin.btn :href="route('admin.news.index')" variant="secondary">عرض الكل</x-admin.btn>
                    @endcan
                </div>

                @if ($latestNews->isEmpty())
                    <p class="px-5 py-10 text-center text-caption text-stone">لم يُنشَر أي خبر بعد.</p>
                @else
                    <ul class="divide-y divide-hairline">
                        @foreach ($latestNews as $item)
                            <li class="flex items-center gap-3.5 px-5 py-3.5">
                                @if ($item->cover_url)
                                    <img src="{{ $item->cover_url }}" alt="" loading="lazy"
                                         class="h-11 w-16 shrink-0 rounded-lg object-cover">
                                @else
                                    <span class="grid h-11 w-16 shrink-0 place-items-center rounded-lg bg-surface text-stone">
                                        <x-icon name="clipboard" class="size-4" />
                                    </span>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-caption font-semibold text-ink">{{ $item->title }}</p>
                                    <p class="mt-0.5 truncate text-micro text-stone">
                                        {{ $item->authorName() }}
                                        ·
                                        <span class="tabular-nums" dir="ltr">{{ $item->published_at?->format('Y-m-d') ?? '—' }}</span>
                                    </p>
                                </div>

                                @if (! $item->is_published)
                                    <x-admin.status-badge status="draft" label="مسودّة" />
                                @elseif (! $item->isVisible())
                                    <x-admin.status-badge status="hidden" label="مجدول" />
                                @else
                                    <x-admin.status-badge status="active" label="منشور" />
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- ═══ آخر الطلبة ═══ --}}
            <div class="mt-6 rounded-xl border border-hairline bg-canvas">
                <div class="flex items-center justify-between gap-3 border-b border-hairline px-5 py-4">
                    <h3 class="text-ui font-bold text-ink">آخر الطلبة المسجّلين</h3>

                    @can('view-users')
                        <x-admin.btn :href="route('admin.users.index')" variant="secondary">عرض الكل</x-admin.btn>
                    @endcan
                </div>

                @if ($latestStudents->isEmpty())
                    <p class="px-5 py-10 text-center text-caption text-stone">لا طلبة مسجّلين بعد.</p>
                @else
                    <ul class="divide-y divide-hairline">
                        @foreach ($latestStudents as $student)
                            <li class="flex items-center gap-3 px-5 py-3">
                                <x-ui.avatar :src="$student->avatar_url" :name="$student->displayName()" size="sm" />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-caption font-semibold text-ink">{{ $student->displayName() }}</p>
                                    {{-- البريد لاتيني داخل نصّ عربي: يُعزَل أو ينقلب ترتيبه. --}}
                                    <p class="mt-0.5 truncate text-micro text-stone">
                                        <bdi dir="ltr">{{ $student->email }}</bdi>
                                    </p>
                                </div>

                                @if ($student->userable?->branch)
                                    <span class="shrink-0 rounded-md bg-surface px-2 py-1 text-micro text-stone">
                                        {{ $student->userable->branch->name }}
                                    </span>
                                @endif

                                <span class="shrink-0 text-micro tabular-nums text-stone" dir="ltr">
                                    {{ $student->created_at?->format('Y-m-d') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- ═══ العمود الجانبي ═══ --}}
        <aside class="space-y-6">

            {{-- التخزين --}}
            <div class="rounded-xl border border-hairline bg-canvas p-5">
                <h3 class="text-ui font-bold text-ink">التخزين والوسائط</h3>

                <dl class="mt-4 space-y-3">
                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-caption text-stone">المساحة المستخدَمة</dt>
                        <dd class="text-ui font-bold tabular-nums text-ink">{{ $formatBytes($storage['bytes']) }}</dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-caption text-stone">الملفات المرفوعة</dt>
                        <dd class="text-ui font-semibold tabular-nums text-ink">{{ $storage['files'] }}</dd>
                    </div>

                    <div class="flex items-baseline justify-between gap-3">
                        <dt class="text-caption text-stone">سجلّات الوسائط</dt>
                        <dd class="text-ui font-semibold tabular-nums text-ink">{{ $storage['images'] }}</dd>
                    </div>
                </dl>

                {{-- ⚠️ يُقال صراحةً: الرقم محسوب دورياً لا لحظياً، لأن حسابه
                     مسح لنظام الملفات لا استعلاماً. --}}
                <p class="mt-4 border-t border-hairline pt-3 text-micro text-stone">
                    يشمل الملفات المرفوعة من اللوحة فقط · يُحدَّث كل خمس دقائق.
                </p>
            </div>

            {{-- حالة المحتوى --}}
            <div class="rounded-xl border border-hairline bg-canvas p-5">
                <h3 class="text-ui font-bold text-ink">حالة المحتوى</h3>

                <ul class="mt-4 space-y-3">
                    @foreach ($health as $row)
                        <li class="flex items-center justify-between gap-3">
                            <span class="flex min-w-0 items-center gap-2">
                                <span @class([
                                    'size-1.5 shrink-0 rounded-full',
                                    'bg-accent' => $row['ok'],
                                    'bg-warn' => ! $row['ok'],
                                ])></span>
                                <span class="truncate text-caption text-steel">{{ $row['label'] }}</span>
                            </span>

                            <span class="shrink-0 text-caption font-semibold tabular-nums text-ink">{{ $row['value'] }}</span>
                        </li>
                    @endforeach
                </ul>

                @can('manage-settings')
                    <div class="mt-4 border-t border-hairline pt-4">
                        <x-admin.btn :href="route('admin.settings.index')" variant="secondary" class="w-full">
                            إدارة الإعدادات
                        </x-admin.btn>
                    </div>
                @endcan
            </div>
        </aside>
    </div>

</x-layouts.admin>
