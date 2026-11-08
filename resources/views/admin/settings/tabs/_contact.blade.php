@php
    /*
     * Contact page — header, channels, working hours, FAQ deflection.
     *
     * Expected variables: $settings (key => value map) · $action
     */
    $defaults = \App\Models\Setting::CONTACT_DEFAULTS;
    $value = fn (string $f) => old('contact.'.$f, $settings['contact.'.$f] ?? $defaults['contact.'.$f]);

    $inputClass = 'w-full rounded-xl border border-hairline bg-canvas px-3 py-2.5 text-ui text-ink
                   transition placeholder:text-muted focus:border-accent focus:outline-none
                   focus:ring-2 focus:ring-accent/25';
    $labelClass = 'mb-1.5 block text-caption font-medium text-steel';
    $hintClass = 'mt-1.5 text-micro text-stone';
    $errorClass = 'mt-1.5 text-micro text-error-deep';
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @method('PUT')

    <div class="space-y-6 rounded-xl border border-hairline bg-canvas p-5 sm:p-6 lg:p-8">

        {{-- ═══ Header ═══ --}}
        <div class="space-y-5">
            <div>
                <h3 class="text-ui font-bold text-ink">ترويسة الصفحة</h3>
                <p class="{{ $hintClass }}">أعلى صفحة «تواصل معنا».</p>
            </div>

            <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
                <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
                <p class="text-caption text-warn-deep">هذه البيانات تظهر لكل زائر، حتى غير المسجّل.</p>
            </div>

            <div>
                <label for="contact_badge" class="{{ $labelClass }}">
                    الشارة <span class="text-error-deep">*</span>
                </label>
                <input type="text" id="contact_badge" name="contact[badge_text]" maxlength="60"
                       value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
                @error('contact.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="contact_title" class="{{ $labelClass }}">
                    العنوان <span class="text-error-deep">*</span>
                </label>
                <input type="text" id="contact_title" name="contact[title]" maxlength="150"
                       value="{{ $value('title') }}" class="{{ $inputClass }}">
                @error('contact.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="contact_sub" class="{{ $labelClass }}">النصّ التمهيدي</label>
                <textarea id="contact_sub" name="contact[sub_title]" rows="2" maxlength="300"
                          class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
                @error('contact.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ═══ Channels ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <div>
                <h3 class="text-ui font-bold text-ink">وسائل التواصل</h3>
                {{-- 🔴 Not all optional: a contact page with every channel
                     cleared invites people to get in touch with no way to. --}}
                <p class="{{ $hintClass }}">اترك وسيلة واحدة على الأقل. الحقل الفارغ يُخفي صفّه من البطاقة.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="contact_whatsapp" class="{{ $labelClass }}">واتساب</label>
                    {{-- dir=ltr on the field: the number is latin, the label
                         is Arabic. The dial-safe form is derived at render
                         time, so write it however reads best for a human. --}}
                    <input type="text" id="contact_whatsapp" name="contact[whatsapp]" dir="ltr" maxlength="40"
                           value="{{ $value('whatsapp') }}" class="{{ $inputClass }}">
                    @error('contact.whatsapp')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="contact_phone" class="{{ $labelClass }}">هاتف</label>
                    <input type="text" id="contact_phone" name="contact[phone]" dir="ltr" maxlength="40"
                           value="{{ $value('phone') }}" class="{{ $inputClass }}">
                    @error('contact.phone')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="contact_email" class="{{ $labelClass }}">البريد الإلكتروني</label>
                    <input type="email" id="contact_email" name="contact[email]" dir="ltr" maxlength="190"
                           value="{{ $value('email') }}" class="{{ $inputClass }}">
                    @error('contact.email')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="contact_address" class="{{ $labelClass }}">العنوان</label>
                    <input type="text" id="contact_address" name="contact[address]" maxlength="255"
                           value="{{ $value('address') }}" class="{{ $inputClass }}">
                    <p class="{{ $hintClass }}">يُعرض نصّاً، بلا رابط.</p>
                    @error('contact.address')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- ═══ Working hours ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <div>
                <h3 class="text-ui font-bold text-ink">ساعات العمل</h3>
                <p class="{{ $hintClass }}">سطر لكل صفّ.</p>
            </div>

            <div>
                <label for="contact_hours" class="{{ $labelClass }}">الساعات</label>
                <textarea id="contact_hours" name="contact[working_hours]" rows="4" maxlength="600"
                          class="{{ $inputClass }} leading-relaxed">{{ $value('working_hours') }}</textarea>
                {{-- ⚠️ The split is on the FIRST colon: Arabic clock times
                     carry colons of their own ("9:00 ص"), so any other rule
                     would cut the time in half. --}}
                <p class="{{ $hintClass }}">
                    صيغة الصفّ: <bdi>اليوم: الوقت</bdi> — يُقسَم عند أول نقطتين.
                    السطر بلا نقطتين يُعرض ملاحظةً أسفل الجدول.
                </p>
                @error('contact.working_hours')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ═══ FAQ deflection ═══ --}}
        <div class="space-y-5 border-t border-hairline pt-6">
            <div>
                <h3 class="text-ui font-bold text-ink">تنبيه الأسئلة الشائعة</h3>
                <p class="{{ $hintClass }}">سطر هادئ أسفل بطاقة التواصل يوجّه الزائر قبل أن ينتظر ردّاً.</p>
            </div>

            <div>
                <label for="contact_faq_notice" class="{{ $labelClass }}">النصّ</label>
                <textarea id="contact_faq_notice" name="contact[faq_notice]" rows="2" maxlength="400"
                          class="{{ $inputClass }} leading-relaxed">{{ $value('faq_notice') }}</textarea>
                @error('contact.faq_notice')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="contact_faq_btn_text" class="{{ $labelClass }}">نصّ الرابط</label>
                    <input type="text" id="contact_faq_btn_text" name="contact[faq_btn_text]" maxlength="80"
                           value="{{ $value('faq_btn_text') }}" class="{{ $inputClass }}">
                    @error('contact.faq_btn_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="contact_faq_btn_link" class="{{ $labelClass }}">وجهة الرابط</label>
                    <input type="text" id="contact_faq_btn_link" name="contact[faq_btn_link]" dir="ltr"
                           maxlength="500" placeholder="/#faq"
                           value="{{ $value('faq_btn_link') }}" class="{{ $inputClass }}">
                    @error('contact.faq_btn_link')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
                </div>
            </div>

            <p class="text-micro text-stone">النصّ والوجهة يُملآن معاً؛ تركهما فارغين يُخفي الرابط.</p>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ التغييرات</x-admin.btn>
    </div>
</form>
