@php
    /*
     * تنقّل لوحة الأدمن — مبني على أسلوب لوحة veyra:
     * مجموعات معنونة · عنصر بحدّ بادئ (border-s-2) يضيء عند النشاط ·
     * أيقونات SVG سطرية 24×24 بسماكة 1.5 · طيّ إلى شريط أيقونات.
     *
     * 🔴 كل عنصر يُفحص بـ@can — لكن الإخفاء **راحة عرض لا حراسة**: المسار
     * نفسه محروس بـcan: middleware، فالطلب المباشر يُرفض 403 سواء ظهر
     * الرابط أم لا.
     */
    $groups = [
        [
            'label' => 'نظرة عامة',
            'items' => [
                [
                    'label' => 'لوحة التحكّم',
                    'route' => 'admin.dashboard',
                    'pattern' => 'admin.dashboard',
                    'permission' => 'view-sliders',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />',
                ],
            ],
        ],
        [
            'label' => 'محتوى الصفحة الرئيسية',
            'items' => [
                [
                    'label' => 'الهيرو',
                    'route' => 'admin.sliders.index',
                    'pattern' => 'admin.sliders.index',
                    'permission' => 'view-sliders',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />',
                ],
                [
                    'label' => 'المميزات',
                    'route' => 'admin.features.index',
                    'pattern' => 'admin.features.*',
                    'permission' => 'view-features',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />',
                ],
                [
                    'label' => 'آراء الطلبة',
                    'route' => 'admin.testimonials.index',
                    'pattern' => 'admin.testimonials.*',
                    'permission' => 'view-testimonials',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />',
                ],
                [
                    'label' => 'الأسئلة الشائعة',
                    'route' => 'admin.faqs.index',
                    'pattern' => 'admin.faqs.*',
                    'permission' => 'view-faqs',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />',
                ],
                [
                    'label' => 'الأسعار',
                    'route' => 'admin.pricing.index',
                    'pattern' => 'admin.pricing.*',
                    'permission' => 'view-pricings',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />',
                ],
                [
                    // الإعدادات داخل «محتوى الصفحة الرئيسية»: كل ما تحمله اليوم
                    // (روابط الشريط العلوي) محتوى صفحة هبوط، فمكانه مع الهيرو
                    // لا في قسم نظام منفصل.
                    'label' => 'الإعدادات',
                    'route' => 'admin.settings.index',
                    'pattern' => 'admin.settings.*',
                    'permission' => 'manage-settings',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.174.1.347.223.52.337.294.191.66.237.983.094l1.22-.547a1.125 1.125 0 0 1 1.45.53l1.298 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.43.992a6.759 6.759 0 0 1 0 .255c-.008.378.137.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.45.533l-1.22-.547a1.125 1.125 0 0 0-.983.094 6.57 6.57 0 0 1-.52.337c-.332.183-.582.495-.645.87l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.52-.337 1.125 1.125 0 0 0-.983-.094l-1.22.547a1.125 1.125 0 0 1-1.45-.53l-1.298-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.298-2.247a1.125 1.125 0 0 1 1.45-.533l1.22.547c.323.144.69.098.983-.094.173-.114.346-.237.52-.337.332-.183.582-.495.644-.87l.214-1.281Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
                ],
            ],
        ],
        [
            // Branches are academic structure, not landing-page content:
            // the cards on the home page are one surface of the entity, not
            // the entity itself.
            'label' => 'المحتوى الأكاديمي',
            'items' => [
                [
                    'label' => 'المواد الدراسية',
                    'route' => 'admin.subjects.index',
                    'pattern' => 'admin.subjects.*',
                    'permission' => 'view-subjects',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />',
                ],
                [
                    'label' => 'الفروع',
                    'route' => 'admin.branches.index',
                    'pattern' => 'admin.branches.*',
                    'permission' => 'view-branches',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />',
                ],
            ],
        ],
        [
            'label' => 'النظام',
            'items' => [
                [
                    'label' => 'المستخدمون',
                    'route' => 'admin.users.index',
                    'pattern' => 'admin.users.*',
                    'permission' => 'view-users',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
                ],
                [
                    'label' => 'رسائل التواصل',
                    'route' => 'admin.messages.index',
                    'pattern' => 'admin.messages.*',
                    'permission' => 'view-messages',
                    /*
                     * ⚠️ Counted ONLY when the viewer may see the inbox. An
                     * unconditional count would run a query on every admin
                     * page for someone who cannot open the screen it links to.
                     */
                    'badge' => auth()->user()?->can('view-messages')
                        ? \App\Models\ContactMessage::query()->unread()->count()
                        : 0,
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />',
                ],
                [
                    'label' => 'الأخبار',
                    'route' => 'admin.news.index',
                    'pattern' => 'admin.news.*',
                    'permission' => 'view-news',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />',
                ],
                [
                    /*
                     * العنصر الوحيد بأبناء — قائمة منسدلة لا رابط.
                     *
                     * ⚠️ الأب بلا `route`: ليس صفحة، بل مفتاح يفتح ابنيه.
                     * وجوده يتبع أبناءه: يظهر إن ظهر أحدهما، ويختفي إن
                     * اختفيا — قائمة منسدلة فارغة أسوأ من غيابها.
                     */
                    'label' => 'النشرة البريدية',
                    'pattern' => 'admin.newsletter.*',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />',
                    'children' => [
                        [
                            'label' => 'المشتركين',
                            'route' => 'admin.newsletter.subscribers.index',
                            'pattern' => 'admin.newsletter.subscribers.*',
                            'permission' => 'view-newsletter',
                        ],
                        [
                            'label' => 'إرسال نشرة',
                            'route' => 'admin.newsletter.send',
                            'pattern' => 'admin.newsletter.send',
                            'permission' => 'send-newsletter',
                        ],
                        [
                            'label' => 'سجل الحملات',
                            'route' => 'admin.newsletter.campaigns.index',
                            'pattern' => 'admin.newsletter.campaigns.*',
                            'permission' => 'view-campaigns',
                        ],
                    ],
                ],
                [
                    // 🔴 سلّة واحدة مركزية لا سلّة لكل قسم: الأدمن الذي حذف
                    // عنصراً بالخطأ يبحث عنه في مكان واحد لا في أربعة.
                    //
                    // ⚠️ بلا صلاحية واحدة: صلاحية السلّة صارت لكل نوع، فالرابط
                    // يظهر لمن يملك **أيّ** نوع قابل للحذف — لا لمن يملك
                    // delete-sliders تحديداً كما كان.
                    'label' => 'سلّة المحذوفات',
                    'route' => 'admin.trash.index',
                    'pattern' => 'admin.trash.*',
                    'permission' => null,
                    'visible' => filled(\App\Support\TrashableResources::allowedKeysFor(auth()->user())),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />',
                ],
                [
                    'label' => 'الأدوار والصلاحيات',
                    'route' => 'admin.roles.index',
                    'pattern' => 'admin.roles.*',
                    'permission' => 'view-roles',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />',
                ],
            ],
        ],
    ];

    /*
     * Visibility is resolved once, here, instead of via @can inside two
     * separate loops.
     *
     * 🔴 Why it changed: the trash link can no longer be described by a
     * single permission — trash access is per entity type now, so the item
     * carries a precomputed `visible` flag instead. Leaving @can in place
     * would mean a null permission silently granting the item to everyone,
     * and @canany() over a list containing null would throw.
     *
     * ⚠️ Hiding a link is a convenience, never a guard: every route behind
     * these items is protected by its own middleware, so a direct request
     * is refused whether or not the link was rendered.
     */
    $groups = collect($groups)
        ->map(function (array $group): array {
            $group['items'] = collect($group['items'])
                ->map(function (array $item): array {
                    /*
                     * ⚠️ A parent's children are filtered FIRST, then the
                     * parent's own visibility is derived from what survived.
                     * Checking the parent by permission would need a
                     * permission it does not have — it is a disclosure
                     * toggle, not a page.
                     */
                    if (isset($item['children'])) {
                        $item['children'] = collect($item['children'])
                            ->filter(fn (array $child): bool => auth()->user()?->can($child['permission']) ?? false)
                            ->values()
                            ->all();

                        $item['visible'] = filled($item['children']);
                    }

                    return $item;
                })
                ->filter(fn (array $item): bool => $item['visible'] ?? auth()->user()?->can($item['permission']) ?? false)
                ->values()
                ->all();

            return $group;
        })
        ->filter(fn (array $group): bool => filled($group['items']))
        ->values()
        ->all();
