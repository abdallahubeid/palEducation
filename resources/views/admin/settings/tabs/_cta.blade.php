@php
    /*
     * لوح «بانر الدعوة» — الشريط الأصفر في الصفحة الرئيسية.
     *
     * المتغيّرات المتوقّعة: $settings (خريطة مفتاح⇐قيمة) · $action
     *
     * ⚠️ الافتراضي من الثابت لا من نصّ مكتوب هنا: البذرة تقرأ نفس الثابت،
     * فنسخة ثانية من النصّ في هذا الملف كانت ستتباعد عن القاعدة بصمت.
     * ويُطبَّق عند **غياب المفتاح** فقط — حقل مسحه الأدمن يبقى ممسوحاً.
     */
    $defaults = \App\Models\Setting::CTA_DEFAULTS;

    $value = fn (string $field) => old('cta.'.$field, $settings['cta.'.$field] ?? $defaults['cta.'.$field]);

    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @method('PUT')

    <div class="space-y-5 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        <div>
            <h3 class="text-ui font-bold text-ink">بانر الدعوة</h3>
            <p class="mt-1.5 text-micro text-stone">
                الشريط الأصفر أسفل الهيرو في الصفحة الرئيسية.
            </p>
        </div>

        {{-- 🔴 نفس تذكير لوح الشريط العلوي: هذه القيم تُقرأ علناً، وجدول
             settings لا يملك عمود is_public بعد يميّز العام من الخاص. --}}
        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="cta_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text"
                   id="cta_title"
                   name="cta[title]"
                   maxlength="150"
                   value="{{ $value('title') }}"
                   class="{{ $inputClass }}">
            @error('cta.title')
                <p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="cta_sub_title" class="{{ $labelClass }}">النصّ التمهيدي</label>
            {{-- سطران عمداً: النصّ عربي وارتفاع سطره ≥1.7، فحقل بسطر واحد
                 يقصّ الجملة ويجبر الأدمن على التمرير الأفقي أثناء الكتابة. --}}
            <textarea id="cta_sub_title"
                      name="cta[sub_title]"
                      rows="2"
                      maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
            <p class="mt-1.5 text-micro text-stone">اتركه فارغاً لإخفاء السطر من البانر.</p>
            @error('cta.sub_title')
                <p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="cta_btn_text" class="{{ $labelClass }}">نصّ الزرّ</label>
                <input type="text"
                       id="cta_btn_text"
                       name="cta[btn_text]"
                       maxlength="40"
                       value="{{ $value('btn_text') }}"
                       class="{{ $inputClass }}">
                @error('cta.btn_text')
                    <p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="cta_btn_link" class="{{ $labelClass }}">وجهة الزرّ</label>

                {{-- dir=ltr على الحقل وحده: الوجهة لاتينية، والتسمية عربية.
                     type=text لا url: /register و#pricing وجهتان صحيحتان
                     يرفضهما المتحقّق المدمج في المتصفّح. --}}
                <input type="text"
                       id="cta_btn_link"
                       name="cta[btn_link]"
                       dir="ltr"
                       maxlength="500"
                       placeholder="/register"
                       value="{{ $value('btn_link') }}"
                       class="{{ $inputClass }}">
                <p class="mt-1.5 text-micro text-stone">مسار داخلي (/register) أو مرساة (#pricing) أو رابط كامل.</p>
                @error('cta.btn_link')
                    <p class="mt-1.5 text-micro text-error-deep">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ التغييرات</x-admin.btn>
    </div>
</form>
