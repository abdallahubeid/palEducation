@php
    /*
     * Pricing panel — section HEADER and the comparison link only.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * 🔴 The tier cards are rows in `pricing_plans` with their own screens:
     * a price, a JSON bullet list, an order and a soft delete.
     *
     * ⚠️ No "prices are in shekels…" field. That line was removed from the
     * page entirely, not relocated — adding it here would bring it back.
     */
    $defaults = \App\Models\Setting::PRICING_DEFAULTS;
    $value = fn (string $f) => old('pricing.'.$f, $settings['pricing.'.$f] ?? $defaults['pricing.'.$f]);

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
            <h3 class="text-ui font-bold text-ink">ترويسة قسم الأسعار</h3>
            <p class="mt-1.5 text-micro text-stone">
                الشارة والعنوان والنصّ التمهيدي، وزرّ المقارنة أسفل البطاقات.
                @can('view-pricings')
                    الخطط نفسها تُدار من <a href="{{ route('admin.pricing.index') }}"
                        class="font-semibold text-accent-deep underline underline-offset-2">شاشة خطط الأسعار</a>.
                @endcan
            </p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="pricing_badge" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="pricing_badge" name="pricing[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('pricing.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="pricing_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text" id="pricing_title" name="pricing[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('pricing.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="pricing_sub" class="{{ $labelClass }}">النصّ التمهيدي</label>
            <textarea id="pricing_sub" name="pricing[sub_title]" rows="2" maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
            @error('pricing.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="pricing_btn_text" class="{{ $labelClass }}">نصّ زرّ المقارنة</label>
                <input type="text" id="pricing_btn_text" name="pricing[btn_text]" maxlength="80"
                       value="{{ $value('btn_text') }}" class="{{ $inputClass }}">
                @error('pricing.btn_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="pricing_btn_link" class="{{ $labelClass }}">وجهة زرّ المقارنة</label>
                <input type="text" id="pricing_btn_link" name="pricing[btn_link]" dir="ltr" maxlength="500"
                       placeholder="/pricing" value="{{ $value('btn_link') }}" class="{{ $inputClass }}">
                @error('pricing.btn_link')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
            </div>
        </div>

        <p class="text-micro text-stone">النصّ والوجهة يُملآن معاً؛ تركهما فارغين يُخفي الزرّ من الصفحة.</p>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ الترويسة</x-admin.btn>
    </div>
</form>
