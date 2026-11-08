@php
    /*
     * Branches panel — section HEADER only.
     *
     * Expected variables: $settings (key => value map) · $action
     *
     * 🔴 HEADER ONLY. The branches themselves are a domain entity with their
     * own screens under /admin/branches — a slug that is a route key, an
     * order, a soft delete and four permissions. None of that belongs in a
     * key/value settings form.
     */
    $defaults = \App\Models\Setting::BRANCHES_DEFAULTS;
    $value = fn (string $f) => old('branches.'.$f, $settings['branches.'.$f] ?? $defaults['branches.'.$f]);

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
            <h3 class="text-ui font-bold text-ink">ترويسة قسم الفروع</h3>
            <p class="mt-1.5 text-micro text-stone">
                الشارة والعنوان والنصّ التمهيدي فوق بطاقات الفروع.
                @can('view-branches')
                    الفروع نفسها تُدار من <a href="{{ route('admin.branches.index') }}"
                        class="font-semibold text-accent-deep underline underline-offset-2">شاشة الفروع</a>.
                @endcan
            </p>
        </div>

        <div class="flex items-start gap-2.5 rounded-xl border border-warn/25 bg-warn/10 px-4 py-3">
            <x-icon name="eye" class="mt-0.5 size-4 shrink-0 text-warn-deep" />
            <p class="text-caption text-warn-deep">هذا المحتوى يظهر لكل زائر، حتى غير المسجّل.</p>
        </div>

        <div>
            <label for="branches_badge" class="{{ $labelClass }}">الشارة</label>
            <input type="text" id="branches_badge" name="branches[badge_text]" maxlength="60"
                   value="{{ $value('badge_text') }}" class="{{ $inputClass }}">
            @error('branches.badge_text')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="branches_title" class="{{ $labelClass }}">العنوان</label>
            <input type="text" id="branches_title" name="branches[title]" maxlength="150"
                   value="{{ $value('title') }}" class="{{ $inputClass }}">
            @error('branches.title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="branches_sub" class="{{ $labelClass }}">النصّ التمهيدي</label>
            <textarea id="branches_sub" name="branches[sub_title]" rows="2" maxlength="300"
                      class="{{ $inputClass }} leading-relaxed">{{ $value('sub_title') }}</textarea>
            @error('branches.sub_title')<p class="{{ $errorClass }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <x-admin.btn variant="primary">حفظ الترويسة</x-admin.btn>
    </div>
</form>
