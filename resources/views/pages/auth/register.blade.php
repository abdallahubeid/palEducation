@php
    // بيانات عرض ثابتة — تُستبدل باستعلام حقيقي عند بناء الخادم
    $countries = [
        'ps' => 'فلسطين',
        'jo' => 'الأردن',
        'other' => 'دولة أخرى',
    ];

    $governorates = [
        'jenin' => 'جنين', 'tulkarm' => 'طولكرم', 'nablus' => 'نابلس',
        'qalqilya' => 'قلقيلية', 'salfit' => 'سلفيت', 'ramallah' => 'رام الله والبيرة',
        'jericho' => 'أريحا', 'jerusalem' => 'القدس', 'bethlehem' => 'بيت لحم',
        'hebron' => 'الخليل', 'gaza' => 'غزة', 'north-gaza' => 'شمال غزة',
        'deir-albalah' => 'دير البلح', 'khan-younis' => 'خان يونس', 'rafah' => 'رفح',
    ];

    $branches = [
        'scientific' => 'علمي',
        'literary' => 'أدبي',
        'commercial' => 'تجاري',
        'industrial' => 'صناعي',
    ];
@endphp

<x-layouts.centered :title="__('auth.register_title')" width="wide">

    <div class="rounded-xl bg-canvas p-6 shadow-subtle sm:p-8"
         data-wizard
         data-step1-title="{{ __('auth.register_step1_title') }}"
         data-step2-title="{{ __('auth.register_step2_title') }}">

        <div class="text-center">
            <h1 class="text-h3 font-bold text-ink">{{ __('auth.register_title') }}</h1>
            <p class="mt-1.5 text-ui text-steel">{{ __('auth.register_subtitle') }}</p>
        </div>

        {{--
            مؤشّر الخطوات — شريطان بعرض متساوٍ. الخطوة الحالية باللون
            accent والقادمة بلون الخط الفاصل. نصّ الحالة يُقرأ صوتياً عبر
            aria-live كي يعرف مستخدم قارئ الشاشة أن الخطوة تبدّلت.
        --}}
        <div class="mt-7">
            <div class="flex items-center gap-2" aria-hidden="true">
                <span data-wizard-bar="1" class="h-1.5 flex-1 rounded-full bg-accent transition-colors"></span>
                <span data-wizard-bar="2" class="h-1.5 flex-1 rounded-full bg-hairline transition-colors"></span>
            </div>

            <p class="mt-2.5 text-caption text-stone" aria-live="polite">
                <span data-wizard-status
                      data-template="{{ __('auth.register_step_of', ['current' => ':current', 'total' => 2]) }}">{{ __('auth.register_step_of', ['current' => 1, 'total' => 2]) }}</span>
                <span class="mx-1" aria-hidden="true">·</span>
                <span data-wizard-title class="font-medium text-steel">{{ __('auth.register_step1_title') }}</span>
            </p>
        </div>

        <form data-demo-submit="{{ route('auth.branch') }}" class="mt-6">

            {{-- ══ الخطوة 1 — الحساب وكلمة المرور ══ --}}
            <div data-wizard-step="1" class="flex flex-col gap-4">

                <x-ui.input
                    name="name"
                    :label="__('auth.register_name_label')"
                    :placeholder="__('auth.register_name_placeholder')"
                    required />

                <x-ui.input
                    name="email"
                    type="email"
                    :label="__('auth.email_label')"
                    :placeholder="__('auth.email_placeholder')"
                    required />

                <x-ui.password-input
                    name="password"
                    :label="__('auth.password_label')"
                    :hint="__('auth.register_password_hint')"
                    required />

                <x-ui.password-input
                    name="password_confirmation"
                    :label="__('auth.register_password_confirm_label')"
                    required />

                <x-ui.checkbox name="terms" required>
                    <span class="block text-ui text-ink">
                        {{ __('auth.register_terms_label') }}
                        {{-- وجهة نائبة — تُستبدل بصفحة الشروط الحقيقية عند كتابتها --}}
                        <a href="#" class="font-medium text-accent-deep transition hover:underline">
                            {{ __('auth.register_terms_link') }}
                        </a>
                    </span>
                </x-ui.checkbox>

                <x-ui.button type="button" data-wizard-next size="md" class="mt-2 w-full">
                    {{ __('auth.register_next') }}
                    <x-icon name="arrow" class="size-4 rtl:-scale-x-100" />
                </x-ui.button>
            </div>

            {{-- ══ الخطوة 2 — البيانات الدراسية ══ --}}
            <div data-wizard-step="2" hidden class="flex flex-col gap-4">

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.select
                        name="country"
                        :label="__('auth.register_country_label')"
                        :placeholder="__('auth.register_country_placeholder')"
                        :options="$countries"
                        value="ps"
                        required />

                    <x-ui.select
                        name="governorate"
                        :label="__('auth.register_governorate_label')"
                        :placeholder="__('auth.register_governorate_placeholder')"
                        :options="$governorates"
                        required />
                </div>

                {{--
                    🔴 رقم الجوال LTR إلزاماً داخل نموذج RTL — الأرقام لا
                    تنعكس في أي لغة. dir=ltr على الحقل نفسه، وtext-start
                    تُبقي المؤشّر عند بداية الرقم لا نهايته.
                --}}
                <x-ui.input
                    name="phone"
                    type="tel"
                    :label="__('auth.register_phone_label')"
                    :placeholder="__('auth.register_phone_placeholder')"
                    dir="ltr"
                    inputmode="tel"
                    required />

                <x-ui.input
                    name="school"
                    :label="__('auth.register_school_label')"
                    :placeholder="__('auth.register_school_placeholder')"
                    required />

                <x-ui.select
                    name="branch"
                    :label="__('auth.register_branch_label')"
                    :placeholder="__('auth.register_branch_placeholder')"
                    :options="$branches"
                    required />

                {{-- نفس ترتيب ui/confirm-dialog: الثانوي أولاً ثم الأساسي.
                     الترتيب من DOM لا بعكس اتجاه الصف — العكس يكسر LTR
                     (rtl-bilingual §1) --}}
                <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                    <x-ui.button type="button" data-wizard-prev variant="secondary" size="md" class="w-full sm:flex-1">
                        {{ __('auth.register_prev') }}
                    </x-ui.button>

                    <x-ui.button type="submit" size="md" class="w-full sm:flex-1">
                        {{ __('auth.register_submit') }}
                    </x-ui.button>
                </div>
            </div>
        </form>
    </div>

    <x-slot:footer>
        {{ __('auth.register_have_account') }}
        <a href="{{ route('auth.login') }}" class="font-semibold text-accent-deep transition hover:underline">
            {{ __('auth.register_login_link') }}
        </a>
    </x-slot:footer>

</x-layouts.centered>
