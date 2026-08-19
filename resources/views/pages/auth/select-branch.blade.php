@php
    // بيانات عرض ثابتة — تُستبدل باستعلام الفروع عند بناء الخادم
    $branches = [
        ['value' => 'scientific', 'name' => 'علمي',  'icon' => 'beaker',    'tone' => 'accent',
         'summary' => 'رياضيات · فيزياء · كيمياء · أحياء', 'subjects' => 7],
        ['value' => 'literary',   'name' => 'أدبي',  'icon' => 'book',      'tone' => 'tag',
         'summary' => 'لغة عربية · تاريخ · جغرافيا · لغة إنجليزية', 'subjects' => 6],
        ['value' => 'commercial', 'name' => 'تجاري', 'icon' => 'briefcase', 'tone' => 'amber',
         'summary' => 'محاسبة · اقتصاد وإدارة · رياضيات تجارية', 'subjects' => 6],
        ['value' => 'industrial', 'name' => 'صناعي', 'icon' => 'wrench',    'tone' => 'warn',
         'summary' => 'رسم صناعي · تقنية إنتاج · إلكترونيات', 'subjects' => 5],
    ];

    // اسم الفرع يُحقن بـJS عند الاختيار — confirm-dialog يعرض body بـ{!! !!}
    $confirmBody = __('auth.branch_confirm_body', [
        'branch' => '<span data-branch-confirm-name class="font-semibold text-ink"></span>',
    ]);
@endphp

<x-layouts.centered :title="__('auth.branch_title')" width="full">

    <div class="text-center">
        <h1 class="text-h2 font-bold text-ink">{{ __('auth.branch_title') }}</h1>
        <p class="measure mx-auto mt-2 text-lead text-steel">{{ __('auth.branch_subtitle') }}</p>
    </div>

    {{--
        🔴 تحذير صريح لا هامشي. القرار شبه دائم ويتخذه طالب متعجّل في
        ثانية — نبرة warn لا error: هذا ليس خطأً، هو قرار يستحق تروّياً.
    --}}
    <x-ui.alert variant="warn" icon="alert" class="mt-7">
        {{ __('auth.branch_warning') }}
    </x-ui.alert>

    <form data-demo-submit="{{ route('student.dashboard') }}" data-branch-picker class="mt-7">
        <fieldset>
            <legend class="sr-only">{{ __('auth.branch_title') }}</legend>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($branches as $branch)
                    <x-ui.selectable-card
                        name="branch"
                        :value="$branch['value']"
                        :title="$branch['name']"
                        :description="$branch['summary']"
                        :icon="$branch['icon']"
                        :tone="$branch['tone']"
                        :data-branch-name="$branch['name']">

                        <span class="mt-auto block pt-2 text-micro text-stone">
                            <span class="num font-medium text-slate">{{ $branch['subjects'] }}</span>
                            {{ __('home.subjects_unit') }}
                        </span>
                    </x-ui.selectable-card>
                @endforeach
            </div>
        </fieldset>

        {{-- معطّل حتى يقع اختيار فعلي — لا يصحّ تأكيد قرار دائم بلا اختيار --}}
        <div class="mt-8 flex justify-center">
            <x-ui.button type="button" data-branch-confirm size="lg" class="w-full sm:w-auto sm:min-w-64" disabled>
                {{ __('auth.branch_submit') }}
            </x-ui.button>
        </div>
    </form>

    {{--
        بوابة أخيرة تذكر الفرع بالاسم — «هل أنت متأكد؟» عامة لا تُحقّق
        الغرض، الطالب يجب أن يقرأ اسم ما اختاره قبل التثبيت.
    --}}
    <x-ui.confirm-dialog
        id="branch-confirm"
        :title="__('auth.branch_confirm_title')"
        :body="$confirmBody"
        :confirm-label="__('auth.branch_confirm_action')"
        :cancel-label="__('auth.branch_confirm_cancel')"
        :confirm-href="route('student.dashboard')"
        variant="primary" />

</x-layouts.centered>
