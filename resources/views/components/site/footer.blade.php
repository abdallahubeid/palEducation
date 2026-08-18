@php
    $columns = [
        [
            'title' => __('footer.product'),
            'links' => [
                ['label' => __('nav.branches'),    'route' => 'branches.index'],
                ['label' => __('nav.pricing'),     'route' => 'pricing'],
                ['label' => __('footer.library'),  'route' => null],
                ['label' => __('footer.teachers'), 'route' => null],
            ],
        ],
        [
            'title' => __('footer.platform'),
            'links' => [
                ['label' => __('nav.about'),      'route' => 'about'],
                ['label' => __('nav.news'),       'route' => 'news.index'],
                ['label' => __('footer.join_us'), 'route' => null],
                ['label' => __('footer.contact'), 'route' => null],
            ],
        ],
        [
            'title' => __('footer.support'),
            'links' => [
                ['label' => __('footer.help_center'),  'route' => null],
                ['label' => __('footer.faq'),          'route' => null],
                ['label' => __('footer.report_issue'), 'route' => null],
            ],
        ],
        [
            'title' => __('footer.legal'),
            'links' => [
                ['label' => __('footer.privacy'), 'route' => null],
                ['label' => __('footer.terms'),   'route' => null],
                ['label' => __('footer.refund'),  'route' => null],
            ],
        ],
    ];
@endphp

<footer class="bg-canvas-dark">
    <div class="mx-auto max-w-[1280px] px-6 pt-14 pb-8">

        <div class="grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.6fr)_repeat(4,minmax(0,1fr))]">

            {{-- النشرة البريدية وحدها في العمود الأول --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <h2 class="text-h5 font-semibold text-on-dark">{{ __('footer.newsletter_title') }}</h2>
                <p class="mt-2.5 max-w-sm text-caption leading-relaxed text-on-dark-muted">
                    {{ __('footer.newsletter_body') }}
                </p>

                <form class="mt-5 flex max-w-sm flex-col gap-2.5 sm:flex-row" method="POST" action="#">
                    @csrf
                    <label for="newsletter-email" class="sr-only">{{ __('footer.email_label') }}</label>
                    <input id="newsletter-email"
                           type="email"
                           name="email"
                           required
                           dir="ltr"
                           placeholder="name@example.com"
                           class="h-11 flex-1 rounded-md border border-on-dark/15 bg-on-dark/5 px-4
                                  font-latin text-ui text-on-dark transition placeholder:text-on-dark/35
                                  focus:border-accent focus:bg-on-dark/10 focus:outline-none">

                    <button type="submit"
                            class="inline-flex h-11 shrink-0 items-center justify-center rounded-full bg-accent px-6
                                   text-ui font-semibold text-on-primary transition hover:brightness-105">
                        {{ __('footer.subscribe') }}
                    </button>
                </form>
            </div>

            {{-- أعمدة الروابط --}}
            @foreach ($columns as $column)
                <div>
                    <h3 class="text-ui font-semibold text-on-dark">{{ $column['title'] }}</h3>
                    <ul class="mt-4 flex flex-col gap-3">
                        @foreach ($column['links'] as $link)
                            <li>
                                <a href="{{ $link['route'] && Route::has($link['route']) ? route($link['route']) : '#' }}"
                                   class="text-caption text-on-dark-muted transition hover:text-accent-on-dark">
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        {{-- الشريط السفلي --}}
        <div class="mt-14 flex flex-col items-center gap-4 border-t border-on-dark/10 pt-6
                    sm:flex-row sm:justify-between">

            <a href="{{ route('home') }}" class="flex items-center gap-2 text-ui font-bold text-on-dark">
                <span class="grid size-7 place-items-center rounded-md bg-on-dark text-micro font-bold text-primary">p</span>
                <span>pal <span class="text-accent-on-dark">education</span></span>
            </a>

            <div class="flex flex-col items-center gap-1.5 text-caption text-on-dark/45
                        sm:flex-row sm:gap-5">
                <p>&copy; <span class="num">{{ date('Y') }}</span> {{ __('footer.rights') }}</p>
                <span class="hidden size-1 rounded-full bg-on-dark/20 sm:block" aria-hidden="true"></span>
                <p>{{ __('footer.made_in') }}</p>
            </div>
        </div>
    </div>
</footer>
