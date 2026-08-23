@php
    /*
     | Contact page - an ADDITION outside the planned 48 screens, like /styleguide.
     |
     | Redesigned 2026-08-22 for calm. The first pass stacked FOUR bordered boxes
     | on one screen (form + channels + hours + an accent FAQ card), which read as
     | busy and made the aside compete with the form. Now there are TWO cards -
     | the form, and one channels card with hours folded into its foot - plus a
     | single quiet line of text for the FAQ deflection. Fewer containers, more
     | air, one obvious action.
     |
     | Contact details are mock. Every value is a Latin/numeric string that sits
     | inside Arabic prose, so each is rendered through <bdi dir="ltr"> - an
     | un-isolated phone number or email visually reorders in RTL and becomes
     | impossible to read or copy correctly.
     */
    $phoneDisplay   = '+970 599 123 456';
    $phoneDial      = '+970599123456';   // bare form: tel: and wa.me reject spaces
    $whatsappDigits = '970599123456';    // wa.me wants digits only, no plus
    $email          = 'support@paleducation.ps';

    $channels = [
        [
            'icon'  => 'users',
            'label' => __('public.contact_whatsapp'),
            'value' => $phoneDisplay,
            'href'  => 'https://wa.me/' . $whatsappDigits,
            'external' => true,
        ],
        [
            'icon'  => 'card',
            'label' => __('public.contact_phone_channel'),
            'value' => $phoneDisplay,
            'href'  => 'tel:' . $phoneDial,
            'external' => false,
        ],
        [
            'icon'  => 'mail',
            'label' => __('public.contact_email_channel'),
            'value' => $email,
            'href'  => 'mailto:' . $email,
            'external' => false,
        ],
        [
            'icon'  => 'home',
            'label' => __('public.contact_office'),
            'value' => __('public.contact_office_desc'),
            'href'  => null,          // static: renders as a div, never a dead link
            'external' => false,
            'plain' => true,          // Arabic text, so no <bdi> and no LTR
        ],
    ];

    $topics = [
        'general' => __('public.contact_topic_general'),
        'support' => __('public.contact_topic_support'),
        'sales'   => __('public.contact_topic_sales'),
        'signup'  => __('public.contact_topic_signup'),
    ];
@endphp

<x-layouts.public :title="__('public.contact_eyebrow')">

    {{-- ═══ Masthead ═══ --}}
    <section class="border-b border-hairline bg-canvas py-14 lg:py-20">
        <div class="mx-auto max-w-[1280px] px-6">
            <div class="measure">
                <x-ui.rule-label>{{ __('public.contact_eyebrow') }}</x-ui.rule-label>
                <h1 class="mt-5 text-h1 font-bold text-ink">{{ __('public.contact_title') }}</h1>
                <p class="mt-4 text-lead leading-[1.8] text-steel">{{ __('public.contact_lede') }}</p>
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
                        <div data-contact-panel="form">
                            <h2 class="text-h4 font-semibold text-ink">{{ __('public.contact_form_title') }}</h2>

                            <div class="mt-7 grid gap-5 sm:grid-cols-2">
                                <x-ui.input :label="__('public.contact_name')"
                                            name="name"
                                            required
                                            :placeholder="__('public.contact_name_placeholder')" />

                                {{-- dir=ltr on the FIELD only - the Arabic label keeps its own direction --}}
                                <x-ui.input :label="__('public.contact_email')"
                                            name="email"
                                            type="email"
                                            dir="ltr"
                                            required
                                            placeholder="name@example.com" />

                                <x-ui.input :label="__('public.contact_phone')"
                                            name="phone"
                                            type="tel"
                                            dir="ltr"
                                            :hint="__('public.contact_phone_hint')"
                                            placeholder="+970 5xx xxx xxx"
                                            class="sm:col-span-2" />

                                <x-ui.select :label="__('public.contact_topic')"
                                             name="topic"
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
                                <x-ui.button variant="primary"
                                             size="md"
                                             type="button"
                                             class="w-full shrink-0 sm:w-auto"
                                             data-contact-submit
                                             data-sending-label="{{ __('public.contact_sending') }}">
                                    {{ __('public.contact_submit') }}
                                </x-ui.button>
                            </div>
                        </div>

                        {{-- Success panel --}}
                        <div data-contact-panel="success" hidden class="py-10 text-center">
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

                            <dl class="mt-3 flex flex-col gap-2 text-ui">
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-steel">{{ __('public.contact_hours_weekdays') }}</dt>
                                    <dd><bdi dir="ltr" class="num font-semibold text-ink">{{ __('public.contact_hours_weekdays_time') }}</bdi></dd>
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-steel">{{ __('public.contact_hours_friday') }}</dt>
                                    <dd class="font-medium text-stone">{{ __('public.contact_hours_closed') }}</dd>
                                </div>
                            </dl>

                            <p class="mt-3 text-caption leading-relaxed text-stone">{{ __('public.contact_hours_note') }}</p>
                        </div>
                    </div>

                    {{--
                        FAQ deflection as one quiet line, not a bordered accent card.
                        Boxing it made it compete with the form's CTA for attention.
                    --}}
                    <p class="mt-6 text-body leading-[1.75] text-steel">
                        {{ __('public.contact_faq_body') }}
                        <a href="{{ route('pricing') }}#faq"
                           class="inline-flex min-h-11 items-center gap-1.5 font-semibold text-accent-deep transition hover:underline">
                            {{ __('public.contact_faq_action') }}
                            <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                        </a>
                    </p>
                </aside>
            </div>
        </div>
    </section>

</x-layouts.public>
