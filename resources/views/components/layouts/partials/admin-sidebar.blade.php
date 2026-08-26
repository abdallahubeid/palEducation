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
            'label' => 'النظام',
            'items' => [
                [
                    // 🔴 سلّة واحدة مركزية لا سلّة لكل قسم: الأدمن الذي حذف
                    // عنصراً بالخطأ يبحث عنه في مكان واحد لا في أربعة.
                    'label' => 'سلّة المحذوفات',
                    'route' => 'admin.trash.index',
                    'pattern' => 'admin.trash.*',
                    'permission' => 'delete-sliders',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />',
                ],
                [
                    'label' => 'الأدوار والصلاحيات',
                    'route' => 'admin.roles.index',
                    'pattern' => 'admin.roles.*',
                    'permission' => 'manage-roles',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />',
                ],
            ],
        ],
    ];
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
            @canany(collect($group['items'])->pluck('permission')->all())
                <div>
                    <p data-admin-label
                       class="px-3 text-micro font-semibold text-stone">{{ $group['label'] }}</p>

                    {{-- فاصل بديل يظهر حين تُطوى القائمة --}}
                    <div data-admin-collapsed-rule hidden
                         class="mx-auto my-2 h-px w-6 bg-hairline"></div>

                    <ul class="mt-2 space-y-1">
                        @foreach ($group['items'] as $item)
                            @can($item['permission'])
                                @php $active = request()->routeIs($item['pattern']); @endphp
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
                                    </a>
                                </li>
                            @endcan
                        @endforeach
                    </ul>
                </div>
            @endcanany
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