@endphp

{{-- طبقة تعتيم الدرج على الجوال --}}
<div data-admin-overlay hidden
     class="fixed inset-0 z-40 bg-ink/50 lg:hidden"></div>

<aside data-admin-sidebar
       role="navigation"
       aria-label="قائمة لوحة التحكّم"
       class="fixed inset-y-0 start-0 z-50 flex w-64 max-w-[85vw] shrink-0 flex-col
              border-e border-hairline bg-canvas transition-[transform,width] duration-300 ease-out
              lg:static lg:z-auto lg:max-w-none lg:translate-x-0">

    {{-- الترويسة — نفس ارتفاع الشريط العلوي (h-16) فتتحاذى الحدود --}}
    <div class="flex h-16 shrink-0 items-center gap-2 border-b border-hairline px-5">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-accent text-on-primary shadow-accent-glow">
                <x-icon name="shield" class="size-5" />
            </span>
            <span data-admin-label class="min-w-0 leading-tight">
                <span class="block text-ui font-bold text-ink">{{ __('common.brand') }}</span>
                <span class="block text-micro text-stone">{{ __('admin.panel') }}</span>
            </span>
        </a>

        <button type="button" data-admin-close
                class="ms-auto cursor-pointer rounded-lg p-1.5 text-stone transition hover:bg-surface lg:hidden"
                aria-label="إغلاق القائمة">
            <x-icon name="close" class="size-5" />
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-6">
        @foreach ($groups as $group)
            {{-- Visibility already resolved above; no @can here. --}}
            <div>
                    <p data-admin-label
                       class="px-3 text-micro font-semibold text-stone">{{ $group['label'] }}</p>

                    {{-- فاصل بديل يظهر حين تُطوى القائمة --}}
                    <div data-admin-collapsed-rule hidden
                         class="mx-auto my-2 h-px w-6 bg-hairline"></div>

                    <ul class="mt-2 space-y-1">
                        @foreach ($group['items'] as $item)
                                @php $active = request()->routeIs($item['pattern']); @endphp

                                @if (! empty($item['children']))
                                    {{--
                                        عنصر بقائمة منسدلة.

                                        🔴 الطيّ يفتح تلقائياً حين يكون أحد
                                        الأبناء نشطاً — وإلا اختفت الصفحة التي
                                        يقف عليها الأدمن خلف مفتاح مغلق.

                                        ⚠️ ويُفتح قسراً حين يُطوى الشريط
                                        الجانبي إلى شريط أيقونات: العناوين
                                        كلها مخفية هناك، فلوحة منسدلة مغلقة
                                        تعني مفتاحاً لا يكشف شيئاً يُقرأ.
                                    --}}
                                    <li x-data="sidebarDisclosure({{ $active ? 'true' : 'false' }})">
                                        <button type="button"
                                                @click="open = ! open"
                                                :aria-expanded="expanded.toString()"
                                                title="{{ $item['label'] }}"
                                                @class([
                                                    'group flex w-full cursor-pointer items-center gap-3 rounded-lg border-s-2 px-3 py-2 text-ui font-medium transition',
                                                    'border-accent bg-accent/10 text-accent-deep' => $active,
                                                    'border-transparent text-steel hover:bg-surface hover:text-ink' => ! $active,
                                                ])>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="1.5"
                                                 class="size-5 shrink-0 {{ $active ? 'text-accent' : 'text-stone group-hover:text-steel' }}">
                                                {!! $item['icon'] !!}
                                            </svg>
                                            <span data-admin-label class="truncate">{{ $item['label'] }}</span>

                                            {{-- سهم رأسي لا أفقي: يدور ولا
                                                 ينعكس، فلا يحتاج معالجة اتجاه. --}}
                                            <svg data-admin-label xmlns="http://www.w3.org/2000/svg"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="2" aria-hidden="true"
                                                 class="ms-auto size-4 shrink-0 transition-transform duration-200"
                                                 :class="expanded && 'rotate-180'">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                            </svg>
                                        </button>

                                        <ul x-show="expanded" x-cloak class="mt-1 space-y-1">
                                            @foreach ($item['children'] as $child)
                                                @php $childActive = request()->routeIs($child['pattern']); @endphp
                                                <li>
                                                    <a href="{{ route($child['route']) }}"
                                                       title="{{ $child['label'] }}"
                                                       @if ($childActive) aria-current="page" @endif
                                                       @class([
                                                           'flex items-center gap-3 rounded-lg py-2 pe-3 text-caption font-medium transition',
                                                           // ⚠️ إزاحة منطقية: الأبناء يزاحون من بداية السطر
                                                           // فينعكسون مع الاتجاه بلا قاعدة إضافية.
                                                           'ps-11',
                                                           'text-accent-deep' => $childActive,
                                                           'text-stone hover:bg-surface hover:text-ink' => ! $childActive,
                                                       ])>
                                                        {{-- نقطة بديلة عن أيقونة: الابن تابع لا قسم مستقل. --}}
                                                        <span @class([
                                                            'size-1.5 shrink-0 rounded-full',
                                                            'bg-accent' => $childActive,
                                                            'bg-hairline-strong' => ! $childActive,
                                                        ])></span>
                                                        <span data-admin-label class="truncate">{{ $child['label'] }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @else
                                <li>
                                    <a href="{{ route($item['route']) }}"
                                       title="{{ $item['label'] }}"
                                       @if ($active) aria-current="page" @endif
                                       @class([
                                           'group flex items-center gap-3 rounded-lg border-s-2 px-3 py-2 text-ui font-medium transition',
                                           'border-accent bg-accent/10 text-accent-deep' => $active,
                                           'border-transparent text-steel hover:bg-surface hover:text-ink' => ! $active,
                                       ])>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                             fill="none" stroke="currentColor" stroke-width="1.5"
                                             class="size-5 shrink-0 {{ $active ? 'text-accent' : 'text-stone group-hover:text-steel' }}">
                                            {!! $item['icon'] !!}
                                        </svg>
                                        <span data-admin-label class="truncate">{{ $item['label'] }}</span>

                                        {{-- Unread counter. Hidden when zero:
                                             a badge reading "0" is noise that
                                             trains the eye to ignore it. --}}
                                        @if (! empty($item['badge']))
                                            <span data-admin-label
                                                  class="ms-auto inline-flex min-w-5 shrink-0 items-center justify-center
                                                         rounded-full bg-warn px-1.5 py-0.5 text-micro font-bold
                                                         tabular-nums text-on-dark">{{ $item['badge'] }}</span>
                                        @endif
                                    </a>
                                </li>
                                @endif
                        @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- زرّ الطيّ — سطح مكتب فقط --}}
    <div class="hidden shrink-0 border-t border-hairline p-3 lg:block">
        <button type="button" data-admin-collapse
                class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-ui
                       text-steel transition hover:bg-surface hover:text-ink">
            {{-- سهم اتجاهي: ينعكس مع dir ومع حالة الطيّ --}}
            <x-icon name="arrow" data-admin-collapse-icon class="size-5 shrink-0 rtl:-scale-x-100" />
            <span data-admin-label>طيّ القائمة</span>
        </button>
    </div>
</aside>
