@php
    $items = [
        ['label' => __('teacher.nav_dashboard'),   'icon' => 'home',      'route' => 'teacher.dashboard'],
        ['label' => __('teacher.nav_subjects'),    'icon' => 'book',      'route' => 'teacher.subjects'],
        ['label' => __('teacher.nav_lectures'),    'icon' => 'play',      'route' => 'teacher.lectures'],
        ['label' => __('teacher.nav_performance'), 'icon' => 'chart',     'route' => 'teacher.performance'],
        ['label' => __('teacher.nav_library'),     'icon' => 'folder',    'route' => 'teacher.files.create'],
        ['label' => __('teacher.nav_trash'),       'icon' => 'trash',     'route' => 'teacher.trash'],
        ['label' => __('teacher.nav_profile'),     'icon' => 'user',      'route' => 'teacher.profile'],
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

        <button type="button" data-sidebar-collapse-toggle
                data-collapse-label="{{ __('student.sidebar_collapse') }}"
                data-expand-label="{{ __('student.sidebar_expand') }}"
                class="hidden size-9 shrink-0 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink lg:grid"
                aria-label="{{ __('student.sidebar_collapse') }}"
                aria-expanded="true"
                title="{{ __('student.sidebar_collapse') }}">
            <x-icon name="chevron-down" class="size-4 rotate-90 transition-transform duration-300" />
        </button>

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
        <a href="{{ Route::has('teacher.lectures.create') ? route('teacher.lectures.create') : '#' }}"
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
                @php $active = Route::has($item['route']) && request()->routeIs($item['route']); @endphp
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
</aside>
