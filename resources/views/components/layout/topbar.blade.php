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
@endphp

<header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-hairline bg-canvas/90 px-4
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

        <button type="button"
                class="relative grid size-10 shrink-0 place-items-center rounded-full text-steel transition hover:bg-surface hover:text-ink"
                aria-label="{{ __('student.notifications') }}">
            <x-icon name="bell" class="size-5" />
            @if ($unreadCount > 0)
                <span class="absolute end-2 top-2 size-2 rounded-full bg-error" aria-hidden="true"></span>
            @endif
        </button>

        <a href="{{ Route::has('student.profile') ? route('student.profile') : '#' }}"
           class="flex shrink-0 items-center gap-2 rounded-full py-1 ps-1 pe-3 transition hover:bg-surface">
            <x-ui.avatar :src="$studentAvatar" :name="$studentName" size="sm" />
            <span class="hidden text-ui font-medium text-ink lg:inline">{{ $studentName }}</span>
        </a>
    </div>
</header>
