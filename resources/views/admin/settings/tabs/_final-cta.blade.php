@php
    /*
     * Closing call to action — the last band on the home page.
     *
     * Expected variables: $settings (key => value map) · $action
     */
    $defaults = \App\Models\Setting::FINAL_CTA_DEFAULTS;
    $value = fn (string $f) => old('final_cta.'.$f, $settings['final_cta.'.$f] ?? $defaults['final_cta.'.$f]);

    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $errorClass = 'mt-1.5 text-micro text-error-deep';
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @method('PUT')

    <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        <div>
            <h3 class="text-ui font-bold text-ink">الدعوة الختامية</h3>
            <p class="mt-1.5 text-micro text-stone">آخر ما يراه الزائر قبل التذييل.</p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="final_cta_title" class="{{ $labelClass }}">
                العنوان <span class="text-error-deep">*</span>
            </label>
            <input type="text" id="final_cta_title" name="final_cta[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('final_cta.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="final_cta_sub" class="{{ $labelClass }}">النصّ التمهيدي</label>
            <textarea id="final_cta_sub" name="final_cta[sub_title]" rows="2" maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
            <p class="mt-1.5 text-micro text-stone">فوق الزرّ. اتركه فارغاً لإخفاء السطر.</p>
            @error('final_cta.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="final_cta_btn_text" class="{{ $labelClass }}">
                    نصّ الزرّ <span class="text-error-deep">*</span>
                </label>
                <input type="text" id="final_cta_btn_text" name="final_cta[btn_text]" maxlength="80"
                       value="{{ $value('btn_text') }}" class="{{ $inputClass }}">
                @error('final_cta.btn_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="final_cta_btn_link" class="{{ $labelClass }}">
                    وجهة الزرّ <span class="text-error-deep">*</span>
                </label>
                <input type="text" id="final_cta_btn_link" name="final_cta[btn_link]" dir="ltr" maxlength="500"
                       placeholder="/register" value="{{ $value('btn_link') }}" class="{{ $inputClass }}">
                @error('final_cta.btn_link')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="final_cta_sub2" class="{{ $labelClass }}">سطر الطمأنة</label>
            <input type="text" id="final_cta_sub2" name="final_cta[sub_title2]" maxlength="200"
                   value="{{ $value('sub_title2') }}" class="{{ $inputClass }}">
            {{-- Distinct from sub_title because it sits BELOW the button and
                 reads at a smaller size — the reassurance, not the pitch. --}}
            <p class="mt-1.5 text-micro text-stone">تحت الزرّ، بخطّ أصغر. اتركه فارغاً لإخفائه.</p>
            @error('final_cta.sub_title2')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ التغييرات</x-admin.btn>
    </div>
</form>
