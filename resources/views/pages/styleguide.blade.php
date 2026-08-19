@php
    // ── بيانات عرض للدليل فقط ─────────────────────────────
    $colorGroups = [
        'colors_action' => [
            ['accent', 'bg-accent', '#525fe1'],
            ['accent-deep', 'bg-accent-deep', '#3f4ab8'],
            ['accent-soft', 'bg-accent-soft', '#ecedff'],
            ['amber', 'bg-amber', '#f0a500'],
            ['amber-deep', 'bg-amber-deep', '#9c6205'],
        ],
        'colors_semantic' => [
            ['tag', 'bg-tag', '#3772cf'],
            ['warn', 'bg-warn', '#c37d0d'],
            ['warn-deep', 'bg-warn-deep', '#8a5a09'],
            ['error', 'bg-error', '#d45656'],
            ['error-deep', 'bg-error-deep', '#b23b3b'],
        ],
        'colors_surface' => [
            ['ground', 'bg-ground', '#f5f8f7'],
            ['canvas', 'bg-canvas', '#ffffff'],
            ['surface', 'bg-surface', '#f7f7f7'],
            ['hairline', 'bg-hairline', '#e5e5e5'],
            ['hairline-strong', 'bg-hairline-strong', '#d4d4d4'],
        ],
        'colors_text' => [
            ['ink', 'bg-ink', '#0b104a'],
            ['charcoal', 'bg-charcoal', '#1a2d62'],
            ['slate', 'bg-slate', '#3d4a6b'],
            ['steel', 'bg-steel', '#4a5355'],
            ['stone', 'bg-stone', '#666e7c'],
            ['muted', 'bg-muted', '#a3aab5'],
        ],
    ];

    $typeScale = [
        ['hero', 'text-hero font-bold', '56 / 1.35'],
        ['display', 'text-display font-bold', '44 / 1.35'],
        ['h1', 'text-h1 font-bold', '36 / 1.35'],
        ['h2', 'text-h2 font-semibold', '28 / 1.40'],
        ['h3', 'text-h3 font-semibold', '24 / 1.45'],
        ['h4', 'text-h4 font-semibold', '20 / 1.50'],
        ['h5', 'text-h5 font-semibold', '18 / 1.55'],
        ['lead', 'text-lead', '18 / 1.80'],
        ['body', 'text-body', '16 / 1.75'],
        ['ui', 'text-ui', '15 / 1.70'],
        ['caption', 'text-caption', '14 / 1.60'],
        ['micro', 'text-micro font-semibold', '13 / 1.50'],
    ];

    $radii = [
        ['xs', 'rounded-xs', '4px'],
        ['sm', 'rounded-sm', '6px'],
        ['md', 'rounded-md', '8px'],
        ['lg', 'rounded-lg', '12px'],
        ['xl', 'rounded-xl', '16px'],
        ['xxl', 'rounded-xxl', '24px'],
        ['full', 'rounded-full', '9999px'],
    ];

    $shadows = [
        ['subtle', 'shadow-subtle'],
        ['card', 'shadow-card'],
        ['mockup', 'shadow-mockup'],
        ['accent-glow', 'shadow-accent-glow'],
    ];

    $branchPicks = [
        ['scientific', 'علمي', 'رياضيات · فيزياء · كيمياء · أحياء', 'beaker', 'accent'],
        ['literary', 'أدبي', 'لغة عربية · تاريخ · جغرافيا · لغة إنجليزية', 'book', 'tag'],
        ['commercial', 'تجاري', 'محاسبة · اقتصاد · إدارة', 'briefcase', 'amber'],
        ['industrial', 'صناعي', 'رسم صناعي · تقنية · إلكترونيات', 'wrench', 'warn'],
    ];
@endphp

