@php
    /*
     * 🔴 ثلاثة روابط كانت **ميّتة** قبل هذا التعديل، وكلها أُصلحت هنا:
     *
     *   teacher.files.create    → حُذف مع بناء المكتبة   ⇒ teacher.library
     *   teacher.profile         → أُعيدت تسميته           ⇒ teacher.profile.edit
     *   teacher.lectures.create → حُذف؛ الرفع صار نافذة  ⇒ teacher.lectures
     *
     * ⚠️ ولم يظهر أيّ منها كخطأ: حارس Route::has أدناه يُرجع '#' بصمت
     * للمسار الغائب. الحارس يمنع الانهيار ويُخفي العطل معاً — فأي إعادة
     * تسمية مسار يجب أن تُتبَع بفحص هذه المصفوفة.
     *
     * `pattern` منفصل عن `route`: التفعيل يشمل المسارات الفرعية
     * (teacher.library.* مثلاً) بينما الرابط يقصد صفحة واحدة.
     */
    $items = [
        ['label' => __('teacher.nav_dashboard'),   'icon' => 'home',   'route' => 'teacher.dashboard',    'pattern' => 'teacher.dashboard'],
        ['label' => __('teacher.nav_subjects'),    'icon' => 'book',   'route' => 'teacher.subjects',     'pattern' => 'teacher.subjects'],
        ['label' => __('teacher.nav_lectures'),    'icon' => 'play',   'route' => 'teacher.lectures',     'pattern' => 'teacher.lectures*'],
        ['label' => __('teacher.nav_library'),     'icon' => 'folder', 'route' => 'teacher.library',      'pattern' => 'teacher.library*'],
        ['label' => __('teacher.nav_performance'), 'icon' => 'chart',  'route' => 'teacher.students',     'pattern' => 'teacher.students'],
        ['label' => __('teacher.nav_trash'),       'icon' => 'trash',  'route' => 'teacher.trash',        'pattern' => 'teacher.trash'],
        ['label' => __('teacher.nav_profile'),     'icon' => 'user',   'route' => 'teacher.profile.edit', 'pattern' => 'teacher.profile*'],
    ];
@endphp

{{-- نفس عقد data-* وأصناف الدرج المستعملة في قشرة الطالب — الـJS
     (initStudentSidebar) والـCSS يعملان هنا بلا سطر إضافي. --}}
<div data-sidebar-backdrop class="student-backdrop fixed inset-0 z-30 bg-primary/40 lg:hidden"></div>

<aside data-sidebar-drawer
       class="teacher-drawer fixed inset-y-0 start-0 z-40 flex w-72 shrink-0 flex-col bg-canvas
              lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-60 lg:transition-[width] lg:duration-300"
       aria-label="{{ __('teacher.nav_dashboard') }}">

    <div class="flex h-16 shrink-0 items-center justify-between px-5 lg:h-[3.75rem]">
        <a href="{{ route('home') }}" data-sidebar-label
           class="flex min-h-11 items-center truncate text-h5 font-bold text-ink transition-opacity duration-200 lg:min-h-0">
            {{ __('common.brand') }}
        </a>

        <button type="button" data-sidebar-close
                class="grid size-11 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink lg:hidden"
                aria-label="{{ __('student.sidebar_close') }}">
            <x-icon name="close" class="size-5" />
        </button>
    </div>

    {{--
        🔴 الفعل المركزي للمعلم — رفع محاضرة. `docs/01` §4.3 صريحة:
        يجب أن يكون أبرز عنصر في الواجهة، لا بنداً في قائمة.
    --}}
    <div class="px-3 pb-3">
        {{-- ⚠️ الرفع صار نافذة داخل شاشة المحاضرات لا صفحة مستقلّة،
             فالزرّ يقود إليها. كان يشير إلى مسار محذوف فيُرسَم '#' —
             أبرز عنصر في الشريط وهو لا يفعل شيئاً. --}}
        <a href="{{ Route::has('teacher.lectures') ? route('teacher.lectures') : '#' }}"
           title="{{ __('teacher.upload_lecture') }}"
           class="flex h-11 items-center justify-center gap-2 overflow-hidden rounded-full bg-accent px-4
                  text-ui font-semibold text-on-primary transition hover:bg-accent-deep">
            <x-icon name="plus" class="size-4 shrink-0" />
            <span data-sidebar-label class="truncate transition-opacity duration-200">{{ __('teacher.upload_lecture') }}</span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-4">
        <ul class="flex flex-col gap-0.5">
            @foreach ($items as $item)
                @php $active = Route::has($item['route']) && request()->routeIs($item['pattern']); @endphp
                <li>
                    <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                       @if ($active) aria-current="page" @endif
                       title="{{ $item['label'] }}"
                       {{-- py-2.5 على اللمس (≥44px) وتتقلّص للكثافة على المؤشّر --}}
                       class="flex items-center gap-3 overflow-hidden rounded-lg px-3 py-2.5 text-ui font-medium transition lg:py-2
                              {{ $active
                                  ? 'bg-accent-soft text-accent-deep'
                                  : 'text-steel hover:bg-surface hover:text-ink' }}">
                        <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                        <span data-sidebar-label class="truncate transition-opacity duration-200">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{--
        زرّ الطيّ — أسفل الشريط، بنفس بنية شريط الأدمن.

        🔴 نُقل من الترويسة إلى هنا: في الأعلى كان يزاحم اسم المنصّة وزرّ
        إغلاق الدرج في مساحة واحدة، وثلاثة أزرار متجاورة في شريط بعرض 60
        تُخطئ باللمس. والأسفل هو موضعه في لوحة الأدمن، فالقشرتان تتصرّفان
        بالمثل بدل أن يتعلّم المستخدم موضعين.

        ⚠️ lg:block فقط: على الجوال الشريط درج يُفتح ويُغلق، ولا معنى لطيّه
        إلى شريط أيقونات — ولهذا بقي زرّ الإغلاق في الترويسة.
    --}}
    <div class="hidden shrink-0 border-t border-hairline p-3 lg:block">
        <button type="button" data-sidebar-collapse-toggle
                data-collapse-label="{{ __('student.sidebar_collapse') }}"
                data-expand-label="{{ __('student.sidebar_expand') }}"
                class="flex w-full cursor-pointer items-center gap-3 overflow-hidden rounded-lg px-3 py-2
                       text-ui text-steel transition hover:bg-surface hover:text-ink"
                aria-label="{{ __('student.sidebar_collapse') }}"
                aria-expanded="true"
                title="{{ __('student.sidebar_collapse') }}">
            {{-- سهم رأسي مُدار: يدور مع حالة الطيّ ولا ينعكس مع الاتجاه،
                 فلا يحتاج rtl: إطلاقاً. --}}
            <x-icon name="chevron-down" class="size-5 shrink-0 rotate-90 transition-transform duration-300" />
            <span data-sidebar-label class="truncate transition-opacity duration-200">
                {{ __('student.sidebar_collapse') }}
            </span>
        </button>
    </div>
</aside>
