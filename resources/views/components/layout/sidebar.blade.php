@php
    $items = [
        ['label' => __('student.nav_dashboard'),    'icon' => 'home',   'route' => 'student.dashboard'],
        ['label' => __('student.nav_subjects'),     'icon' => 'book',   'route' => 'student.subjects.index'],
        ['label' => __('student.nav_library'),      'icon' => 'folder', 'route' => 'student.library'],
        ['label' => __('student.nav_progress'),     'icon' => 'chart',  'route' => 'student.progress'],
        ['label' => __('student.nav_subscription'), 'icon' => 'card',   'route' => 'student.subscription'],
        ['label' => __('student.nav_profile'),      'icon' => 'user',   'route' => 'student.profile'],
    ];
@endphp

{{-- ستارة الجوال خلف الدرج --}}
<div data-sidebar-backdrop class="student-backdrop fixed inset-0 z-30 bg-primary/40 lg:hidden"></div>

<aside data-sidebar-drawer
       class="student-drawer fixed inset-y-0 start-0 z-40 flex w-72 shrink-0 flex-col border-e border-hairline bg-canvas
              lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-64 lg:transition-none"
       aria-label="{{ __('student.nav_dashboard') }}">

    <div class="flex h-16 shrink-0 items-center justify-between border-b border-hairline px-5 lg:h-18">
        <a href="{{ route('home') }}" class="text-h5 font-bold text-ink">{{ __('common.brand') }}</a>

        <button type="button" data-sidebar-close
                class="grid size-9 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink lg:hidden"
                aria-label="{{ __('student.sidebar_close') }}">
            <x-icon name="close" class="size-5" />
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <ul class="flex flex-col gap-1">
            @foreach ($items as $item)
                @php $active = Route::has($item['route']) && request()->routeIs($item['route']); @endphp
                <li>
                    <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                       @if ($active) aria-current="page" @endif
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-ui font-medium transition
                              {{ $active
                                  ? 'bg-accent-soft text-accent-deep'
                                  : 'text-steel hover:bg-surface hover:text-ink' }}">
                        <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</aside>
