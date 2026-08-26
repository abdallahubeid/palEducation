@php
    /*
     | يخدم حالتين:
     |  1. تدفّق تسجيل جوجل — $branches قادمة من GoogleController (نماذج Branch)
     |     والنموذج POST حقيقي.
     |  2. مسار المعاينة /preview/select-branch — بلا متغيّرات، بيانات ثابتة.
     */
    $isLive = isset($branches);

    $cards = $isLive
        ? $branches->map(fn ($b) => [
            'value' => $b->id,
            'name' => $b->name,
            'icon' => $b->icon ?? 'circle',
            'tone' => $b->accent_token ?? 'accent',
            'summary' => $b->description,
        ])
        : collect([
            ['value' => 'scientific', 'name' => 'علمي', 'icon' => 'beaker', 'tone' => 'accent',
             'summary' => 'رياضيات · فيزياء · كيمياء · أحياء'],
            ['value' => 'literary', 'name' => 'أدبي', 'icon' => 'book', 'tone' => 'tag',
             'summary' => 'لغة عربية · تاريخ · جغرافيا · لغة إنجليزية'],
            ['value' => 'commercial', 'name' => 'تجاري', 'icon' => 'briefcase', 'tone' => 'amber',
             'summary' => 'محاسبة · اقتصاد وإدارة · رياضيات تجارية'],
            ['value' => 'industrial', 'name' => 'صناعي', 'icon' => 'wrench', 'tone' => 'warn',
             'summary' => 'رسم صناعي · تقنية إنتاج · إلكترونيات'],
        ]);

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

    @if ($errors->any())
        <x-ui.form-errors class="mt-5">{{ $errors->first() }}</x-ui.form-errors>
    @endif

    <form
        @if ($isLive) method="POST" action="{{ route('auth.google.branch.store') }}"
        @else data-demo-submit="{{ route('student.dashboard') }}" @endif
        data-branch-picker class="mt-7">

        @if ($isLive) @csrf @endif

        <fieldset>
            <legend class="sr-only">{{ __('auth.branch_title') }}</legend>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($cards as $branch)
                    <x-ui.selectable-card
                        name="branch_id"
                        :value="$branch['value']"
                        :title="$branch['name']"
                        :description="$branch['summary']"
                        :icon="$branch['icon']"
                        :tone="$branch['tone']"
                        :data-branch-name="$branch['name']" />
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
        :confirm-href="$isLive ? '#' : route('student.dashboard')"
        variant="primary" />

</x-layouts.centered>
