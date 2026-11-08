@php
    /*
     * 🔴 The footer reads settings DIRECTLY rather than taking them from a
     * controller. It renders on every public page — home, branches, news,
     * contact, pricing, the auth screens — and half of those are still
     * Route::view with no controller to pass anything through. One read here
     * beats threading the same array through eight call sites.
     *
     * ⚠️ Two queries total (Setting::footer() and the columns share one
     * resolve() call each), not one per link.
     */
    $footer = \App\Models\Setting::footer();
    $columns = \App\Models\Setting::footerColumns();

    // :year is substituted at render time — a hardcoded year in the stored
    // string is correct for a few months and wrong forever after.
    $copyright = str_replace(':year', (string) date('Y'), $footer['copyright_text']);
@endphp

<footer class="bg-canvas-dark">
    <div class="mx-auto max-w-[1280px] px-6 pt-14 pb-8">

        <div class="grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.6fr)_repeat(4,minmax(0,1fr))]">

            {{-- النشرة البريدية وحدها في العمود الأول --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <h2 class="text-h5 font-semibold text-on-dark">{{ $footer['newsletter_title'] }}</h2>
                <p class="mt-2.5 max-w-sm text-caption leading-relaxed text-on-dark-muted">
                    {{ $footer['newsletter_sub_title'] }}
                </p>

                {{-- الحالة تُعرَض هنا لا في صفحة منفصلة: النموذج في تذييل كل
                     صفحة، فالعودة إلى الصفحة نفسها تُبقي القارئ حيث كان. --}}
                @if (session('newsletter.status'))
                    <p role="status"
                       class="mt-5 rounded-lg bg-accent/15 px-4 py-3 text-caption text-on-dark">
                        {{ __('newsletter.'.session('newsletter.status')) }}
                    </p>
                @endif

                @error('email')
                    <p class="mt-5 rounded-lg bg-error/20 px-4 py-3 text-caption text-on-dark">{{ $message }}</p>
                @enderror

                <form class="mt-5 flex max-w-sm flex-col gap-2.5 sm:flex-row"
                      method="POST" action="{{ route('newsletter.subscribe') }}">
                    @csrf
                    <label for="newsletter-email" class="sr-only">{{ __('footer.email_label') }}</label>
                    <input id="newsletter-email"
                           type="email"
                           name="email"
                           required
                           dir="ltr"
                           placeholder="name@example.com"
                           {{-- w-full ثم sm:flex-1: في الحاوية العمودية (الجوال) كان
                                flex-1 يضبط flex-basis:0% على المحور الرأسي فينهار
                                ارتفاع الحقل إلى 22px رغم h-11. النمو مقصود للصف فقط. --}}
                           class="h-11 w-full rounded-md border border-on-dark/15 bg-on-dark/5 px-4 sm:flex-1
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
                    {{-- قائمة تنقّل لا نص جارٍ: إعفاء WCAG للروابط المضمّنة في
                         الفقرات لا ينطبق هنا، فكل رابط يبلغ 44px على اللمس. --}}
                    <ul class="mt-4 flex flex-col gap-1 lg:gap-3">
                        @foreach ($column['links'] as $link)
                            <li>
                                {{-- The href comes straight from the parsed
                                     link. Setting::parseLinks() has already
                                     forced anything that is not #, /… or
                                     http(s)://… down to # — these render on
                                     every public page. --}}
                                <a href="{{ $link['href'] }}"
                                   class="inline-flex min-h-11 items-center text-caption text-on-dark-muted transition
                                          hover:text-accent-on-dark lg:min-h-0">
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

            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-2 text-ui font-bold text-on-dark lg:min-h-0">
                <span class="grid size-7 place-items-center rounded-md bg-on-dark text-micro font-bold text-primary">p</span>
                <span>pal <span class="text-accent-on-dark">education</span></span>
            </a>

            <div class="flex flex-col items-center gap-1.5 text-caption text-on-dark/45
                        sm:flex-row sm:gap-5">
                {{-- The year lives inside the stored string via :year,
                     so an admin can reword the whole line freely. --}}
                <p>&copy; {{ $copyright }}</p>
                <span class="hidden size-1 rounded-full bg-on-dark/20 sm:block" aria-hidden="true"></span>
                @if (filled($footer['made_in_text']))
                    <p>{{ $footer['made_in_text'] }}</p>
                @endif
            </div>
        </div>
    </div>
</footer>
