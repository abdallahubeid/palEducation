@php
    /*
     | Contact page - an ADDITION outside the planned 48 screens, like /styleguide.
     |
     | Redesigned 2026-08-22 for calm. The first pass stacked FOUR bordered boxes
     | on one screen (form + channels + hours + an accent FAQ card), which read as
     | busy and made the aside compete with the form. Now there are TWO cards -
     | the form, and one channels card with hours folded into its foot - plus a
     | single quiet line of text for the FAQ deflection.
     |
     | 🔁 The contact details used to be hardcoded here. They now arrive from
     | `settings` via ContactController as $contact, $channels and $hoursRows.
     | The dial-safe forms of each number are derived there too: tel: and wa.me
     | both reject spaces, and wa.me rejects the leading plus as well.
     |
     | ⚠️ Every latin or numeric value still renders inside <bdi dir="ltr"> -
     | an un-isolated phone number or email visually reorders in RTL and becomes
     | impossible to read or copy correctly.
     */
    // 🔴 From the ENUM, not a literal list. The controller validates the
    // submitted value against those same cases, so a select built from a
    // second copy could offer an option the server would reject.
    $topics = \App\Enums\ContactSubject::options();
@endphp

<x-layouts.public :title="$contact['badge_text']">

    {{-- ═══ Masthead ═══ --}}
    <section class="border-b border-hairline bg-canvas py-14 lg:py-20">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="measure">
                <x-ui.rule-label>{{ $contact['badge_text'] }}</x-ui.rule-label>
                <h1 class="mt-5 text-h1 font-bold text-ink">{{ $contact['title'] }}</h1>
                @if (filled($contact['sub_title']))
                    <p class="mt-4 text-lead leading-[1.8] text-steel">{{ $contact['sub_title'] }}</p>
                @endif
            </div>
        </div>
    </section>

    {{--
        ═══ Split surface ═══

        Form on the inline start (right in RTL), channels on the inline end.
        Placement is by grid coordinate only, so both directions mirror with no
        rtl: variant. DOM order is form-then-channels, which is also the required
        mobile stacking order - no `order` utility needed.
    --}}
    <section class="py-14 lg:py-20">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="lg:grid lg:grid-cols-12 lg:items-start lg:gap-10 xl:gap-12">

                {{-- ── Form ── --}}
                <div class="lg:col-span-7 lg:col-start-1">
                    <div class="rounded-xxl bg-canvas p-6 shadow-subtle ring-1 ring-hairline sm:p-9"
                         data-contact-form>

                        {{--
                            Form and success panel are siblings toggled by JS. Success
                            REPLACES the form rather than stacking above it, so nobody is
                            left looking at a form they already sent. Same pattern as the
                            forgot-password screen.
                        --}}
                        {{-- 🔁 A REAL form now. It used to be a JS-only mock:
                             the submit button toggled a success panel and
                             nothing was ever sent anywhere. --}}
                        <form method="POST" action="{{ route('contact.store') }}"
                              data-contact-panel="form" @if (session('contact.sent')) hidden @endif>
                            @csrf

                            <h2 class="text-h4 font-semibold text-ink">{{ __('public.contact_form_title') }}</h2>

                            {{-- Validation errors come back on a redirect, so
                                 they are shown here rather than swallowed. --}}
                            @if ($errors->any())
                                <ul class="mt-5 space-y-1 rounded-xl border border-error/25 bg-error/10 px-4 py-3">
                                    @foreach ($errors->all() as $error)
                                        <li class="text-caption text-error-deep">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="mt-7 grid gap-5 sm:grid-cols-2">
                                <x-ui.input :label="__('public.contact_name')"
                                            name="name"
                                            required
                                            :value="old('name')"
                                            :placeholder="__('public.contact_name_placeholder')" />

                                {{-- dir=ltr on the FIELD only - the Arabic label keeps its own direction --}}
                                <x-ui.input :label="__('public.contact_email')"
                                            name="email"
                                            type="email"
                                            dir="ltr"
                                            required
                                            :value="old('email')"
                                            placeholder="name@example.com" />

                                <x-ui.input :label="__('public.contact_phone')"
                                            name="phone"
                                            type="tel"
                                            dir="ltr"
                                            :hint="__('public.contact_phone_hint')"
                                            :value="old('phone')"
                                            placeholder="+970 5xx xxx xxx"
                                            class="sm:col-span-2" />

                                {{-- 🔴 name matches the COLUMN, not the old
                                     mock's "topic": the controller validates
                                     subject_type against the enum. --}}
                                <x-ui.select :label="__('public.contact_topic')"
                                             name="subject_type"
                                             required
                                             :placeholder="__('public.contact_topic_placeholder')"
                                             :options="$topics"
                                             class="sm:col-span-2" />

                                <x-ui.textarea :label="__('public.contact_message')"
                                               name="message"
                                               rows="6"
                                               required
                                               :placeholder="__('public.contact_message_placeholder')"
                                               class="sm:col-span-2" />
                            </div>

                            <div class="mt-7 flex flex-col gap-4 border-t border-hairline pt-6 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-caption leading-relaxed text-stone">{{ __('public.contact_privacy_note') }}</p>

                                {{--
                                    A real <button>, not an anchor: it carries a disabled
                                    state while sending, which an anchor cannot express.
                                --}}
                                {{-- type=submit, not button: the form posts
                                     for real, and the JS only adds the
                                     "sending…" label on top. --}}
                                <x-ui.button variant="primary"
                                             size="md"
                                             type="submit"
                                             class="w-full shrink-0 sm:w-auto"
                                             data-contact-submit
                                             data-sending-label="{{ __('public.contact_sending') }}">
                                    {{ __('public.contact_submit') }}
                                </x-ui.button>
                            </div>
                        </form>

                        {{-- Success panel. Shown by the SERVER after a real
                             submission, so it works with JS disabled too. --}}
                        <div data-contact-panel="success" @unless (session('contact.sent')) hidden @endunless
                             class="py-10 text-center">
                            <span class="mx-auto grid size-16 place-items-center rounded-full bg-accent text-on-primary">
                                <x-icon name="check" class="size-8" />
                            </span>

                            <h2 class="mt-6 text-h4 font-semibold text-ink">{{ __('public.contact_success_title') }}</h2>
                            <p class="measure mx-auto mt-3 text-body leading-[1.75] text-steel">
                                {{ __('public.contact_success_body') }}
                            </p>

                            <x-ui.button variant="secondary" size="md" type="button" class="mt-7" data-contact-reset>
                                {{ __('public.contact_send_another') }}
                            </x-ui.button>
                        </div>
                    </div>
                </div>

                {{--
                    ── Direct channels ──

                    One card, not three. Rows are separated by hairlines rather than
                    being individual boxes, and hover changes the icon and value only -
                    no background swap - which keeps the column quiet next to the form.
                --}}
                <aside class="mt-8 lg:col-span-5 lg:col-start-8 lg:mt-0">
                    <div class="rounded-xxl bg-canvas p-6 shadow-subtle ring-1 ring-hairline sm:p-7">
                        <h2 class="text-h5 font-semibold text-ink">{{ __('public.contact_channels_title') }}</h2>

                        {{-- ⚠️ Validation guarantees at least one channel, so an
                             empty list here means every optional row was cleared
                             after the required one — still worth not rendering a
                             bare <ul>. --}}

                        <ul class="mt-2 flex flex-col divide-y divide-hairline-soft">
                            @foreach ($channels as $c)
                                @php
                                    // A card with a destination is a link; the office row is
                                    // static, so it must NOT be one - a link that goes nowhere
                                    // is worse than plain text for keyboard and AT users.
                                    $tag = $c['href'] ? 'a' : 'div';
                                @endphp

                                <li>
                                    <{{ $tag }}
                                        @if ($c['href']) href="{{ $c['href'] }}" @endif
                                        @if ($c['external']) target="_blank" rel="noopener noreferrer" @endif
                                        @class([
                                            'group flex min-h-11 items-center gap-4 py-4',
                                            'transition' => $c['href'],
                                        ])>
                                        <span @class([
                                            'grid size-11 shrink-0 place-items-center rounded-full bg-accent/10 text-accent-deep transition',
                                            'group-hover:bg-accent group-hover:text-on-primary' => $c['href'],
                                        ])>
                                            <x-icon :name="$c['icon']" class="size-5" />
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block text-caption text-stone">{{ $c['label'] }}</span>

                                            @if (! empty($c['plain']))
                                                <span class="mt-0.5 block text-ui font-medium leading-relaxed text-ink">{{ $c['value'] }}</span>
                                            @else
                                                {{-- bdi: phone numbers and emails never mirror --}}
                                                <bdi dir="ltr" @class([
                                                    'num mt-0.5 block text-ui font-semibold text-ink transition',
                                                    'group-hover:text-accent-deep' => $c['href'],
                                                ])>{{ $c['value'] }}</bdi>
                                            @endif
                                        </span>

                                        @if ($c['href'])
                                            <x-icon name="arrow"
                                                    class="size-4 shrink-0 text-muted transition group-hover:text-accent-deep rtl:-scale-x-100" />
                                        @endif
                                    </{{ $tag }}>
                                </li>
                            @endforeach
                        </ul>

                        {{--
                            Working hours live in the foot of this card instead of a fourth
                            box of their own. They are context for the channels above, not a
                            separate subject.
                        --}}
                        <div class="mt-5 rounded-lg bg-surface p-4">
                            <p class="text-caption font-semibold text-stone">{{ __('public.contact_hours_title') }}</p>

                            {{-- Rows come parsed from one textarea: `Label: value`
                                 split on the FIRST colon, because Arabic clock
                                 times carry colons of their own. A line without
                                 one renders as a note under the table. --}}
                            @php
                                $hourPairs = array_filter($hoursRows, fn ($row) => $row['label'] !== null);
                                $hourNotes = array_filter($hoursRows, fn ($row) => $row['label'] === null);
                            @endphp

                            @if ($hourPairs !== [])
                                <dl class="mt-3 flex flex-col gap-2 text-ui">
                                    @foreach ($hourPairs as $row)
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-steel">{{ $row['label'] }}</dt>
                                            {{-- bdi: a clock range is numeric and
                                                 must not mirror inside RTL. --}}
                                            <dd><bdi dir="ltr" class="num font-semibold text-ink">{{ $row['value'] }}</bdi></dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif

                            @foreach ($hourNotes as $row)
                                <p class="mt-3 text-caption leading-relaxed text-stone">{{ $row['value'] }}</p>
                            @endforeach
                        </div>
                    </div>

                    {{--
                        FAQ deflection as one quiet line, not a bordered accent card.
                        Boxing it made it compete with the form's CTA for attention.
                    --}}
                    @if (filled($contact['faq_notice']) || filled($contact['faq_btn_text']))
                        <p class="mt-6 text-body leading-[1.75] text-steel">
                            {{ $contact['faq_notice'] }}

                            @if (filled($contact['faq_btn_text']) && filled($contact['faq_btn_link']))
                                <a href="{{ $contact['faq_btn_link'] }}"
                                   class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-accent-deep transition hover:underline">
                                    {{ $contact['faq_btn_text'] }}
                                    <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                                </a>
                            @endif
                        </p>
                    @endif
                </aside>
            </div>
        </div>
    </section>

</x-layouts.public>
