@php
    /*
     * Testimonials panel — section HEADER only.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * 🔴 The reviews are `testimonials` rows with their own screens, and the
     * reviewer's name, branch and city are read from the linked student
     * account — none of that is typed here.
     */
    $defaults = \App\Models\Setting::TESTIMONIALS_DEFAULTS;
    $value = fn (string $f) => old('testimonials.'.$f, $settings['testimonials.'.$f] ?? $defaults['testimonials.'.$f]);

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
            <h3 class="text-ui font-bold text-ink">ترويسة قسم آراء الطلبة</h3>
            <p class="mt-1.5 text-micro text-stone">
                الشارة والعنوان والنصّ التمهيدي فوق الآراء.
                @can('view-testimonials')
                    الآراء نفسها تُدار من <a href="{{ route('admin.testimonials.index') }}"
                        class="font-semibold text-accent-deep underline underline-offset-2">شاشة آراء الطلبة</a>.
                @endcan
            </p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="testimonials_badge" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="testimonials_badge" name="testimonials[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('testimonials.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="testimonials_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text" id="testimonials_title" name="testimonials[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('testimonials.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="testimonials_sub" class="{{ $labelClass }}">النصّ التمهيدي</label>
            <textarea id="testimonials_sub" name="testimonials[sub_title]" rows="2" maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
            @error('testimonials.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 border-t border-hairline pt-5 sm:grid-cols-2">
            <div>
                <label for="testimonials_btn_text" class="{{ $labelClass }}">نصّ زرّ إضافة رأي</label>
                <input type="text" id="testimonials_btn_text" name="testimonials[btn_text]" maxlength="60"
                       placeholder="أضف رأيك" value="{{ $value('btn_text') }}" class="{{ $inputClass }}">
                {{-- 🔴 Empty text is the off switch — the whole button
                     disappears from the page. --}}
                <p class="mt-1.5 text-micro text-stone">اتركه فارغاً لإخفاء الزرّ من الصفحة.</p>
                @error('testimonials.btn_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="testimonials_btn_link" class="{{ $labelClass }}">وجهة الزرّ</label>
                <input type="text" id="testimonials_btn_link" name="testimonials[btn_link]" dir="ltr"
                       maxlength="500" placeholder="اتركه فارغاً" value="{{ $value('btn_link') }}"
                       class="{{ $inputClass }}">
                {{-- Empty is the normal case, so the hint says what empty
                     DOES rather than treating it as a missing value. --}}
                <p class="mt-1.5 text-micro text-stone">
                    فارغ = يفتح نموذج إضافة الرأي داخل الصفحة. أدخل رابطاً لتحويل الزرّ إلى رابط عادي.
                </p>
                @error('testimonials.btn_link')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Says plainly that submissions are held: an admin who does not
             know this will not understand why nothing appeared. --}}
        <div class="flex items-start gap-2.5 rounded-xl border border-hairline bg-surface px-4 py-3">
            <x-icon name="alert" class="mt-0.5 size-4 shrink-0 text-stone" />
            <p class="text-caption text-stone">
                الآراء التي يرسلها الطلبة تُحفَظ <strong class="font-semibold text-ink">مخفيّة</strong>،
                ولا تظهر في الصفحة حتى تُفعّلها من شاشة آراء الطلبة.
            </p>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ الترويسة</x-admin.btn>
    </div>
</form>