<x-layouts.styleguide>

    <x-ui.alert variant="accent" class="mb-10">
        {{ __('styleguide.dir_note') }}
    </x-ui.alert>

    {{-- ═══════════ الأساسيات ═══════════ --}}
    <section id="foundations" class="scroll-mt-32">
        <h2 class="text-h2 font-bold text-ink">{{ __('styleguide.nav_foundations') }}</h2>

        {{-- الألوان --}}
        <h3 class="mt-8 text-h4 font-semibold text-ink">{{ __('styleguide.colors_title') }}</h3>
        <p class="mt-1.5 max-w-prose text-caption text-steel">{{ __('styleguide.colors_note') }}</p>

        @foreach ($colorGroups as $groupKey => $swatches)
            <p class="mt-6 text-micro font-semibold text-stone">{{ __("styleguide.{$groupKey}") }}</p>
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($swatches as [$token, $bgClass, $hex])
                    <div class="overflow-hidden rounded-lg bg-canvas shadow-subtle">
                        <div class="h-16 w-full {{ $bgClass }} border-b border-hairline-soft"></div>
                        <div class="px-3 py-2">
                            <p class="truncate text-caption font-medium text-ink">{{ $token }}</p>
                            <p class="num text-micro text-stone" dir="ltr">{{ $hex }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        {{-- الطباعة --}}
        <h3 class="mt-12 text-h4 font-semibold text-ink">{{ __('styleguide.typography_title') }}</h3>
        <p class="mt-1.5 max-w-prose text-caption text-steel">{{ __('styleguide.typography_note') }}</p>

        <div class="mt-4 divide-y divide-hairline-soft rounded-xl bg-canvas px-5 shadow-subtle">
            @foreach ($typeScale as [$name, $classes, $spec])
                <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 py-4">
                    <p class="{{ $classes }} min-w-0 text-ink">{{ __('styleguide.typography_sample') }}</p>
                    <span class="shrink-0 text-micro text-stone">
                        {{ $name }} · <span class="num" dir="ltr">{{ $spec }}</span>
                    </span>
                </div>
            @endforeach
        </div>

        {{-- الأنصاف والارتفاع --}}
        <div class="mt-12 grid gap-8 lg:grid-cols-2">
            <div>
                <h3 class="text-h4 font-semibold text-ink">{{ __('styleguide.radius_title') }}</h3>
                <div class="mt-4 flex flex-wrap gap-4">
                    @foreach ($radii as [$name, $class, $value])
                        <div class="text-center">
                            <div class="size-16 bg-accent-soft {{ $class }} ring-1 ring-accent/20"></div>
                            <p class="mt-1.5 text-micro text-stone">{{ $name }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-h4 font-semibold text-ink">{{ __('styleguide.shadow_title') }}</h3>
                <div class="mt-4 flex flex-wrap gap-5">
                    @foreach ($shadows as [$name, $class])
                        <div class="text-center">
                            <div class="size-16 rounded-lg bg-canvas {{ $class }}"></div>
                            <p class="mt-2 text-micro text-stone">{{ $name }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════ الأزرار ═══════════ --}}
    <section id="buttons" class="mt-16 scroll-mt-32">
        <h2 class="text-h2 font-bold text-ink">{{ __('styleguide.buttons_title') }}</h2>
        <p class="mt-1.5 max-w-prose text-caption text-steel">{{ __('styleguide.buttons_note') }}</p>

        <p class="mt-6 text-micro font-semibold text-stone">{{ __('styleguide.buttons_variants') }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            @foreach (['primary', 'accent', 'secondary', 'ghost', 'danger'] as $variant)
                <x-ui.button :variant="$variant">{{ $variant }}</x-ui.button>
            @endforeach
            <span class="inline-flex rounded-full bg-canvas-dark p-3">
                <x-ui.button variant="on-dark">on-dark</x-ui.button>
            </span>
        </div>

        <p class="mt-8 text-micro font-semibold text-stone">{{ __('styleguide.buttons_sizes') }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            @foreach (['sm', 'md', 'lg'] as $size)
                <x-ui.button :size="$size">{{ $size }}</x-ui.button>
            @endforeach
        </div>

        <p class="mt-8 text-micro font-semibold text-stone">{{ __('styleguide.buttons_states') }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <x-ui.button>{{ __('styleguide.state_default') }}</x-ui.button>
            <x-ui.button disabled>{{ __('styleguide.state_disabled') }}</x-ui.button>
            <x-ui.button variant="secondary">
                <x-icon name="download" class="size-4" />
                {{ __('styleguide.state_with_icon') }}
            </x-ui.button>
        </div>
    </section>

    {{-- ═══════════ حقول النماذج ═══════════ --}}
    <section id="forms" class="mt-16 scroll-mt-32">
        <h2 class="text-h2 font-bold text-ink">{{ __('styleguide.forms_title') }}</h2>
        <p class="mt-1.5 max-w-prose text-caption text-steel">{{ __('styleguide.forms_note') }}</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">

            {{-- حقل نصي --}}
            <div class="rounded-xl bg-canvas p-6 shadow-subtle">
                <p class="text-h5 font-semibold text-ink">{{ __('styleguide.field_input') }}</p>
                <div class="mt-4 flex flex-col gap-4">
                    <x-ui.input name="sg_email_rest"
                                :label="__('styleguide.demo_label')"
                                :placeholder="__('styleguide.demo_placeholder')" />

                    <x-ui.input name="sg_email_filled"
                                :label="__('styleguide.state_filled')"
                                value="student@paledu.ps" />

                    <x-ui.input name="sg_email_required"
                                :label="__('styleguide.state_required')"
                                :placeholder="__('styleguide.demo_placeholder')"
                                required />

                    <x-ui.input name="sg_email_error"
                                :label="__('styleguide.state_error')"
                                value="wrong@mail"
                                :error="__('styleguide.demo_error')" />

                    <x-ui.input name="sg_email_disabled"
                                :label="__('styleguide.state_disabled')"
                                value="locked@paledu.ps"
                                disabled />
                </div>
            </div>

            {{-- قائمة منسدلة --}}
            <div class="rounded-xl bg-canvas p-6 shadow-subtle">
                <p class="text-h5 font-semibold text-ink">{{ __('styleguide.field_select') }}</p>
                <div class="mt-4 flex flex-col gap-4">
                    <x-ui.select name="sg_branch_rest"
                                 :label="__('auth.register_branch_label')"
                                 :placeholder="__('auth.register_branch_placeholder')"
                                 :options="['scientific' => 'علمي', 'literary' => 'أدبي', 'commercial' => 'تجاري', 'industrial' => 'صناعي']" />

                    <x-ui.select name="sg_branch_filled"
                                 :label="__('styleguide.state_filled')"
                                 value="literary"
                                 :options="['scientific' => 'علمي', 'literary' => 'أدبي']" />

                    <x-ui.select name="sg_branch_error"
                                 :label="__('styleguide.state_error')"
                                 :placeholder="__('auth.register_branch_placeholder')"
                                 :options="['scientific' => 'علمي']"
                                 error="اختر فرعاً للمتابعة" />

                    <x-ui.select name="sg_branch_disabled"
                                 :label="__('styleguide.state_disabled')"
                                 value="scientific"
                                 :options="['scientific' => 'علمي']"
                                 disabled />
                </div>
            </div>

            {{-- كلمة المرور --}}
            <div class="rounded-xl bg-canvas p-6 shadow-subtle">
                <p class="text-h5 font-semibold text-ink">{{ __('styleguide.field_password') }}</p>
                <div class="mt-4 flex flex-col gap-4">
                    <x-ui.password-input name="sg_pw_rest"
                                         :label="__('auth.password_label')"
                                         :hint="__('styleguide.demo_hint')" />

                    <x-ui.password-input name="sg_pw_error"
                                         :label="__('styleguide.state_error')"
                                         error="كلمة المرور قصيرة جداً" />
                </div>
            </div>

            {{-- ملخّص أخطاء النموذج --}}
            <div class="rounded-xl bg-canvas p-6 shadow-subtle">
                <p class="text-h5 font-semibold text-ink">{{ __('styleguide.field_errors') }}</p>
                <div class="mt-4 flex flex-col gap-4">
                    <x-ui.form-errors :title="__('auth.login_failed_title')">
                        {{ __('auth.login_failed_body') }}
                    </x-ui.form-errors>

                    <x-ui.form-errors
                        :title="__('styleguide.demo_form_error_title')"
                        :errors="['البريد الإلكتروني غير صالح', 'كلمة المرور لا تطابق التأكيد', 'يجب الموافقة على الشروط']" />
                </div>
            </div>

            {{-- مربّعات وأزرار الاختيار --}}
            <div class="rounded-xl bg-canvas p-6 shadow-subtle">
                <p class="text-h5 font-semibold text-ink">{{ __('styleguide.field_checkbox') }}</p>
                <div class="mt-4 flex flex-col gap-2">
                    <x-ui.checkbox name="sg_cb_rest" :label="__('styleguide.state_rest')" />
                    <x-ui.checkbox name="sg_cb_checked" :label="__('styleguide.state_checked')" checked />
                    <x-ui.checkbox name="sg_cb_hint"
                                   :label="__('auth.register_terms_label')"
                                   hint="تُفتح في صفحة منفصلة" />
                    <x-ui.checkbox name="sg_cb_error"
                                   :label="__('styleguide.state_error')"
                                   error="يجب الموافقة قبل المتابعة" />
                </div>
            </div>

            <div class="rounded-xl bg-canvas p-6 shadow-subtle">
                <p class="text-h5 font-semibold text-ink">{{ __('styleguide.field_radio') }}</p>
                <fieldset class="mt-4">
                    <legend class="sr-only">{{ __('auth.register_branch_label') }}</legend>
                    <div class="flex flex-col gap-2">
                        <x-ui.radio name="sg_radio" value="a" label="محاولة واحدة" checked />
                        <x-ui.radio name="sg_radio" value="b" label="محاولتان" hint="التوصية المسجّلة في م-2" />
                        <x-ui.radio name="sg_radio" value="c" label="بلا حدّ" />
                    </div>
                </fieldset>

                <p class="mt-8 text-h5 font-semibold text-ink">{{ __('styleguide.field_toggle') }}</p>
                <div class="mt-2 flex flex-col">
                    <x-ui.toggle name="sg_toggle_on" label="محاضرة جديدة في موادي" checked />
                    <x-ui.toggle name="sg_toggle_off" label="أخبار المنصة" />
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════ بطاقات الاختيار ═══════════ --}}
    <section id="selection" class="mt-16 scroll-mt-32">
        <h2 class="text-h2 font-bold text-ink">{{ __('styleguide.selection_title') }}</h2>
        <p class="mt-1.5 max-w-prose text-caption text-steel">{{ __('styleguide.selection_note') }}</p>

        <fieldset class="mt-6">
            <legend class="sr-only">{{ __('auth.branch_title') }}</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($branchPicks as $i => [$value, $title, $desc, $icon, $tone])
                    <x-ui.selectable-card
                        name="sg_pick_branch"
                        :value="$value"
                        :title="$title"
                        :description="$desc"
                        :icon="$icon"
                        :tone="$tone"
                        :checked="$i === 0" />
                @endforeach
            </div>
        </fieldset>
    </section>

    {{-- ═══════════ التغذية الراجعة ═══════════ --}}
    <section id="feedback" class="mt-16 scroll-mt-32">
        <h2 class="text-h2 font-bold text-ink">{{ __('styleguide.feedback_title') }}</h2>

        <h3 class="mt-6 text-h4 font-semibold text-ink">{{ __('styleguide.alerts_title') }}</h3>
        <div class="mt-3 flex flex-col gap-3">
            <x-ui.alert variant="accent">اشتراكك ساري حتى 12 يونيو 2026.</x-ui.alert>
            <x-ui.alert variant="warn">باقي 5 أيام على انتهاء اشتراكك — جدّده حتى لا تفقد الوصول.</x-ui.alert>
            <x-ui.alert variant="error" role="alert">انتهى اشتراكك. جدّد للعودة إلى محاضراتك.</x-ui.alert>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <div>
                <h3 class="text-h4 font-semibold text-ink">{{ __('styleguide.empty_title') }}</h3>
                <div class="mt-3 rounded-xl bg-canvas shadow-subtle">
                    <x-ui.empty-state icon="folder"
                                      :title="__('student.library_empty_title')"
                                      :body="__('student.library_empty_body')">
                        <x-ui.button variant="secondary" size="sm">تصفّح موادي</x-ui.button>
                    </x-ui.empty-state>
                </div>
            </div>

            <div>
                <h3 class="text-h4 font-semibold text-ink">{{ __('styleguide.modal_title') }}</h3>
                <div class="mt-3 flex flex-wrap gap-3">
                    <x-ui.button variant="secondary" data-modal-open="sg-modal">
                        {{ __('styleguide.modal_open') }}
                    </x-ui.button>
                    <x-ui.button variant="secondary" data-modal-open="sg-confirm">
                        {{ __('styleguide.confirm_open') }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════ عرض البيانات ═══════════ --}}
    <section id="data" class="mt-16 scroll-mt-32">
        <h2 class="text-h2 font-bold text-ink">{{ __('styleguide.data_title') }}</h2>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl bg-canvas p-6 shadow-subtle">
                <p class="text-h5 font-semibold text-ink">{{ __('styleguide.badges_title') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach (['neutral', 'branch', 'accent', 'warn', 'error'] as $variant)
                        <x-ui.badge :variant="$variant">{{ $variant }}</x-ui.badge>
                    @endforeach
                    <x-ui.badge variant="duration">12:45</x-ui.badge>
                    <x-ui.badge variant="accent" shape="chip">chip</x-ui.badge>
                </div>

                <p class="mt-8 text-h5 font-semibold text-ink">{{ __('styleguide.avatar_title') }}</p>
                <div class="mt-4 flex items-center gap-3">
                    <x-ui.avatar name="محمد" size="sm" />
                    <x-ui.avatar name="ليان" size="md" />
                    <x-ui.avatar name="أحمد" size="lg" />
                </div>

                <p class="mt-8 text-h5 font-semibold text-ink">{{ __('styleguide.progress_title') }}</p>
                <div class="mt-4 flex flex-col gap-3">
                    <x-ui.progress-bar :percent="35" />
                    <x-ui.progress-bar :percent="72" size="sm" />
                    <x-ui.progress-bar :percent="100" />
                </div>
            </div>

            <div class="rounded-xl bg-canvas p-6 shadow-subtle">
                <p class="text-h5 font-semibold text-ink">{{ __('styleguide.score_title') }}</p>
                <div class="mt-4 flex justify-center">
                    <x-ui.score-ring :percent="80" :size="132" />
                </div>

                <p class="mt-8 text-h5 font-semibold text-ink">{{ __('styleguide.breadcrumb_title') }}</p>
                <div class="mt-3">
                    <x-ui.breadcrumb :items="[
                        ['label' => 'موادي', 'href' => '#'],
                        ['label' => 'الرياضيات', 'href' => '#'],
                        ['label' => 'المحاضرة 3'],
                    ]" />
                </div>

                <p class="mt-8 text-h5 font-semibold text-ink">{{ __('styleguide.tabs_title') }}</p>
                <div class="mt-3">
                    <x-ui.tabs :items="['lectures' => 'محاضرات', 'files' => 'ملفات']" active="lectures" />
                </div>

                <p class="mt-8 text-h5 font-semibold text-ink">{{ __('styleguide.pagination_title') }}</p>
                <div class="mt-3">
                    <x-ui.pagination :current-page="2" :last-page="5" />
                </div>
            </div>
        </div>
    </section>

    {{-- مودالات الدليل --}}
    <x-ui.modal id="sg-modal" size="md" labelledby="sg-modal-title">
        <h2 id="sg-modal-title" class="text-h4 font-bold text-ink">{{ __('styleguide.modal_title') }}</h2>
        <p class="mt-2 text-ui text-steel">
            المودال يُغلق بمفتاح Escape، وبالنقر خارج اللوح، وبزر الإغلاق — ويعيد التركيز للزر الذي فتحه.
        </p>
    </x-ui.modal>

    <x-ui.confirm-dialog
        id="sg-confirm"
        title="حذف المحاضرة؟"
        body="ستنتقل إلى سلة المحذوفات ويمكن استرجاعها خلال 30 يوماً."
        confirm-label="نعم، احذف"
        variant="danger" />

</x-layouts.styleguide>
