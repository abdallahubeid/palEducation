@php
    /*
     * "What sets us apart" panel — section HEADER only.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * 🔴 The four cards are rows in the `features` table with their own
     * screens under /admin/features: an icon name, a colour token, an order
     * and a soft delete. None of that fits a key/value settings form.
     */
    $defaults = \App\Models\Setting::FEATURES_DEFAULTS;
    $value = fn (string $f) => old('features.'.$f, $settings['features.'.$f] ?? $defaults['features.'.$f]);

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
            <h3 class="text-ui font-bold text-ink">ترويسة قسم «ما يميّزنا»</h3>
            <p class="mt-1.5 text-micro text-stone">
                الشارة والعنوان فوق بطاقات المميزات.
                @can('view-features')
                    البطاقات نفسها تُدار من <a href="{{ route('admin.features.index') }}"
                        class="font-semibold text-accent-deep underline underline-offset-2">شاشة المميزات</a>.
                @endcan
            </p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="features_badge" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="features_badge" name="features[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('features.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="features_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text" id="features_title" name="features[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('features.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ الترويسة</x-admin.btn>
    </div>
</form>
