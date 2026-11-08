@php
    /*
     * "Why us" panel — header and the two buttons.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * The counter cards beside this section stay static, as specified.
     */
    $defaults = \App\Models\Setting::WHY_US_DEFAULTS;
    $value = fn (string $f) => old('why_us.'.$f, $settings['why_us.'.$f] ?? $defaults['why_us.'.$f]);

    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $errorClass = 'mt-1.5 text-micro text-error-deep';

    $buttons = [
        ['n' => 1, 'label' => 'الزرّ الأساسي', 'placeholder' => '/register'],
        ['n' => 2, 'label' => 'الزرّ الثانوي', 'placeholder' => '#branches'],
    ];
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @method('PUT')

    <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        <div>
            <h3 class="text-ui font-bold text-ink">قسم «لماذا نحن»</h3>
            <p class="mt-1.5 text-micro text-stone">
                الشارة والعنوان والوصف والزرّان. بطاقات الأرقام بجانبه ثابتة ولا تُدار من هنا.
            </p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="why_badge" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="why_badge" name="why_us[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('why_us.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="why_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text" id="why_title" name="why_us[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('why_us.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="why_desc" class="{{ $labelClass }}">الوصف</label>
            <textarea id="why_desc" name="why_us[description]" rows="4" maxlength="1000"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('description') }}</textarea>
            @error('why_us.description')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        @foreach ($buttons as $btn)
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="why_btn{{ $btn['n'] }}_text" class="{{ $labelClass }}">نصّ {{ $btn['label'] }}</label>
                    <input type="text" id="why_btn{{ $btn['n'] }}_text"
                           name="why_us[btn{{ $btn['n'] }}_text]" maxlength="40"
                           value="{{ $value('btn'.$btn['n'].'_text') }}" class="{{ $inputClass }}">
                    @error('why_us.btn'.$btn['n'].'_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="why_btn{{ $btn['n'] }}_link" class="{{ $labelClass }}">وجهة {{ $btn['label'] }}</label>
                    <input type="text" id="why_btn{{ $btn['n'] }}_link" dir="ltr"
                           name="why_us[btn{{ $btn['n'] }}_link]" maxlength="500"
                           placeholder="{{ $btn['placeholder'] }}"
                           value="{{ $value('btn'.$btn['n'].'_link') }}" class="{{ $inputClass }}">
                    @error('why_us.btn'.$btn['n'].'_link')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ التغييرات</x-admin.btn>
    </div>
</form>
