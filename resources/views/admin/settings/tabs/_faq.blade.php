@php
    /*
     * FAQ panel — section HEADER and the contact button only.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * 🔴 The questions are `faqs` rows with their own screens: they carry an
     * order, a visibility flag and a soft delete, and the list has no fixed
     * length.
     */
    $defaults = \App\Models\Setting::FAQ_DEFAULTS;
    $value = fn (string $f) => old('faq.'.$f, $settings['faq.'.$f] ?? $defaults['faq.'.$f]);

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
            <h3 class="text-ui font-bold text-ink">ترويسة قسم الأسئلة الشائعة</h3>
            <p class="mt-1.5 text-micro text-stone">
                الشارة والعنوان والنصّ التمهيدي، وزرّ التواصل بجانبها.
                @can('view-faqs')
                    الأسئلة نفسها تُدار من <a href="{{ route('admin.faqs.index') }}"
                        class="font-semibold text-accent-deep underline underline-offset-2">شاشة الأسئلة الشائعة</a>.
                @endcan
            </p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="faq_badge" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="faq_badge" name="faq[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('faq.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="faq_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text" id="faq_title" name="faq[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('faq.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="faq_sub" class="{{ $labelClass }}">النصّ التمهيدي</label>
            <textarea id="faq_sub" name="faq[sub_title]" rows="2" maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
            @error('faq.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="faq_btn_text" class="{{ $labelClass }}">نصّ زرّ التواصل</label>
                <input type="text" id="faq_btn_text" name="faq[btn_text]" maxlength="80"
                       value="{{ $value('btn_text') }}" class="{{ $inputClass }}">
                @error('faq.btn_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="faq_btn_link" class="{{ $labelClass }}">وجهة الزرّ</label>
                <input type="text" id="faq_btn_link" name="faq[btn_link]" dir="ltr" maxlength="500"
                       placeholder="/contact" value="{{ $value('btn_link') }}" class="{{ $inputClass }}">
                @error('faq.btn_link')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        <p class="text-micro text-stone">النصّ والوجهة يُملآن معاً؛ تركهما فارغين يُخفي الزرّ من الصفحة.</p>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ الترويسة</x-admin.btn>
    </div>
</form>
