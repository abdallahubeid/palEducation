@php
    $links = [
        ['label' => __('nav.home'),     'route' => 'home'],
        ['label' => __('nav.branches'), 'route' => 'branches.index'],
        ['label' => __('nav.about'),    'route' => 'about'],
        ['label' => __('nav.pricing'),  'route' => 'pricing'],
        ['label' => __('nav.news'),     'route' => 'news.index'],
    ];

    $socials = [
        ['label' => 'Facebook',  'path' => 'M14 8.5h2V5.8h-2.3c-2.3 0-3.5 1.4-3.5 3.6v1.4H8v2.8h2.2V21h3v-7.4h2.2l.4-2.8h-2.6V9.7c0-.8.3-1.2 1.1-1.2z'],
        ['label' => 'Instagram', 'path' => 'M12 8.2a3.8 3.8 0 1 0 0 7.6 3.8 3.8 0 0 0 0-7.6m0 6.3a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5M16 3H8a5 5 0 0 0-5 5v8a5 5 0 0 0 5 5h8a5 5 0 0 0 5-5V8a5 5 0 0 0-5-5m3.6 13a3.6 3.6 0 0 1-3.6 3.6H8A3.6 3.6 0 0 1 4.4 16V8A3.6 3.6 0 0 1 8 4.4h8A3.6 3.6 0 0 1 19.6 8zM17 6.1a1 1 0 1 0 0 2 1 1 0 0 0 0-2'],
        ['label' => 'YouTube',   'path' => 'M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12c0 1.6.1 3.2.4 4.8a2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.3-1.6.4-3.2.4-4.8s-.1-3.2-.4-4.8M10 15V9l5.2 3z'],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-hairline bg-ground/95 backdrop-blur-sm">
    <div class="mx-auto max-w-[1280px] px-6">
        <nav class="flex h-18 items-center gap-6" aria-label="{{ __('nav.primary') }}">

            {{-- الشعار --}}
            {{-- py-1 يرفع هدف اللمس إلى 44px دون تكبير العلامة بصرياً --}}
            <a href="{{ route('home') }}"
               class="flex shrink-0 items-center gap-2.5 py-1 text-h5 font-bold text-ink transition hover:opacity-75">
                <span class="grid size-9 place-items-center rounded-lg bg-primary text-ui font-bold text-on-primary">p</span>
                <span class="hidden sm:inline">pal <span class="text-mint-deep">education</span></span>
            </a>

            {{-- الروابط — وسط الشريط --}}
            <ul class="hidden flex-1 items-center justify-center gap-1 lg:flex">
                @foreach ($links as $link)
                    @php $active = Route::has($link['route']) && request()->routeIs($link['route']); @endphp
                    <li>
                        <a href="{{ Route::has($link['route']) ? route($link['route']) : '#' }}"
                           @class([
                               'relative block rounded-md px-3.5 py-2 text-ui transition',
                               'text-ink font-medium' => $active,
                               'text-steel hover:text-ink' => ! $active,
                           ])>
                            {{ $link['label'] }}
                            @if ($active)
                                <span class="absolute inset-x-3.5 -bottom-px h-0.5 rounded-full bg-mint"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- الإجراءات --}}
            <div class="ms-auto flex shrink-0 items-center gap-3 lg:ms-0">

                {{-- سوشيال ميديا --}}
                <div class="hidden items-center gap-1.5 xl:flex">
                    @foreach ($socials as $social)
                        <a href="#"
                           aria-label="{{ $social['label'] }}"
                           class="grid size-9 place-items-center rounded-md text-steel
                                  transition hover:bg-primary hover:text-on-primary">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="{{ $social['path'] }}"/>
                            </svg>
                        </a>
                    @endforeach
                </div>

                <span class="hidden h-6 w-px bg-hairline xl:block" aria-hidden="true"></span>

                <a href="{{ Route::has('login') ? route('login') : '#' }}"
                   class="hidden px-1 text-ui text-steel transition hover:text-ink sm:inline-flex">
                    {{ __('nav.login') }}
                </a>

                <x-ui.button :href="Route::has('register') ? route('register') : '#'" size="sm" class="hidden sm:inline-flex">
                    {{ __('nav.register') }}
                </x-ui.button>

                <button type="button"
                        data-nav-toggle
                        aria-expanded="false"
                        aria-controls="nav-drawer"
                        aria-label="{{ __('nav.open_menu') }}"
                        class="grid size-11 place-items-center rounded-md text-ink transition hover:bg-surface lg:hidden">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                        <path d="M3 6h18M3 12h18M3 18h18"/>
                    </svg>
                </button>
            </div>
        </nav>
    </div>

    {{-- درج الجوال --}}
    <div id="nav-drawer" data-nav-drawer hidden class="border-t border-hairline bg-ground lg:hidden">
        <div class="mx-auto max-w-[1280px] px-6 py-4">
            <ul class="flex flex-col gap-1">
                @foreach ($links as $link)
                    <li>
                        <a href="{{ Route::has($link['route']) ? route($link['route']) : '#' }}"
                           class="block rounded-md px-3 py-3 text-body text-steel transition hover:bg-surface hover:text-ink">
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 flex flex-col gap-2 border-t border-hairline-soft pt-4">
                <x-ui.button :href="Route::has('login') ? route('login') : '#'" variant="secondary" size="md">
                    {{ __('nav.login') }}
                </x-ui.button>
                <x-ui.button :href="Route::has('register') ? route('register') : '#'" size="md">
                    {{ __('nav.register') }}
                </x-ui.button>
            </div>

            <div class="mt-5 flex items-center gap-2 border-t border-hairline-soft pt-4">
                @foreach ($socials as $social)
                    <a href="#"
                       aria-label="{{ $social['label'] }}"
                       class="grid size-10 place-items-center rounded-md bg-surface text-steel
                              transition hover:bg-primary hover:text-on-primary">
                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="{{ $social['path'] }}"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</header>
