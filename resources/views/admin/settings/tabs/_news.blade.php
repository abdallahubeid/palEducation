@php
    /*
     * "أخبارنا" panel — the HEADER of the public news page.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * 🔴 Header copy only. The articles are rows in `news` with their own
     * CRUD screen; settings hold what surrounds a list, never the list.
     */
    $defaults = \App\Models\Setting::NEWS_DEFAULTS;
    $value = fn (string $f) => old('news.'.$f, $settings['news.'.$f] ?? $defaults['news.'.$f]);

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
            <h3 class="text-ui font-bold text-ink">ترويسة صفحة «أخبارنا»</h3>
            <p class="mt-1.5 text-micro text-stone">
                النصوص أعلى صفحة الأخبار العامة. الأخبار نفسها تُدار من شاشة «الأخبار».
            </p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="news_badge" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="news_badge" name="news[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('news.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="news_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text" id="news_title" name="news[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('news.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="news_sub" class="{{ $labelClass }}">النصّ التمهيدي</label>
            <textarea id="news_sub" name="news[sub_title]" rows="3" maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
            @error('news.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ التغييرات</x-admin.btn>
    </div>
</form>
